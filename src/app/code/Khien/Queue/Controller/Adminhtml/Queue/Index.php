<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Khien\Queue\Controller\Adminhtml\Queue;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Index action.
 */
class Index extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Khien_Queue::queue';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    const TOPIC_NAME = 'amq.topic.test';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        DataPersistorInterface $dataPersistor = null,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        ProductFactory $factory
    ) {
        $product = $factory->create();
        $product->setId(123);
        $product->setUrlKey('khien_nam');

        for ($i = 1; $i <= 100; $i++) {
            $publisher->publish(self::TOPIC_NAME, $product);
        }

        die('sdf');
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->dataPersistor = $dataPersistor ?: ObjectManager::getInstance()->get(DataPersistorInterface::class);
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {

    }
}
