<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Api;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection as SharedCatalogCollection;
use Magento\SharedCatalog\Model\SharedCatalog;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog.php
 * @magentoDataFixture Magento/Catalog/_files/categories_no_products.php
 */
class CategoryManagementInterfaceTest extends TestCase
{
    /**
     * @var SharedCatalog
     */
    private $customSharedCatalog;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var CategoryManagementInterface
     */
    private $categoryManagement;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $sharedCatalogCollection = $objectManager->create(SharedCatalogCollection::class);
        $this->customSharedCatalog = $sharedCatalogCollection->getLastItem();
        $this->categoryRepository = $objectManager->get(CategoryRepositoryInterface::class);

        $this->categoryManagement = $objectManager->create(CategoryManagementInterface::class);
    }

    /**
     * @return void
     */
    public function testGetCategories($count = 0)
    {
        $categories = $this->categoryManagement->getCategories($this->customSharedCatalog->getId());
        $this->assertCount($count, $categories);
    }

    /**
     * @return void
     */
    public function testAssignCategories()
    {
        $categories = [];
        $categories[] = $this->categoryRepository->get(3);
        $categories[] = $this->categoryRepository->get(4);
        $categories[] = $this->categoryRepository->get(5);
        $this->categoryManagement->assignCategories($this->customSharedCatalog->getId(), $categories);
        $this->testGetCategories(3);
    }

    /**
     * @return void
     */
    public function testUnassignCategories()
    {
        $this->testAssignCategories();

        $categories = [];
        $categories[] = $this->categoryRepository->get(3);
        $categories[] = $this->categoryRepository->get(4);
        $categories[] = $this->categoryRepository->get(5);
        $this->categoryManagement->unassignCategories($this->customSharedCatalog->getId(), $categories);
        $this->testGetCategories(0);
    }
}
