<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Service\V1;

use Magento\Framework\App\Config;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;

/**
 * Tests for negotiable quote actions (create, send and decline).
 *
 * @magentoAppIsolation enabled
 */
class NegotiableQuoteManagementTest extends WebapiAbstract
{
    const SERVICE_READ_NAME = 'negotiableQuoteNegotiableQuoteManagementV1';

    const SERVICE_VERSION = 'V1';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    private $quoteManager;

    /**
     * @var \Magento\Quote\Api\CartItemRepositoryInterface
     */
    private $cartItemRepository;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface
     */
    private $negotiableRepository;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var int
     */
    private $quoteId;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->customerRepository = $this->objectManager->get(
            \Magento\Customer\Api\CustomerRepositoryInterface::class
        );
        $this->quoteManager = $this->objectManager->get(\Magento\Quote\Api\CartManagementInterface::class);
        $this->cartItemRepository = $this->objectManager->get(\Magento\Quote\Api\CartItemRepositoryInterface::class);
        $this->negotiableRepository = $this->objectManager->get(
            \Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface::class
        );
        $this->quoteRepository = $this->objectManager->get(\Magento\Quote\Api\CartRepositoryInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        try {
            $quote = $this->quoteRepository->get($this->quoteId);
            $this->quoteRepository->delete($quote);
        } catch (\InvalidArgumentException $e) {
            // Do nothing if cart fixture was not used
        }
        parent::tearDown();
    }

    /**
     * Create quote for customer and request negotiable guote.
     *
     * @return void
     * @magentoApiDataFixture Magento/NegotiableQuote/_files/company_with_customer_for_quote.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture default_store btob/website_configuration/negotiablequote_active true
     */
    public function testRequestQuote()
    {
        $this->quoteId = $this->createQuoteForCustomer();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/negotiableQuote/request',
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'create',
            ],
        ];
        $quoteName = 'new quote';
        $result = $this->_webApiCall($serviceInfo, ['quoteId' => $this->quoteId, 'quoteName' => $quoteName]);
        $negotiableQuote = $this->negotiableRepository->getById($this->quoteId);

        $this->assertTrue($result, 'Negotiable quote isn\'t created');
        $this->assertEquals($negotiableQuote->getQuoteId(), $this->quoteId, 'Negotiable quote isn\'t created');
        $this->assertEquals($negotiableQuote->getQuoteName(), $quoteName, 'Negotiable quote has incorrect name');
        $this->assertEquals(
            $negotiableQuote->getStatus(),
            NegotiableQuoteInterface::STATUS_CREATED,
            'Negotiable quote has incorrect status'
        );
    }

    /**
     * Create and retrieve quote for customer for test.
     *
     * @return int
     */
    private function createQuoteForCustomer()
    {
        $customer = $this->customerRepository->get('email@companyquote.com');
        $this->quoteId = $this->quoteManager->createEmptyCartForCustomer($customer->getId());
        /** @var \Magento\Quote\Api\Data\CartItemInterface $item */
        $item = $this->objectManager->get(\Magento\Quote\Api\Data\CartItemInterface::class);
        $item->setQuoteId($this->quoteId);
        $item->setSku('simple');
        $item->setQty(1);
        $this->cartItemRepository->save($item);

        return $this->quoteId;
    }

    /**
     * Decline quote for customer.
     *
     * @return void
     * @magentoApiDataFixture Magento/NegotiableQuote/_files/company_with_customer_for_quote.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoApiDataFixture Magento/NegotiableQuote/_files/negotiable_quote.php
     * @magentoConfigFixture default_store btob/website_configuration/negotiablequote_active true
     */
    public function testDecline()
    {
        $customer = $this->customerRepository->get('email@companyquote.com');
        $quotes = $this->negotiableRepository->getListByCustomerId($customer->getId());
        $this->quoteId = end($quotes)->getId();
        $negotiableQuote = $this->negotiableRepository->getById($this->quoteId);
        $negotiableQuote->setStatus(NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN);
        $this->negotiableRepository->save($negotiableQuote);
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/negotiableQuote/decline',
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'decline',
            ],
        ];
        $result = $this->_webApiCall($serviceInfo, ['quoteId' => $this->quoteId, 'reason' => 'decline']);

        $negotiableQuote = $this->negotiableRepository->getById($this->quoteId);

        $this->assertTrue($result, 'Negotiable quote isn\'t decline');
        $this->assertEmpty($negotiableQuote->getNegotiatedPriceType(), 'Negotiable quote has incorrect price type');
        $this->assertEmpty($negotiableQuote->getNegotiatedPriceValue(), 'Negotiable quote has incorrect price value');
        $this->assertEquals(
            $negotiableQuote->getStatus(),
            NegotiableQuoteInterface::STATUS_DECLINED,
            'Negotiable quote has incorrect status'
        );
    }

    /**
     * Send quote from merchant to customer.
     *
     * @return void
     * @magentoApiDataFixture Magento/NegotiableQuote/_files/company_with_customer_for_quote.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoApiDataFixture Magento/NegotiableQuote/_files/negotiable_quote.php
     * @magentoConfigFixture default_store btob/website_configuration/negotiablequote_active true
     */
    public function testSubmitToCustomer()
    {
        $customer = $this->customerRepository->get('email@companyquote.com');
        $quotes = $this->negotiableRepository->getListByCustomerId($customer->getId());
        $this->quoteId = end($quotes)->getId();
        $negotiableQuote = $this->negotiableRepository->getById($this->quoteId);
        $priceType = $negotiableQuote->getNegotiatedPriceType();
        $priceValue = $negotiableQuote->getNegotiatedPriceValue();
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/negotiableQuote/submitToCustomer',
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'adminSend',
            ],
        ];

        $result = $this->_webApiCall($serviceInfo, ['quoteId' => $this->quoteId, 'comment' => 'decline']);

        $negotiableQuote = $this->negotiableRepository->getById($this->quoteId);

        $this->assertTrue($result, 'Negotiable quote isn\'t decline');
        $this->assertEquals(
            $priceType,
            $negotiableQuote->getNegotiatedPriceType(),
            'Negotiable quote has incorrect price type'
        );
        $this->assertEquals(
            $priceValue,
            $negotiableQuote->getNegotiatedPriceValue(),
            'Negotiable quote has incorrect price value'
        );
        $this->assertEquals(
            $negotiableQuote->getStatus(),
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
            'Negotiable quote has incorrect status'
        );
    }
}
