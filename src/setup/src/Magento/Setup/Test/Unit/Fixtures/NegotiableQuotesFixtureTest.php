<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Setup\Test\Unit\Fixtures;

/**
 * Test Magento\Setup\Fixtures\NegotiableQuotesFixture class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NegotiableQuotesFixtureTest extends \PHPUnit\Framework\TestCase
{
    const QUOTE_ID = 2;

    const COMPANY_ID = 11;

    const CUSTOMER_ID = 1;

    /**
     * @var \Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuote|\PHPUnit\Framework\MockObject\MockObject
     */
    private $negotiableQuoteResource;

    /**
     * @var \Magento\NegotiableQuote\Model\NegotiableQuoteFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $negotiableQuoteFactory;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Collection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteCollectionFactory;

    /**
     * @var \Magento\Company\Model\ResourceModel\Company\Collection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $companyCollectionFactory;

    /**
     * @var \Magento\Company\Api\CompanyManagementInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $companyManagement;

    /**
     * @var \Magento\Setup\Fixtures\Quote\NegotiableQuoteConfigurationFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteConfigFactory;

    /**
     * @var \Magento\Setup\Fixtures\Quote\QuoteGeneratorFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteGeneratorFactory;

    /**
     * @var \Magento\NegotiableQuote\Api\Data\HistoryInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $historyLogFactory;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serializer;

    /**
     * @var \Magento\Setup\Fixtures\FixtureModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fixtureModel;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resources;

    /**
     * @var \Magento\Setup\Fixtures\NegotiableQuotesFixture
     */
    private $fixture;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->negotiableQuoteResource = $this->getMockBuilder(
            \Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuote::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getTable', 'saveNegotiatedQuoteData'])
            ->getMock();
        $this->negotiableQuoteFactory = $this->getMockBuilder(
            \Magento\NegotiableQuote\Model\NegotiableQuoteFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->quoteCollectionFactory = $this->getMockBuilder(
            \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyCollectionFactory = $this->getMockBuilder(
            \Magento\Company\Model\ResourceModel\Company\CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->companyManagement = $this
            ->getMockBuilder(\Magento\Company\Api\CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteConfigFactory = $this->getMockBuilder(
            \Magento\Setup\Fixtures\Quote\NegotiableQuoteConfigurationFactory::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteGeneratorFactory = $this->getMockBuilder(
            \Magento\Setup\Fixtures\Quote\QuoteGeneratorFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create', 'generateQuotes'])
            ->getMock();
        $this->historyLogFactory = $this->getMockBuilder(
            \Magento\NegotiableQuote\Api\Data\HistoryInterfaceFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();
        $this->serializer = $this->getMockBuilder(\Magento\Framework\Serialize\SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->fixtureModel = $this->getMockBuilder(\Magento\Setup\Fixtures\FixtureModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getObjectManager', 'getValue'])
            ->getMock();
        $this->resources = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'getTableName'])
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->fixture = $objectManagerHelper->getObject(
            \Magento\Setup\Fixtures\NegotiableQuotesFixture::class,
            [
                'negotiableQuoteResource' => $this->negotiableQuoteResource,
                'negotiableQuoteFactory' => $this->negotiableQuoteFactory,
                'quoteCollectionFactory' => $this->quoteCollectionFactory,
                'companyCollectionFactory' => $this->companyCollectionFactory,
                'companyManagement' => $this->companyManagement,
                'quoteConfigurationFactory' => $this->quoteConfigFactory,
                'quoteGeneratorFactory' => $this->quoteGeneratorFactory,
                'historyLogFactory' => $this->historyLogFactory,
                'serializer' => $this->serializer,
                'fixtureModel' => $this->fixtureModel,
                'resources' => $this->resources,
            ]
        );
    }

    /**
     * Test execute method.
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return void
     */
    public function testExecute()
    {
        $connection = $this->mockConnection();
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['from', 'where', 'columns', 'insertFromSelect'])
            ->getMock();

        $this->resources->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($connection);

        $statement = $this->getMockBuilder(\Magento\Framework\DB\Statement\Pdo\Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->atLeastOnce())
            ->method('query')
            ->willReturn($statement);

        $statement->expects($this->atLeastOnce())->method('fetchColumn')->willReturn(1);

        $this->fixtureModel->expects($this->at(0))
            ->method('getValue')
            ->with('negotiable_quotes', 0)
            ->willReturn(100);

        $this->fixtureModel->expects($this->at(1))
            ->method('getValue')
            ->with('customers', 0)
            ->willReturn(42);

        $connection->expects($this->atLeastOnce())->method('select')->willReturn($select);
        $select->expects($this->atLeastOnce())->method('from')
            ->withConsecutive(
                ['negotiable_quote',  'COUNT(*)'],
                [['qa' => 'quote_address']],
                ['quote_item'],
                ['quote_item'],
                [['quote_item' => 'quote_item'], []]
            )
            ->willReturnSelf();
        $connection->expects($this->atLeastOnce())
            ->method('fetchOne')
            ->with($select)
            ->willReturn(99);
        $this->initCompanyCollection(1, self::COMPANY_ID);
        $this->prepareQuoteConfiguration();
        $negotiableQuote = $this->prepareNegotiableQuoteMockCommonData();
        $quoteCollection = $this->prepareQuoteCollection(100);
        $quoteCollection->expects($this->atLeastOnce())->method('getConnection')->willReturn($connection);
        $quoteCollection->expects($this->once())->method('getAllIds')->willReturn([self::QUOTE_ID]);
        $select->expects($this->atLeastOnce())
            ->method('where')
            ->withConsecutive(
                ['qa.quote_id IN (?)', [2]],
                ['quote_id=?', 2],
                ['parent_item_id=?', 44],
                ['quote_item.quote_id = 2']
            )
            ->willReturnSelf();
        $select->expects($this->atLeastOnce())
            ->method('columns')
            ->with(
                [
                    'quote_item_id' => 'quote_item.item_id',
                    'original_price' => 'quote_item.base_price',
                    'original_tax_amount' => null,
                    'original_discount_amount' => null
                ]
            )
            ->willReturnSelf();
        $customer = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->once())->method('getId')->willReturn(self::CUSTOMER_ID);
        $this->companyManagement->expects($this->once())
            ->method('getAdminByCompanyId')
            ->with(self::COMPANY_ID)
            ->willReturn($customer);
        $this->serializer->expects($this->atLeastOnce())
            ->method('serialize')
            ->willReturnOnConsecutiveCalls('{Serialized Data 1}', '{Serialized Data 2}', '{Serialized Data 3}');
        $negotiableQuote->expects($this->once())
            ->method('setQuoteId')
            ->with(self::QUOTE_ID)
            ->willReturnSelf();

        $negotiableQuote->expects($this->once())
            ->method('setQuoteName')
            ->with('Quote2')
            ->willReturnSelf();
        $negotiableQuote->expects($this->once())
            ->method('setCreatorId')
            ->with(self::CUSTOMER_ID)
            ->willReturnSelf();
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('getQuoteId')
            ->willReturn(self::QUOTE_ID);
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('getStatus')
            ->willReturn('submitted_by_customer');
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('getCreatorId')
            ->willReturn(self::CUSTOMER_ID);
        $this->negotiableQuoteResource->expects($this->once())
            ->method('saveNegotiatedQuoteData')
            ->with($negotiableQuote)
            ->willReturnSelf();
        $select->expects($this->atLeastOnce())
            ->method('insertFromSelect')
            ->with(
                'negotiable_quote_item',
                ['quote_item_id', 'original_price', 'original_tax_amount', 'original_discount_amount']
            )
            ->willReturnSelf();
        $this->populateHistoryLog();

        $this->fixture->execute();
    }

    /**
     * Prepare connection mock objects and expectations.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function mockConnection()
    {
        $connection = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resources->expects($this->atLeastOnce())
            ->method('getTableName')
            ->withConsecutive(
                ['negotiable_quote'],
                ['quote_address'],
                ['quote_item'],
                ['quote_item'],
                ['quote_item'],
                ['negotiable_quote_item'],
                ['negotiable_quote_history'],
                ['negotiable_quote_grid'],
                ['quote']
            )
            ->willReturnOnConsecutiveCalls(
                'negotiable_quote',
                'quote_address',
                'quote_item',
                'quote_item',
                'quote_item',
                'negotiable_quote_item',
                'negotiable_quote_history',
                'negotiable_quote_grid',
                'quote'
            );
        $connection->expects($this->atLeastOnce())
            ->method('fetchAll')
            ->willReturnOnConsecutiveCalls(
                [['entity_id' => self::QUOTE_ID, 'store_id' => 1, 'status' => 'created']],
                [
                    ['quote_id' => self::QUOTE_ID, 'address_type' => 'billing'],
                    ['quote_id' => self::QUOTE_ID, 'address_type' => 'shipping']
                ],
                [['item_id' => 44, 'base_price' => '99.00', 'product_id' => 144]],
                [[
                    'option_id' => '750001',
                    'item_id' => '750001',
                    'product_id' => 1,
                    'code' => 'info_buyRequest',
                    'value' => '{"product":"1","qty":"1","uenc":"aHR0cDovL21hZ2UyLmNvbS9jYXRlZ29yeS0xLmh0bWw"}',
                ]]
            );
        $connection->expects($this->atLeastOnce())
            ->method('insertOnDuplicate')
            ->withConsecutive(
                [
                    'negotiable_quote_history',
                    $this->getNegotiableQuoteHistorySampleData(),
                    array_keys($this->getNegotiableQuoteHistorySampleData())
                ],
                [
                    'negotiable_quote_grid',
                    $this->getNegotiableQuoteGridSampleData(),
                    array_keys($this->getNegotiableQuoteGridSampleData())
                ],
                [
                    'quote',
                    [$this->getQuoteSampleData()],
                    array_keys($this->getQuoteSampleData())
                ]
            )
            ->willReturn(1);
        $connection->expects($this->atLeastOnce())
            ->method('getTransactionLevel')
            ->willReturnOnConsecutiveCalls(1, 0);
        $connection->expects($this->once())->method('commit')->willReturnSelf();
        $connection->expects($this->once())->method('beginTransaction')->willReturnSelf();

        return $connection;
    }

    /**
     * Prepare history log mock objects and expectations.
     *
     * @return void
     */
    private function populateHistoryLog()
    {
        $historyLog = $this->getMockBuilder(\Magento\NegotiableQuote\Api\Data\HistoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                ['setQuoteId', 'setIsSeller', 'setAuthorId', 'setStatus', 'setLogData', 'setSnapshotData', 'getData']
            )
            ->getMockForAbstractClass();
        $historyLog->expects($this->once())
            ->method('setQuoteId')
            ->with(self::QUOTE_ID)
            ->willReturnSelf();
        $historyLog->expects($this->once())
            ->method('setIsSeller')
            ->with(true)
            ->willReturnSelf();
        $historyLog->expects($this->once())
            ->method('setAuthorId')
            ->with(self::CUSTOMER_ID)
            ->willReturnSelf();
        $historyLog->expects($this->once())
            ->method('setStatus')
            ->with('created')
            ->willReturnSelf();
        $historyLog->expects($this->once())
            ->method('setLogData')
            ->with('{Serialized Data 2}')
            ->willReturnSelf();
        $historyLog->expects($this->once())
            ->method('setSnapshotData')
            ->with('{Serialized Data 3}')
            ->willReturnSelf();
        $historyLog->expects($this->once())
            ->method('getData')
            ->willReturn([
                'quote_id' => self::QUOTE_ID,
                'is_seller' => true,
                'author_id' => self::CUSTOMER_ID,
                'status' => 'created',
                'log_data' => '{Serialized Data 1}',
                'snapshot_data' => '{Serialized Data 2}',
            ]);
        $this->historyLogFactory->expects($this->once())
            ->method('create')
            ->willReturn($historyLog);
    }

    /**
     * Prepare company collection mock for negotiable quotes.
     *
     * @param int $count
     * @param int $companyId
     * @return void
     */
    private function initCompanyCollection($count, $companyId)
    {
        $companyCollection = $this
            ->getMockBuilder(\Magento\Company\Model\ResourceModel\Company\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $companyCollection->expects($this->once())->method('getSize')->willReturn($count);
        $company = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        if ($count > 0) {
            $company->expects($this->once())->method('getId')->willReturn($companyId);
            $companyCollection->expects($this->atLeastOnce())
                ->method('getIterator')
                ->willReturn(new \ArrayIterator([$company]));
        }
        $this->companyCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($companyCollection);
    }

    /**
     * Prepare quote collection mock for negotiable quotes generation.
     *
     * @param int $collectionSize
     * @return \Magento\Quote\Model\ResourceModel\Quote\Collection|\PHPUnit\Framework\MockObject\MockObject
     */
    private function prepareQuoteCollection($collectionSize)
    {
        $quoteCollection = $this->getMockBuilder(\Magento\Quote\Model\ResourceModel\Quote\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteCollectionFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($quoteCollection);
        $resource = $this->getMockBuilder(\Magento\Framework\Model\ResourceModel\Db\AbstractDb::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdFieldName'])
            ->getMockForAbstractClass();
        $resource->expects($this->once())->method('getIdFieldName')->willReturn('entity_id');
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteCollection->expects($this->once())->method('getResource')->willReturn($resource);
        $quoteCollection->expects($this->once())->method('removeAllFieldsFromSelect')->willReturnSelf();
        $quoteCollection->expects($this->atLeastOnce())
            ->method('addFieldToSelect')
            ->withConsecutive([['entity_id', 'store_id']], ['checkout_method', 'guest'])
            ->willReturnSelf();
        $quoteCollection->expects($this->atLeastOnce())
            ->method('getTable')
            ->with('sales_order')
            ->willReturn('sales_order');
        $quoteCollection->expects($this->atLeastOnce())->method('getSelect')->willReturn($select);
        $quoteCollection->expects($this->once())->method('getSize')->willReturn($collectionSize);
        $select->expects($this->once())
            ->method('joinLeft')
            ->with(
                ['order' => 'sales_order'],
                'main_table.entity_id = order.quote_id',
                ''
            )
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('where')
            ->with('order.entity_id is NULL')
            ->willReturnSelf();

        return $quoteCollection;
    }

    /**
     * Prepare quote configuration mock and quote generation mock.
     *
     * @return void
     */
    private function prepareQuoteConfiguration()
    {
        $quoteConfig = $this
            ->getMockBuilder(\Magento\Setup\Fixtures\Quote\NegotiableQuoteConfiguration::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'setExistsQuoteQuantity', 'setRequiredQuoteQuantity'])
            ->getMock();
        $this->quoteConfigFactory->expects($this->once())
            ->method('create')
            ->willReturn($quoteConfig);
        $quoteConfig->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $quoteConfig->expects($this->once())
            ->method('setExistsQuoteQuantity')
            ->willReturnSelf();
        $quoteConfig->expects($this->once())
            ->method('setRequiredQuoteQuantity')
            ->willReturnSelf();
        $this->quoteGeneratorFactory->expects($this->once())
            ->method('create')
            ->with(['config' => $quoteConfig, 'fixtureModel' => $this->fixtureModel])
            ->willReturnSelf();
        $this->quoteGeneratorFactory->expects($this->once())
            ->method('generateQuotes')
            ->willReturnSelf();
    }

    /**
     * Test execute method if companies collection is empty.
     *
     * @return void
     */
    public function testExecuteWithEmptyCompaniesCollection()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('At least one Company entity is required to create Negotiable Quote');

        $connection = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['from', 'where', 'columns', 'insertFromSelect'])
            ->getMock();
        $this->resources->expects($this->atLeastOnce())
            ->method('getConnection')
            ->with('checkout')
            ->willReturn($connection);
        $connection->expects($this->atLeastOnce())->method('select')->willReturn($select);
        $this->fixtureModel->expects($this->once())
            ->method('getValue')->with('negotiable_quotes', 0)->willReturn(100);
        $this->initCompanyCollection(0, 0);

        $this->fixture->execute();
    }

    /**
     * Test execute method when there is not enough quotes to convert into negotiable quotes.
     *
     * @return void
     */
    public function testExecuteWithNotEnoughQuotes()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Not enough Quotes to be converted into Negotiable Quotes');

        $connection = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $select = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['from', 'where', 'columns', 'insertFromSelect'])
            ->getMock();
        $this->resources->expects($this->atLeastOnce())
            ->method('getConnection')
            ->with('checkout')
            ->willReturn($connection);
        $this->fixtureModel->expects($this->once())
            ->method('getValue')->with('negotiable_quotes', 0)->willReturn(100);
        $connection->expects($this->atLeastOnce())->method('select')->willReturn($select);
        $this->resources->expects($this->atLeastOnce())->method('getTableName')
            ->with('negotiable_quote')->willReturn('negotiable_quote');
        $select->expects($this->once())
            ->method('from')
            ->with('negotiable_quote', 'COUNT(*)')
            ->willReturnSelf();

        $connection->expects($this->atLeastOnce())->method('fetchOne')->with($select)->willReturn(99);
        $companyCollection = $this
            ->getMockBuilder(\Magento\Company\Model\ResourceModel\Company\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $companyCollection->expects($this->once())->method('getSize')->willReturn(1);

        $this->companyCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($companyCollection);

        $this->prepareNegotiableQuoteMockCommonData();
        $this->prepareQuoteConfiguration();
        $this->prepareQuoteCollection(99);
        $this->fixture->execute();
    }

    /**
     * Prepare Negotiable Quote mock object.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function prepareNegotiableQuoteMockCommonData()
    {
        $negotiableQuote = $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteFactory->expects($this->once())->method('create')->willReturn($negotiableQuote);

        $negotiableQuote->expects($this->once())
            ->method('setIsRegularQuote')
            ->with(1)
            ->willReturnSelf();

        $negotiableQuote->expects($this->atLeastOnce())->method('setStatus')->willReturnSelf();

        $negotiableQuote->expects($this->once())
            ->method('setHasUnconfirmedChanges')
            ->with(0)
            ->willReturnSelf();

        $negotiableQuote->expects($this->once())
            ->method('setIsCustomerPriceChanged')
            ->with(0)
            ->willReturnSelf();

        $negotiableQuote->expects($this->once())
            ->method('setIsShippingTaxChanged')
            ->with(0)
            ->willReturnSelf();

        $negotiableQuote->expects($this->once())
            ->method('setEmailNotificationStatus')
            ->with(0)
            ->willReturnSelf();

        $negotiableQuote->expects($this->once())
            ->method('setCreatorType')
            ->with(\Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER)
            ->willReturnSelf();

        $negotiableQuote->expects($this->once())
            ->method('setIsAddressDraft')
            ->with(0)
            ->willReturnSelf();

        return $negotiableQuote;
    }

    /**
     * Prepare sample data for quote.
     *
     * @return array
     */
    private function getQuoteSampleData()
    {
        return [
            'entity_id' => self::QUOTE_ID,
            'customer_id' => self::CUSTOMER_ID,
            'checkout_method' => 'customer',
            'customer_is_guest' => false
        ];
    }

    /**
     * Get negotiable quote history data array that should be saved.
     *
     * @return array
     */
    private function getNegotiableQuoteHistorySampleData()
    {
        return [
            'quote_id' => self::QUOTE_ID,
            'is_seller' => true,
            'author_id' => self::CUSTOMER_ID,
            'status' => 'created',
            'log_data' => '{Serialized Data 1}',
            'snapshot_data' => '{Serialized Data 2}',
        ];
    }

    /**
     * Get negotiable quote grid data array that should be saved.
     *
     * @return array
     */
    private function getNegotiableQuoteGridSampleData()
    {
        return [
            'entity_id' => self::QUOTE_ID,
            'created_at' => '1970-01-01 03:00:00',
            'updated_at' => '1970-01-01 03:00:00',
            'base_grand_total' => 9.9900000000000002,
            'grand_total' => 9.9900000000000002,
            'quote_name' => 'Quote2',
            'status' => 'submitted_by_customer',
            'base_negotiated_grand_total' => 9.9900000000000002,
            'negotiated_grand_total' => 9.9900000000000002,
            'base_currency_code' => 'USD',
            'quote_currency_code' => 'USD',
            'store_id' => 1,
            'rate' => 1,
            'customer_id' => self::CUSTOMER_ID,
            'submitted_by' => 'John Doe',
            'company_id' => self::COMPANY_ID,
            'company_name' => 'Company 11',
        ];
    }

    /**
     * Test getActionTitle method.
     *
     * @return void
     */
    public function testGetActionTitle()
    {
        $this->assertEquals('Generating Negotiable Quotes', $this->fixture->getActionTitle());
    }

    /**
     * Test introduceParamLabels method.
     *
     * @return void
     */
    public function testIntroduceParamLabels()
    {
        $this->assertEquals(['negotiable_quotes' => 'Negotiable Quotes'], $this->fixture->introduceParamLabels());
    }
}
