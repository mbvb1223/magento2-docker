<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Controller\Adminhtml\Index;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Model\Company;
use Magento\CompanyCredit\Controller\Adminhtml\Index\Reimburse;
use Magento\Framework\Acl;
use Magento\Framework\Acl\Builder as AclBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 */
class EditTest extends AbstractBackendController
{
    /**
     * @inheritDoc
     */
    protected $resource = Edit::ADMIN_RESOURCE;

    /**
     * @inheritDoc
     */
    protected $uri = 'backend/company/index/edit';

    /**
     * @inheritDoc
     */
    protected $httpMethod = HttpRequest::METHOD_GET;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->acl = $this->_objectManager->get(AclBuilder::class)->getAcl();
    }

    /**
     * Tests that expected button is visible when the respective ACL resource is enabled on the role.
     *
     * @magentoDataFixture Magento/Company/_files/company.php
     * @magentoAppIsolation enabled
     * @dataProvider expectedButtonsDataProvider
     * @param string $aclResourceForButton
     * @param string $expectedStringForButton
     */
    public function testButtonVisibleWhenAclRoleIsEnabled(
        string $aclResourceForButton,
        string $expectedStringForButton
    ) {
        $this->acl->allow(null, $aclResourceForButton);

        $uriForCompanyEditPage = $this->buildUriForCompanyEditPage();
        $this->dispatch($uriForCompanyEditPage);
        $responseHtml = $this->getResponse()->getContent();

        $this->assertStringContainsString($expectedStringForButton, $responseHtml);
    }

    /**
     * Tests that the expected button is absent when the respective ACL resource is disabled on the role.
     *
     * @magentoDataFixture Magento/Company/_files/company.php
     * @magentoAppIsolation enabled
     * @dataProvider expectedButtonsDataProvider
     * @param string $aclResourceForButton
     * @param string $expectedStringForButton
     */
    public function testButtonNotVisibleWhenAclRoleIsDisabled(
        string $aclResourceForButton,
        string $expectedStringForButton
    ) {
        $this->acl->deny(null, $aclResourceForButton);

        $uriForCompanyEditPage = $this->buildUriForCompanyEditPage();
        $this->dispatch($uriForCompanyEditPage);
        $responseHtml = $this->getResponse()->getContent();

        $this->assertStringNotContainsString($expectedStringForButton, $responseHtml);
    }

    /**
     * @return array
     */
    public function expectedButtonsDataProvider()
    {
        return [
            'Save Button' => [Save::ADMIN_RESOURCE, 'save-button'],
            'Delete Button' => [Delete::ADMIN_RESOURCE, 'company-edit-delete-button'],
            'Reimburse Button' => [Reimburse::ADMIN_RESOURCE, 'company-edit-reimburse-button']
        ];
    }

    /**
     * Generates the edit page uri for the company created by the data fixture.
     * Takes the base uri and appends the company id.
     *
     * @return string
     */
    private function buildUriForCompanyEditPage()
    {
        return $this->uri . '/id/' . $this->getCompanyCreatedByFixture()->getId();
    }

    /**
     * Gets the company created by the data fixture.
     *
     * @return Company
     */
    private function getCompanyCreatedByFixture()
    {
        $searchCriteriaBuilder = $this->_objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->addFilter('company_name', 'Magento');
        $searchCriteria = $searchCriteriaBuilder->create();

        $companyRepository = $this->_objectManager->get(CompanyRepositoryInterface::class);
        $companySearchResults = $companyRepository->getList($searchCriteria);
        $items = $companySearchResults->getItems();

        /** @var Company $company */
        $company = reset($items);

        return $company;
    }
}
