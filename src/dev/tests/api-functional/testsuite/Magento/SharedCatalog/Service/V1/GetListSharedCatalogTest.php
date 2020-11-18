<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Service\V1;

/**
 * Test for shared catalog, getting list of shared catalogs and basic properties for each catalog.
 */
class GetListSharedCatalogTest extends AbstractSharedCatalogTest
{
    const SERVICE_READ_NAME = 'sharedCatalogSharedCatalogRepositoryV1';
    const SERVICE_VERSION = 'V1';
    const RESOURCE_PATH = '/V1/sharedCatalog';

    /**
     * Test for shared catalog, getting list of shared catalogs and basic properties for each catalog.
     *
     * @return void
     * @magentoApiDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     */
    public function testInvoke()
    {
        /** @var $searchCriteriaBuilder  \Magento\Framework\Api\SearchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->create(
            \Magento\Framework\Api\SearchCriteriaBuilder::class
        );
        $searchCriteriaBuilder->setPageSize(2);
        $searchData = $searchCriteriaBuilder->create();

        $requestData = ['searchCriteria' => $searchData->__toArray()];
        /** @var \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface $sharedCatalogRepository */
        $sharedCatalogRepository = $this->objectManager
            ->get(\Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface::class);
        $expectedListSharedCatalog = $sharedCatalogRepository->getList($searchData)->getItems();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '?' . http_build_query($requestData),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'getList',
            ],
        ];
        $searchResults = $this->_webApiCall($serviceInfo, $requestData);

        $searchResultsCatalogs = $searchResults['items'];
        foreach ($searchResultsCatalogs as $catalog) {
            $this->compareCatalogs($expectedListSharedCatalog[$catalog['id']], $catalog);
        }
    }
}
