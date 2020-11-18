<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Logging\Model\Event;
use Magento\Logging\Model\ResourceModel\Event\Changes\CollectionFactory as EventChangesCollectionFactory;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection;
use Magento\Logging\Model\ResourceModel\Event\CollectionFactory;

/**
 * Test for class \Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Delete
 *
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 */
class LoggingTest extends AbstractBackendController
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CollectionFactory
     */
    private $eventCollectionFactory;

    /**
     * @var EventChangesCollectionFactory
     */
    private $eventChangesCollectionFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->searchCriteriaBuilder = $this->_objectManager->get(SearchCriteriaBuilder::class);
        $this->eventCollectionFactory = $this->_objectManager->get(CollectionFactory::class);
        $this->eventChangesCollectionFactory = $this->_objectManager->get(EventChangesCollectionFactory::class);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->searchCriteriaBuilder = null;
        $this->eventCollectionFactory = null;
        $this->eventChangesCollectionFactory = null;
    }

    /**
     * Test logging entry after shared catalog edit action
     *
     * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @return void
     */
    public function testEditSharedCatalogActionLogging(): void
    {
        $sharedCatalog = $this->getTestFixture();
        $this->dispatch('backend/shared_catalog/sharedCatalog/edit/shared_catalog_id/' . $sharedCatalog->getId());

        $event = $this->provideLatestEvent();

        $this->assertEquals('shared_catalog_sharedCatalog_edit', $event->getFullaction());
        $this->assertEquals('view', $event->getAction());
        $this->assertStringContainsString('(' . $sharedCatalog->getName() . ')', $event->getInfo());
        $this->assertEventData($event, [
            'Id' => $sharedCatalog->getId(),
        ]);
    }

    /**
     * Test logging entry after shared catalog save action
     *
     * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @return void
     */
    public function testSaveSharedCatalogActionLogging(): void
    {
        $sharedCatalog = $this->getTestFixture();

        $sharedCatalogNameBeforeChange = $sharedCatalog->getName();
        $requestData = [
            'name' => 'Testaaaaa',
            'description' => 'Descriptions test',
            'customer_group_id' => (string)$sharedCatalog->getCustomerGroupId(),
            'type' => (string)$sharedCatalog->getType(),
            'tax_class_id' => (string)$sharedCatalog->getTaxClassId(),
        ];

        $this->getRequest()->setPostValue(['catalog_details' => $requestData]);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);

        $this->dispatch('backend/shared_catalog/sharedCatalog/save/shared_catalog_id/' . $sharedCatalog->getId());

        $event = $this->provideLatestEvent();

        $this->assertEquals('shared_catalog_sharedCatalog_save', $event->getFullaction());
        $this->assertEquals('save', $event->getAction());
        $this->assertStringContainsString('(' . $requestData['name'] . ')', $event->getInfo());

        $this->assertEventData($event, [
            'Id' => $sharedCatalog->getId(),
        ]);

        $changes = $this->eventChangesCollectionFactory->create()
            ->addFieldToFilter('event_id', $event->getId())
            ->getItems();

        foreach ($changes as $change) {
            $this->assertStringContainsString($sharedCatalogNameBeforeChange, $change->getOriginalData());
            $this->assertStringContainsString($requestData['name'], $change->getResultData());
        }
    }

    /**
     * Test logging entry after new shared catalog save action
     *
     * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @return void
     */
    public function testSaveNewSharedCatalogActionLogging(): void
    {
        $existingSharedCatalog = $this->getTestFixture();

        $requestData = [
            'name' => 'New Shared Catalog',
            'description' => 'New Shared Catalog description',
            'type' => (string)$existingSharedCatalog->getType(),
            'tax_class_id' => (string)$existingSharedCatalog->getTaxClassId(),
        ];

        $this->getRequest()->setPostValue(['catalog_details' => $requestData]);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);

        $this->dispatch('backend/shared_catalog/sharedCatalog/save');

        $createdSharedCatalog = $this->getTestFixture();
        $event = $this->provideLatestEvent();

        $this->assertEquals('shared_catalog_sharedCatalog_save', $event->getFullaction());
        $this->assertEquals('save', $event->getAction());
        $this->assertStringContainsString('(' . $requestData['name'] . ')', $event->getInfo());

        $this->assertEventData($event, [
            'Id' => $createdSharedCatalog->getId(),
        ]);

        $changes = $this->eventChangesCollectionFactory->create()
            ->addFieldToFilter('event_id', $event->getId())
            ->getItems();

        foreach ($changes as $change) {
            $this->assertStringContainsString('"__was_created":true', $change->getOriginalData());
            $this->assertStringContainsString($requestData['name'], $change->getResultData());
        }
    }

    /**
     * Test logging entry after shared catalog delete action
     *
     * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @return void
     */
    public function testDeleteSharedCatalogActionLogging(): void
    {
        $sharedCatalog = $this->getTestFixture();
        $sharedCatalogId = $sharedCatalog->getId();
        $sharedCatalogName = $sharedCatalog->getName();

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('backend/shared_catalog/sharedCatalog/delete/shared_catalog_id/' . $sharedCatalogId);

        $event = $this->provideLatestEvent();

        $this->assertEquals('shared_catalog_sharedCatalog_delete', $event->getFullaction());
        $this->assertEquals('delete', $event->getAction());

        $this->assertEventData($event, [
            'Id' => $sharedCatalog->getId(),
        ]);

        $changes = $this->eventChangesCollectionFactory->create()
            ->addFieldToFilter('event_id', $event->getId())
            ->getItems();

        foreach ($changes as $change) {
            $this->assertStringContainsString(
                sprintf('"entity_id":"%s"', $sharedCatalogId),
                $change->getOriginalData()
            );
            $this->assertStringContainsString(sprintf('"name":"%s"', $sharedCatalogName), $change->getOriginalData());
            $this->assertStringContainsString('__was_deleted', $change->getResultData());
        }
    }

    /**
     * Test logging entry after shared catalog delete action of non existing shared catalog
     *
     * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @return void
     */
    public function testDeleteSharedCatalogActionLoggingWithBadData(): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('backend/shared_catalog/sharedCatalog/delete/shared_catalog_id/x1233221');

        $event = $this->provideLatestEvent();

        $this->assertEquals('shared_catalog_sharedCatalog_delete', $event->getFullaction());
        $this->assertEquals('delete', $event->getAction());
        $this->assertEquals('failure', $event->getStatus());
        $this->assertStringContainsString(
            (string)__('Requested Shared Catalog is not found'),
            $event->getErrorMessage()
        );
    }

    /**
     * Test logging entry after shared catalog mass delete action
     *
     * @magentoDataFixture Magento/SharedCatalog/_files/several_shared_catalogs.php
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testMassDeleteSharedCatalogActionLogging(): void
    {
        /** @var SharedCatalogRepositoryInterface $sharedCatalogRepository */
        $sharedCatalogRepository = Bootstrap::getObjectManager()->create(SharedCatalogRepositoryInterface::class);
        $sharedCatalogs = $sharedCatalogRepository->getList(
            $this->searchCriteriaBuilder->addFilter('type', SharedCatalogInterface::TYPE_CUSTOM)->create()
        )->getItems();

        $sharedCatalogNames = [];
        foreach ($sharedCatalogs as $sharedCatalog) {
            $sharedCatalogNames[$sharedCatalog->getId()] = $sharedCatalog->getName();
        }

        $requestData = [
            'selected' => array_keys($sharedCatalogNames),
            'namespace' => 'shared_catalog_listing',
            'filters' => ['placeholder' => true],
        ];

        $this->getRequest()->setPostValue($requestData);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('backend/shared_catalog/sharedCatalog/massDelete');

        $event = $this->provideLatestEvent();

        $this->assertEquals('shared_catalog_sharedCatalog_massDelete', $event->getFullaction());
        $this->assertEquals('massDelete', $event->getAction());
        $this->assertStringContainsString(implode(', ', array_keys($sharedCatalogNames)), $event->getInfo());
        $this->assertEventData($event);
    }

    /**
     * Test logging entry after shared catalog companies edit action
     *
     * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @return void
     */
    public function testEditSharedCatalogCompanyActionLogging(): void
    {
        $sharedCatalog = $this->getTestFixture();
        $this->dispatch('backend/shared_catalog/sharedCatalog/companies/shared_catalog_id/' . $sharedCatalog->getId());

        $event = $this->provideLatestEvent();

        $this->assertEquals('shared_catalog_sharedCatalog_companies', $event->getFullaction());
        $this->assertEquals('save', $event->getAction());
        $this->assertStringContainsString('(' . $sharedCatalog->getName() . ')', $event->getInfo());
        $this->assertEventData($event, [
            'Id' => $sharedCatalog->getId(),
        ]);
    }

    /**
     * Test logging entry after shared catalog companies save action
     *
     * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @return void
     */
    public function testSharedCatalogCompanySaveActionLogging(): void
    {
        $sharedCatalog = $this->getTestFixture();

        $requestData = [
            'shared_catalog_id' => $sharedCatalog->getId()
        ];

        $this->getRequest()->setPostValue($requestData);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);

        $this->dispatch('backend/shared_catalog/sharedCatalog_company/save');

        $event = $this->provideLatestEvent();

        $this->assertEquals('shared_catalog_sharedCatalog_company_save', $event->getFullaction());
        $this->assertEquals('save', $event->getAction());
        $this->assertStringContainsString('(' . $sharedCatalog->getName() . ')', $event->getInfo());

        $this->assertEventData($event, [
            'Id' => $sharedCatalog->getId(),
        ]);
    }

    /**
     * Returns latest logging entry
     *
     * @return Event
     */
    private function provideLatestEvent(): Event
    {
        $eventCollection = $this->eventCollectionFactory->create();
        $eventCollection->setOrder('log_id', Collection::SORT_ORDER_DESC);

        return $eventCollection->getFirstItem();
    }

    /**
     * Asserts common entry data
     *
     * @param Event $event
     * @param array $additionalData
     */
    private function assertEventData(Event $event, array $additionalData = []): void
    {
        $this->assertEquals('success', $event->getStatus());
        $this->assertIsNumeric($event->getUserId());
        $this->assertEquals($this->_getAdminCredentials()['user'], $event->getUser());
        $this->assertStringContainsString(date('Y-m-d H:i'), $event->getTime());
        foreach ($additionalData as $fieldName => $fieldValue) {
            $this->assertStringContainsString(sprintf('%s: %s', $fieldName, $fieldValue), $event->getInfo());
        }
    }

    /**
     * Gets Shared Catalog Fixture.
     *
     * @return SharedCatalog
     */
    private function getTestFixture(): SharedCatalog
    {
        /** @var Collection $sharedCatalogCollection */
        $sharedCatalogCollection = Bootstrap::getObjectManager()->create(Collection::class);
        return $sharedCatalogCollection->getLastItem();
    }
}
