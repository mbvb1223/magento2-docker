<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Controller\PurchaseOrder;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\HistoryManagementInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteRepository;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\Comment;
use Magento\PurchaseOrder\Model\CommentManagement;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\Framework\Message\MessageInterface;

/**
 * Controller test class for cancelling purchase order..
 *
 * @see \Magento\PurchaseOrder\Controller\PurchaseOrder\Cancel
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class CancelTest extends AbstractController
{
    /**
     * Url to dispatch.
     */
    private const URI = 'purchaseorder/purchaseorder/cancel';

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
     * @var NegotiableQuoteRepository
     */
    private $negotiableQuoteRepository;

    /**
     * @var HistoryManagementInterface
     */
    private $negotiableQuoteHistory;

    /**
     * @var CommentManagement
     */
    private $commentManagement;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        $this->customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
        $this->purchaseOrderRepository = $objectManager->get(PurchaseOrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->session = $objectManager->get(Session::class);
        $this->negotiableQuoteRepository = $objectManager->get(NegotiableQuoteRepository::class);
        $this->negotiableQuoteHistory = $objectManager->get(HistoryManagementInterface::class);
        $this->commentManagement = $objectManager->get(CommentManagement::class);

        // Enable company functionality at the system level
        $scopeConfig = $objectManager->get(MutableScopeConfigInterface::class);
        $scopeConfig->setValue('btob/website_configuration/company_active', '1', ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     */
    public function testCancelActionGetRequest()
    {
        $purchaseOrder = $this->getPurchaseOrderForCustomer('alex.smith@example.com');
        $this->assertEquals(PurchaseOrderInterface::STATUS_PENDING, $purchaseOrder->getStatus());

        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());
        $this->assert404NotFound();
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrder->getEntityId());
        $this->assertEquals(PurchaseOrderInterface::STATUS_PENDING, $postPurchaseOrder->getStatus());
    }

    /**
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     */
    public function testCancelActionAsGuestUser()
    {
        $purchaseOrder = $this->getPurchaseOrderForCustomer('alex.smith@example.com');
        $this->assertEquals(PurchaseOrderInterface::STATUS_PENDING, $purchaseOrder->getStatus());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());

        $this->assertEquals(302, $this->getResponse()->getHttpResponseCode());
        $this->assertRedirect($this->stringContains('customer/account/login'));
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrder->getEntityId());
        $this->assertEquals(PurchaseOrderInterface::STATUS_PENDING, $postPurchaseOrder->getStatus());
    }

    /**
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testCancelActionAsNonCompanyUser()
    {
        $nonCompanyUser = $this->customerRepository->get('customer@example.com');
        $this->session->loginById($nonCompanyUser->getId());

        $purchaseOrder = $this->getPurchaseOrderForCustomer('alex.smith@example.com');
        $this->assertEquals(PurchaseOrderInterface::STATUS_PENDING, $purchaseOrder->getStatus());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());

        $this->assertEquals(302, $this->getResponse()->getHttpResponseCode());
        $this->assertRedirect($this->stringContains('noroute'));
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrder->getEntityId());
        $this->assertEquals(PurchaseOrderInterface::STATUS_PENDING, $postPurchaseOrder->getStatus());

        $this->session->logout();
    }

    /**
     * Test purchase order cancellation as company user.
     *
     * @param $currentUserEmail
     * @param $createdByUserEmail
     * @param $expectedHttpResponseCode
     * @param $expectedRedirect
     * @param string $expectedStatus
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     * @dataProvider cancelActionAsCompanyUserDataProvider
     */
    public function testCancelActionAsCompanyUser(
        $currentUserEmail,
        $createdByUserEmail,
        $expectedHttpResponseCode,
        $expectedRedirect,
        $expectedStatus = PurchaseOrderInterface::STATUS_PENDING
    ) {
        // Log in as the current user
        $currentUser = $this->customerRepository->get($currentUserEmail);
        $this->session->loginById($currentUser->getId());

        // Get purchase order for current customer
        $purchaseOrder = $this->getPurchaseOrderForCustomer($createdByUserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_PENDING);
        $this->purchaseOrderRepository->save($purchaseOrder);

        // Verify purchase order status
        $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrder->getEntityId());
        $this->assertEquals(PurchaseOrderInterface::STATUS_PENDING, $purchaseOrder->getStatus());

        // Dispatch the request to cancel purchase order
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());

        // Perform assertions
        $this->assertEquals($expectedHttpResponseCode, $this->getResponse()->getHttpResponseCode());
        $this->assertRedirect($this->stringContains($expectedRedirect));
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrder->getEntityId());
        $this->assertEquals($expectedStatus, $postPurchaseOrder->getStatus());

        $this->session->logout();
    }

    /**
     * Data provider for various reject action scenarios for company users.
     *
     * @return array
     */
    public function cancelActionAsCompanyUserDataProvider()
    {
        return [
            'cancel_my_purchase_order' => [
                'current_customer' => 'veronica.costello@example.com',
                'created_by_customer' => 'veronica.costello@example.com',
                'expected_http_response_code' => 302,
                'expected_redirect' => '',
                'expected_status' => PurchaseOrderInterface::STATUS_CANCELED
            ],
            'cancel_subordinate_purchase_order' => [
                'current_customer' => 'veronica.costello@example.com',
                'created_by_customer' => 'alex.smith@example.com',
                'expected_http_response_code' => 302,
                'expected_redirect' => '',
                'expected_status' => PurchaseOrderInterface::STATUS_CANCELED
            ],
            'cancel_superior_purchase_order' => [
                'current_customer' => 'veronica.costello@example.com',
                'created_by_customer' => 'john.doe@example.com',
                'expected_http_response_code' => 302,
                'expected_redirect' => 'company/accessdenied'
            ]
        ];
    }

    /**
     * Test cancellation by company admin of purchase order belonging to another company.
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     * @magentoDataFixture Magento/Company/_files/company_with_admin.php
     */
    public function testCancelActionAsOtherCompanyAdmin()
    {
        $nonCompanyUser = $this->customerRepository->get('company-admin@example.com');
        $this->session->loginById($nonCompanyUser->getId());
        $purchaseOrder = $this->getPurchaseOrderForCustomer('alex.smith@example.com');
        $this->assertEquals(PurchaseOrderInterface::STATUS_PENDING, $purchaseOrder->getStatus());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());

        // Perform assertions
        $this->assertEquals(302, $this->getResponse()->getHttpResponseCode());
        $this->assertRedirect($this->stringContains('company/accessdenied'));
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrder->getEntityId());
        $this->assertEquals(PurchaseOrderInterface::STATUS_PENDING, $postPurchaseOrder->getStatus());

        $this->session->logout();
    }

    /**
     * Test cancellation of unexistent purchase order.
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders.php
     */
    public function testCancelActionNonexistingPurchaseOrder()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->dispatch(self::URI . '/request_id/5000');
        $this->assertRedirect($this->stringContains('company/accessdenied'));

        $this->session->logout();
    }

    /**
     * Test cancellation of purchase order in status that does not allow cancellation.
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders.php
     * @dataProvider nonCancellablePurchaseOrderStatusDataProvider
     * @param string $finalStatus
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testCancelAsCompanyAdminNonCancellablePurchaseOrder($finalStatus)
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $purchaseOrder = $this->getPurchaseOrderForCustomer('customer@example.com');
        $purchaseOrder->setStatus($finalStatus);
        $this->purchaseOrderRepository->save($purchaseOrder);

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());

        $message = 'Purchase order ' . $purchaseOrder->getIncrementId() . ' cannot be canceled.';
        $this->assertSessionMessages($this->equalTo([(string)__($message)]), MessageInterface::TYPE_ERROR);
        $this->session->logout();
    }

    /**
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_order_using_negotiable_quote.php
     */
    public function testCancelPurchaseOrderCreatedFromNegotiableQuote()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $purchaseOrder = $this->getPurchaseOrderForCustomer('customer@example.com');
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVAL_REQUIRED);
        $this->purchaseOrderRepository->save($purchaseOrder);

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrder->getEntityId());
        $this->assertEquals(PurchaseOrderInterface::STATUS_CANCELED, $postPurchaseOrder->getStatus());
        // fetching negotiable quote (has same ID as the regular quote attached to Purchase Order)
        $negotiableQuote = $this->negotiableQuoteRepository->getById($purchaseOrder->getQuoteId());
        $this->assertEquals(NegotiableQuoteInterface::STATUS_CLOSED, $negotiableQuote->getStatus());
        $quoteHistory = $this->negotiableQuoteHistory->getQuoteHistory($purchaseOrder->getQuoteId());
        /** @var ExtensibleDataInterface $logEntry */
        $logEntry = array_shift($quoteHistory);
        $logEntryData = json_decode($logEntry->getLogData(), true);
        $this->assertEquals(NegotiableQuoteInterface::STATUS_CLOSED, $logEntryData['status']['new_value']);
        $this->assertEquals(0, $logEntry->getAuthorId());
        $this->session->logout();
    }

    /**
     * Data provider of purchase order statuses that do not allow cancellation.
     *
     * @return string[]
     */
    public function nonCancellablePurchaseOrderStatusDataProvider()
    {
        return [
            [PurchaseOrderInterface::STATUS_CANCELED],
            [PurchaseOrderInterface::STATUS_APPROVED],
            [PurchaseOrderInterface::STATUS_REJECTED],
            [PurchaseOrderInterface::STATUS_ORDER_IN_PROGRESS],
            [PurchaseOrderInterface::STATUS_ORDER_PLACED],
        ];
    }

    /**
     * Verify a company admin cancelling a purchase with a comment
     *
     * @magentoDataFixture Magento/PurchaseOrder/_files/purchase_orders.php
     */
    public function testCancelActionAsCompanyAdminWithCommentPurchaseOrder()
    {
        $companyAdmin = $this->customerRepository->get('admin@magento.com');
        $this->session->loginById($companyAdmin->getId());

        $purchaserEmail = 'customer@example.com';
        $purchaseOrder = $this->getPurchaseOrderForCustomer($purchaserEmail);
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVAL_REQUIRED);
        $this->purchaseOrderRepository->save($purchaseOrder);

        // Cancel the purchase order
        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->getRequest()->setParams([
            'comment' => 'A cancellation comment'
        ]);
        $this->dispatch(self::URI . '/request_id/' . $purchaseOrder->getEntityId());

        // Assert the Purchase Order is now cancelled
        $postPurchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrder->getEntityId());
        $this->assertEquals(PurchaseOrderInterface::STATUS_CANCELED, $postPurchaseOrder->getStatus());

        // Verify the comment was added to the Purchase Order
        $comments = $this->commentManagement->getPurchaseOrderComments($purchaseOrder->getEntityId());
        $this->assertEquals(1, $comments->getSize());
        /** @var Comment $comment */
        $comment = $comments->getFirstItem();
        $this->assertEquals('A cancellation comment', $comment->getComment());
        $this->assertEquals($companyAdmin->getId(), $comment->getCreatorId());

        $this->session->logout();
    }

    /**
     * Get first purchase order by creator email.
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
