<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\SharedCatalog\Model\SharedCatalog;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection;

/**
 * Test for class \Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Delete
 *
 * @magentoAppArea adminhtml
 */
class DeleteTest extends AbstractBackendController
{

    /** Test Delete SharedCatalog
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/SharedCatalog/_files/shared_catalog.php
     * @return void
     */
    public function testDeleteSharedCatalog(): void
    {
        $sharedCatalog = $this->getTestFixture();
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(['shared_catalog_id' => $sharedCatalog->getId()]);
        $this->dispatch('backend/shared_catalog/sharedCatalog/delete');
        $successMessage = (string) __('The shared catalog was deleted successfully.');
        $this->assertSessionMessages($this->equalTo([$successMessage]));
    }

    /**
     * Test Delete Incorrect Shared Catalog ID
     *
     * @return void
     */
    public function testDeleteIncorrectSharedCatalog(): void
    {
        $incorrectId = 8;
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(['shared_catalog_id' => $incorrectId]);
        $this->dispatch('backend/shared_catalog/sharedcatalog/delete');
        $errorMessage = (string) __('Requested Shared Catalog is not found');
        $this->assertSessionMessages(
            $this->equalTo([$errorMessage]),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * Gets Shared Catalog Fixture.
     *
     * @return SharedCatalog
     */
    private function getTestFixture(): SharedCatalog
    {
        /** @var Collection $sharedCatalogCollection */
        $sharedCatalogCollection = Bootstrap::getObjectManager()->create(Collection::class);
        return $sharedCatalogCollection->getLastItem();
    }
}
