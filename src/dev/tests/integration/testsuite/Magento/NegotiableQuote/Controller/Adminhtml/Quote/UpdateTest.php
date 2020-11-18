<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\NegotiableQuote\Controller\Adminhtml\Quote;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\QuoteUpdatesInfo;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Tests quote update.
 *
 * @magentoAppArea adminhtml
 */
class UpdateTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @magentoConfigFixture default_store btob/website_configuration/negotiablequote_active true
     * @magentoConfigFixture default_store btob/website_configuration/company_active true
     * @magentoDataFixture Magento/NegotiableQuote/_files/two_simple_products_for_quote.php
     * @magentoDataFixture Magento/NegotiableQuote/_files/company_with_customer_for_quote.php
     * @magentoDataFixture Magento/NegotiableQuote/_files/negotiable_quote.php
     *
     * @return void
     */
    public function testUpdateQuote(): void
    {
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->_objectManager->create(CustomerRepositoryInterface::class);
        /** @var NegotiableQuoteRepositoryInterface $negotiableRepository */
        $negotiableRepository = $this->_objectManager->get(NegotiableQuoteRepositoryInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->_objectManager->create(ProductRepositoryInterface::class);

        $customer = $customerRepository->get('email@companyquote.com');
        $quotes = $negotiableRepository->getListByCustomerId($customer->getId());

        $quoteId = end($quotes)->getId();

        $postData = [
            'quote_id' => $quoteId,
            'quote' => [
                'items' => [
                    0 => [
                        'id' => $productRepository->get('simple')->getId(),
                        'qty' => '1',
                        'sku' => 'simple',
                        'productSku' => 'simple',
                        'config' => '',
                    ],
                ],
                'addItems' => [
                    0 => [
                        'qty' => '1',
                        'sku' => 'simple_for_quote',
                    ],
                ],
                'update' => 1,
                'recalcPrice' => 1,
            ],
        ];

        $this->getRequest()->setPostValue($postData)->setMethod('POST');
        $this->dispatch('backend/quotes/quote/update/?isAjax=true');

        /** @var CartRepositoryInterface $quoteRepository */
        $quoteRepository = $this->_objectManager->get(CartRepositoryInterface::class);
        $quote = $quoteRepository->get($quoteId);
        /** @var  QuoteUpdatesInfo $quoteInfo */
        $quoteInfo = $this->_objectManager->create(QuoteUpdatesInfo::class);
        $updatedData = $quoteInfo->getQuoteUpdatedData($quote, $postData);

        foreach ($updatedData['items'] as $item) {
            $this->assertUpdatedItemsData($item);
        }
    }

    /**
     * Assert updated quote items data.
     *
     * @param $item
     * @return void
     */
    private function assertUpdatedItemsData($item): void
    {
        if ($item['sku'] === 'simple_for_quote') {
            $this->assertEquals('$16.00', $item['subtotal']);
            $this->assertEquals('$20.00', $item['cartPrice']);
            $this->assertEquals('$20.00', $item['originalPrice']);
            $this->assertEquals('$16.00', $item['proposedPrice']);
        } elseif ($item['sku'] === 'simple') {
            $this->assertEquals('$8.00', $item['subtotal']);
            $this->assertEquals('$10.00', $item['cartPrice']);
            $this->assertEquals('$10.00', $item['originalPrice']);
            $this->assertEquals('$8.00', $item['proposedPrice']);
        }
    }
}
