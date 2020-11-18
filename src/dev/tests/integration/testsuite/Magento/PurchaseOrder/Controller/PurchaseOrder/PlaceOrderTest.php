<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Controller\PurchaseOrder;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\Comment;
use Magento\PurchaseOrder\Model\CommentManagement;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\Framework\Message\MessageInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Tax\Api\TaxRateManagementInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as CatalogRuleCollectionFactory;
use Magento\CatalogRule\Model\Indexer\IndexBuilder as CatalogRuleIndexBuilder;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\CustomerBalance\Model\BalanceFactory as CustomerBalanceFactory;
use Magento\CompanyCredit\Api\CreditLimitManagementInterface;
use Magento\CompanyCredit\Api\CreditLimitRepositoryInterface;

/**
 * Controller test class for the purchase order place order.
 *
 * @see \Magento\PurchaseOrder\Controller\PurchaseOrder\PlaceOrder
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class PlaceOrderTest extends AbstractController
{
    /**
     * Url to dispatch.
     */
    private const URI = 'purchaseorder/purchaseorder/placeorder';

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var CommentManagement
     */
    private $commentManagement;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var TaxRateRepositoryInterface
     */
    private $taxRateRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var TaxRateManagementInterface
     */
    private $taxRateManagement;

    /**
     * @var TaxRuleRepositoryInterface
     */
    private $taxRuleRepository;

    /**
     * @var CatalogRuleRepositoryInterface
     */
    private $catalogRuleRepository;

    /**
     * @var CatalogRuleCollectionFactory
     */
    private $catalogRuleCollectionFactory;

    /**
     * @var CatalogRuleIndexBuilder
     */
    private $catalogRuleIndexBuilder;

    /**
     * @var MutableScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var CustomerBalanceFactory
     */
    private $customerBalanceFactory;

    /**
     * @var CreditLimitManagementInterface
     */
    private $creditLimitManagement;

    /**
     * @var CreditLimitRepositoryInterface
     */
    private $creditLimitRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var ObjectManager $objectManager */
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->purchaseOrderRepository = $this->objectManager->get(PurchaseOrderRepositoryInterface::class);
        $this->orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->session = $this->objectManager->get(Session::class);
        $this->commentManagement = $this->objectManager->get(CommentManagement::class);
        $this->ruleRepository = $this->objectManager->get(RuleRepositoryInterface::class);
        $this->taxRateRepository = $this->objectManager->get(TaxRateRepositoryInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->taxRateManagement = $this->objectManager->get(TaxRateManagementInterface::class);
        $this->taxRuleRepository = $this->objectManager->get(TaxRuleRepositoryInterface::class);
        $this->catalogRuleRepository = $this->objectManager->get(CatalogRuleRepositoryInterface::class);
        $this->catalogRuleCollectionFactory = $this->objectManager->get(CatalogRuleCollectionFactory::class);
        $this->catalogRuleIndexBuilder = $this->objectManager->get(CatalogRuleIndexBuilder::class);
        $this->scopeConfig = $this->objectManager->get(MutableScopeConfigInterface::class);
        $this->configWriter = $this->objectManager->get(WriterInterface::class);
        $this->customerBalanceFactory = $this->objectManager->get(CustomerBalanceFactory::class);
        $this->creditLimitManagement = $this->objectManager->get(CreditLimitManagementInterface::class);
        $this->creditLimitRepository = $this->objectManager->get(CreditLimitRepositoryInterface::class);
        // Enable company functionality at the system level
        $this->scopeConfig->setValue(
            'btob/website_configuration/company_active',
            true ? '1' : '0',
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     */
    public function testPlaceOrderActionGetRequest()
    {
        $purchaseOrder = $this->getPurchaseOrderForCustomer('alex.smith@example.com');
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());
        $this->assert404NotFound();
    }

    /**
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     */
    public function testPlaceOrderActionAsGuestUser()
    {
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaseOrder = $this->getPurchaseOrderForCustomer('alex.smith@example.com');
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());
        $this->assertEquals(302, $this->getResponse()->getHttpResponseCode());
        $this->assertRedirect($this->stringContains('customer/account/login'));
    }

    /**
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testPlaceOrderActionAsNonCompanyUser()
    {
        $nonCompanyUser = $this->customerRepository->get('customer@example.com');
        $this->session->loginById($nonCompanyUser->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaseOrder = $this->getPurchaseOrderForCustomer('alex.smith@example.com');
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());
        $this->assertEquals(302, $this->getResponse()->getHttpResponseCode());
        $this->assertRedirect($this->stringContains('noroute'));

        $this->session->logout();
    }

    /**
     * @param string $currentUserEmail
     * @param string $createdByUserEmail
     * @param int $expectedHttpResponseCode
     * @param string $expectedRedirect
     * @dataProvider placeOrderActionAsCompanyUserDataProvider
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     */
    public function testPlaceOrderActionAsCompanyUser(
        $currentUserEmail,
        $createdByUserEmail,
        $expectedHttpResponseCode,
        $expectedRedirect
    ) {
        // Log in as the current user
        $currentUser = $this->customerRepository->get($currentUserEmail);
        $this->session->loginById($currentUser->getId());

        // Dispatch the request
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaseOrder = $this->getPurchaseOrderForCustomer($createdByUserEmail);
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());

        // Perform assertions
        $this->assertEquals($expectedHttpResponseCode, $this->getResponse()->getHttpResponseCode());
        $this->assertRedirect($this->stringContains($expectedRedirect));

        $this->session->logout();
    }

    /**
     * Data provider for various place order action scenarios for company users.
     *
     * @return array
     */
    public function placeOrderActionAsCompanyUserDataProvider()
    {
        return [
            'place_order_my_purchase_order' => [
                'current_customer' => 'veronica.costello@example.com',
                'created_by_customer' => 'veronica.costello@example.com',
                'expected_http_response_code' => 302,
                'expected_redirect' => 'company/accessdenied'
            ],
            'place_order_subordinate_purchase_order' => [
                'current_customer' => 'veronica.costello@example.com',
                'created_by_customer' => 'alex.smith@example.com',
                'expected_http_response_code' => 302,
                'expected_redirect' => 'company/accessdenied'
            ],
            'place_order_superior_purchase_order' => [
                'current_customer' => 'veronica.costello@example.com',
                'created_by_customer' => 'john.doe@example.com',
                'expected_http_response_code' => 302,
                'expected_redirect' => 'company/accessdenied'
            ]
        ];
    }

    /**
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     * @magentoDataFixture Magento/Company/_files/company_with_admin.php
     */
    public function testPlaceOrderActionAsOtherCompanyAdmin()
    {
        $otherCompanyAdmin = $this->customerRepository->get('company-admin@example.com');
        $this->session->loginById($otherCompanyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaseOrder = $this->getPurchaseOrderForCustomer('alex.smith@example.com');
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());

        // Perform assertions
        $this->assertEquals(302, $this->getResponse()->getHttpResponseCode());
        $this->assertRedirect($this->stringContains('company/accessdenied'));

        $this->session->logout();
    }

    /**
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders.php
     */
    public function testPlaceOrderActionNonexistingPurchaseOrder()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->dispatch(self::URI . '/request_id/' . '5000');
        $this->assertRedirect($this->stringContains('company/accessdenied'));

        $this->session->logout();
    }

    /**
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders.php
     * @dataProvider unconvertablePurchaseOrderStatusDataProvider
     * @param string $status
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testPlaceOrderAsCompanyAdminNonConvertablePurchaseOrder($status)
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaseOrder = $this->getPurchaseOrderForCustomer('customer@example.com');
        $purchaseOrder->setStatus($status);
        $this->purchaseOrderRepository->save($purchaseOrder);

        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());
        $message = 'Order cannot be placed with purchase order #' . $purchaseOrder->getIncrementId() . '.';
        $this->assertSessionMessages($this->equalTo([(string)__($message)]), MessageInterface::TYPE_ERROR);
        $this->session->logout();
    }

    /**
     * Data provider of purchase order statuses that do not allow approval.
     *
     * @return string[]
     */
    public function unconvertablePurchaseOrderStatusDataProvider()
    {
        return [
            [PurchaseOrderInterface::STATUS_PENDING],
            [PurchaseOrderInterface::STATUS_APPROVAL_REQUIRED],
            [PurchaseOrderInterface::STATUS_CANCELED],
            [PurchaseOrderInterface::STATUS_REJECTED],
            [PurchaseOrderInterface::STATUS_ORDER_PLACED],
            [PurchaseOrderInterface::STATUS_ORDER_IN_PROGRESS],
        ];
    }

    /**
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrder()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        $this->dispatch(self::URI . '/request_id/' . $id);

        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_with_virtual_product from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());

        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_with_virtual_product</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a company admin can place order with a comment
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders.php
     */
    public function testPlaceOrderActionAsCompanyAdminWithCommentPurchaseOrder()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);

        // Place the order against the Purchase Order
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->getRequest()->setParams([
            'comment' => 'This is our test comment'
        ]);
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());

        // Assert the Purchase Order is now approved
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrder->getEntityId());
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());

        // Verify the comment was added to the Purchase Order
        $comments = $this->commentManagement->getPurchaseOrderComments($purchaseOrder->getEntityId());
        $this->assertEquals(1, $comments->getSize());
        /** @var Comment $comment */
        $comment = $comments->getFirstItem();
        $this->assertEquals('This is our test comment', $comment->getComment());
        $this->assertEquals($companyAdmin->getId(), $comment->getCreatorId());

        $this->session->logout();
    }

    /**
     * Verify a purchase place order totals with cart price rule with coupon
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_coupon_applied.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithCartPriceRuleCoupon()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        $this->dispatch(self::URI . '/request_id/' . $id);

        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_1 from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_1</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with removed cart price rule rate with coupon
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_coupon_applied.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderOrderWithCartPriceRuleCouponRemove()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        //remove applied rule
        $appliedRule = $this->ruleRepository->getById($purchaseOrder->getSnapshotQuote()->getAppliedRuleIds());
        $this->ruleRepository->deleteById($appliedRule->getRuleId());
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_1 from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_1</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with changed cart price rule rate with coupon
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_coupon_applied.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithCartPriceRuleCouponChange()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        //change applied rule discount rate
        $appliedRule = $this->ruleRepository->getById($purchaseOrder->getSnapshotQuote()->getAppliedRuleIds());
        $appliedRule->setDiscountAmount(1);
        $this->ruleRepository->save($appliedRule);
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_1 from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_1</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with changed cart price rule rate without coupon
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_salesrule_without_coupon_applied.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithCartPriceRuleNoCouponChange()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        //change applied rule discount rate
        $appliedRule = $this->ruleRepository->getById($purchaseOrder->getSnapshotQuote()->getAppliedRuleIds());
        $appliedRule->setDiscountAmount(1);
        $this->ruleRepository->save($appliedRule);
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_1 from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_1</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with disabled cart price rule without coupon
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_salesrule_without_coupon_applied.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithCartPriceRuleNoCouponDisable()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        //change applied rule discount rate
        $appliedRule = $this->ruleRepository->getById($purchaseOrder->getSnapshotQuote()->getAppliedRuleIds());
        $appliedRule->setIsActive(false);
        $this->ruleRepository->save($appliedRule);
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_1 from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_1</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with disabled cart price rule with coupon code
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_coupon_applied.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPOWithCartPriceRuleCouponDiscountAfterRuleDisable()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        //change applied rule discount rate
        $appliedRule = $this->ruleRepository->getById($purchaseOrder->getSnapshotQuote()->getAppliedRuleIds());
        $appliedRule->setIsActive(false);
        $this->ruleRepository->save($appliedRule);
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_1 from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_1</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with tax
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_tax.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithTax()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_with_virtual_product from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_with_virtual_product</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with disabled/removed tax rate
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_tax.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithTaxAfterTaxDisable()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();

        //remove tax rate
        $product = $this->productRepository->get('virtual-product');
        $taxRates = $this->taxRateManagement->getRatesByCustomerAndProductTaxClassId(
            $purchaseOrder->getSnapshotQuote()->getCustomerTaxClassId(),
            $product->getTaxClassId()
        );
        foreach ($taxRates as $taxRate) {
            $searchCriteria =  $this->searchCriteriaBuilder
                ->addFilter('tax_calculation_rate_id', $taxRate->getId())
                ->create();
            $taxRules = $this->taxRuleRepository->getList($searchCriteria);
            foreach ($taxRules->getItems() as $taxRule) {
                $this->taxRuleRepository->delete($taxRule);
            }
            $this->taxRateRepository->delete($taxRate);
        }
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_with_virtual_product from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_with_virtual_product</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with changed tax rate
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_tax.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithTaxChangingTaxRate()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();

        //remove tax rate
        $product = $this->productRepository->get('virtual-product');
        $taxRates = $this->taxRateManagement->getRatesByCustomerAndProductTaxClassId(
            $purchaseOrder->getSnapshotQuote()->getCustomerTaxClassId(),
            $product->getTaxClassId()
        );
        $taxRate = array_shift($taxRates);
        $taxRate->setRate(40);
        $this->taxRateRepository->save($taxRate);
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_with_virtual_product from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_with_virtual_product</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with applied catalog rule
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_catalogrule.php
     *
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithCatalogPriceRule()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->setCustomerId(null);
        $this->session->loginById($companyAdmin->getId());
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_with_virtual_product from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_with_virtual_product</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with removed catalog rule
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_catalogrule.php
     *
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithCatalogPriceRuleRemove()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->setCustomerId(null);
        $this->session->loginById($companyAdmin->getId());
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        //remove catalog rule
        $ruleCollection = $this->catalogRuleCollectionFactory->create();
        $ruleCollection->addFieldToFilter('name', ['eq' => 'Test Catalog Rule for logged user']);
        $ruleCollection->setPageSize(1);
        /** @var RuleInterface $catalogRule */
        $catalogRule = $ruleCollection->getFirstItem();
        $this->catalogRuleRepository->delete($catalogRule);
        $this->catalogRuleIndexBuilder->reindexFull();
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_with_virtual_product from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_with_virtual_product</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with disabled catalog price rule
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_catalogrule.php
     *
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithCatalogPriceRuleDisable()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->setCustomerId(null);
        $this->session->loginById($companyAdmin->getId());
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        //remove catalog rule
        $ruleCollection = $this->catalogRuleCollectionFactory->create();
        $ruleCollection->addFieldToFilter('name', ['eq' => 'Test Catalog Rule for logged user']);
        $ruleCollection->setPageSize(1);
        /** @var RuleInterface $catalogRule */
        $catalogRule = $ruleCollection->getFirstItem();
        $catalogRule->setIsActive(false);
        $this->catalogRuleRepository->save($catalogRule);
        $this->catalogRuleIndexBuilder->reindexFull();
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_with_virtual_product from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_with_virtual_product</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with catalog price rule changed rate
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_catalogrule.php
     *
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithCatalogPriceRuleChangeRate()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->setCustomerId(null);
        $this->session->loginById($companyAdmin->getId());
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        //remove catalog rule
        $ruleCollection = $this->catalogRuleCollectionFactory->create();
        $ruleCollection->addFieldToFilter('name', ['eq' => 'Test Catalog Rule for logged user']);
        $ruleCollection->setPageSize(1);
        /** @var RuleInterface $catalogRule */
        $catalogRule = $ruleCollection->getFirstItem();
        $catalogRule->setDiscountAmount(1);
        $this->catalogRuleRepository->save($catalogRule);
        $this->catalogRuleIndexBuilder->reindexFull();
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_with_virtual_product from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_with_virtual_product</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with changed shipping rate
     *
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_shipping_method.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithChangingShippingRate()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        //change rate value
        $this->configWriter->save('carriers/flatrate/price', 1);
        //change shipping rate
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_1 from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_1</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with disabled shipping method
     *
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_shipping_method.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithDisableShippingMethod()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        //change rate value
        $this->configWriter->save('carriers/flatrate/active', 0);
        //change shipping rate
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_1 from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_1</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with shipping changed handling fee
     *
     * @magentoConfigFixture current_store carriers/flatrate/active 1
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     * @magentoConfigFixture current_store carriers/flatrate/handling_fee 5.00
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_shipping_method.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithShippingChangingHandlingFee()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        //change rate value
        $this->configWriter->save('carriers/flatrate/handling_fee', 1);
        //change shipping rate
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_1 from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_1</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Verify a purchase place order totals with customer store credit = 0
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_customer_balance.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPurchaseOrderWithStoreCredit()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        //set customer balance to 0
        $customerBalance = $this->customerBalanceFactory->create()
            ->load($purchaseOrder->getSnapshotQuote()->getCustomer()->getId(), 'customer_id');
        $customerBalance->setAmount(0)->save();
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_FAILED, $postPurchaseOrder->getStatus());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_SUCCESS);
        $errorMessage = 'You do not have enough store credit to complete this order.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($errorMessage)]),
            MessageInterface::TYPE_ERROR
        );
        $this->session->logout();
    }

    /**
     * Verify a place order failed by payment on account payment method with not allowed credit limit and balance = 0
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_company_credit.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPOWithPaymentOnAccountNotAllowedToExceedCreditLimit()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        //set credit limit to 0
        $creditLimit = $this->creditLimitManagement->getCreditByCompanyId($purchaseOrder->getCompanyId());
        $creditLimit->setBalance(0);
        $this->creditLimitRepository->save($creditLimit);
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_FAILED, $postPurchaseOrder->getStatus());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_SUCCESS);
        $errorMessage = 'Payment On Account cannot be used for this order '
            . 'because your order amount exceeds your credit amount.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($errorMessage)]),
            MessageInterface::TYPE_ERROR
        );
        $this->session->logout();
    }

    /**
     * Verify place order totals ordered by payment on account payment method with exceed credit limit and balance = 0
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders_with_company_credit_with_credit_limit.php
     */
    public function testPlaceOrderActionAsCompanyAdminApprovedPOWithPaymentOnAccountAllowedToExceedCreditLimit()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->assertNull($purchaseOrder->getOrderId());
        $this->assertNull($purchaseOrder->getOrderIncrementId());
        $id = $purchaseOrder->getEntityId();
        //set credit limit to 0
        $creditLimit = $this->creditLimitManagement->getCreditByCompanyId($purchaseOrder->getCompanyId());
        $creditLimit->setBalance(0);
        $this->creditLimitRepository->save($creditLimit);
        $this->dispatch(self::URI . '/request_id/' . $id);
        // assert result
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($id);
        $this->assertEquals(PurchaseOrderInterface::STATUS_ORDER_PLACED, $postPurchaseOrder->getStatus());
        $this->assertNotNull($postPurchaseOrder->getOrderId());
        $this->assertNotNull($postPurchaseOrder->getOrderIncrementId());
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $successMessage = 'Successfully placed order #test_order_with_virtual_product from purchase order #'
            . $postPurchaseOrder->getIncrementId()
            . '.';
        $this->assertSessionMessages(
            $this->equalTo([(string)__($successMessage)]),
            MessageInterface::TYPE_SUCCESS
        );

        $order = $this->orderRepository->get($postPurchaseOrder->getOrderId());
        $this->assertEquals($order->getIncrementId(), $postPurchaseOrder->getOrderIncrementId());
        $this->assertEquals($order->getGrandTotal(), $purchaseOrder->getSnapshotQuote()->getGrandTotal());
        $this->session->logout();

        // Assert email notification
        /** @var TransportBuilderMock $transportBuilderMock */
        $transportBuilderMock = $this->objectManager->get(TransportBuilderMock::class);
        $sentMessage = $transportBuilderMock->getSentMessage();
        $this->assertStringContainsString('order confirmation', $sentMessage->getSubject());
        $this->assertStringContainsString(
            'Thank you for your order from ',
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
        $this->assertStringContainsString(
            "Your Order <span class=\"no-link\">#test_order_with_virtual_product</span>",
            $sentMessage->getBody()->getParts()[0]->getRawContent()
        );
    }

    /**
     * Get purchase order for the given customer.
     *
     * @param string $customerEmail
     * @return PurchaseOrderInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
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
