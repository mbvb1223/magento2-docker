<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Controller\PurchaseOrder;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\Company\Config\RepositoryInterface as CompanyConfigRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Controller test class for the purchase order details page.
 *
 * @see \Magento\PurchaseOrder\Controller\PurchaseOrder\View
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class ViewTest extends AbstractController
{
    const URI = 'purchaseorder/purchaseorder/view';

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var CompanyConfigRepositoryInterface
     */
    private $companyConfigRepository;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Session
     */
    private $session;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        $this->companyRepository = $objectManager->get(CompanyRepositoryInterface::class);
        $this->companyConfigRepository = $objectManager->get(CompanyConfigRepositoryInterface::class);
        $this->roleRepository = $objectManager->get(RoleRepositoryInterface::class);
        $this->customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
        $this->purchaseOrderRepository = $objectManager->get(PurchaseOrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->session = $objectManager->get(Session::class);

        // Enable company functionality for the website scope
        $this->setWebsiteConfig('btob/website_configuration/company_active', true);

        // Enable purchase order functionality for the website scope
        $this->setWebsiteConfig('btob/website_configuration/purchaseorder_enabled', true);

        // Grant the "Default User" role with permission to the purchase order grouping resource.
        $this->setCompanyRolePermission(
            'Magento',
            'Default User',
            'Magento_PurchaseOrder::all',
            PermissionInterface::ALLOW_PERMISSION
        );

        $this->setCompanyRolePermission(
            'Magento',
            'Default User',
            'Magento_PurchaseOrder::view_purchase_orders_for_subordinates',
            PermissionInterface::DENY_PERMISSION
        );

        $this->setCompanyRolePermission(
            'Magento',
            'Default User',
            'Magento_PurchaseOrder::view_purchase_orders',
            PermissionInterface::DENY_PERMISSION
        );
    }

    /**
     * Enable/Disable configuration for the website scope.
     *
     * magentoConfigFixture does not allow changing the value for website scope.
     *
     * @param string $path
     * @param bool $isEnabled
     */
    private function setWebsiteConfig(string $path, bool $isEnabled)
    {
        /** @var MutableScopeConfigInterface $scopeConfig */
        $scopeConfig = Bootstrap::getObjectManager()->get(MutableScopeConfigInterface::class);
        $scopeConfig->setValue(
            $path,
            $isEnabled ? '1' : '0',
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Enable/Disable purchase order functionality on a per company basis.
     *
     * @param string $companyName
     * @param bool $isEnabled
     * @throws LocalizedException
     */
    private function setCompanyPurchaseOrderConfig(string $companyName, bool $isEnabled)
    {
        $this->searchCriteriaBuilder->addFilter('company_name', $companyName);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $results = $this->companyRepository->getList($searchCriteria)->getItems();

        /** @var CompanyInterface $company */
        $company = reset($results);

        $companyConfig = $this->companyConfigRepository->get($company->getId());
        $companyConfig->setIsPurchaseOrderEnabled($isEnabled);

        $this->companyConfigRepository->save($companyConfig);
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
     * Test that a company user has the proper access to view the purchase order details page.
     *
     * This is based on various configuration/permission settings as well as the company hierarchy.
     *
     * @dataProvider viewActionAsCompanyUserDataProvider
     * @param string $currentUserEmail
     * @param string $createdByUserEmail
     * @param int $companyPurchaseOrdersConfigEnabled
     * @param string[] $viewPurchaseOrdersPermissions
     * @param int $expectedHttpResponseCode
     * @param string $expectedRedirect
     * @param string $purchaseOrderId
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     */
    public function testViewActionAsCompanyUser(
        $currentUserEmail,
        $createdByUserEmail,
        $companyPurchaseOrdersConfigEnabled,
        $viewPurchaseOrdersPermissions,
        $expectedHttpResponseCode,
        $expectedRedirect,
        $purchaseOrderId = ''
    ) {
        // Enable/Disable purchase order functionality for the specific company
        $this->setCompanyPurchaseOrderConfig('Magento', (bool) $companyPurchaseOrdersConfigEnabled);

        foreach ($viewPurchaseOrdersPermissions as $viewPurchaseOrdersPermission) {
            $this->setCompanyRolePermission(
                'Magento',
                'Default User',
                $viewPurchaseOrdersPermission,
                PermissionInterface::ALLOW_PERMISSION
            );
        }

        // Log in as the current user
        $currentUser = $this->customerRepository->get($currentUserEmail);
        $this->session->loginById($currentUser->getId());

        // Dispatch the request to the view details page for the desired purchase order
        $purchaseOrderId = $purchaseOrderId ?: $this->getPurchaseOrderForCustomer($createdByUserEmail)->getEntityId();
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrderId);

        // Perform assertions
        $this->assertEquals($expectedHttpResponseCode, $this->getResponse()->getHttpResponseCode());

        if ($expectedRedirect) {
            $this->assertRedirect($this->stringContains($expectedRedirect));
        }

        $this->session->logout();
    }

    /**
     * Data provider for various view action scenarios for company users.
     *
     * @return array
     */
    public function viewActionAsCompanyUserDataProvider()
    {
        return [
            'view_my_purchase_order' => [
                'current_customer' => 'veronica.costello@example.com',
                'created_by_customer' => 'veronica.costello@example.com',
                'company_purchase_order_config_is_enabled' => 1,
                'view_purchase_order_permissions' => ['Magento_PurchaseOrder::view_purchase_orders'],
                'expected_http_response_code' => 200,
                'expected_redirect' => ''
            ],
            'view_my_purchase_order_without_permission' => [
                'current_customer' => 'veronica.costello@example.com',
                'created_by_customer' => 'veronica.costello@example.com',
                'company_purchase_order_config_is_enabled' => 1,
                'view_purchase_order_permissions' => [],
                'expected_http_response_code' => 302,
                'expected_redirect' => 'company/accessdenied'
            ],
            'view_my_purchase_order_without_company_purchase_orders_enabled' => [
                'current_customer' => 'veronica.costello@example.com',
                'created_by_customer' => 'veronica.costello@example.com',
                'company_purchase_order_config_is_enabled' => 0,
                'view_purchase_order_permissions' => ['Magento_PurchaseOrder::view_purchase_orders'],
                'expected_http_response_code' => 302,
                'expected_redirect' => 'company/accessdenied'
            ],
            'view_subordinate_purchase_order_no_view_subordinate_permission' => [
                'current_customer' => 'veronica.costello@example.com',
                'created_by_customer' => 'alex.smith@example.com',
                'company_purchase_order_config_is_enabled' => 1,
                'view_purchase_order_permissions' => ['Magento_PurchaseOrder::view_purchase_orders'],
                'expected_http_response_code' => 302,
                'expected_redirect' => ''
            ],
            'view_subordinate_purchase_order_with_view_subordinate_permission' => [
                'current_customer' => 'veronica.costello@example.com',
                'created_by_customer' => 'alex.smith@example.com',
                'company_purchase_order_config_is_enabled' => 1,
                'view_purchase_order_permissions' => [
                    'Magento_PurchaseOrder::view_purchase_orders',
                    'Magento_PurchaseOrder::view_purchase_orders_for_subordinates'
                ],
                'expected_http_response_code' => 200,
                'expected_redirect' => ''
            ],
            'view_superior_purchase_order' => [
                'current_customer' => 'veronica.costello@example.com',
                'created_by_customer' => 'john.doe@example.com',
                'company_purchase_order_config_is_enabled' => 1,
                'view_purchase_order_permissions' => ['Magento_PurchaseOrder::view_purchase_orders'],
                'expected_http_response_code' => 302,
                'expected_redirect' => 'company/accessdenied'
            ],
            'view_superior_purchase_order_with_view_company_permission' => [
                'current_customer' => 'veronica.costello@example.com',
                'created_by_customer' => 'john.doe@example.com',
                'company_purchase_order_config_is_enabled' => 1,
                'view_purchase_order_permissions' => [
                    'Magento_PurchaseOrder::view_purchase_orders',
                    'Magento_PurchaseOrder::view_purchase_orders_for_company'
                ],
                'expected_http_response_code' => 200,
                'expected_redirect' => ''
            ],
            'view_subordinate_purchase_order_with_view_company_permission' => [
                'current_customer' => 'veronica.costello@example.com',
                'created_by_customer' => 'alex.smith@example.com',
                'company_purchase_order_config_is_enabled' => 1,
                'view_purchase_order_permissions' => [
                    'Magento_PurchaseOrder::view_purchase_orders',
                    'Magento_PurchaseOrder::view_purchase_orders_for_company'
                ],
                'expected_http_response_code' => 200,
                'expected_redirect' => ''
            ],
            'company_admin_view_purchase_order' => [
                'current_customer' => 'john.doe@example.com',
                'created_by_customer' => 'alex.smith@example.com',
                'company_purchase_order_config_is_enabled' => 1,
                'view_purchase_order_permission_value' => [],
                'expected_http_response_code' => 200,
                'expected_redirect' => ''
            ],
            'company_admin_view_!existing_purchase_order' => [
                'current_customer' => 'john.doe@example.com',
                'created_by_customer' => 'alex.smith@example.com',
                'company_purchase_order_config_is_enabled' => 1,
                'view_purchase_order_permission_value' => [],
                'expected_http_response_code' => 302,
                'expected_redirect' => 'company/accessdenied',
                'purchase_order_id' => '5000'
            ]
        ];
    }

    /**
     * Test that a user who is not affiliated with a company is redirected to a 'noroute' page.
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testViewActionAsNonCompanyUser()
    {
        $nonCompanyUser = $this->customerRepository->get('customer@example.com');

        $this->session->loginById($nonCompanyUser->getId());
        $purchaseOrder = $this->getPurchaseOrderForCustomer('alex.smith@example.com');
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());
        $this->assertRedirect($this->stringContains('noroute'));

        $this->session->logout();
    }

    /**
     * Test that a guest user is redirected to the login page.
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     */
    public function testViewActionAsGuestUser()
    {
        $purchaseOrder = $this->getPurchaseOrderForCustomer('alex.smith@example.com');
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());
        $this->assertRedirect($this->stringContains('customer/account/login'));
    }

    /**
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     * @magentoDataFixture Magento/Company/_files/company_with_admin.php
     */
    public function testViewActionAsOtherCompanyAdmin()
    {
        $otherCompanyAdmin = $this->customerRepository->get('company-admin@example.com');
        $this->session->loginById($otherCompanyAdmin->getId());

        $purchaseOrder = $this->getPurchaseOrderForCustomer('alex.smith@example.com');
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());

        // Perform assertions
        $this->assertEquals(302, $this->getResponse()->getHttpResponseCode());
        $this->assertRedirect($this->stringContains('company/accessdenied'));

        $this->session->logout();
    }

    /**
     * Get purchase order for the given customer.
     *
     * @param string $customerEmail
     * @return \Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface
     */
    private function getPurchaseOrderForCustomer(string $customerEmail)
    {
        $customer = $this->customerRepository->get($customerEmail);
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(PurchaseOrderInterface::CREATOR_ID, $customer->getId())
            ->create();
        $purchaseOrders = $this->purchaseOrderRepository->getList($searchCriteria)->getItems();
        return array_shift($purchaseOrders);
    }
}
