<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Payment\Checks;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\MethodList as PaymentMethodList;
use Magento\PurchaseOrder\Model\Company\Config\RepositoryInterface as CompanyPoConfigRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

/**
 * Test class for the OfflinePayment check when retrieving available payment methods.
 *
 * @see \Magento\PurchaseOrder\Model\Payment\Checks\OfflinePayment
 * @see \Magento\Payment\Model\MethodList
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @magentoAppIsolation enabled
 * @magentoAppArea frontend
 */
class OfflinePaymentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentMethodList
     */
    private $paymentMethodList;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var CompanyPoConfigRepositoryInterface
     */
    private $companyPoConfigRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Session
     */
    private $session;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        $this->paymentMethodList = $objectManager->create(PaymentMethodList::class);
        $this->companyRepository = $objectManager->get(CompanyRepositoryInterface::class);
        $this->companyPoConfigRepository = $objectManager->get(CompanyPoConfigRepositoryInterface::class);
        $this->customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
        $this->quoteRepository = $objectManager->get(QuoteRepository::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->session = $objectManager->get(Session::class);

        // Enable company functionality at the website level
        $this->setWebsiteConfig('btob/website_configuration/company_active', true);

        // Enable purchase order functionality at the website level
        $this->setWebsiteConfig('btob/website_configuration/purchaseorder_enabled', true);
    }

    /**
     * Enable/Disable the configuration at the website level.
     *
     * magentoConfigFixture does not allow changing the value for website scope.
     *
     * @param string $path
     * @param bool $isEnabled
     */
    private function setWebsiteConfig(string $path, bool $isEnabled)
    {
        /** @var MutableScopeConfigInterface $scopeConfig */
        $scopeConfig = Bootstrap::getObjectManager()->get(MutableScopeConfigInterface::class);
        $scopeConfig->setValue(
            $path,
            $isEnabled ? '1' : '0',
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Enable/Disable purchase order functionality on a per company basis.
     *
     * @param string $companyName
     * @param bool $isEnabled
     * @throws LocalizedException
     */
    private function setCompanyPurchaseOrderConfig(string $companyName, bool $isEnabled)
    {
        $this->searchCriteriaBuilder->addFilter('company_name', $companyName);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $results = $this->companyRepository->getList($searchCriteria)->getItems();

        /** @var CompanyInterface $company */
        $company = reset($results);

        $companyConfig = $this->companyPoConfigRepository->get($company->getId());
        $companyConfig->setIsPurchaseOrderEnabled($isEnabled);

        $this->companyPoConfigRepository->save($companyConfig);
    }

    /**
     * Test that only offline payment methods are available for a company user when purchase orders are enabled.
     *
     * The available payment methods must be amongst those which are configured for the website and the company.
     *
     * @magentoConfigFixture current_store payment/banktransfer/active 1
     * @magentoConfigFixture current_store payment/cashondelivery/active 1
     * @magentoConfigFixture current_store payment/checkmo/active 1
     * @magentoConfigFixture current_store payment/companycredit/active 1
     * @magentoConfigFixture current_store payment/free/active 1
     * @magentoConfigFixture current_store payment/paypal_express/active 1
     * @magentoConfigFixture current_store payment/purchaseorder/active 1
     * @magentoConfigFixture current_store payment/fake/active 0
     * @magentoConfigFixture current_store payment/fake_vault/active 0
     * @magentoConfigFixture default/btob/default_b2b_payment_methods/applicable_payment_methods 0
     * @magentoDataFixture Magento/PurchaseOrder/_files/company_with_structure_and_purchase_orders.php
     * @dataProvider availablePaymentMethodsDataProvider
     * @param string $companyUserEmail
     * @param bool $companyPurchaseOrdersEnabled
     * @param string[] $expectedPaymentMethodCodes
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testOnlyOfflinePaymentMethodsAvailableWhenPurchaseOrdersEnabled(
        $companyUserEmail,
        $companyPurchaseOrdersEnabled,
        $expectedPaymentMethodCodes
    ) {
        // Enable/Disable purchase orders at the company level
        $this->setCompanyPurchaseOrderConfig('Magento', $companyPurchaseOrdersEnabled);

        // Login as a company user
        $companyUser = $this->customerRepository->get($companyUserEmail);
        $this->session->loginById($companyUser->getId());

        // Get the quote that the payment methods will apply to
        $quote = $this->getQuoteByCustomerId($companyUser->getId());

        $actualPaymentMethods = $this->paymentMethodList->getAvailableMethods($quote);
        $actualPaymentMethodCodes = [];

        /** @var MethodInterface $paymentMethod */
        foreach ($actualPaymentMethods as $paymentMethod) {
            $actualPaymentMethodCodes[] = $paymentMethod->getCode();
        }

        sort($actualPaymentMethodCodes);
        sort($expectedPaymentMethodCodes);

        $this->assertEquals($expectedPaymentMethodCodes, $actualPaymentMethodCodes);

        $this->session->logout();
    }

    /**
     * Get the quote for the specified customer by customerId.
     *
     * @param int $customerId
     * @return CartInterface|mixed
     */
    public function getQuoteByCustomerId($customerId)
    {
        $this->searchCriteriaBuilder->addFilter('customer_id', $customerId);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        /** @var CartInterface[] $items */
        $items = $this->quoteRepository->getList($searchCriteria)->getItems();
        $customerQuote = reset($items);

        return $customerQuote;
    }

    /**
     * @return array
     */
    public function availablePaymentMethodsDataProvider()
    {
        return [
            'company_admin_with_purchase_orders_enabled' => [
                'company_user_email' => 'john.doe@example.com',
                'company_purchase_orders_enabled' => true,
                'expected_payment_methods' => [
                    'banktransfer',
                    'cashondelivery',
                    'checkmo',
                    'companycredit',
                    'purchaseorder',
                    'free'
                ]
            ],
            'company_admin_with_purchase_orders_disabled' => [
                'company_user_email' => 'john.doe@example.com',
                'company_purchase_orders_enabled' => false,
                'expected_payment_methods' => [
                    'banktransfer',
                    'cashondelivery',
                    'checkmo',
                    'companycredit',
                    'paypal_express',
                    'paypal_express_bml',
                    'purchaseorder',
                    'free'
                ]
            ],
            'company_defaultuser_with_purchase_orders_enabled' => [
                'company_user_email' => 'veronica.costello@example.com',
                'company_purchase_orders_enabled' => true,
                'expected_payment_methods' => [
                    'banktransfer',
                    'cashondelivery',
                    'checkmo',
                    'companycredit',
                    'free',
                    'purchaseorder'
                ]
            ],
            'company_defaultuser_with_purchase_orders_disabled' => [
                'company_user_email' => 'veronica.costello@example.com',
                'company_purchase_orders_enabled' => false,
                'expected_payment_methods' => [
                    'banktransfer',
                    'cashondelivery',
                    'checkmo',
                    'companycredit',
                    'free',
                    'paypal_express',
                    'paypal_express_bml',
                    'purchaseorder'
                ]
            ]
        ];
    }
}
