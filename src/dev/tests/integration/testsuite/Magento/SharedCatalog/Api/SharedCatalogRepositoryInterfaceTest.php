<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Api;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\TestFramework\Helper\Bootstrap;

class SharedCatalogRepositoryInterfaceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var SharedCatalogRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->repository = $this->objectManager->create(SharedCatalogRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture Magento/SharedCatalog/_files/catalogs_for_search.php
     */
    public function testGetList()
    {
        /** @var FilterBuilder $filterBuilder */
        $filterBuilder = $this->objectManager->create(FilterBuilder::class);

        $filter1 = $filterBuilder->setField(SharedCatalogInterface::NAME)
            ->setValue('catalog 2')
            ->create();
        $filter2 = $filterBuilder->setField(SharedCatalogInterface::NAME)
            ->setValue('catalog 3')
            ->create();
        $filter3 = $filterBuilder->setField(SharedCatalogInterface::NAME)
            ->setValue('catalog 4')
            ->create();
        $filter4 = $filterBuilder->setField(SharedCatalogInterface::NAME)
            ->setValue('catalog 5')
            ->create();
        $filter5 = $filterBuilder->setField(SharedCatalogInterface::CUSTOMER_GROUP_ID)
            ->setValue(1)
            ->create();

        /**@var SortOrderBuilder $sortOrderBuilder */
        $sortOrderBuilder = $this->objectManager->create(SortOrderBuilder::class);

        /** @var SortOrder $sortOrder */
        $sortOrder = $sortOrderBuilder->setField(SharedCatalogInterface::DESCRIPTION)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);

        $searchCriteriaBuilder->addFilters([$filter1, $filter2, $filter3, $filter4]);
        $searchCriteriaBuilder->addFilters([$filter5]);
        $searchCriteriaBuilder->setSortOrders([$sortOrder]);

        $searchCriteriaBuilder->setPageSize(2);
        $searchCriteriaBuilder->setCurrentPage(2);

        $searchCriteria = $searchCriteriaBuilder->create();

        $searchResult = $this->repository->getList($searchCriteria);

        $this->assertEquals(3, $searchResult->getTotalCount());
        $items = array_values($searchResult->getItems());
        $this->assertCount(1, $items);
        $this->assertEquals('catalog 4', $items[0][SharedCatalogInterface::NAME]);
    }
}
