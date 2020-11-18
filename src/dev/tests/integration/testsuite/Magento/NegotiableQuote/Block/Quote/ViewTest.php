<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\Block\Quote;

use Magento\NegotiableQuote\Controller\Adminhtml\Quote\Decline;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\PrintAction;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\Save;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\Send;
use Magento\Framework\Acl\Builder as AclBuilder;
use Magento\Framework\Acl;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View;
use Magento\Framework\View\LayoutInterface;

/**
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 */
class ViewTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->acl = $objectManager->get(AclBuilder::class)->getAcl();
        $this->layout = Bootstrap::getObjectManager()->get(LayoutInterface::class);
    }

    /**
     * @param string $aclResource
     * @param string $expectedStringToSee
     * @dataProvider buttonVisibilityDataProvider
     */
    public function testButtonVisibleWhenAclRoleIsEnabled($aclResource, $expectedStringToSee)
    {
        $this->acl->allow(null, $aclResource);

        $html = $this->createBlockInstance()->getButtonsHtml();

        $this->assertStringContainsString($expectedStringToSee, $html);
    }

    /**
     * @param string $aclResource
     * @param string $expectedStringToNotSee
     * @dataProvider buttonVisibilityDataProvider
     */
    public function testButtonNotVisibleWhenAclRoleIsDisabled($aclResource, $expectedStringToNotSee)
    {
        $this->acl->deny(null, $aclResource);

        $html = $this->createBlockInstance()->getButtonsHtml();

        $this->assertStringNotContainsString($expectedStringToNotSee, $html);
    }

    /**
     * @return array
     */
    public function buttonVisibilityDataProvider()
    {
        return [
            [Decline::ADMIN_RESOURCE, 'quote-view-decline-button'],
            [PrintAction::ADMIN_RESOURCE, 'quote_print'],
            [Save::ADMIN_RESOURCE, 'quote_save'],
            [Send::ADMIN_RESOURCE, 'quote_send'],
        ];
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    private function createBlockInstance()
    {
        return $this->layout->createBlock(View::class);
    }
}
