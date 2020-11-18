<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Service\V1;

/**
 * Tests for shared catalog companies actions (assign, unassign, getting).
 */
class CompanyManagementTest extends AbstractSharedCatalogTest
{
    const SERVICE_READ_NAME = 'sharedCatalogCompanyManagementV1';

    const SERVICE_VERSION = 'V1';

    /**
     * Check list of company IDs for the companies assigned to the selected catalog.
     *
     * @return void
     * @magentoApiDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/companies.php
     */
    public function testGetCompanies()
    {
        $sharedCatalog = $this->getSharedCatalog();
        $sharedCatalogId = $sharedCatalog->getId();
        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf('/V1/sharedCatalog/%d/companies', $sharedCatalogId),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'getCompanies',
            ],
        ];
        $companies = $this->retrieveCompanies();
        /** @var \Magento\SharedCatalog\Api\CompanyManagementInterface $companyManagement */
        $companyManagement = $this->objectManager->create(\Magento\SharedCatalog\Api\CompanyManagementInterface::class);
        $companyManagement->assignCompanies($sharedCatalog->getId(), $companies);
        $respCompanyIds = $this->_webApiCall($serviceInfo, ['sharedCatalogId' => $sharedCatalogId]);
        $respCompanyIds = json_decode($respCompanyIds);
        $expectedCompanyIds = [];
        foreach ($companies as $company) {
            $expectedCompanyIds[] = $company->getId();
        }
        $this->assertEmpty(array_diff($respCompanyIds, $expectedCompanyIds), 'List of companies is wrong.');
    }

    /**
     * Test assign companies to shared catalog.
     *
     * @return void
     * @magentoApiDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/companies.php
     */
    public function testAssignCompanies()
    {
        $sharedCatalog = $this->getSharedCatalog();
        $sharedCatalogId = $sharedCatalog->getId();
        $companies = $this->retrieveCompanies();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf('/V1/sharedCatalog/%d/assignCompanies', $sharedCatalogId),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'assignCompanies',
            ],
        ];
        $companiesParam = $this->prepareCompaniesData($companies);
        $params = ['sharedCatalogId' => $sharedCatalogId, 'companies' => $companiesParam];
        $resp = $this->_webApiCall($serviceInfo, $params);
        $this->assertTrue($resp);
        $assignedCompanies = $this->retrieveCompanies($sharedCatalog->getCustomerGroupId());
        $this->assertTrue(
            $this->prepareItems($companies) == $this->prepareItems($assignedCompanies),
            'Companies are not assigned.'
        );
    }

    /**
     * Test unassign companies from custom shared catalog.
     *
     * @return void
     * @magentoApiDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/assigned_companies.php
     */
    public function testUnassignCompaniesFromCustomSharedCatalog()
    {
        $sharedCatalog = $this->getSharedCatalog();
        $resp = $this->unassignCompaniesWebApiCall($sharedCatalog);
        $this->assertTrue($resp);
        $assignedCompanies = $this->retrieveCompanies($sharedCatalog->getCustomerGroupId());
        $this->assertEmpty($assignedCompanies);
    }

    /**
     * Test unassign companies from public shared catalog.
     *
     * @return void
     * @magentoApiDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @magentoApiDataFixture Magento/SharedCatalog/_files/assigned_companies.php
     */
    public function testUnassignCompaniesFromPublicSharedCatalog()
    {
        $publicCatalog = $this->getPublicCatalog();
        $expectedMessage = 'You cannot unassign a company from the public shared catalog.';
        try {
            $this->unassignCompaniesWebApiCall($publicCatalog);
            $this->fail("Expected exception");
        } catch (\SoapFault $e) {
            $this->assertStringContainsString(
                $expectedMessage,
                $e->getMessage(),
                "SoapFault does not contain expected message."
            );
        } catch (\Exception $e) {
            $errorObj = $this->processRestExceptionResult($e);
            $this->assertEquals($expectedMessage, $errorObj['message']);
        }
    }

    /**
     * Perform Web API call to the system under test.
     *
     * @param \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog
     * @return bool
     */
    private function unassignCompaniesWebApiCall(\Magento\SharedCatalog\Api\Data\SharedCatalogInterface $sharedCatalog)
    {
        $sharedCatalogId = $sharedCatalog->getId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf('/V1/sharedCatalog/%d/unassignCompanies', $sharedCatalogId),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'unassignCompanies',
            ],
        ];

        $assignedCompanies = $this->retrieveCompanies($sharedCatalog->getCustomerGroupId());
        $companiesParam = $this->prepareCompaniesData($assignedCompanies);
        $params = ['sharedCatalogId' => $sharedCatalogId, 'companies' => $companiesParam];
        return $this->_webApiCall($serviceInfo, $params);
    }

    /**
     * Retrieve companies.
     *
     * @param int|null $customerGroupId [optional]
     * @return \Magento\Company\Api\Data\CompanyInterface[]
     */
    private function retrieveCompanies($customerGroupId = null)
    {
        $searchBuilder = $this->objectManager->get(
            \Magento\Framework\Api\SearchCriteriaBuilder::class
        );
        if ($customerGroupId === null) {
            $searchBuilder->setPageSize(5)->setCurrentPage(1);
        } else {
            $searchBuilder->addFilter('customer_group_id', $customerGroupId);
        }
        $sortOrderBuilder = $this->objectManager->create(\Magento\Framework\Api\SortOrderBuilder::class);
        $sortOrder = $sortOrderBuilder
            ->setField('entity_id')
            ->setDirection(\Magento\Framework\Api\SortOrder::SORT_DESC)
            ->create();
        $searchBuilder->addSortOrder($sortOrder);
        $companies = $this->objectManager->create(\Magento\Company\Api\CompanyRepositoryInterface::class);
        return $companies->getList($searchBuilder->create())->getItems();
    }

    /**
     * Prepare companies for Web API call.
     *
     * @param \Magento\Company\Api\Data\CompanyInterface[] $companies
     * @return array
     */
    private function prepareCompaniesData(array $companies)
    {
        $companiesParam = [];
        foreach ($companies as $item) {
            $company = [
                'id' => $item->getId(),
                'street' => $item->getStreet(),
                'sales_representative_id' => $item->getSalesRepresentativeId(),
                'reject_reason' => $item->getRejectReason(),
                'rejected_at' => $item->getRejectedAt(),
                'customer_group_id' => $item->getCustomerGroupId(),
                'super_user_id' => $item->getSuperUserId()
            ];
            $companiesParam[] = $company;
        }
        return $companiesParam;
    }
}
