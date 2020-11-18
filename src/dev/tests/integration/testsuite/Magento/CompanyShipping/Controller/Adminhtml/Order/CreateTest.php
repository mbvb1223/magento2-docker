<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Controller\Adminhtml\Order;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Backend\Model\Session\Quote as SessionQuote;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\CompanyShipping\Model\CompanyShippingMethodFactory;
use Magento\CompanyShipping\Model\Source\CompanyApplicableShippingMethod;
use Magento\Config\Model\Config\Factory as ConfigFactory;

/**
 * Test Class for B2B shipping method settings by admin create order flow
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateTest extends AbstractBackendController
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var CompanyShippingMethodFactory
     */
    private $companyShippingMethodFactory;

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    protected $uri = 'backend/sales/order_create';

    protected $resource = 'Magento_Sales::create';

    /**
     * @inheritDoc
     *
     * @throws AuthenticationException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->cartRepository = $this->_objectManager->get(CartRepositoryInterface::class);
        $this->customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
        $this->companyRepository = $this->_objectManager->get(CompanyRepositoryInterface::class);
        $this->companyShippingMethodFactory = $this->_objectManager->get(CompanyShippingMethodFactory::class);
        $this->configFactory = $this->_objectManager->get(ConfigFactory::class);
    }

    /**
     * Test available shipping rates for non company customer quote by admin create order with:
     * B2B applicable shipping methods enabled
     * B2B applicable shipping methods is: free shipping
     * Global sales shipping methods are: free shipping, flat rate, table rate
     *
     * @param array $configData
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoDataFixture Magento/OfflineShipping/_files/tablerates.php
     * @dataProvider shippingConfigDataProviderWithSelectedShippingMethodsEnabled
     */
    public function testLoadBlockShippingMethodWithNonCompanyCustomerWithB2BApplicableShippingMethod(
        $configData
    ) {
        $this->setConfigValues($configData);
        $quote = $this->cartRepository->getForCustomer(1);
        $customer = $quote->getCustomer();
        $session = $this->_objectManager->get(SessionQuote::class);
        $session->setQuoteId($quote->getId());

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(
            [
                'customer_id' => $customer->getId(),
                'collect_shipping_rates' => 1,
                'store_id' => 1,
                'json' => true
            ]
        );
        $this->dispatch('backend/sales/order_create/loadBlock/block/shipping_method');
        $body = $this->getResponse()->getBody();

        $this->assertStringContainsString('freeshipping_freeshipping', $body);
        $this->assertStringContainsString('flatrate_flatrate', $body);
        $this->assertStringContainsString('tablerate_bestway', $body);
    }

    /**
     * Test available shipping rates for company customer quote by admin create order with:
     * Company B2B shipping methods is B2BShippingMethods
     * B2B settings selected shipping methods enabled
     * B2B settings selected shipping methods are: free shipping, table rate
     * Global sales shipping methods are: free shipping, flat rate shipping
     *
     * @param array $configData
     *
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoDataFixture Magento/OfflineShipping/_files/tablerates.php
     * @dataProvider shippingConfigDataProviderWithSelectedShippingMethodsEnabled
     */
    public function testLoadBlockShippingMethodWithCompanyCustomerB2BShippingMethodsAndSelectedShippingMethods(
        $configData
    ) {
        $this->setConfigValues($configData);
        $quote = $this->cartRepository->getForCustomer(1);
        //replace quote customer to company customer
        $companyCustomer = $this->customerRepository->get('alex.smith@example.com');

        $company = $this->companyRepository->get(
            $companyCustomer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId()
        );

        $companyShippingSettings = $this->companyShippingMethodFactory->create()->addData(
            [
                'company_id' => $company->getId(),
                'applicable_shipping_method' => CompanyApplicableShippingMethod::B2B_SHIPPING_METHODS_VALUE,
                'use_config_settings' => 0
            ]
        );
        $companyShippingSettings->save();

        $quote->setCustomerId($companyCustomer->getId());
        $quote->setCustomer($companyCustomer);
        $quote->save();

        $session = $this->_objectManager->get(SessionQuote::class);
        $session->setQuoteId($quote->getId());

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(
            [
                'customer_id' => $companyCustomer->getId(),
                'collect_shipping_rates' => 1,
                'store_id' => 1,
                'json' => true
            ]
        );
        $this->dispatch('backend/sales/order_create/loadBlock/block/shipping_method');
        $body = $this->getResponse()->getBody();

        $this->assertStringContainsString('freeshipping_freeshipping', $body);
        $this->assertStringContainsString('tablerate_bestway', $body);
        $this->assertStringNotContainsString('flatrate_flatrate', $body);
    }

    /**
     * Test available shipping rates for company customer quote by admin create order with:
     * Company B2B shipping methods is ALL Shipping Methods
     * B2B settings selected shipping methods enabled
     * B2B settings selected shipping methods are: free shipping, table rate
     * Global sales shipping methods are: free shipping, flat rate shipping
     *
     * @param array $configData
     *
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoDataFixture Magento/OfflineShipping/_files/tablerates.php
     * @dataProvider shippingConfigDataProviderWithSelectedShippingMethodsEnabled
     */
    public function testLoadBlockShippingMethodWithCompanyCustomerAllShippingMethodsAndSelectedShippingMethods(
        $configData
    ) {
        $this->setConfigValues($configData);
        $quote = $this->cartRepository->getForCustomer(1);
        //replace quote customer to company customer
        $companyCustomer = $this->customerRepository->get('alex.smith@example.com');

        $company = $this->companyRepository->get(
            $companyCustomer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId()
        );

        $companyShippingSettings = $this->companyShippingMethodFactory->create()->addData(
            [
                'company_id' => $company->getId(),
                'applicable_shipping_method' => CompanyApplicableShippingMethod::ALL_SHIPPING_METHODS_VALUE,
                'use_config_settings' => 0
            ]
        );
        $companyShippingSettings->save();

        $quote->setCustomerId($companyCustomer->getId());
        $quote->setCustomer($companyCustomer);
        $quote->save();

        $session = $this->_objectManager->get(SessionQuote::class);
        $session->setQuoteId($quote->getId());

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(
            [
                'customer_id' => $companyCustomer->getId(),
                'collect_shipping_rates' => 1,
                'store_id' => 1,
                'json' => true
            ]
        );
        $this->dispatch('backend/sales/order_create/loadBlock/block/shipping_method');
        $body = $this->getResponse()->getBody();

        $this->assertStringContainsString('freeshipping_freeshipping', $body);
        $this->assertStringContainsString('tablerate_bestway', $body);
        $this->assertStringContainsString('flatrate_flatrate', $body);
    }

    /**
     * Test available shipping rates for company customer quote by admin create order with:
     * Company B2B shipping methods is default
     * B2B settings selected shipping methods enabled
     * B2B settings selected shipping methods are: free shipping, table rate
     * Global sales shipping methods are: free shipping, flat rate shipping, table rate
     *
     * @param array $configData
     *
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoDataFixture Magento/OfflineShipping/_files/tablerates.php
     * @dataProvider shippingConfigDataProviderWithSelectedShippingMethodsEnabled
     */
    public function testLoadBlockShippingMethodWithCompanyCustomerDefaultB2BShippingMethodsAndSelectedShippingMethods(
        $configData
    ) {
        $this->setConfigValues($configData);
        $quote = $this->cartRepository->getForCustomer(1);
        //replace quote customer to company customer
        $companyCustomer = $this->customerRepository->get('alex.smith@example.com');

        $quote->setCustomerId($companyCustomer->getId());
        $quote->setCustomer($companyCustomer);
        $quote->save();

        $session = $this->_objectManager->get(SessionQuote::class);
        $session->setQuoteId($quote->getId());

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(
            [
                'customer_id' => $companyCustomer->getId(),
                'collect_shipping_rates' => 1,
                'store_id' => 1,
                'json' => true
            ]
        );
        $this->dispatch('backend/sales/order_create/loadBlock/block/shipping_method');
        $body = $this->getResponse()->getBody();

        $this->assertStringContainsString('freeshipping_freeshipping', $body);
        $this->assertStringContainsString('tablerate_bestway', $body);
        $this->assertStringNotContainsString('flatrate_flatrate', $body);
    }

    /**
     * Test available shipping rates for company customer quote by admin create order with:
     * B2B applicable shipping methods enabled
     * B2B applicable shipping methods are: free shipping
     * Global sales shipping methods free shipping is disabled
     *
     * @param array $configData
     *
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoDataFixture Magento/OfflineShipping/_files/tablerates.php
     * @dataProvider shippingConfigDataProviderWithSelectedDisabledShippingMethods
     */
    public function testLoadBlockShippingWithCompanyCustomerB2BApplicableShippingMethodsAndDisabledAvailableShipping(
        $configData
    ) {
        $this->setConfigValues($configData);
        $quote = $this->cartRepository->getForCustomer(1);
        //replace quote customer to company customer
        $companyCustomer = $this->customerRepository->get('alex.smith@example.com');
        $quote->setCustomerId($companyCustomer->getId());
        $quote->setCustomer($companyCustomer);
        $quote->save();

        $session = $this->_objectManager->get(SessionQuote::class);
        $session->setQuoteId($quote->getId());

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(
            [
                'customer_id' => $companyCustomer->getId(),
                'collect_shipping_rates' => 1,
                'store_id' => 1,
                'json' => true
            ]
        );
        $this->dispatch('backend/sales/order_create/loadBlock/block/shipping_method');
        $body = $this->getResponse()->getBody();

        $this->assertStringContainsString('Sorry, no quotes are available for this order.', $body);
    }

    /**
     * Test available shipping rates for non company quote by admin create order with:
     * Company B2B shipping methods is B2BShippingMethods
     * B2B settings selected shipping methods enabled
     * B2B settings selected shipping methods are: free shipping, table rate
     * Global sales shipping methods are: free shipping, flat rate, table rate
     *
     * @param array $configData
     *
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoDataFixture Magento/OfflineShipping/_files/tablerates.php
     * @dataProvider shippingConfigDataProviderWithSelectedShippingMethodsEnabled
     */
    public function testLoadBlockShippingMethodWithNonCompanyCustomerB2BShippingMethodsAndSelectedShippingMethods(
        $configData
    ) {
        $this->setConfigValues($configData);
        $quote = $this->cartRepository->getForCustomer(1);
        //replace quote customer to company customer
        $companyCustomer = $this->customerRepository->get('alex.smith@example.com');

        $company = $this->companyRepository->get(
            $companyCustomer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId()
        );

        $companyShippingSettings = $this->companyShippingMethodFactory->create()->addData(
            [
                'company_id' => $company->getId(),
                'applicable_shipping_method' => CompanyApplicableShippingMethod::B2B_SHIPPING_METHODS_VALUE,
                'use_config_settings' => 0
            ]
        );
        $companyShippingSettings->save();

        $session = $this->_objectManager->get(SessionQuote::class);
        $session->setQuoteId($quote->getId());

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(
            [
                'customer_id' => 1,
                'collect_shipping_rates' => 1,
                'store_id' => 1,
                'json' => true
            ]
        );
        $this->dispatch('backend/sales/order_create/loadBlock/block/shipping_method');
        $body = $this->getResponse()->getBody();

        $this->assertStringContainsString('freeshipping_freeshipping', $body);
        $this->assertStringContainsString('tablerate_bestway', $body);
        $this->assertStringContainsString('flatrate_flatrate', $body);
    }

    /**
     * Test available shipping rates for company customer quote by admin create order with:
     * Company B2B shipping methods is selected shipping methods: free shipping
     * B2B settings selected shipping methods enabled
     * B2B settings selected shipping methods are: free shipping, table rate
     * Global sales shipping methods are: free shipping, flat rate shipping
     *
     * @param array $configData
     *
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoDataFixture Magento/OfflineShipping/_files/tablerates.php
     * @dataProvider shippingConfigDataProviderWithSelectedShippingMethodsEnabled
     */
    public function testLoadBlockShippingMethodWithCompanyCustomerSelectedShippingMethodsAndB2BSelectedShippingMethods(
        $configData
    ) {
        $this->setConfigValues($configData);
        $quote = $this->cartRepository->getForCustomer(1);
        //replace quote customer to company customer
        $companyCustomer = $this->customerRepository->get('alex.smith@example.com');

        $company = $this->companyRepository->get(
            $companyCustomer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId()
        );

        $companyShippingSettings = $this->companyShippingMethodFactory->create()->addData(
            [
                'company_id' => $company->getId(),
                'applicable_shipping_method' => CompanyApplicableShippingMethod::SELECTED_SHIPPING_METHODS_VALUE,
                'available_shipping_methods' => 'freeshipping',
                'use_config_settings' => 0
            ]
        );
        $companyShippingSettings->save();

        $quote->setCustomerId($companyCustomer->getId());
        $quote->setCustomer($companyCustomer);
        $quote->save();

        $session = $this->_objectManager->get(SessionQuote::class);
        $session->setQuoteId($quote->getId());

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(
            [
                'customer_id' => $companyCustomer->getId(),
                'collect_shipping_rates' => 1,
                'store_id' => 1,
                'json' => true
            ]
        );
        $this->dispatch('backend/sales/order_create/loadBlock/block/shipping_method');
        $body = $this->getResponse()->getBody();

        $this->assertStringContainsString('freeshipping_freeshipping', $body);
        $this->assertStringNotContainsString('tablerate_bestway', $body);
        $this->assertStringNotContainsString('flatrate_flatrate', $body);
    }

    /**
     * Test available shipping rates for different company customer quote by admin create order with:
     * Company B2B shipping methods is selected shipping methods: free shipping
     * B2B settings selected shipping methods enabled
     * B2B settings selected shipping methods are: free shipping, table rate
     * Global sales shipping methods are: free shipping, flat rate shipping
     *
     * @param array $configData
     *
     * @magentoDataFixture Magento/Company/_files/company_with_structure.php
     * @magentoDataFixture Magento/Company/_files/company.php
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoDataFixture Magento/OfflineShipping/_files/tablerates.php
     * @dataProvider shippingConfigDataProviderWithSelectedShippingMethodsEnabled
     */
    public function testLoadBlockShippingMethodWithDiffCustomerB2BShippingMethodsAndB2BSelectedShippingMethods(
        $configData
    ) {
        $this->setConfigValues($configData);
        $quote = $this->cartRepository->getForCustomer(1);
        //replace quote customer to company customer
        $companyCustomer = $this->customerRepository->get('alex.smith@example.com');
        $diffCompanyCustomer = $this->customerRepository->get('admin@magento.com');

        $company = $this->companyRepository->get(
            $companyCustomer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId()
        );

        $companyShippingSettings = $this->companyShippingMethodFactory->create()->addData(
            [
                'company_id' => $company->getId(),
                'applicable_shipping_method' => CompanyApplicableShippingMethod::SELECTED_SHIPPING_METHODS_VALUE,
                'available_shipping_methods' => 'freeshipping',
                'use_config_settings' => 0
            ]
        );
        $companyShippingSettings->save();

        $quote->setCustomerId($diffCompanyCustomer->getId());
        $quote->setCustomer($diffCompanyCustomer);
        $quote->save();

        $session = $this->_objectManager->get(SessionQuote::class);
        $session->setQuoteId($quote->getId());

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(
            [
                'customer_id' => $diffCompanyCustomer->getId(),
                'collect_shipping_rates' => 1,
                'store_id' => 1,
                'json' => true
            ]
        );
        $this->dispatch('backend/sales/order_create/loadBlock/block/shipping_method');
        $body = $this->getResponse()->getBody();

        $this->assertStringContainsString('freeshipping_freeshipping', $body);
        $this->assertStringContainsString('tablerate_bestway', $body);
        $this->assertStringNotContainsString('flatrate_flatrate', $body);
    }

    /**
     * Config data provider with B2B All Shipping Methods Enabled
     * @return array
     */
    public function shippingConfigDataProviderWithAllShippingMethodsEnabled()
    {
        return [
            'defaultScope' => [
                'config_data' => [
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT => [
                        '' => [
                            'carriers/flatrate/active' => '1',
                            'carriers/freeshipping/active' => '1',
                            'carriers/tablerate/active' => '1',
                            'carriers/tablerate/condition_name' => 'package_qty',
                            'btob/default_b2b_shipping_methods/applicable_shipping_methods' => 0,
                            'btob/order_approval/purchaseorder_active' => 0
                        ]
                    ],
                ]
            ],
            'defaultScopeWithPurchaseOrderEnabled' => [
                'config_data' => [
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT => [
                        '' => [
                            'carriers/flatrate/active' => '1',
                            'carriers/freeshipping/active' => '1',
                            'carriers/tablerate/active' => '1',
                            'carriers/tablerate/condition_name' => 'package_qty',
                            'btob/default_b2b_shipping_methods/applicable_shipping_methods' => 0,
                            'btob/order_approval/purchaseorder_active' => 1
                        ]
                    ],
                ]
            ],
            'websiteScope' => [
                'config_data' => [
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT => [
                        '' => [
                            'carriers/flatrate/active' => '0',
                            'carriers/freeshipping/active' => '0',
                            'carriers/tablerate/active' => '0',
                            'carriers/tablerate/condition_name' => 'package_qty',
                            'btob/default_b2b_shipping_methods/applicable_shipping_methods' => 0,
                            'btob/order_approval/purchaseorder_active' => 0
                        ]
                    ],
                    ScopeInterface::SCOPE_WEBSITES => [
                        'base' => [
                            'carriers/flatrate/active' => '1',
                            'carriers/freeshipping/active' => '1',
                            'carriers/tablerate/active' => '1',
                            'carriers/tablerate/condition_name' => 'package_qty',
                        ]
                    ],
                ]
            ],
            'websiteScopeWithPurchaseOrderEnabled' => [
                'config_data' => [
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT => [
                        '' => [
                            'carriers/flatrate/active' => '0',
                            'carriers/freeshipping/active' => '0',
                            'carriers/tablerate/active' => '0',
                            'carriers/tablerate/condition_name' => 'package_qty',
                            'btob/default_b2b_shipping_methods/applicable_shipping_methods' => 0,
                            'btob/order_approval/purchaseorder_active' => 1
                        ]
                    ],
                    ScopeInterface::SCOPE_WEBSITES => [
                        'base' => [
                            'carriers/flatrate/active' => '1',
                            'carriers/freeshipping/active' => '1',
                            'carriers/tablerate/active' => '1',
                            'carriers/tablerate/condition_name' => 'package_qty',
                        ]
                    ],
                ]
            ]
        ];
    }

    /**
     * Config data provider with B2B Selected Shipping Methods Enabled
     * @return array
     */
    public function shippingConfigDataProviderWithSelectedShippingMethodsEnabled()
    {
        return [
            'defaultScope' => [
                'config_data' => [
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT => [
                        '' => [
                            'carriers/flatrate/active' => '1',
                            'carriers/freeshipping/active' => '1',
                            'carriers/tablerate/active' => '1',
                            'carriers/tablerate/condition_name' => 'package_qty',
                            'btob/default_b2b_shipping_methods/applicable_shipping_methods' => 1,
                            'btob/default_b2b_shipping_methods/available_shipping_methods' => 'freeshipping,tablerate',
                            'btob/order_approval/purchaseorder_active' => 0
                        ]
                    ],
                ]
            ],
            'defaultScopeWithPurchaseOrderEnabled' => [
                'config_data' => [
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT => [
                        '' => [
                            'carriers/flatrate/active' => '1',
                            'carriers/freeshipping/active' => '1',
                            'carriers/tablerate/active' => '1',
                            'carriers/tablerate/condition_name' => 'package_qty',
                            'btob/default_b2b_shipping_methods/applicable_shipping_methods' => 1,
                            'btob/default_b2b_shipping_methods/available_shipping_methods' => 'freeshipping,tablerate',
                            'btob/order_approval/purchaseorder_active' => 1
                        ]
                    ],
                ]
            ],
            'websiteScope' => [
                'config_data' => [
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT => [
                        '' => [
                            'carriers/flatrate/active' => '0',
                            'carriers/freeshipping/active' => '0',
                            'carriers/tablerate/active' => '0',
                            'carriers/tablerate/condition_name' => 'package_qty',
                            'btob/default_b2b_shipping_methods/applicable_shipping_methods' => 1,
                            'btob/default_b2b_shipping_methods/available_shipping_methods' => 'freeshipping,tablerate',
                            'btob/order_approval/purchaseorder_active' => 0
                        ]
                    ],
                    ScopeInterface::SCOPE_WEBSITES => [
                        'base' => [
                            'carriers/flatrate/active' => '1',
                            'carriers/freeshipping/active' => '1',
                            'carriers/tablerate/active' => '1',
                            'carriers/tablerate/condition_name' => 'package_qty',
                        ]
                    ],
                ]
            ],
            'websiteScopeWithPurchaseOrderEnabled' => [
                'config_data' => [
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT => [
                        '' => [
                            'carriers/flatrate/active' => '0',
                            'carriers/freeshipping/active' => '0',
                            'carriers/tablerate/active' => '0',
                            'carriers/tablerate/condition_name' => 'package_qty',
                            'btob/default_b2b_shipping_methods/applicable_shipping_methods' => 1,
                            'btob/default_b2b_shipping_methods/available_shipping_methods' => 'freeshipping,tablerate',
                            'btob/order_approval/purchaseorder_active' => 1
                        ]
                    ],
                    ScopeInterface::SCOPE_WEBSITES => [
                        'base' => [
                            'carriers/flatrate/active' => '1',
                            'carriers/freeshipping/active' => '1',
                            'carriers/tablerate/active' => '1',
                            'carriers/tablerate/condition_name' => 'package_qty',
                        ]
                    ],
                ]
            ]
        ];
    }

    /**
     * Config data provider with B2B Selected Disabled Shipping Methods Enabled
     * @return array
     */
    public function shippingConfigDataProviderWithSelectedDisabledShippingMethods()
    {
        return [
            'defaultScope' => [
                'config_data' => [
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT => [
                        '' => [
                            'carriers/flatrate/active' => '1',
                            'carriers/freeshipping/active' => '0',
                            'carriers/tablerate/active' => '1',
                            'carriers/tablerate/condition_name' => 'package_qty',
                            'btob/default_b2b_shipping_methods/applicable_shipping_methods' => 1,
                            'btob/default_b2b_shipping_methods/available_shipping_methods' => 'freeshipping',
                            'btob/order_approval/purchaseorder_active' => 0
                        ]
                    ],
                ]
            ],
            'defaultScopeWithPurchaseOrderEnabled' => [
                'config_data' => [
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT => [
                        '' => [
                            'carriers/flatrate/active' => '1',
                            'carriers/freeshipping/active' => '0',
                            'carriers/tablerate/active' => '1',
                            'carriers/tablerate/condition_name' => 'package_qty',
                            'btob/default_b2b_shipping_methods/applicable_shipping_methods' => 1,
                            'btob/default_b2b_shipping_methods/available_shipping_methods' => 'freeshipping',
                            'btob/order_approval/purchaseorder_active' => 1
                        ]
                    ],
                ]
            ],
            'websiteScope' => [
                'config_data' => [
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT => [
                        '' => [
                            'carriers/flatrate/active' => '0',
                            'carriers/freeshipping/active' => '0',
                            'carriers/tablerate/active' => '0',
                            'carriers/tablerate/condition_name' => 'package_qty',
                            'btob/default_b2b_shipping_methods/applicable_shipping_methods' => 1,
                            'btob/default_b2b_shipping_methods/available_shipping_methods' => 'freeshipping',
                            'btob/order_approval/purchaseorder_active' => 0
                        ]
                    ],
                    ScopeInterface::SCOPE_WEBSITES => [
                        'base' => [
                            'carriers/flatrate/active' => '1',
                            'carriers/freeshipping/active' => '0',
                            'carriers/tablerate/active' => '1',
                            'carriers/tablerate/condition_name' => 'package_qty',
                        ]
                    ],
                ]
            ],
            'websiteScopeWithPurchaseOrderEnabled' => [
                'config_data' => [
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT => [
                        '' => [
                            'carriers/flatrate/active' => '0',
                            'carriers/freeshipping/active' => '0',
                            'carriers/tablerate/active' => '0',
                            'carriers/tablerate/condition_name' => 'package_qty',
                            'btob/default_b2b_shipping_methods/applicable_shipping_methods' => 1,
                            'btob/default_b2b_shipping_methods/available_shipping_methods' => 'freeshipping',
                            'btob/order_approval/purchaseorder_active' => 1
                        ]
                    ],
                    ScopeInterface::SCOPE_WEBSITES => [
                        'base' => [
                            'carriers/flatrate/active' => '1',
                            'carriers/freeshipping/active' => '0',
                            'carriers/tablerate/active' => '1',
                            'carriers/tablerate/condition_name' => 'package_qty',
                        ]
                    ],
                ]
            ]
        ];
    }

    /**
     * Tests ACL.
     *
     * @param string $actionName
     * @param boolean $reordered
     * @param string $expectedResult
     *
     * @dataProvider getAclResourceDataProvider
     * @magentoAppIsolation enabled
     */
    public function testGetAclResource($actionName, $reordered, $expectedResult)
    {
        $this->_objectManager->get(SessionQuote::class)->setReordered($reordered);
        $orderController = $this->_objectManager->get(
            \Magento\Sales\Controller\Adminhtml\Order\Stub\OrderCreateStub::class
        );

        $this->getRequest()->setActionName($actionName);

        $method = new \ReflectionMethod(\Magento\Sales\Controller\Adminhtml\Order\Create::class, '_getAclResource');
        $method->setAccessible(true);
        $result = $method->invoke($orderController);
        $this->assertEquals($result, $expectedResult);
    }

    /**
     * @return array
     */
    public function getAclResourceDataProvider()
    {
        return [
            ['index', false, 'Magento_Sales::create'],
        ];
    }

    /**
     * Update scope config settings
     * @param array $configData
     * @throws \Exception
     */
    private function setConfigValues($configData)
    {
        foreach ($configData as $scope => $data) {
            foreach ($data as $scopeCode => $scopeData) {
                foreach ($scopeData as $path => $value) {
                    $config = $this->configFactory->create();
                    $config->setScope($scope);

                    if ($scope == ScopeInterface::SCOPE_WEBSITES) {
                        $config->setWebsite($scopeCode);
                    }

                    if ($scope == ScopeInterface::SCOPE_STORES) {
                        $config->setStore($scopeCode);
                    }

                    $config->setDataByPath($path, $value);
                    $config->save();
                }
            }
        }
    }
}
