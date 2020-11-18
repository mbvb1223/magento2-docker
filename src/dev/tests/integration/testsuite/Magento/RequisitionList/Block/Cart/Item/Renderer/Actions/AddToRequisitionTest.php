<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Block\Cart\Item\Renderer\Actions;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Integration Test for AddToRequisition Block
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class AddToRequisitionTest extends TestCase
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var AddToRequisition
     */
    private $block;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        /** @var ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        $this->customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
        $this->customerSession = $objectManager->get(Session::class);
        $this->cartRepository = $objectManager->get(CartRepositoryInterface::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);

        $this->block = $objectManager->get(AddToRequisition::class);
        $this->block->setTemplate('Magento_RequisitionList::cart/item/renderer/actions/add_to_requisition_list.phtml');
    }

    /**
     * Given I am a guest
     * When this block gets rendered
     * Then the guest will receive empty string as output
     */
    public function testNothingIsOutputForGuestCart()
    {
        $this->assertEquals('', $this->block->toHtml());
    }

    /**
     * Given I am a customer
     * When this block gets a cart item assigned to it
     * Then the init script rendered by the block is namespaced by cart item ID
     *
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     */
    public function testThatScriptIsNamespacedByCartItemIdWithLoggedInCustomer()
    {
        $carts = $this->cartRepository->getList(
            $this->searchCriteriaBuilder->addFilter('reserved_order_id', 'test01')->create()
        )->getItems();

        $cart = array_pop($carts);

        $customer = $this->customerRepository->getById(1);
        $this->customerSession->loginById($customer->getId());
        $cartItem = $cart->getItems()[0];
        $this->block->setItem($cartItem);

        $this->assertStringContainsString(
            "requisition_{$cartItem->getItemId()}",
            $this->block->toHtml()
        );
    }
}
