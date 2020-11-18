<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model\Order\Customer;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\OrderHistorySearch\Model\Order\Customer\DataProvider as CustomerDataProvider;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

/**
 * Test for Magento\OrderHistorySearch\Model\Order\Customer\DataProvider class.
 *
 * @magentoAppIsolation enabled
 * @magentoAppArea frontend
 */
class DataProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CustomerDataProvider
     */
    private $customerDataProvider;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    protected function setUp(): void
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        $this->customerDataProvider = $objectManager->create(CustomerDataProvider::class);
        $this->customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
        $this->companyRepository = $objectManager->get(CompanyRepositoryInterface::class);
        $this->roleRepository = $objectManager->get(RoleRepositoryInterface::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->setCompanyActiveStatus(true);
    }

    /**
     * Set company active status.
     *
     * magentoConfigFixture does not allow changing the value for website scope.
     *
     * @param bool $isActive
     */
    private function setCompanyActiveStatus(bool $isActive)
    {
        $scopeConfig = Bootstrap::getObjectManager()->get(MutableScopeConfigInterface::class);
        $scopeConfig->setValue(
            'btob/website_configuration/company_active',
            $isActive ? '1' : '0',
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Sets the permission value for the specified company role.
     *
     * @param string $companyName
     * @param string $roleName
     * @param string $resourceId
     * @param string $permissionValue
     */
    private function setCompanyRolePermission(
        string $companyName,
        string $roleName,
        string $resourceId,
        string $permissionValue
    ) {
        // Get the company
        $this->searchCriteriaBuilder->addFilter('company_name', $companyName);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $results = $this->companyRepository->getList($searchCriteria)->getItems();

        /** @var CompanyInterface $company */
        $company = reset($results);

        // Get the company role
        $this->searchCriteriaBuilder->addFilter('company_id', $company->getId());
        $this->searchCriteriaBuilder->addFilter('role_name', $roleName);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $results = $this->roleRepository->getList($searchCriteria)->getItems();

        /** @var RoleInterface $role */
        $role = reset($results);

        // For that role, find the specified permission and set it to the desired value
        /** @var PermissionInterface $permission */
        foreach ($role->getPermissions() as $permission) {
            if ($permission->getResourceId() === $resourceId) {
                $permission->setPermission($permissionValue);
                break;
            }
        }

        $this->roleRepository->save($role);
    }

    /**
     * Login as a customer.
     *
     * @param string $customerId Customer to mark as logged in for the session
     * @return void
     */
    private function loginAsCustomer($customerId)
    {
        /** @var Session $session */
        $session = Bootstrap::getObjectManager()->get(Session::class);
        $session->loginById($customerId);
    }

    /**
     * Test that a customer who is not affiliated with a company can only see themselves as a customer option.
     *
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testGetAllowedCustomerOptionsWithNoCompany()
    {
        $noCompanyCustomerId = $this->customerRepository->get('customer@example.com')->getId();
        $expectedCustomerOptions = [
            [
               'value' => $noCompanyCustomerId,
               'label' => 'John Smith'
            ]
        ];

        $this->assertCustomerHasExpectedOptions($noCompanyCustomerId, $expectedCustomerOptions);
    }

    /**
     * Test that a customer who does not have permission to view subordinate orders can only see themselves
     * as a customer option.
     *
     * This is irrespective of how many subordinates the customer has.
     *
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     */
    public function testGetAllowedCustomerOptionsWithoutCompanyRolePermission()
    {
        // Deny the company "Default User" role the ability to view subordinate orders.
        $this->setCompanyRolePermission(
            'Magento',
            'Default User',
            'Magento_Sales::view_orders_sub',
            PermissionInterface::DENY_PERMISSION
        );

        $defaultUserWithSubordinates = $this->customerRepository->get('veronica.costello@example.com');
        $expectedCustomerOptions = [
            [
                'value' => $defaultUserWithSubordinates->getId(),
                'label' => 'Veronica Costello'
            ]
        ];

        $this->assertCustomerHasExpectedOptions($defaultUserWithSubordinates->getId(), $expectedCustomerOptions);
    }

    /**
     * Test that the expected customer options are returned for a customer.
     *
     * This is based on the customer's location in the company hierarchy.
     *
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     */
    public function testGetAllowedCustomerOptionsWithCompanyHierarchy()
    {
        // Allow the company "Default User" role to view subordinate orders.
        $this->setCompanyRolePermission(
            'Magento',
            'Default User',
            'Magento_Sales::view_orders_sub',
            PermissionInterface::ALLOW_PERMISSION
        );

        $scenarioData = $this->getAllowedCustomerOptionsDataProvider('customer_with_no_subordinates');
        $this->assertCustomerHasExpectedOptions(
            $scenarioData['customer_id'],
            $scenarioData['expected_customer_options']
        );

        $scenarioData = $this->getAllowedCustomerOptionsDataProvider('customer_with_direct_subordinate');
        $this->assertCustomerHasExpectedOptions(
            $scenarioData['customer_id'],
            $scenarioData['expected_customer_options']
        );

        $scenarioData = $this->getAllowedCustomerOptionsDataProvider('customer_with_nested_subordinates');
        $this->assertCustomerHasExpectedOptions(
            $scenarioData['customer_id'],
            $scenarioData['expected_customer_options']
        );
    }

    /**
     * Assert that the expected customer options are returned for the specified customer.
     *
     * @param $customerId
     * @param $expectedCustomerOptions
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function assertCustomerHasExpectedOptions($customerId, $expectedCustomerOptions)
    {
        $this->loginAsCustomer($customerId);

        $actualCustomerOptions = $this->customerDataProvider->getAllowedCustomerOptions();

        // Assert that the EXACT number of expected customer options are present
        $this->assertEquals(count($expectedCustomerOptions), count($actualCustomerOptions));

        // Assert that each expected customer option is present (ignore order)
        foreach ($expectedCustomerOptions as $expectedCustomerOption) {
            $this->assertContains($expectedCustomerOption, $actualCustomerOptions);
        }
    }

    /**
     * Data provider for testGetAllowedCustomerOptions.
     *
     * This data provider is not used with an annotation since the customers created by the fixture must be fetched
     * from the database before determining the expected result data.
     *
     * @param string $scenario
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAllowedCustomerOptionsDataProvider(string $scenario)
    {
        $adminCustomerId = $this->customerRepository->get('john.doe@example.com')->getId();
        $adminCustomerOption = [
            'value' => $adminCustomerId,
            'label' => 'John Doe'
        ];

        $levelOneCustomerId = $this->customerRepository->get('veronica.costello@example.com')->getId();
        $levelOneCustomerOption = [
            'value' => $levelOneCustomerId,
            'label' => 'Veronica Costello'
        ];

        $levelTwoCustomerId = $this->customerRepository->get('alex.smith@example.com')->getId();
        $levelTwoCustomerOption = [
            'value' => $levelTwoCustomerId,
            'label' => 'Alex Smith'
        ];

        $scenarioData = [
            'customer_with_no_subordinates' => [
                'customer_id' => $levelTwoCustomerId,
                'expected_customer_options' => [
                    $levelTwoCustomerOption
                ]
            ],
            'customer_with_direct_subordinate' => [
                'customer_id' => $levelOneCustomerId,
                'expected_customer_options' => [
                    $levelOneCustomerOption,
                    $levelTwoCustomerOption
                ]
            ],
            'customer_with_nested_subordinates' => [
                'customer_id' => $adminCustomerId,
                'expected_customer_options' => [
                    $adminCustomerOption,
                    $levelOneCustomerOption,
                    $levelTwoCustomerOption
                ]
            ]
        ];

        return $scenarioData[$scenario];
    }
}
