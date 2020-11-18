<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model\Indexer;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Store\Model\StoreManagerInterface;

class CopyIndexTest extends \Magento\TestFramework\Indexer\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $db = Bootstrap::getInstance()->getBootstrap()
            ->getApplication()
            ->getDbInstance();
        if (!$db->isDbDumpExists()) {
            throw new \LogicException('DB dump does not exist.');
        }
        $db->restoreFromDbDump();

        parent::setUpBeforeClass();
    }

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/products.php
     * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @magentoDbIsolation disabled
     */
    public function testSavePricesFromDefaultGroupCopied()
    {
        $processor = $this->objectManager->create(Processor::class);
        $processor->getIndexer()->setScheduled(true);
        /** @var GroupCollection $customerGroupCollection */
        $customerGroupCollection = $this->objectManager->create(GroupCollection::class);
        $customerGroupId = $customerGroupCollection->getLastItem()->getId();

        /** @var ProductCollection $productCollection */
        $productCollection = $this->objectManager->create(ProductCollection::class);
        $productCollection->addPriceData($customerGroupId, 1);

        $this->assertCount(2, $productCollection->getItems());
        $product = $productCollection->getItemByColumnValue('sku', 'simple');
        $this->assertNotNull($product);
        $this->assertEquals(10, $product->getPrice());
        $processor->getIndexer()->setScheduled(false);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/categories.php
     * @magentoDataFixture Magento/CatalogPermissions/_files/enable_permissions_for_specific_customer_group.php
     * @magentoDataFixture Magento/CatalogPermissions/_files/permission.php
     * @magentoDataFixture Magento/CatalogPermissions/_files/reindex_permissions.php
     * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @magentoDbIsolation disabled
     */
    public function testSaveCatalogPermissionsFromDefaultGroupCopied()
    {
        $fixturePermissionData = [
            'category_id' => 6,
            'website_id' => $this->objectManager->get(StoreManagerInterface::class)->getWebsite()->getId(),
            'grant_catalog_category_view' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY,
            'grant_catalog_product_price' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY,
            'grant_checkout_items' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY,
        ];

        $index = $this->objectManager->create(
            \Magento\CatalogPermissions\Model\Permission\Index::class
        );

        /** @var GroupCollection $customerGroupCollection */
        $customerGroupCollection = $this->objectManager->create(GroupCollection::class);
        $customerGroupId = $customerGroupCollection->getLastItem()->getId();

        $categoryPermissionsIndex = $index->getIndexForCategory(6, $customerGroupId, 1);

        $this->assertArrayHasKey(6, $categoryPermissionsIndex);
        $this->assertCount(1, $categoryPermissionsIndex);
        foreach ($fixturePermissionData as $key => $value) {
            $this->assertArrayHasKey($key, $categoryPermissionsIndex[6]);
            $this->assertEquals($value, $categoryPermissionsIndex[6][$key]);
        }

        /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->create(
            \Magento\Catalog\Api\ProductRepositoryInterface::class
        );
        $product = $productRepository->get('12345-1');

        $productPermissionsIndex = $index->getIndexForProduct($product->getId(), $customerGroupId, 1);
        $this->assertArrayHasKey($product->getId(), $productPermissionsIndex);
    }

    /**
     * teardown
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
