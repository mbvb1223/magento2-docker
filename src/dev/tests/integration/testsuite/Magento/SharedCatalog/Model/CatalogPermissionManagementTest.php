<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Model;

use Magento\CatalogPermissions\Model\Permission as CatalogPermission;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface as ConfigResourceInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Model\Config as SharedCatalogConfig;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 * @magentoDataFixture Magento/Store/_files/website.php
 * @magentoDataFixture Magento/Catalog/_files/category.php
 * @magentoDataFixture Magento/SharedCatalog/_files/shared_category_product.php
 * @magentoConfigFixture btob/website_configuration/company_active 1
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CatalogPermissionManagementTest extends TestCase
{
    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var ConfigResourceInterface
     */
    private $configResource;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var SharedCatalogManagementInterface
     */
    private $sharedCatalogManagement;

    /**
     * @var SharedCatalogInterface
     */
    private $publicCatalog;

    /**
     * @var CustomerGroupManagement
     */
    private $customerGroupManagement;

    /**
     * @var CatalogPermissionManagement
     */
    private $catalogPermissionManagement;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $this->websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
        $this->configResource = $objectManager->get(ConfigResourceInterface::class);
        $this->reinitableConfig = $objectManager->get(ReinitableConfigInterface::class);
        $this->sharedCatalogManagement = $objectManager->get(SharedCatalogManagementInterface::class);
        $this->publicCatalog = $this->sharedCatalogManagement->getPublicCatalog();
        $this->customerGroupManagement = $objectManager->get(CustomerGroupManagement::class);

        $this->catalogPermissionManagement = $objectManager->create(CatalogPermissionManagement::class);
    }

    /**
     * @return void
     */
    public function testGetSharedCatalogPermission()
    {
        $categoryId = 10;
        $websiteId = (int) $this->websiteRepository->get('test')->getId();
        $groupId = (int) $this->publicCatalog->getCustomerGroupId();

        $sharedCatalogPermission = $this->catalogPermissionManagement->getSharedCatalogPermission(
            $categoryId,
            $websiteId,
            $groupId
        );
        $this->assertNull($sharedCatalogPermission->getPermission());

        $newSharedCatalogPermission = Bootstrap::getObjectManager()->create(Permission::class);
        $newSharedCatalogPermission->setCategoryId($categoryId);
        $newSharedCatalogPermission->setWebsiteId(null);
        $newSharedCatalogPermission->setCustomerGroupId($groupId);
        $newSharedCatalogPermission->setPermission(CatalogPermission::PERMISSION_DENY);
        $newSharedCatalogPermission->save();
        foreach ([null, $websiteId] as $scopeId) {
            $sharedCatalogPermission = $this->catalogPermissionManagement->getSharedCatalogPermission(
                $categoryId,
                $scopeId,
                $groupId
            );
            $this->assertEquals(CatalogPermission::PERMISSION_DENY, $sharedCatalogPermission->getPermission());
        }
    }

    /**
     * @depends testGetSharedCatalogPermission
     * @dataProvider scopesDataProvider
     * @param string|null $scopeCode
     * @return void
     */
    public function testSetPermissionsForAllCategories($scopeCode)
    {
        $scope = $scopeCode
            ? ScopeInterface::SCOPE_WEBSITES
            : ReinitableConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeId = $scopeCode
            ? (int) $this->websiteRepository->get($scopeCode)->getId()
            : null;
        $this->configResource->saveConfig(SharedCatalogConfig::CONFIG_SHARED_CATALOG, 1, $scope, (int) $scopeId);
        $this->reinitableConfig->reinit();
        $this->catalogPermissionManagement->setPermissionsForAllCategories($scopeId);

        $groupIdsNotInSharedCatalogs = $this->customerGroupManagement->getGroupIdsNotInSharedCatalogs();
        foreach ($groupIdsNotInSharedCatalogs as $groupId) {
            foreach ([10, 333] as $categoryId) {
                $sharedCatalogPermission = $this->catalogPermissionManagement->getSharedCatalogPermission(
                    $categoryId,
                    $scopeId,
                    (int) $groupId
                );
                $this->assertNull($sharedCatalogPermission->getPermission());
            }
        }

        $publicGroupsId = [
            GroupInterface::NOT_LOGGED_IN_ID,
            (int) $this->publicCatalog->getCustomerGroupId(),
        ];
        foreach ($publicGroupsId as $groupId) {
            foreach ([10, 333] as $categoryId) {
                $sharedCatalogPermission = $this->catalogPermissionManagement->getSharedCatalogPermission(
                    $categoryId,
                    $scopeId,
                    $groupId
                );
                $this->assertEquals(CatalogPermission::PERMISSION_DENY, $sharedCatalogPermission->getPermission());
            }
        }

        $sharedCatalogGroupIds = $this->customerGroupManagement->getSharedCatalogGroupIds();
        $customGroupsId = array_diff($sharedCatalogGroupIds, $publicGroupsId);
        foreach ($customGroupsId as $groupId) {
            $sharedCatalogPermission = $this->catalogPermissionManagement->getSharedCatalogPermission(
                10,
                $scopeId,
                (int) $groupId
            );
            $this->assertEquals(CatalogPermission::PERMISSION_ALLOW, $sharedCatalogPermission->getPermission());

            $sharedCatalogPermission = $this->catalogPermissionManagement->getSharedCatalogPermission(
                333,
                $scopeId,
                (int) $groupId
            );
            $this->assertEquals(CatalogPermission::PERMISSION_DENY, $sharedCatalogPermission->getPermission());
        }
    }

    /**
     * @return void
     */
    public function testSetDenyPermissionsForCategory()
    {
        $categoryId = 10;

        $this->catalogPermissionManagement->setDenyPermissionsForCategory($categoryId);
        foreach ($this->customerGroupManagement->getSharedCatalogGroupIds() as $groupId) {
            $sharedCatalogPermission = $this->catalogPermissionManagement->getSharedCatalogPermission(
                $categoryId,
                null,
                (int) $groupId
            );
            $this->assertEquals(CatalogPermission::PERMISSION_DENY, $sharedCatalogPermission->getPermission());
        }
        foreach ($this->customerGroupManagement->getGroupIdsNotInSharedCatalogs() as $groupId) {
            $sharedCatalogPermission = $this->catalogPermissionManagement->getSharedCatalogPermission(
                $categoryId,
                null,
                (int) $groupId
            );
            $this->assertNull($sharedCatalogPermission->getPermission());
        }
    }

    /**
     * @return void
     */
    public function testSetAllowPermissions()
    {
        $categoryIds = [10, 333];
        $groupIds = $this->customerGroupManagement->getSharedCatalogGroupIds();
        $this->catalogPermissionManagement->setAllowPermissions($categoryIds, $groupIds);
        foreach ($categoryIds as $categoryId) {
            foreach ($groupIds as $groupId) {
                $sharedCatalogPermission = $this->catalogPermissionManagement->getSharedCatalogPermission(
                    $categoryId,
                    null,
                    (int) $groupId
                );
                $this->assertEquals(CatalogPermission::PERMISSION_ALLOW, $sharedCatalogPermission->getPermission());
            }
        }
    }

    /**
     * @return void
     */
    public function testSetDenyPermissions()
    {
        $categoryIds = [10, 333];
        $groupIds = $this->customerGroupManagement->getSharedCatalogGroupIds();

        $this->catalogPermissionManagement->setDenyPermissions($categoryIds, $groupIds);
        foreach ($categoryIds as $categoryId) {
            foreach ($groupIds as $groupId) {
                $sharedCatalogPermission = $this->catalogPermissionManagement->getSharedCatalogPermission(
                    $categoryId,
                    null,
                    (int) $groupId
                );
                $this->assertEquals(CatalogPermission::PERMISSION_DENY, $sharedCatalogPermission->getPermission());
            }
        }
    }

    /**
     * @depends testSetAllowPermissions
     * @return void
     */
    public function testRemoveAllPermissions()
    {
        $categoryIds = [10, 333];
        $groupId = (int) $this->publicCatalog->getCustomerGroupId();

        $this->catalogPermissionManagement->setAllowPermissions($categoryIds, [$groupId]);
        $this->catalogPermissionManagement->removeAllPermissions($groupId);
        foreach ($categoryIds as $categoryId) {
            $sharedCatalogPermission = $this->catalogPermissionManagement->getSharedCatalogPermission(
                $categoryId,
                null,
                $groupId
            );
            $this->assertNull($sharedCatalogPermission->getPermission());
        }
    }

    /**
     * @return array
     */
    public function scopesDataProvider(): array
    {
        return [
            'Global scope' => [null],
            'Main website scope' => ['base'],
            'Second website scope' => ['test'],
        ];
    }
}
