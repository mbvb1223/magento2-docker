<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Controller\Create;

use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleManagementInterface;
use Magento\Company\Model\CompanyUser;
use Magento\Company\Model\Role;
use Magento\Company\Model\RoleRepository;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\PurchaseOrderRule\Controller\Create\Save as SaveController;
use Magento\PurchaseOrderRule\Model\RuleRepository;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var RequestInterface|HttpRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    private $request;

    /**
     * @var RedirectFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var Redirect|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultRedirect;

    /**
     * @var ManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageManager;

    /**
     * @var RoleRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $roleRepository;

    /**
     * @var CompanyUser|\PHPUnit\Framework\MockObject\MockObject
     */
    private $companyUser;

    /**
     * @var RoleInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $companyRole;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var SaveController
     */
    private $controller;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = ObjectManager::getInstance();

        $this->request = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['getPostValue']
        );
        $this->resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->disableOriginalConstructor()->getMock();
        $this->resultRedirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()->getMock();
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->roleRepository = $this->getMockBuilder(RoleRepository::class)
            ->disableOriginalConstructor()->getMock();
        $this->companyUser = $this->getMockBuilder(CompanyUser::class)
            ->disableOriginalConstructor()->getMock();
        $this->companyRole = $this->getMockBuilder(RoleInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->session = $this->objectManager->get(Session::class);

        $context = $this->objectManager->create(
            Context::class,
            [
                'request' => $this->request,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $this->resultRedirectFactory
            ]
        );

        $this->controller = $this->objectManager->create(
            SaveController::class,
            [
                'context' => $context,
                'roleRepository' => $this->roleRepository,
                'companyUser' => $this->companyUser
            ]
        );
    }

    /**
     * Test that when the form is submitted without a name the correct message and redirect response are followed
     */
    public function testMissingRequiredName()
    {
        $this->request->expects($this->atLeastOnce())
            ->method('getParam')
            ->willReturn(null);

        $this->assertErrorMethodAndRedirect('Required field is not complete.');
    }

    /**
     * Test to verify the output of missing conditions array but with the name present
     */
    public function testNullConditionsArray()
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['name', null, 'Purchase Order Rule'],
                    ['conditions', null, null]
                ]
            );

        $this->assertErrorMethodAndRedirect('Rule conditions have not been configured.');
    }

    /**
     * Verify incomplete conditions array produces error
     */
    public function testMissingConditionOperator()
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['name', null, 'Purchase Order Rule'],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'grand_total',
                                'value' => '100'
                            ]
                        ]
                    ]
                ]
            );

        $this->assertErrorMethodAndRedirect('Required data is missing from a rule condition.');
    }

    /**
     * Test a condition which does not exist in the system
     */
    public function testInvalidRuleConditionName()
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['name', null, 'Purchase Order Rule'],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'does_not_exist',
                                'operator' => '>',
                                'value' => '100',
                                'currency_code' => null
                            ]
                        ]
                    ]
                ]
            );

        $this->assertErrorMethodAndRedirect('Rule condition does not exist.');
    }

    /**
     * Test a negative order total value, which is invalid
     */
    public function testNegativeOrderTotalCondition()
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['name', null, 'Purchase Order Rule'],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'grand_total',
                                'value' => '-100',
                                'operator' => '>',
                                'currency_code' => 'USD'
                            ]
                        ]
                    ]
                ]
            );

        $this->assertErrorMethodAndRedirect('Rule is incorrectly configured.');
    }

    /**
     * Test an invalid approver for the current users company
     */
    public function testMissingRequiredApprover()
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['name', null, 'Purchase Order Rule'],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'grand_total',
                                'value' => '100',
                                'operator' => '>',
                                'currency_code' => 'USD'
                            ]
                        ],
                    ],
                    ['approvers', null, null]
                ]
            );

        $this->assertErrorMethodAndRedirect('At least one approver is required to configure this rule.');
    }

    /**
     * Test an invalid approver, which doesn't exist
     */
    public function testInvalidApprover()
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['name', null, 'Purchase Order Rule'],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'grand_total',
                                'value' => '100',
                                'operator' => '>',
                                'currency_code' => 'USD'
                            ]
                        ],
                    ],
                    [
                        'applies_to_all',
                        null,
                        '1'
                    ],
                    [
                        'approvers',
                        null,
                        [9999]
                    ]
                ]
            );

        $this->roleRepository->expects($this->once())
            ->method('get')
            ->with(9999)
            ->willThrowException(new NoSuchEntityException());

        $this->assertErrorMethodAndRedirect('The approver role which was selected does not exist.');
    }

    /**
     * Test that trying to use an approver role for another company fails
     */
    public function testApproverRoleFromOtherCompany()
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['name', null, 'Purchase Order Rule'],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'grand_total',
                                'value' => '100',
                                'operator' => '>',
                                'currency_code' => 'USD'
                            ]
                        ],
                    ],
                    [
                        'applies_to_all',
                        null,
                        '1'
                    ],
                    [
                        'approvers',
                        null,
                        [10]
                    ]
                ]
            );

        $this->companyRole->expects($this->once())
            ->method('getCompanyId')
            ->willReturn(1);

        $this->roleRepository->expects($this->once())
            ->method('get')
            ->with(10)
            ->willReturn($this->companyRole);

        $this->companyUser->expects($this->exactly(2))
            ->method('getCurrentCompanyId')
            ->willReturn(2);

        $this->assertErrorMethodAndRedirect('The approver role which was selected does not exist.');
    }

    /**
     * Test that not applying a rule to all and not specifying any role IDs throws an error
     *
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     */
    public function testMissingAppliesToRoles()
    {
        $roleId = $this->getRoleId();

        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['name', null, 'Purchase Order Rule'],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'grand_total',
                                'value' => '100',
                                'operator' => '>',
                                'currency_code' => 'USD'
                            ]
                        ],
                    ],
                    [
                        'applies_to_all',
                        null,
                        '0'
                    ],
                    [
                        'approvers',
                        null,
                        [$roleId]
                    ]
                ]
            );

        $this->assertErrorMethodAndRedirect('This rule must apply to at least one or all roles.');
    }

    /**
     * Verify a role from a different company triggers an error when used in "Applies To"
     */
    public function testAppliesToRoleFromDifferentCompany()
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['name', null, 'Purchase Order Rule'],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'grand_total',
                                'value' => '100',
                                'operator' => '>',
                                'currency_code' => 'USD'
                            ]
                        ],
                    ],
                    [
                        'applies_to_all',
                        null,
                        '1'
                    ],
                    [
                        'applies_to',
                        null,
                        [10]
                    ],
                    [
                        'approvers',
                        null,
                        [12]
                    ]
                ]
            );

        $this->companyRole->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(1);

        $this->roleRepository->expects($this->any())
            ->method('get')
            ->with(10)
            ->willReturn($this->companyRole);

        $this->companyUser->expects($this->any())
            ->method('getCurrentCompanyId')
            ->willReturn(2);

        $this->assertErrorMethodAndRedirect('One of the "Applies To" roles does not exist.');
    }

    /**
     * @param $field
     * @param $value
     * @return \Magento\PurchaseOrderRule\Api\Data\RuleSearchResultsInterface
     * @throws LocalizedException
     */
    private function findRules($field, $value)
    {
        /* @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        /* @var RuleRepository $ruleRepository */
        $ruleRepository = $this->objectManager->get(RuleRepository::class);

        $ruleSearch = $ruleRepository->getList(
            $searchCriteriaBuilder
                ->addFilter($field, $value)
                ->create()
        );

        return $ruleSearch;
    }

    /**
     * Save a valid rule for a logged in customer and ensure a success message is received and rule is created.
     *
     * @param $ruleName
     * @param $activeRulesCount
     * @param $successMessage
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @dataProvider saveRuleDataProvider
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     * @magentoDataFixture Magento/PurchaseOrderRule/_files/simple_approval_rule.php
     */
    public function testSaveValidRule(
        $ruleName,
        $activeRulesCount,
        $successMessage
    ) {
        $roleId = $this->getRoleId();
        $rulesInitialSearch = $this->findRules('name', $ruleName);
        $ruleId = null;
        if ($rulesInitialSearch->getTotalCount() > 0) {
            $ruleId = current($rulesInitialSearch->getItems())->getId();
        }

        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['rule_id', null, $ruleId],
                    ['name', null, $ruleName],
                    ['is_active', null, '1'],
                    ['description', null, ''],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'grand_total',
                                'value' => '100',
                                'operator' => '>',
                                'currency_code' => 'USD'
                            ]
                        ],
                    ],
                    [
                        'applies_to_all',
                        null,
                        '1'
                    ],
                    [
                        'approvers',
                        null,
                        [$roleId]
                    ]
                ]
            );

        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__($successMessage));

        $this->resultRedirectFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirect);

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('purchaseorderrule');

        $this->controller->execute();

        $ruleSearch = $this->findRules('name', $ruleName);
        $activeRulesSearch = $this->findRules('is_active', 1);
        $this->assertEquals($activeRulesSearch->getTotalCount(), $activeRulesCount);
        $this->assertEquals($ruleSearch->getTotalCount(), 1);

        /* @var RuleInterface $rule */
        $rule = current($ruleSearch->getItems());

        // Assert basic information about the created rule
        $this->assertEquals($ruleName, $rule->getName());
        $this->assertNull($rule->getDescription());
        $this->assertNotEmpty($rule->getConditionsSerialized());
        $this->assertEquals($this->companyUser->getCurrentCompanyId(), $rule->getCompanyId());
        $this->assertEquals([$roleId], $rule->getApproverRoleIds());
        $this->assertTrue($rule->isAppliesToAll());
        $this->assertTrue($rule->isActive());

        // Verify the condition that was generated contains the correct information regarding the input
        $storedCondition = json_decode($rule->getConditionsSerialized(), true);
        $this->assertArrayHasKey('conditions', $storedCondition);
        $this->assertCount(1, $storedCondition['conditions']);
        $this->assertEquals('grand_total', $storedCondition['conditions'][0]['attribute']);
        $this->assertEquals('>', $storedCondition['conditions'][0]['operator']);
        $this->assertEquals('100', $storedCondition['conditions'][0]['value']);
    }

    /**
     * Create a valid rule for a logged in customer and ensure a success message is received and rule is created
     *
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     */
    public function testSaveValidRuleWithSpecificAppliesTo()
    {
        /* @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        /* @var RuleRepository $ruleRepository */
        $ruleRepository = $this->objectManager->get(RuleRepository::class);

        $roleId = $this->getRoleId();
        $ruleName = 'Created Purchase Order Rule';

        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['name', null, $ruleName],
                    ['is_active', null, '1'],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'grand_total',
                                'value' => '100',
                                'operator' => '>',
                                'currency_code' => 'USD'
                            ]
                        ],
                    ],
                    [
                        'applies_to_all',
                        null,
                        '0'
                    ],
                    [
                        'applies_to',
                        null,
                        [$roleId]
                    ],
                    [
                        'approvers',
                        null,
                        [$roleId]
                    ]
                ]
            );

        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('The approval rule has been created.'));

        $this->resultRedirectFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirect);

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('purchaseorderrule');

        $this->controller->execute();

        $ruleSearch = $ruleRepository->getList(
            $searchCriteriaBuilder
                ->addFilter('name', $ruleName)
                ->create()
        );

        $this->assertEquals($ruleSearch->getTotalCount(), 1);

        /* @var RuleInterface $rule */
        $rule = current($ruleSearch->getItems());

        // Assert basic information about the created rule
        $this->assertEquals($ruleName, $rule->getName());
        $this->assertNull($rule->getDescription());
        $this->assertNotEmpty($rule->getConditionsSerialized());
        $this->assertEquals($this->companyUser->getCurrentCompanyId(), $rule->getCompanyId());
        $this->assertEquals([$roleId], $rule->getApproverRoleIds());
        $this->assertFalse($rule->isAppliesToAll());
        $this->assertEquals([$roleId], $rule->getAppliesToRoleIds());
        $this->assertTrue($rule->isActive());
        $this->assertEquals($this->session->getCustomerId(), $rule->getCreatedBy());

        // Verify the condition that was generated contains the correct information regarding the input
        $storedCondition = json_decode($rule->getConditionsSerialized(), true);
        $this->assertArrayHasKey('conditions', $storedCondition);
        $this->assertCount(1, $storedCondition['conditions']);
        $this->assertEquals('grand_total', $storedCondition['conditions'][0]['attribute']);
        $this->assertEquals('>', $storedCondition['conditions'][0]['operator']);
        $this->assertEquals('100', $storedCondition['conditions'][0]['value']);
    }

    /**
     * Verify a rule is able to remove admin approval requirement after it is created
     *
     * @magentoDataFixture Magento/Company/_files/company.php
     * @magentoDataFixture Magento/PurchaseOrderRule/_files/approval_admin_rule.php
     */
    public function testAbleToUnsetAdminAsApprover()
    {
        /* @var RuleRepository $ruleRepository */
        $ruleRepository = $this->objectManager->get(RuleRepository::class);

        /** @var RoleManagementInterface $roleManagement */
        $roleManagement = $this->objectManager->get(RoleManagementInterface::class);

        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $companyId = (int) $companyAdmin->getExtensionAttributes()->getCompanyAttributes()->getCompanyId();
        $defaultRoleId = $roleManagement->getCompanyDefaultRole($companyId)->getId();

        $this->session->loginById($companyAdmin->getId());

        $rules = $ruleRepository->getByCompanyId($companyId)->getItems();

        $this->assertCount(1, $rules);

        $rule = current($rules);
        $this->assertTrue($rule->isAdminApprovalRequired());

        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['rule_id', null, $rule->getId()],
                    ['name', null, $rule->getName()],
                    ['is_active', null, '1'],
                    [
                        'conditions',
                        null,
                        json_decode($rule->getConditionsSerialized(), true)['conditions']
                    ],
                    ['applies_to_all', null, '1'],
                    ['approvers', null, [$defaultRoleId]]
                ]
            );

        $this->companyUser->expects($this->any())
            ->method('getCurrentCompanyId')
            ->willReturn($companyId);

        $this->companyRole->expects($this->any())
            ->method('getCompanyId')
            ->willReturn($companyId);

        $this->roleRepository->expects($this->any())
            ->method('get')
            ->with($defaultRoleId)
            ->willReturn($this->companyRole);

        $this->resultRedirectFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirect);

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('purchaseorderrule');

        $this->controller->execute();

        $rules = $ruleRepository->getByCompanyId($companyId)->getItems();
        $this->assertCount(1, $rules);
        $rule = current($rules);

        $this->assertFalse($rule->isAdminApprovalRequired());
        $this->assertEquals([$defaultRoleId], $rule->getApproverRoleIds());
    }

    /**
     * Verify a rule is able to remove manager approval requirement after it is created
     *
     * @magentoDataFixture Magento/Company/_files/company.php
     * @magentoDataFixture Magento/PurchaseOrderRule/_files/approval_manager_rule.php
     */
    public function testAbleToUnsetManagerAsApprover()
    {
        /* @var RuleRepository $ruleRepository */
        $ruleRepository = $this->objectManager->get(RuleRepository::class);

        /** @var RoleManagementInterface $roleManagement */
        $roleManagement = $this->objectManager->get(RoleManagementInterface::class);

        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $companyId = (int) $companyAdmin->getExtensionAttributes()->getCompanyAttributes()->getCompanyId();
        $defaultRoleId = $roleManagement->getCompanyDefaultRole($companyId)->getId();

        $this->session->loginById($companyAdmin->getId());

        $rules = $ruleRepository->getByCompanyId($companyId)->getItems();

        $this->assertCount(1, $rules);

        $rule = current($rules);
        $this->assertTrue($rule->isManagerApprovalRequired());

        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['rule_id', null, $rule->getId()],
                    ['name', null, $rule->getName()],
                    ['is_active', null, '1'],
                    [
                        'conditions',
                        null,
                        json_decode($rule->getConditionsSerialized(), true)['conditions']
                    ],
                    ['applies_to_all', null, '1'],
                    ['approvers', null, [$defaultRoleId]]
                ]
            );

        $this->companyUser->expects($this->any())
            ->method('getCurrentCompanyId')
            ->willReturn($companyId);

        $this->companyRole->expects($this->any())
            ->method('getCompanyId')
            ->willReturn($companyId);

        $this->roleRepository->expects($this->any())
            ->method('get')
            ->with($defaultRoleId)
            ->willReturn($this->companyRole);

        $this->resultRedirectFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirect);

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('purchaseorderrule');

        $this->controller->execute();

        $rules = $ruleRepository->getByCompanyId($companyId)->getItems();
        $this->assertCount(1, $rules);
        $rule = current($rules);

        $this->assertFalse($rule->isManagerApprovalRequired());
        $this->assertEquals([$defaultRoleId], $rule->getApproverRoleIds());
    }

    /**
     * Create a valid admin rule for a logged in customer and ensure a success message is received and rule is created
     *
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     */
    public function testSaveValidAdminRule()
    {
        /* @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        /* @var RuleRepository $ruleRepository */
        $ruleRepository = $this->objectManager->get(RuleRepository::class);
        /** @var RoleManagementInterface $roleManagement */
        $roleManagement = $this->objectManager->get(RoleManagementInterface::class);

        $roleId = $this->getRoleId();
        $companyAdmin = $this->customerRepository->get('john.doe@example.com');
        $this->session->loginById($companyAdmin->getId());

        $adminRoleId = $roleManagement->getAdminRole()->getId();

        $ruleName = 'Created Purchase Order Rule';

        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['name', null, $ruleName],
                    ['is_active', null, '1'],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'grand_total',
                                'value' => '100',
                                'operator' => '>',
                                'currency_code' => 'USD'
                            ]
                        ],
                    ],
                    [
                        'applies_to',
                        null,
                        [$roleId]
                    ],
                    ['approvers', null, [$adminRoleId]]
                ]
            );

        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('The approval rule has been created.'));

        $this->resultRedirectFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirect);

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('purchaseorderrule');

        $this->controller->execute();

        $ruleSearch = $ruleRepository->getList(
            $searchCriteriaBuilder
                ->addFilter('name', $ruleName)
                ->create()
        );

        $this->assertEquals($ruleSearch->getTotalCount(), 1);

        /* @var RuleInterface $rule */
        $rule = current($ruleSearch->getItems());

        // Assert basic information about the created rule
        $this->assertEquals($ruleName, $rule->getName());
        $this->assertNull($rule->getDescription());
        $this->assertNotEmpty($rule->getConditionsSerialized());
        $this->assertEquals($this->companyUser->getCurrentCompanyId(), $rule->getCompanyId());
        $this->assertEquals([], $rule->getApproverRoleIds());
        $this->assertTrue($rule->isAdminApprovalRequired());
        $this->assertTrue($rule->isActive());

        // Verify the condition that was generated contains the correct information regarding the input
        $storedCondition = json_decode($rule->getConditionsSerialized(), true);
        $this->assertArrayHasKey('conditions', $storedCondition);
        $this->assertCount(1, $storedCondition['conditions']);
        $this->assertEquals('grand_total', $storedCondition['conditions'][0]['attribute']);
        $this->assertEquals('>', $storedCondition['conditions'][0]['operator']);
        $this->assertEquals('100', $storedCondition['conditions'][0]['value']);
    }

    /**
     * Create a valid manager rule for a logged in customer and ensure a success message is received and rule is created
     *
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     */
    public function testSaveValidManagerRule()
    {
        /* @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        /* @var RuleRepository $ruleRepository */
        $ruleRepository = $this->objectManager->get(RuleRepository::class);
        /** @var RoleManagementInterface $roleManagement */
        $roleManagement = $this->objectManager->get(RoleManagementInterface::class);

        $roleId = $this->getRoleId();
        $companyAdmin = $this->customerRepository->get('john.doe@example.com');
        $this->session->loginById($companyAdmin->getId());

        $managerRoleId = $roleManagement->getManagerRole()->getId();

        $ruleName = 'Created Purchase Order Rule';

        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['name', null, $ruleName],
                    ['is_active', null, '1'],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'grand_total',
                                'value' => '100',
                                'operator' => '>',
                                'currency_code' => 'USD'
                            ]
                        ],
                    ],
                    [
                        'applies_to',
                        null,
                        [$roleId]
                    ],
                    ['approvers', null, [$managerRoleId]]
                ]
            );

        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('The approval rule has been created.'));

        $this->resultRedirectFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirect);

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('purchaseorderrule');

        $this->controller->execute();

        $ruleSearch = $ruleRepository->getList(
            $searchCriteriaBuilder
                ->addFilter('name', $ruleName)
                ->create()
        );

        $this->assertEquals($ruleSearch->getTotalCount(), 1);

        /* @var RuleInterface $rule */
        $rule = current($ruleSearch->getItems());

        // Assert basic information about the created rule
        $this->assertEquals($ruleName, $rule->getName());
        $this->assertNull($rule->getDescription());
        $this->assertNotEmpty($rule->getConditionsSerialized());
        $this->assertEquals($this->companyUser->getCurrentCompanyId(), $rule->getCompanyId());
        $this->assertEquals([], $rule->getApproverRoleIds());
        $this->assertTrue($rule->isManagerApprovalRequired());
        $this->assertTrue($rule->isActive());

        // Verify the condition that was generated contains the correct information regarding the input
        $storedCondition = json_decode($rule->getConditionsSerialized(), true);
        $this->assertArrayHasKey('conditions', $storedCondition);
        $this->assertCount(1, $storedCondition['conditions']);
        $this->assertEquals('grand_total', $storedCondition['conditions'][0]['attribute']);
        $this->assertEquals('>', $storedCondition['conditions'][0]['operator']);
        $this->assertEquals('100', $storedCondition['conditions'][0]['value']);
    }

    /**
     * Retrieve the role ID for the created company
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getRoleId()
    {
        // We require the concrete version of role repository for this test
        $this->roleRepository = $this->objectManager->get(RoleRepository::class);
        $this->companyUser = $this->objectManager->get(CompanyUser::class);
        /* @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);

        $context = $this->objectManager->create(
            Context::class,
            [
                'request' => $this->request,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $this->resultRedirectFactory
            ]
        );

        $this->controller = $this->objectManager->create(
            SaveController::class,
            [
                'context' => $context,
                'roleRepository' => $this->roleRepository,
                'companyUser' => $this->companyUser
            ]
        );

        $companyAdmin = $this->customerRepository->get('john.doe@example.com');
        $this->session->loginById($companyAdmin->getId());
        $roles = $this->roleRepository->getList(
            $searchCriteriaBuilder
                ->addFilter('company_id', $this->companyUser->getCurrentCompanyId())
                ->create()
        );

        if ($roles->getTotalCount() === 0) {
            $this->fail('Company does not contain at least one role to create rule for.');
            return;
        }

        /* @var Role $role */
        $role = current($roles->getItems());
        return $role->getId();
    }

    /**
     * Error an error message is present and a redirect to the referral URL occurs
     *
     * @param $message
     * @throws LocalizedException
     */
    private function assertErrorMethodAndRedirect($message)
    {
        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__($message));

        $this->resultRedirectFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirect);

        $this->resultRedirect->expects($this->once())
            ->method('setRefererUrl');

        $this->controller->execute();
    }

    /**
     * Data provider for save validation of new and existing rules.
     *
     * @return array
     */
    public function saveRuleDataProvider()
    {
        return [
            'save_new_valid_rule' => [
                'rule_name' => 'New Purchase Order Rule',
                'active_rules_count' => 2,
                'success_message' => 'The approval rule has been created.'
            ],
            'save_changes_to_existing_rule' => [
                'rule_name' => 'Integration Test Rule Name',
                'active_rules_count' => 1,
                'success_message' => 'The approval rule has been updated.'
            ]
        ];
    }

    /**
     * Test that trying to save changes to existing rule from other company
     */
    public function testSaveChangesToNotExistingRule()
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['rule_id', null, 99999],
                    ['name', null, 'Purchase Order Rule'],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'grand_total',
                                'value' => '100',
                                'operator' => '>',
                                'currency_code' => 'USD'
                            ]
                        ],
                    ],
                    [
                        'applies_to_all',
                        null,
                        '1'
                    ],
                    [
                        'applies_to',
                        null,
                        [10]
                    ],
                    [
                        'approvers',
                        null,
                        [10]
                    ]
                ]
            );

        $this->companyRole->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(1);

        $this->roleRepository->expects($this->any())
            ->method('get')
            ->with(10)
            ->willReturn($this->companyRole);

        $this->companyUser->expects($this->any())
            ->method('getCurrentCompanyId')
            ->willReturn(1);

        $this->assertErrorMethodAndRedirect('Rule with id "99999" does not exist.');
    }

    /**
     * Test that trying to save changes to existing rule from other company
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     * @magentoDataFixture Magento/PurchaseOrderRule/_files/approval_rule.php
     */
    public function testSaveChangesToRuleFromOtherCompany()
    {
        /* @var RuleInterface $rule */
        $rule = current($this->findRules('name', 'Integration Test Rule Name')->getItems());
        $roleId = $this->getRoleId();

        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['rule_id', null, $rule->getId()],
                    ['name', null, 'Purchase Order Rule'],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'grand_total',
                                'value' => '100',
                                'operator' => '>',
                                'currency_code' => 'USD'
                            ]
                        ],
                    ],
                    [
                        'applies_to_all',
                        null,
                        '1'
                    ],
                    [
                        'applies_to',
                        null,
                        [$roleId]
                    ],
                    [
                        'approvers',
                        null,
                        [$roleId]
                    ]
                ]
            );

        $this->assertErrorMethodAndRedirect('The selected rule does not exist.');
    }

    /**
     *  Checks that existent rule save do not override creator id.
     *
     * @magentoDataFixture Magento/PurchaseOrderRule/_files/company_with_purchase_order_multiple_approvers_multiple_rules.php
     */
    public function testSaveExistingRuleDoesNotChangeCreator()
    {
        $roleId = $this->getRoleId();
        $rule = current($this->findRules('name', 'Integration Test Rule Name')->getItems());
        $this->assertNotNull($rule->getCreatedBy());
        $companyUser = $this->customerRepository->get('veronica.costello@example.com');
        $this->session->loginById($companyUser->getId());

        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['rule_id', null, $rule->getId()],
                    ['name', null, $rule->getName()],
                    ['is_active', null, '1'],
                    ['description', null, $rule->getDescription()],
                    [
                        'conditions',
                        null,
                        [
                            [
                                'attribute' => 'grand_total',
                                'operator' => '>',
                                'value' => '100'
                            ]
                        ]
                    ],
                    [
                        'applies_to_all',
                        null,
                        '1'
                    ],
                    [
                        'approvers',
                        null,
                        [$roleId]
                    ]
                ]
            );

        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('The approval rule has been updated.'));

        $this->resultRedirectFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirect);

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('purchaseorderrule');

        $this->controller->execute();
        $rule = current($this->findRules('name', 'Integration Test Rule Name')->getItems());
        $this->assertNotEquals($this->session->getCustomerId(), $rule->getCreatedBy());
    }
}
