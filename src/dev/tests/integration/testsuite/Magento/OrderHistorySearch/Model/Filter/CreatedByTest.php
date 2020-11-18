<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model\Filter;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Block\Order\History as OrderHistory;
use Magento\Framework\App\RequestInterface;

/**
 * CreatedBy Filter for Order History Search Test
 *
 * @see \Magento\OrderHistorySearch\Model\Filter\CreatedBy
 *
 * @magentoDataFixture Magento/Company/_files/company_with_structure.php
 * @magentoDataFixture Magento/Sales/_files/order_list.php
 * @magentoAppIsolation enabled
 * @magentoDbIsolation disabled
 * @magentoAppArea frontend
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreatedByTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

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

    /**
     * @var CreatedBy
     */
    private $createdByFilter;

    /**
     * @var CustomerInterface[]
     */
    private $companyUsersByRole;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $this->session = $objectManager->get(Session::class);
        $this->createdByFilter = $objectManager->get(CreatedBy::class);
        $this->customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
        $this->request = $objectManager->get(RequestInterface::class);
        $this->orderRepository = $objectManager->create(OrderRepositoryInterface::class);
        $this->companyRepository = $objectManager->get(CompanyRepositoryInterface::class);
        $this->roleRepository = $objectManager->get(RoleRepositoryInterface::class);
        $this->searchCriteriaBuilder = $objectManager->create(SearchCriteriaBuilder::class);

        $customerRepository = $this->customerRepository;

        $this->companyUsersByRole = [
            'companyAdmin' => $customerRepository->get('john.doe@example.com'),
            'companyUserLevelOne' => $customerRepository->get('veronica.costello@example.com'),
            'companyUserLevelTwo' => $customerRepository->get('alex.smith@example.com'),
        ];

        $this->setCompanyActiveStatus(true);
        $this->assignOrdersFromFixtureToCompanyUsers($this->companyUsersByRole);
    }

    /**
     * Test createdBy apply filter for each member in company structure returns correct order list
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testApplyFilter()
    {
        // Allow the company "Default User" role to view subordinate orders
        $this->setCompanyRolePermission(
            'Magento',
            'Default User',
            'Magento_Sales::view_orders_sub',
            PermissionInterface::ALLOW_PERMISSION
        );

        $companyUsersByRole = $this->companyUsersByRole;

        ///////
        // Company Admin assertions
        $this->loginAsCustomer($companyUsersByRole['companyAdmin']);
        $this->assertCreatedByFilterShowsOnlyTheseOrderIds(
            'all',
            [
                '100000001',
                '100000002',
                '100000003',
            ]
        );

        $this->assertCreatedByFilterShowsOnlyTheseOrderIds(
            $companyUsersByRole['companyAdmin']->getId(),
            [
                '100000001',
            ]
        );

        $this->assertCreatedByFilterShowsOnlyTheseOrderIds(
            $companyUsersByRole['companyUserLevelOne']->getId(),
            [
                '100000002',
            ]
        );

        $this->assertCreatedByFilterShowsOnlyTheseOrderIds(
            $companyUsersByRole['companyUserLevelTwo']->getId(),
            [
                '100000003',
            ]
        );

        ////////
        // Company User Level One assertions
        $this->loginAsCustomer($companyUsersByRole['companyUserLevelOne']);

        $this->assertCreatedByFilterShowsOnlyTheseOrderIds(
            'all',
            [
                '100000002',
                '100000003',
            ]
        );

        $this->assertCreatedByFilterShowsOnlyTheseOrderIds(
            $companyUsersByRole['companyAdmin']->getId(),
            []
        );

        $this->assertCreatedByFilterShowsOnlyTheseOrderIds(
            $companyUsersByRole['companyUserLevelOne']->getId(),
            [
                '100000002',
            ]
        );

        $this->assertCreatedByFilterShowsOnlyTheseOrderIds(
            $companyUsersByRole['companyUserLevelTwo']->getId(),
            [
                '100000003',
            ]
        );

        ////////
        // Company User Level Two assertions
        $this->loginAsCustomer($companyUsersByRole['companyUserLevelTwo']);
        $this->assertCreatedByFilterShowsOnlyTheseOrderIds(
            'all',
            [
                '100000003',
            ]
        );

        $this->assertCreatedByFilterShowsOnlyTheseOrderIds(
            $companyUsersByRole['companyAdmin']->getId(),
            []
        );

        $this->assertCreatedByFilterShowsOnlyTheseOrderIds(
            $companyUsersByRole['companyUserLevelOne']->getId(),
            []
        );

        $this->assertCreatedByFilterShowsOnlyTheseOrderIds(
            $companyUsersByRole['companyUserLevelTwo']->getId(),
            [
                '100000003',
            ]
        );
    }

    /**
     * Test createdBy apply filter when the customer does not have permission to view subordinate orders.
     *
     * The customer should only see orders which they created, regardless of how many subordinates they have.
     */
    public function testApplyFilterWithoutCompanyRolePermission()
    {
        // Deny the company "Default User" role the ability to view subordinate orders
        $this->setCompanyRolePermission(
            'Magento',
            'Default User',
            'Magento_Sales::view_orders_sub',
            PermissionInterface::DENY_PERMISSION
        );

        $this->loginAsCustomer($this->companyUsersByRole['companyUserLevelOne']);

        // Assert that attempting to filter by "all" only includes orders for self
        $this->assertCreatedByFilterShowsOnlyTheseOrderIds(
            'all',
            ['100000002']
        );

         // Assert that attempting to filter by a subordinate's order instead returns orders for self
        $this->assertCreatedByFilterShowsOnlyTheseOrderIds(
            $this->companyUsersByRole['companyUserLevelOne']->getId(),
            ['100000002']
        );
    }

    /**
     * @param string $createdByCustomerId
     * @param array $expectedOrderIds
     */
    private function assertCreatedByFilterShowsOnlyTheseOrderIds($createdByCustomerId, array $expectedOrderIds)
    {
        $this->request->setParam('advanced-filtering', '');

        if ($createdByCustomerId === 'all') {
            $this->request->setParam('created-by', '');
        } else {
            $this->request->setParam('created-by', $createdByCustomerId);
        }

        $orderHistory = Bootstrap::getObjectManager()->create(OrderHistory::class);
        $actualOrderCollection = $orderHistory->getOrders()->load();

        $actualOrderIds = array_column($actualOrderCollection->toArray()['items'], 'increment_id');

        sort($expectedOrderIds, SORT_NUMERIC);
        sort($actualOrderIds, SORT_NUMERIC);

        $this->assertEquals($expectedOrderIds, $actualOrderIds);
    }

    /**
     * Login as a customer.
     *
     * @param CustomerInterface $customer
     * @return void
     */
    private function loginAsCustomer(CustomerInterface $customer)
    {
        $this->session->loginById($customer->getId());
    }

    /**
     * @param CustomerInterface[] $companyUsers
     */
    private function assignOrdersFromFixtureToCompanyUsers(array $companyUsers)
    {
        $orderIncrementIdToCompanyUserMap = [
            '100000001' => $companyUsers['companyAdmin'],
            '100000002' => $companyUsers['companyUserLevelOne'],
            '100000003' => $companyUsers['companyUserLevelTwo'],
        ];

        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            OrderInterface::INCREMENT_ID,
            array_keys($orderIncrementIdToCompanyUserMap),
            'in'
        )->create();

        $orderRepository = $this->orderRepository;
        $orders = $orderRepository->getList($searchCriteria)->getItems();

        // assign orders to company user based on $orderIncrementIdToCompanyUserMap
        foreach ($orders as $order) {
            /** @var CustomerInterface $companyUserToAssignOrderTo */
            $companyUserToAssignOrderTo = $orderIncrementIdToCompanyUserMap[$order->getIncrementId()];

            $order
                ->setCustomerId($companyUserToAssignOrderTo->getId())
                ->setCustomerEmail($companyUserToAssignOrderTo->getEmail())
                ->setCustomerIsGuest(false);

            $orderRepository->save($order);
        }
    }

    /**
     * Set company active status.
     *
     * magentoConfigFixture does not support changing the value for website scope.
     *
     * @param bool $isActive
     */
    private function setCompanyActiveStatus($isActive)
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
}
