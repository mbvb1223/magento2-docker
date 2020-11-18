<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Controller\Adminhtml\Index;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Message\ManagerInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Framework\App\Request\Http as HttpRequest;

/**
 * @magentoAppArea adminhtml
 */
class SaveTest extends AbstractBackendController
{
    private static $companyName = 'New Company';

    private static $companyEmail = 'company@test.com';

    private static $customerEmail = 'company.admin@test.com';

    /**
     * @inheritDoc
     */
    protected $resource = Save::ADMIN_RESOURCE;

    /**
     * @inheritDoc
     */
    protected $uri = 'backend/company/index/save';

    /**
     * @inheritDoc
     */
    protected $httpMethod = HttpRequest::METHOD_POST;

    /**
     * Checks if new B2B company can be created with enabled Website Restrictions.
     *
     * @magentoConfigFixture default_store general/restriction/is_active 1
     * @magentoConfigFixture default_store general/restriction/mode 1
     */
    public function testCreateCompanyWithWebsiteRestrictions()
    {
        $params = $this->getRequestData();
        $request = $this->getRequest();
        $request->setParams($params);
        $request->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('backend/company/index/save');

        $message = $this->getSuccessMessage();
        self::assertEquals('You have created company ' . self::$companyName . '.', $message);

        $customer = $this->getCustomer(self::$customerEmail);
        self::assertNotEmpty($customer);

        $company = $this->getCompany(self::$companyEmail);
        self::assertNotEmpty($company);

        $this->deleteCompany($company);
        $this->deleteCustomer($customer);
    }

    /**
     * Checks if new B2B company can be created with country address that is allowed on non-default website only.
     *
     * @magentoDataFixture Magento/Store/_files/websites_different_countries.php
     */
    public function testCreateCompanyWithCountryFromNonDefaultWebsite(): void
    {
        $params = $this->getRequestData();
        $request = $this->getRequest();
        $request->setParams($params);
        $request->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('backend/company/index/save');

        $message = $this->getSuccessMessage();
        self::assertEquals('You have created company ' . self::$companyName . '.', $message);

        $company = $this->getCompany(self::$companyEmail);
        self::assertNotEmpty($company);
    }

    /**
     * Gets request params.
     *
     * @return array
     */
    private function getRequestData(): array
    {
        return [
            'general' => [
                'company_name' => self::$companyName,
                'company_email' => self::$companyEmail,
                'sales_representative_id' => 1,
                'status' => 1,
            ],
            'address' => [
                'street' => ['6161 West Centinela Avenue'],
                'city' => 'Culver City',
                'postcode' => 90230,
                'country_id' => 'US',
                'region_id' => 12,
                'telephone' => '555-55-555-55'
            ],
            'company_admin' => [
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => self::$customerEmail,
                'gender' => 3,
                'website_id' => 1,
            ],
            'settings' => [
                'customer_group_id' => 1
            ]
        ];
    }

    /**
     * Gets success message after dispatching the controller.
     *
     * @return string|null
     */
    private function getSuccessMessage(): ?string
    {
        /** @var ManagerInterface $messageManager */
        $messageManager = $this->_objectManager->get(ManagerInterface::class);
        $messages = $messageManager->getMessages(true)->getItems();
        if ($messages) {
            return $messages[0]->getText();
        }

        return null;
    }

    /**
     * Gets customer entity by email.
     *
     * @param string $email
     * @return CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomer(string $email): CustomerInterface
    {
        /** @var CustomerRepositoryInterface $repository */
        $repository = $this->_objectManager->get(CustomerRepositoryInterface::class);
        return $repository->get($email);
    }

    /**
     * Deletes customer entity.
     *
     * @param CustomerInterface $customer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function deleteCustomer(CustomerInterface $customer)
    {
        /** @var CustomerRepositoryInterface $repository */
        $repository = $this->_objectManager->get(CustomerRepositoryInterface::class);
        $repository->delete($customer);
    }

    /**
     * Gets company entity by email.
     *
     * @param string $email
     * @return CompanyInterface
     */
    private function getCompany(string $email): CompanyInterface
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->_objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('company_email', $email)
            ->create();
        /** @var CompanyRepositoryInterface $repository */
        $repository = $this->_objectManager->get(CompanyRepositoryInterface::class);
        $items = $repository->getList($searchCriteria)
            ->getItems();

        return array_pop($items);
    }

    /**
     * Deletes company entity.
     *
     * @param CompanyInterface $company
     */
    private function deleteCompany(CompanyInterface $company)
    {
        /** @var CompanyRepositoryInterface $repository */
        $repository = $this->_objectManager->get(CompanyRepositoryInterface::class);
        $repository->delete($company);
    }
}
