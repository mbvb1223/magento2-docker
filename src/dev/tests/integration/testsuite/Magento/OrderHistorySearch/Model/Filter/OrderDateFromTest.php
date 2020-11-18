<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model\Filter;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DB\Select;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

/**
 * OrderDate 'From' Filter for Order History Search Test
 *
 * @see \Magento\OrderHistorySearch\Model\Filter\OrderDateFrom
 *
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderDateFromTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var OrderDateFrom
     */
    private $orderDateFromFilter;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        /** @var ObjectManager $objectManager */
        $this->objectManager = Bootstrap::getObjectManager();
        $this->orderDateFromFilter = $this->objectManager->get(OrderDateFrom::class);
        $this->orderRepository = $this->objectManager->get(OrderRepository::class);
    }

    /**
     * Test that applying a order date 'from' filter returns the expected orders from the search results.
     *
     * @magentoConfigFixture default_store general/locale/code en_US
     * @magentoConfigFixture default_store general/locale/timezone UTC
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     * @dataProvider applyFilterDataProvider
     * @param string $orderDateFrom
     * @param string[] $expectedOrderIds
     * @throws \Exception
     */
    public function testApplyFilter($orderDateFrom, $expectedOrderIds)
    {
        $this->setCreateDateForOrdersFromFixture();

        /** @var OrderCollection $orderCollection */
        $orderCollection = $this->objectManager->create(OrderCollection::class);

        $this->orderDateFromFilter->applyFilter(
            $orderCollection,
            $orderDateFrom
        );

        $actualOrderIds = array_column($orderCollection->load()->toArray()['items'], 'increment_id');
        sort($expectedOrderIds, SORT_NUMERIC);
        sort($actualOrderIds, SORT_NUMERIC);

        $this->assertEquals($expectedOrderIds, $actualOrderIds);
    }

    /**
     * Test that the date supplied to the orderDateFrom filter is correctly parsed for the en_US locale - MM/DD/YYY
     *
     * @magentoConfigFixture default_store general/locale/code en_US
     * @magentoConfigFixture default_store general/locale/timezone UTC
     */
    public function testApplyFilterParsesDateForLocaleUnitedStates()
    {
        $orderCollection = $this->objectManager->create(OrderCollection::class);

        $this->orderDateFromFilter->applyFilter(
            $orderCollection,
            '01/31/2020'
        );

        $this->assertFilterHasExpectedTimestamp($orderCollection, '2020-01-31 00:00:00');
    }

    /**
     * Test that the date supplied to the orderDateFrom filter is correctly parsed for the en_GB locale - DD/MM/YYYY
     *
     * @magentoConfigFixture default_store general/locale/code en_GB
     * @magentoConfigFixture default_store general/locale/timezone UTC
     */
    public function testApplyFilterParsesDateForLocaleUnitedKingdom()
    {
        $orderCollection = $this->objectManager->create(OrderCollection::class);

        $this->orderDateFromFilter->applyFilter(
            $orderCollection,
            '31/01/2020'
        );

        $this->assertFilterHasExpectedTimestamp($orderCollection, '2020-01-31 00:00:00');
    }

    /**
     * Test that the date supplied to the orderDateFrom filter is correctly parsed for the zh_Hans locale - YYYY-MM-DD
     *
     * @magentoConfigFixture default_store general/locale/code zh_Hans
     * @magentoConfigFixture default_store general/locale/timezone UTC
     */
    public function testApplyFilterParsesDateForLocaleChina()
    {
        $orderCollection = $this->objectManager->create(OrderCollection::class);

        $this->orderDateFromFilter->applyFilter(
            $orderCollection,
            '2020-01-31'
        );

        $this->assertFilterHasExpectedTimestamp($orderCollection, '2020-01-31 00:00:00');
    }

    /**
     * Assert that the order collection is filtered by the expected timestamp.
     *
     * @param OrderCollection $orderCollection
     * @param string $expectedTimeStamp
     * @throws \Zend_Db_Select_Exception
     */
    private function assertFilterHasExpectedTimestamp(OrderCollection $orderCollection, string $expectedTimeStamp)
    {
        $whereClauseConditions = $orderCollection
            ->getSelect()
            ->getPart(Select::WHERE);

        $this->assertCount(1, $whereClauseConditions);
        $this->assertStringContainsString($expectedTimeStamp, $whereClauseConditions[0]);
    }

    /**
     * Explicitly set the createdAt date for the orders created by the fixture.
     *
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function setCreateDateForOrdersFromFixture()
    {
        $orderCreateDates = [
            '100000001' => '01/01/2020 00:00:00',
            '100000002' => '01/31/2020 00:00:00',
            '100000003' => '01/31/2020 11:59:59',
            '100000004' => '2/01/2020 00:00:00'
        ];

        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter(
            OrderInterface::INCREMENT_ID,
            array_keys($orderCreateDates),
            'in'
        )->create();

        $fixtureOrders = $this->orderRepository->getList($searchCriteria)->getItems();

        foreach ($fixtureOrders as $order) {
            $createDate = $orderCreateDates[$order->getIncrementId()];
            $order->setCreatedAt($createDate);
            $this->orderRepository->save($order);
        }
    }

    /**
     * Data provider for testApplyFilter.
     */
    public function applyFilterDataProvider()
    {
        return [
            ['12/31/2019', ['100000001','100000002','100000003','100000004']],
            ['01/31/2020', ['100000002','100000003','100000004']],
            ['02/01/2020', ['100000004']],
            ['02/02/2020', []]
        ];
    }
}
