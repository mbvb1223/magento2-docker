<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\Api;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class NegotiableQuoteRepositoryInterfaceTest.
 * @package Magento\NegotiableQuote\Api
 */
class NegotiableQuoteRepositoryInterfaceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NegotiableQuoteRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        $this->repository = Bootstrap::getObjectManager()->create(NegotiableQuoteRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture Magento/NegotiableQuote/_files/negotiable_quotes_for_search.php
     */
    public function testGetList()
    {
        /** @var FilterBuilder $filterBuilder */
        $filterBuilder = Bootstrap::getObjectManager()->create(FilterBuilder::class);

        $filter1 = $filterBuilder->setField(NegotiableQuoteInterface::QUOTE_NAME)
            ->setValue('quote 2')
            ->create();
        $filter2 = $filterBuilder->setField(NegotiableQuoteInterface::QUOTE_NAME)
            ->setValue('quote 3')
            ->create();
        $filter3 = $filterBuilder->setField(NegotiableQuoteInterface::QUOTE_NAME)
            ->setValue('quote 4')
            ->create();
        $filter4 = $filterBuilder->setField(NegotiableQuoteInterface::QUOTE_NAME)
            ->setValue('quote 5')
            ->create();
        $filter5 = $filterBuilder->setField(NegotiableQuoteInterface::IS_REGULAR_QUOTE)
            ->setValue(1)
            ->create();

        /**@var SortOrderBuilder $sortOrderBuilder */
        $sortOrderBuilder = Bootstrap::getObjectManager()->create(SortOrderBuilder::class);

        /** @var SortOrder $sortOrder */
        $sortOrder = $sortOrderBuilder->setField(NegotiableQuoteInterface::SNAPSHOT)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = Bootstrap::getObjectManager()->create(SearchCriteriaBuilder::class);

        $searchCriteriaBuilder->addFilters([$filter1, $filter2, $filter3, $filter4]);
        $searchCriteriaBuilder->addFilters([$filter5]);
        $searchCriteriaBuilder->setSortOrders([$sortOrder]);

        $searchCriteriaBuilder->setPageSize(2);
        $searchCriteriaBuilder->setCurrentPage(2);

        $searchCriteria = $searchCriteriaBuilder->create();

        $searchResult = $this->repository->getList($searchCriteria);

        $this->assertEquals(3, $searchResult->getTotalCount());
        /** @var Quote[] $items */
        $items = array_values($searchResult->getItems());
        $this->assertCount(1, $items);
        $this->assertEquals('quote 3', $items[0]->getExtensionAttributes()->getNegotiableQuote()->getQuoteName());
    }
}
