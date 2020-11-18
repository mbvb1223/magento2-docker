<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Controller\Adminhtml\Index;

use Magento\Framework\Acl;
use Magento\Framework\Acl\Builder as AclBuilder;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 */
class IndexTest extends AbstractBackendController
{
    /**
     * @inheritDoc
     */
    protected $resource = Index::ADMIN_RESOURCE;

    /**
     * @inheritDoc
     */
    protected $uri = 'backend/company/index/index';

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
        $this->dispatch($this->uri);
        $responseHtml = $this->getResponse()->getContent();

        $this->assertStringContainsString($expectedStringForButton, $responseHtml);
    }

    /**
     * Tests that the expected button is absent when the respective ACL resource is disabled on the role.
     *
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
        $this->dispatch($this->uri);
        $responseHtml = $this->getResponse()->getContent();

        $this->assertStringNotContainsString($expectedStringForButton, $responseHtml);
    }

    /**
     * @return array
     */
    public function expectedButtonsDataProvider()
    {
        return [
            'Add Button' => [NewAction::ADMIN_RESOURCE, 'add-button']
        ];
    }
}
