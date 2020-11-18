<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickOrder\Plugin\AdvancedCheckout;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Model\Config as SharedCatalogConfig;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Test for CartPlugin class.
 *
 * @magentoDataFixture Magento/Catalog/_files/multiple_products.php
 * @magentoDataFixture Magento/Catalog/_files/simple_products_not_visible_individually.php
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CartPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\AdvancedCheckout\Model\Cart
     */
    private $cart;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->cart = $this->objectManager->create(\Magento\AdvancedCheckout\Model\Cart::class);

        $configResource = $this->objectManager->get(\Magento\Config\Model\ResourceModel\Config::class);
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $appConfig = $this->objectManager->get(\Magento\Framework\App\Config::class);

        $configResource->saveConfig(
            'btob/website_configuration/quickorder_active',
            1,
            'default',
            $storeManager->getDefaultStoreView()->getId()
        );
        $appConfig->clean();
    }

    /**
     * Test for method CheckItem.
     *
     * @dataProvider checkItemDataProvider
     * @param array $passedData
     * @param array $expectedItem
     * @return void
     */
    public function testCheckItems(array $passedData, array $expectedItem): void
    {
        unset($expectedItem['sc_code']);
        $this->cart->setContext(\Magento\AdvancedCheckout\Model\Cart::CONTEXT_FRONTEND);
        $result = $this->cart->checkItems([$passedData]);
        foreach ($result as $resultItem) {
            foreach ($expectedItem as $itemKey => $itemValue) {
                $this->assertEquals($itemValue, $resultItem[$itemKey]);
            }
        }
    }

    /**
     * @param array $passedData
     * @param array $expectedItem
     * @return void
     * @dataProvider checkItemDataProvider
     * @see \Magento\SharedCatalog\Plugin\AdvancedCheckout\Model\HideProductsAbsentInSharedCatalogPlugin::afterCheckItem
     */
    public function testCheckItemWithSharedCatalog(array $passedData, array $expectedItem): void
    {
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $website = $storeManager->getWebsite();
        $mutableConfig = $this->objectManager->get(MutableScopeConfigInterface::class);
        $mutableConfig->setValue(
            SharedCatalogConfig::CONFIG_SHARED_CATALOG,
            1,
            ScopeInterface::SCOPE_WEBSITE,
            $website->getCode()
        );

        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        try {
            $product = $productRepository->get($passedData['sku']);

            $sharedCatalogManagement = $this->objectManager->get(SharedCatalogManagementInterface::class);
            $sharedCatalog = $sharedCatalogManagement->getPublicCatalog();
            $productManagement = $this->objectManager->get(ProductManagementInterface::class);
            $productManagement->assignProducts($sharedCatalog->getId(), [$product]);
        } catch (NoSuchEntityException $e) {
        }

        $this->testCheckItems($passedData, $expectedItem);
    }

    /**
     * @param array $passedData
     * @param array $expectedItem
     * @return void
     * @dataProvider checkItemDataProvider
     * @see \Magento\SharedCatalog\Plugin\AdvancedCheckout\Model\HideProductsAbsentInSharedCatalogPlugin::afterCheckItem
     * @magentoConfigFixture current_store customer/create_account/auto_group_assign 1
     * @magentoConfigFixture current_store customer/create_account/default_group 2
     * @magentoDataFixture Magento/Catalog/_files/multiple_products.php
     * @magentoDataFixture Magento/SharedCatalog/_files/assigned_company.php
     */
    public function testCheckItemWithSharedCatalogAndCompany(array $passedData, array $expectedItem): void
    {
        $customerRegistry = $this->objectManager->get(CustomerRegistry::class);
        $customer = $customerRegistry->retrieveByEmail('email1@companyquote.com');
        $this->cart->setCustomer($customer);
        $expectedItem['code'] = $expectedItem['sc_code'];

        $this->testCheckItemWithSharedCatalog($passedData, $expectedItem);
    }

    /**
     * Check item with shared catalog and customer with not default group
     *
     * @param array $passedData
     * @param array $expectedItem
     * @return void
     * @dataProvider checkItemDataProvider
     * @see \Magento\SharedCatalog\Plugin\AdvancedCheckout\Model\HideProductsAbsentInSharedCatalogPlugin::afterCheckItem
     * @magentoConfigFixture current_store customer/create_account/auto_group_assign 1
     * @magentoConfigFixture current_store customer/create_account/default_group 2
     * @magentoDataFixture Magento/Catalog/_files/multiple_products.php
     * @magentoDataFixture Magento/SharedCatalog/_files/assigned_company.php
     */
    public function testCheckItemWithSharedCatalogAndCustomerWithNotDefaultGroup(
        array $passedData,
        array $expectedItem
    ): void {
        $customerRegistry = $this->objectManager->get(CustomerRegistry::class);
        $customer = $customerRegistry->retrieveByEmail('email1@companyquote.com');
        $customer->setGroupId(3);
        $this->cart->setCustomer($customer);
        $expectedItem['code'] = $expectedItem['sc_code'];
        $this->testCheckItemWithSharedCatalog($passedData, $expectedItem);
    }

    /**
     * @return array
     */
    public function checkItemDataProvider(): array
    {
        return [
            [
                [
                    'sku' => 'simple1',
                    'qty' => ''
                ],
                [
                    'qty' => (float)1,
                    'sku' => 'simple1',
                    'code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_SUCCESS,
                    'sc_code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_SUCCESS,
                ]
            ],
            [
                [
                    'sku' => 'simple1',
                    'qty' => (float)101,
                ],
                [
                    'qty' => (float)101,
                    'sku' => 'simple1',
                    'code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED,
                    'sc_code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED,
                ]
            ],
            [
                [
                    'sku' => 'simple3',
                    'qty' => ''
                ],
                [
                    'qty' => (float)1,
                    'sku' => 'simple3',
                    'code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_SKU,
                    'sc_code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_SKU,
                ]
            ],
            [
                [
                    'sku' => 'not_existing_product',
                    'qty' => ''
                ],
                [
                    'qty' => (float)1,
                    'sku' => 'not_existing_product',
                    'code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_SKU,
                    'sc_code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_SKU,
                ]
            ],
            [
                [
                    'sku' => 'simple_not_visible_1',
                    'qty' => '',
                ],
                [
                    'qty' => (float)1,
                    'sku' => 'simple_not_visible_1',
                    'code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_SUCCESS,
                    'sc_code' => \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_SKU,
                ],
            ],
        ];
    }
}
