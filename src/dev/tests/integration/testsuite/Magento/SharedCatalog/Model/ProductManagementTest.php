<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test of managing products assigned to shared catalog.
 */
class ProductManagementTest extends TestCase
{
    /**
     * @var ProductManagementInterface
     */
    private $productManagement;

    /**
     * @var ProductInterfaceFactory
     */
    private $productFactory;

    /**
     * @var SharedCatalogManagementInterface
     */
    private $sharedCatalogManagement;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->productManagement = $objectManager->get(ProductManagementInterface::class);
        $this->productFactory = $objectManager->get(ProductInterfaceFactory::class);
        $this->sharedCatalogManagement = $objectManager->get(SharedCatalogManagementInterface::class);
    }

    /**
     * Check that shared catalog records are created without duplicates.
     *
     * @magentoDataFixture Magento/SharedCatalog/_files/public_shared_catalog_products.php
     * @magentoConfigFixture btob/website_configuration/company_active 1
     * @magentoConfigFixture btob/website_configuration/sharedcatalog_active 1
     */
    public function testAssignProductsWithSameSku(): void
    {
        $customerGroupId = $this->sharedCatalogManagement->getPublicCatalog()
            ->getCustomerGroupId();

        $productsBeforeAssign = $this->productManagement->getProducts($customerGroupId);
        $productSku = current($productsBeforeAssign);

        $this->productManagement->assignProducts(
            $customerGroupId,
            $this->getProductInstances([$productSku, $productSku, 'assign_product_sku'])
        );
        $productsAfterAssign = $this->productManagement->getProducts($customerGroupId);
        $assignedProducts = array_diff($productsAfterAssign, $productsBeforeAssign);

        $this->assertEquals(['assign_product_sku'], array_values($assignedProducts));
        $this->assertEquals(1, array_count_values($productsAfterAssign)[$productSku]);
    }

    /**
     * Check that only required shared catalog records are removed.
     *
     * @magentoDataFixture Magento/SharedCatalog/_files/public_shared_catalog_products.php
     * @magentoConfigFixture btob/website_configuration/company_active 1
     * @magentoConfigFixture btob/website_configuration/sharedcatalog_active 1
     */
    public function testUnassignProductsWithSameSku(): void
    {
        $customerGroupId = $this->sharedCatalogManagement->getPublicCatalog()
            ->getCustomerGroupId();

        $productsBeforeUnassign = $this->productManagement->getProducts($customerGroupId);
        $productSku = current($productsBeforeUnassign);

        $this->productManagement->unassignProducts(
            $customerGroupId,
            $this->getProductInstances([$productSku, $productSku])
        );
        $productsAfterUnassign = $this->productManagement->getProducts($customerGroupId);
        $actualResult = array_diff($productsBeforeUnassign, $productsAfterUnassign);

        $this->assertEquals([$productSku], $actualResult);
    }

    /**
     * Check that providing product with non-existent SKU throws exception.
     *
     * @magentoDataFixture Magento/SharedCatalog/_files/public_shared_catalog_products.php
     * @magentoConfigFixture btob/website_configuration/company_active 1
     * @magentoConfigFixture btob/website_configuration/sharedcatalog_active 1
     */
    public function testUnassignProductWithNonExistentSku(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Requested product doesn\'t exist: non_existent_product_sku.');

        $customerGroupId = $this->sharedCatalogManagement->getPublicCatalog()
            ->getCustomerGroupId();

        $products = $this->productManagement->getProducts($customerGroupId);
        $productSku = current($products);

        $this->productManagement->unassignProducts(
            $customerGroupId,
            $this->getProductInstances([$productSku, $productSku, 'non_existent_product_sku'])
        );
    }

    /**
     * Retrieve product instances for shared catalog actions.
     *
     * @param array $skus
     * @return ProductInterface[]
     */
    private function getProductInstances(array $skus): array
    {
        $products = [];
        foreach ($skus as $sku) {
            $products[] = $this->productFactory->create()
                ->setSku($sku);
        }

        return $products;
    }
}
