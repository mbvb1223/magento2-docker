<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Model\Price;

use Magento\Catalog\Api\Data\TierPriceInterface;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog_products_with_tier_price.php
 * @magentoAppIsolation enabled
 */
class TierPriceFetcherTest extends TestCase
{
    /**
     * @var TierPriceFetcher
     */
    private $tierPriceFetcher;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->tierPriceFetcher = Bootstrap::getObjectManager()->create(TierPriceFetcher::class);
    }

    /**
     * @dataProvider fetchDataProvider
     * @param array $skuList
     * @param int $tierPricesCount
     */
    public function testFetch(array $skuList, int $tierPricesCount)
    {
        $sharedCatalogManagement = Bootstrap::getObjectManager()->get(SharedCatalogManagementInterface::class);
        $sharedCatalog = $sharedCatalogManagement->getPublicCatalog();

        /** @var TierPriceInterface[] $tierPrices */
        $tierPrices = \iterator_to_array($this->tierPriceFetcher->fetch($sharedCatalog, $skuList));
        $this->assertCount($tierPricesCount, $tierPrices);
        foreach ($tierPrices as $tierPrice) {
            $this->assertInstanceOf(TierPriceInterface::class, $tierPrice);
            $this->assertTrue(\in_array($tierPrice->getSku(), $skuList, true));
        }
    }

    /**
     * @return array
     */
    public function fetchDataProvider(): array
    {
        return [
            [
                ['simple_product_1'],
                2,
            ],
            [
                ['simple_product_1', 'simple_product_2'],
                3,
            ],
            [
                ['simple_product_3'],
                0,
            ],
        ];
    }
}
