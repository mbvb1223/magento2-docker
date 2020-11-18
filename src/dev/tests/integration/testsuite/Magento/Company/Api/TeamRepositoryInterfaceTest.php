<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Api;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Company\Api\Data\TeamInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class TeamRepositoryInterfaceTest.
 * @package Magento\Company\Api
 */
class TeamRepositoryInterfaceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TeamRepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        $this->repository = Bootstrap::getObjectManager()->create(TeamRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Company/_files/teams_for_search.php
     */
    public function testGetList()
    {
        /** @var FilterBuilder $filterBuilder */
        $filterBuilder = Bootstrap::getObjectManager()->create(FilterBuilder::class);

        $filter1 = $filterBuilder->setField(TeamInterface::NAME)
            ->setValue('team 2')
            ->create();
        $filter2 = $filterBuilder->setField(TeamInterface::NAME)
            ->setValue('team 3')
            ->create();
        $filter3 = $filterBuilder->setField(TeamInterface::NAME)
            ->setValue('team 4')
            ->create();
        $filter4 = $filterBuilder->setField(TeamInterface::NAME)
            ->setValue('team 5')
            ->create();
        $filter5 = $filterBuilder->setField(TeamInterface::DESCRIPTION)
            ->setValue('description 1')
            ->create();
        $filter6 = $filterBuilder->setField(TeamInterface::DESCRIPTION)
            ->setValue('description 2')
            ->create();
        $filter7 = $filterBuilder->setField(TeamInterface::DESCRIPTION)
            ->setValue('description 4')
            ->create();
        $filter8 = $filterBuilder->setField(TeamInterface::DESCRIPTION)
            ->setValue('description 5')
            ->create();

        /**@var SortOrderBuilder $sortOrderBuilder */
        $sortOrderBuilder = Bootstrap::getObjectManager()->create(SortOrderBuilder::class);

        /** @var SortOrder $sortOrder */
        $sortOrder = $sortOrderBuilder->setField(TeamInterface::DESCRIPTION)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = Bootstrap::getObjectManager()->create(SearchCriteriaBuilder::class);

        $searchCriteriaBuilder->addFilters([$filter1, $filter2, $filter3, $filter4]);
        $searchCriteriaBuilder->addFilters([$filter5, $filter6, $filter7, $filter8]);
        $searchCriteriaBuilder->setSortOrders([$sortOrder]);

        $searchCriteriaBuilder->setPageSize(2);
        $searchCriteriaBuilder->setCurrentPage(2);

        $searchCriteria = $searchCriteriaBuilder->create();

        $searchResult = $this->repository->getList($searchCriteria);

        $this->assertEquals(3, $searchResult->getTotalCount());
        $items = array_values($searchResult->getItems());
        $this->assertCount(1, $items);
        $this->assertEquals('team 4', $items[0][TeamInterface::NAME]);
    }
}
