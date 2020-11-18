<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Service\V1;

/**
 * Tests for shared catalog categories actions (assign, unassign, getting).
 */
class CategoryManagementTest extends AbstractSharedCatalogTest
{
    const SERVICE_READ_NAME = 'sharedCatalogCategoryManagementV1';

    const SERVICE_VERSION = 'V1';

    /**
     * Check list of categories in the selected shared catalog.
     *
     * @return void
     * @magentoApiDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @magentoApiDataFixture Magento/Catalog/_files/categories.php
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled true
     */
    public function testGetCategories()
    {
        $sharedCatalog = $this->getSharedCatalog();
        $sharedCatalogId = $sharedCatalog->getId();
        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf('/V1/sharedCatalog/%d/categories', $sharedCatalogId),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'getCategories',
            ],
        ];

        $categories = $this->getCategories();
        $this->assignCategories($sharedCatalog, $categories);
        $respCategoryIds = $this->_webApiCall($serviceInfo, ['id' => $sharedCatalogId]);
        $expectedCategoryIds = $this->prepareItems($categories);
        foreach ($expectedCategoryIds as $value) {
            $this->assertContains((int) $value['id'], $respCategoryIds, 'List of categories is wrong.');
        }
    }

    /**
     * Test assign categories to shared catalog.
     *
     * @return void
     * @magentoApiDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @magentoApiDataFixture Magento/Catalog/_files/categories.php
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled true
     */
    public function testAssignCategories()
    {
        $sharedCatalog = $this->getSharedCatalog();
        $sharedCatalogId = $sharedCatalog->getId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf('/V1/sharedCatalog/%d/assignCategories', $sharedCatalogId),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'assignCategories',
            ],
        ];

        $categories = $this->getCategories();

        $categoriesParams = [
            [
                'id' => 10,
                'name' => 'Movable Position 2'
            ],
            [
                'id' => 11,
                'name' => 'Movable Position 3'
            ],
            [
                'id' => 12,
                'name' => 'Category 12'
            ]
        ];
        $this->_webApiCall($serviceInfo, ['id' => $sharedCatalogId, 'categories' => $categoriesParams]);

        $assignCategoryIds = $this->retrieveAssignedCategoryIds($sharedCatalog->getCustomerGroupId());

        foreach ($this->prepareItems($categories) as $key => $value) {
            $this->assertEmpty(
                array_diff($value, $assignCategoryIds[$key]),
                'Categories are not assigned.'
            );
        }
    }

    /**
     * Test unassign categories from shared catalog.
     *
     * @return void
     * @magentoApiDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @magentoApiDataFixture Magento/Catalog/_files/categories.php
     * @magentoConfigFixture default_store catalog/magento_catalogpermissions/enabled true
     */
    public function testUnassignCategories()
    {
        $sharedCatalog = $this->getSharedCatalog();
        $sharedCatalogId = $sharedCatalog->getId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf('/V1/sharedCatalog/%d/unassignCategories', $sharedCatalogId),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'unassignCategories',
            ],
        ];
        $categoriesParams = [
            [
                'id' => 10,
                'name' => 'Movable Position 2'
            ],
            [
                'id' => 11,
                'name' => 'Movable Position 3'
            ],
            [
                'id' => 12,
                'name' => 'Category 12'
            ]
        ];

        $categories = $this->getCategories();
        $this->assignCategories($sharedCatalog, $categories);
        $resp = $this->_webApiCall($serviceInfo, ['id' => $sharedCatalogId, 'categories' => $categoriesParams]);
        $this->assertTrue($resp);
        $assignedCategories = $this->retrieveAssignCategoryIds($sharedCatalog->getCustomerGroupId(), false);

        $this->assertEmpty($assignedCategories);
    }

    /**
     * Assign categories for testUnassignCategories, testGetCategories.
     *
     * @param \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog
     * @param \Magento\Catalog\Api\Data\CategoryInterface[] $categories
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
     * Get categories.
     *
     * @return \Magento\Catalog\Api\Data\CategoryInterface[]
     */
    private function getCategories()
    {
        /**
         * Category Ids from Magento/Catalog/_files/categories.php.
         */
        $categoryIds = [10, 11, 12];
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->objectManager->create(\Magento\Catalog\Model\ResourceModel\Category\Collection::class);
        $collection->addAttributeToFilter('entity_id', ['in' => $categoryIds]);
        return $collection->getItems();
    }

    /**
     * Retrieve assign category Ids.
     *
     * @param int $customerGroupId
     * @param bool $hasViewPermissions [optional]
     * @return array
     */
    private function retrieveAssignCategoryIds($customerGroupId, $hasViewPermissions = true)
    {
        $permissionCollection = $this->objectManager->create(
            \Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection::class
        );
        $permissionCollection->addFieldToFilter('customer_group_id', $customerGroupId);
        if (!$hasViewPermissions) {
            $permissionCollection->addFieldToFilter(
                'grant_catalog_category_view',
                ['neq' => \Magento\CatalogPermissions\Model\Permission::PERMISSION_DENY]
            );
        }
        $categoryIds = [];
        foreach ($permissionCollection as $permission) {
            $categoryIds[] = ['id' => $permission->getCategoryId()];
        }
        return $categoryIds;
    }

    /**
     * Retrieve assign category Ids for Shared Catalog.
     *
     * @param int $customerGroupId
     * @return array
     */
    private function retrieveAssignedCategoryIds($customerGroupId)
    {
        $permissionCollection = $this->objectManager->create(
            \Magento\SharedCatalog\Model\ResourceModel\Permission\Collection::class
        );
        $permissionCollection->addFieldToFilter('customer_group_id', $customerGroupId);
        $categoryIds = [];
        foreach ($permissionCollection as $permission) {
            $categoryIds[] = ['id' => $permission->getCategoryId()];
        }
        return $categoryIds;
    }
}
