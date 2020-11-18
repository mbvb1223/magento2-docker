<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\QuickOrder\Model\CatalogPermissions;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;

class PermissionsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Permissions
     */
    private $permissionsModel;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->permissionsModel = $this->objectManager->create(Permissions::class);
    }

    /**
     * @magentoConfigFixture current_store catalog/magento_catalogpermissions/enabled 1
     * @magentoDataFixture Magento/Catalog/_files/category_product.php
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     */
    public function testApplyPermissionsToProductCollection()
    {
        $productCollection = $this->objectManager->create(ProductCollection::class);
        $productCollection->addIdFilter(333);
        $this->permissionsModel->applyPermissionsToProductCollection($productCollection);
        $this->assertEmpty($productCollection->getItems());

        $productCollection = $this->objectManager->create(ProductCollection::class);
        $productCollection->addIdFilter(6);
        $this->permissionsModel->applyPermissionsToProductCollection($productCollection);
        $this->assertNotEmpty($productCollection->getItems());
    }
}
