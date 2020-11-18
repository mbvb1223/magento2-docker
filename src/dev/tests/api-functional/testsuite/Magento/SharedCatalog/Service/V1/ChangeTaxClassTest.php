<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Service\V1;

use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Class ChangeTaxClassTest.
 */
class ChangeTaxClassTest extends WebapiAbstract
{
    const RESOURCE_PATH = '/V1/sharedCatalog';

    const SERVICE_READ_NAME = 'sharedCatalogSharedCatalogRepositoryV1';

    const SERVICE_VERSION = 'V1';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    /**
     * Test changing shared catalog customer tax class.
     *
     * @return void
     * @magentoApiDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     */
    public function testInvoke()
    {
        $sharedCatalog = $this->getSharedCatalog();
        $sharedCatalogId = $sharedCatalog->getId();
        $taxClass = $this->getTaxClass();
        $taxClassId = $taxClass->getClassId();
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $sharedCatalogId,
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_PUT,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'Save',
            ],
        ];
        $params = [
            'sharedCatalog' => [
                'id' => $sharedCatalogId,
                'name' => $sharedCatalog->getName(),
                'description' => $sharedCatalog->getDescription(),
                'customerGroupId' => $sharedCatalog->getCustomerGroupId(),
                'type' => $sharedCatalog->getType(),
                'createdAt' => $sharedCatalog->getCreatedAt(),
                'createdBy' => $sharedCatalog->getCreatedBy(),
                'storeId' => $sharedCatalog->getStoreId(),
                'tax_class_id' => $taxClassId
            ]
        ];
        $this->_webApiCall($serviceInfo, $params);
        $updatedCustomerGroup = $this->getCustomerGroup();

        $this->assertEquals(
            $taxClassId,
            $updatedCustomerGroup->getData('tax_class_id'),
            'Shared catalog has wrong tax class value.'
        );
    }

    /**
     * Get shared catalog.
     *
     * @return \Magento\SharedCatalog\Model\SharedCatalog
     */
    private function getSharedCatalog()
    {
        /** @var \Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection $sharedCatalogCollection */
        $sharedCatalogCollection = $this->objectManager->get(
            \Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection::class
        );

        return $sharedCatalogCollection->getLastItem();
    }

    /**
     * Get tax class.
     *
     * @return \Magento\Tax\Model\ClassModel
     */
    private function getTaxClass()
    {
        /** @var \Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection $taxClassCollection */
        $taxClassCollection = $this->objectManager
            ->create(\Magento\Tax\Model\ResourceModel\TaxClass\Collection::class);

        return $taxClassCollection->getLastItem();
    }

    /**
     * Get customer group.
     *
     * @return \Magento\Customer\Model\Group
     */
    private function getCustomerGroup()
    {
        /** @var \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroupCollection */
        $customerGroupCollection = $this->objectManager
            ->create(\Magento\Customer\Model\ResourceModel\Group\Collection::class);

        return $customerGroupCollection->getLastItem();
    }
}
