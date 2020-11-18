<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Service\V1;

/**
 * Tests for shared catalog products actions (assign, unassign, getting).
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductManagementTest extends AbstractSharedCatalogTest
{
    const SERVICE_READ_NAME = 'sharedCatalogProductManagementV1';

    const SERVICE_VERSION = 'V1';

    /**
     * Check list of product SKUs in the selected shared catalog.
     *
     * @return void
     * @magentoApiDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @magentoApiDataFixture Magento/Catalog/_files/categories.php
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled true
     */
    public function testGetProducts()
    {
        $sharedCatalog = $this->getSharedCatalog();
        $sharedCatalogId = $sharedCatalog->getId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf('/V1/sharedCatalog/%d/products', $sharedCatalogId),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'getProducts',
            ],
        ];
        $categories = $this->getCategories();
        $this->assignCategories($sharedCatalog, $categories);
        $products = $this->getProducts();
        $this->assignProducts($sharedCatalog, $products);
        $respProductSkus = $this->_webApiCall($serviceInfo, ['id' => $sharedCatalogId]);
        $expectedResult = [];
        foreach ($products as $product) {
            $expectedResult[] = $product->getSku();
        }
        $this->assertEquals($respProductSkus, $expectedResult, 'List of products is wrong.');
    }

    /**
     * Test assign products to shared catalog.
     *
     * @return void
     * @magentoApiDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @magentoApiDataFixture Magento/Catalog/_files/categories.php
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled true
     */
    public function testAssignProducts()
    {
        $sharedCatalog = $this->getSharedCatalog();
        $sharedCatalogId = $sharedCatalog->getId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf('/V1/sharedCatalog/%d/assignProducts', $sharedCatalogId),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'assignProducts',
            ],
        ];

        $products = $this->getProducts();
        $this->allowCategoryPermissions($sharedCatalog->getCustomerGroupId());
        $categories = $this->getCategories();
        $this->assignCategories($sharedCatalog, $categories);

        $params = [
            'id' => $sharedCatalogId,
            'products' => $this->prepareItems($products, 'sku')
        ];
        $resp = $this->_webApiCall($serviceInfo, $params);
        $this->assertTrue($resp);
        $assignProductSkus = $this->retrieveAssignProductSkus($sharedCatalog->getCustomerGroupId());
        $this->assertEquals(
            $this->prepareItems($products, 'sku'),
            $this->prepareItems($assignProductSkus, 'sku'),
            'Products are not assigned.'
        );
    }

    /**
     * Test unassign products from shared catalog.
     *
     * @return void
     * @magentoApiDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @magentoApiDataFixture Magento/Catalog/_files/categories.php
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled true
     */
    public function testUnassignProducts()
    {
        $sharedCatalog = $this->getSharedCatalog();
        $sharedCatalogId = $sharedCatalog->getId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf('/V1/sharedCatalog/%d/unassignProducts', $sharedCatalogId),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'unassignProducts',
            ],
        ];
        $categories = $this->getCategories();
        $this->assignCategories($sharedCatalog, $categories);
        $products = $this->getProducts();
        $this->assignProducts($sharedCatalog, $products);
        $params = [
            'id' => $sharedCatalogId,
            'products' => $this->prepareItems($products, 'sku')
        ];
        $resp = $this->_webApiCall($serviceInfo, $params);
        $this->assertTrue($resp);
        $assignProductSkus = $this->retrieveAssignProductSkus($sharedCatalog->getCustomerGroupId());
        $this->assertEmpty($assignProductSkus);
    }

    /**
     * Test unassign product with invalid sku from shared catalog.
     *
     * @return void
     * @magentoApiDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     */
    public function testUnassignProductsWithInvalidSku()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Requested product doesn\'t exist: %sku.');

        $sharedCatalog = $this->getSharedCatalog();
        $sharedCatalogId = $sharedCatalog->getId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf('/V1/sharedCatalog/%d/unassignProducts', $sharedCatalogId),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'unassignProducts',
            ],
        ];
        $productsSku[] = ['sku' => 'nonexistent'];
        $params = [
            'id' => $sharedCatalogId,
            'products' => $productsSku
        ];
        $this->_webApiCall($serviceInfo, $params);
    }

    /**
     * Assign products for testUnassignProducts, testGetProducts.
     *
     * @param \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog
     * @param array $products
     * @return void
     */
    private function assignProducts(
        \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog,
        array $products
    ) {
        $this->allowCategoryPermissions($sharedCatalog->getCustomerGroupId());
        /** @var \Magento\SharedCatalog\Api\ProductManagementInterface $productManagement */
        $productManagement = $this->objectManager->create(\Magento\SharedCatalog\Api\ProductManagementInterface::class);
        $productManagement->assignProducts($sharedCatalog->getId(), $products);
    }

    /**
     * Assign products for testUnassignProducts, testGetProducts.
     *
     * @param \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog
     * @param array $categories
     * @return void
     */
    private function assignCategories(
        \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog,
        array $categories
    ) {
        /** @var \Magento\SharedCatalog\Api\CategoryManagementInterface $categoryManagement */
        $categoryManagement = $this->objectManager->create(
            \Magento\SharedCatalog\Api\CategoryManagementInterface::class
        );
        $categoryManagement->assignCategories($sharedCatalog->getId(), $categories);
    }

    /**
     * Retrieve assign products sku.
     *
     * @param int $customerGroupId
     * @return array
     */
    private function retrieveAssignProductSkus($customerGroupId)
    {
        $builder = $this->objectManager->get(
            \Magento\Framework\Api\SearchCriteriaBuilder::class
        );
        $builder->addFilter('customer_group_id', $customerGroupId);
        $products = $this->objectManager->create(\Magento\SharedCatalog\Api\ProductItemRepositoryInterface::class);
        return $products->getList($builder->create())->getItems();
    }

    /**
     * Get products.
     *
     * @return \Magento\SharedCatalog\Api\Data\ProductItemInterface[]
     */
    private function getProducts()
    {
        /**
         * Products sku from Magento/Catalog/_files/categories.php.
         */
        $productSku = ['simple', '12345', 'simple-3', 'simple-4'];
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->objectManager->create(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
        $collection->addAttributeToFilter('sku', ['in' => $productSku]);

        return $collection->getItems();
    }

    /**
     * Allow category permissions for assign product.
     *
     * @param int $customerGroupId
     * @return void
     */
    private function allowCategoryPermissions($customerGroupId)
    {
        /**
         * Category Ids 3-13 from Magento/Catalog/_files/categories.php.
         */
        for ($id = 3; $id <= 12; $id++) {
            /** @var $permissionAllow \Magento\CatalogPermissions\Model\Permission */
            $permissionAllow = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
                \Magento\CatalogPermissions\Model\Permission::class
            );
            $permissionAllow->setWebsiteId(
                \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
                    \Magento\Store\Model\StoreManagerInterface::class
                )->getWebsite()->getId()
            )->setCategoryId(
                $id
            )->setCustomerGroupId(
                $customerGroupId
            )->setGrantCatalogCategoryView(
                \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW
            )->setGrantCatalogProductPrice(
                \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW
            )->setGrantCheckoutItems(
                \Magento\CatalogPermissions\Model\Permission::PERMISSION_ALLOW
            )->save();
        }
    }

    /**
     * Get categories.
     *
     * @return \Magento\Catalog\Api\Data\CategoryInterface[]
     */
    private function getCategories()
    {
        /**
         * Category Ids from Magento/Catalog/_files/categories.php.
         */
        $categoryIds = [2, 3, 4, 5, 10, 11, 12, 13];
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->objectManager->create(\Magento\Catalog\Model\ResourceModel\Category\Collection::class);
        $collection->addAttributeToFilter('entity_id', ['in' => $categoryIds]);
        return $collection->getItems();
    }
}
