<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Service\V1;

/**
 * Test for removing shared catalog.
 */
class DeleteSharedCatalogTest extends AbstractSharedCatalogTest
{
    const SERVICE_READ_NAME = 'sharedCatalogSharedCatalogRepositoryV1';
    const SERVICE_VERSION = 'V1';
    const RESOURCE_PATH = '/V1/sharedCatalog/%d';

    /**
     * Test for removing shared catalog.
     *
     * @return void
     * @magentoApiDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     */
    public function testInvoke()
    {
        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $this->expectExceptionMessage('Requested Shared Catalog is not found');

        /** @var \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog */
        $sharedCatalog = $this->getSharedCatalog();
        $sharedCatalogId = $sharedCatalog->getId();
        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf(self::RESOURCE_PATH, $sharedCatalogId),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_DELETE,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'deleteById',
            ],
        ];

        $respSharedCatalogData = $this->_webApiCall($serviceInfo, ['sharedCatalogId' => $sharedCatalogId]);
        $this->assertTrue($respSharedCatalogData, 'Shared Catalog is not deleted.');
        $this->sharedCatalogRepository->get($sharedCatalogId);
    }
}
