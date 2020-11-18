<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Config\Model\Config\Factory as ConfigFactory;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Area;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Model\Config as SharedCatalogConfig;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 * @magentoDbIsolation disabled
 * @magentoAppArea frontend
 * @magentoDataFixture Magento/Catalog/_files/products_in_category.php
 * @magentoDataFixture Magento/SharedCatalog/_files/public_shared_catalog_products.php
 * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog.php
 * @magentoConfigFixture btob/website_configuration/company_active 1
 */
class ProductCollectionTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->enableSharedCatalog();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        $config = Bootstrap::getObjectManager()->get(ConfigFactory::class)
            ->create();
        $config->setDataByPath(SharedCatalogConfig::CONFIG_SHARED_CATALOG, 0);
        $config->save();
    }

    /**
     * @return void
     */
    public function testLoadForDefaultSharedCatalogCustomerGroup()
    {
        $sharedCatalogManagement = Bootstrap::getObjectManager()->get(SharedCatalogManagementInterface::class);
        $publicCatalog = $sharedCatalogManagement->getPublicCatalog();
        $customerGroupId = (int) $publicCatalog->getCustomerGroupId();

        $items = $this->loadItems($customerGroupId);
        $this->assertCount(3, $items);
    }

    /**
     * @return void
     */
    public function testLoadForCustomSharedCatalogCustomerGroup()
    {
        $customerGroupManagement = Bootstrap::getObjectManager()->get(CustomerGroupManagement::class);
        $sharedCatalogGroupIds = $customerGroupManagement->getSharedCatalogGroupIds();
        $customerGroupId = (int) array_pop($sharedCatalogGroupIds);

        $items = $this->loadItems($customerGroupId);
        $this->assertCount(0, $items);
    }

    /**
     * @return void
     */
    public function testLoadForNonSharedCatalogCustomerGroup()
    {
        $customerGroupManagement = Bootstrap::getObjectManager()->get(CustomerGroupManagement::class);
        $notSharedCatalogsGroups = $customerGroupManagement->getGroupIdsNotInSharedCatalogs();
        $customerGroupId = (int) array_pop($notSharedCatalogsGroups);

        $items = $this->loadItems($customerGroupId);
        $this->assertCount(5, $items);
    }

    /**
     * @param int $customerGroupId
     * @return Product[]
     */
    private function loadItems(int $customerGroupId): array
    {
        Bootstrap::getObjectManager()->get(HttpContext::class)
            ->setValue(CustomerContext::CONTEXT_GROUP, $customerGroupId, null);

        $productCollection = Bootstrap::getObjectManager()->create(ProductCollection::class);
        $productCollection->addPriceData($customerGroupId);
        $productCollection->load();
        $items = $productCollection->getItems();

        return $items;
    }

    /**
     * @return void
     */
    private function enableSharedCatalog(): void
    {
        Bootstrap::getInstance()->reinitialize();
        Bootstrap::getInstance()->loadArea(Area::AREA_ADMINHTML);

        $config = Bootstrap::getObjectManager()->get(ConfigFactory::class)
            ->create();
        $config->setDataByPath(SharedCatalogConfig::CONFIG_SHARED_CATALOG, 1);
        $config->save();

        $consumerFactory = Bootstrap::getObjectManager()->get(ConsumerFactory::class);
        $categoryPermissionsUpdater = $consumerFactory->get('sharedCatalogUpdateCategoryPermissions');
        $categoryPermissionsUpdater->process(100);

        Bootstrap::getInstance()->reinitialize();
        Bootstrap::getInstance()->loadArea(Area::AREA_FRONTEND);
    }
}
