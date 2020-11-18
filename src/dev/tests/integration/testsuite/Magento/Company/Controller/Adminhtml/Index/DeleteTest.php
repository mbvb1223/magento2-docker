<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Controller\Adminhtml\Index;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 */
class DeleteTest extends AbstractBackendController
{
    /**
     * @inheritDoc
     */
    protected $resource = Delete::ADMIN_RESOURCE;

    /**
     * @inheritDoc
     */
    protected $uri = 'backend/company/index/delete';

    /**
     * @inheritDoc
     */
    protected $httpMethod = HttpRequest::METHOD_POST;
}
