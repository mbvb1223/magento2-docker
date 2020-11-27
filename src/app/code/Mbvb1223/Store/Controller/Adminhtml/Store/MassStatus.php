<?php
namespace Mbvb1223\Store\Controller\Adminhtml\Store;

use Mbvb1223\Store\Model\ResourceModel\Store\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Mbvb1223\Store\Controller\Adminhtml\Store;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Mbvb1223\Store\Model\StoreFactory;
use Mbvb1223\Store\Model\ResourceModel\StoreFactory as resPostsFactory;

class MassStatus extends Store
{
    protected $_resPostsFactory;

    /**
     * MassActions filter
     *
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        StoreFactory $postsFactory,
        resPostsFactory $resPostsFactory,
        Filter $filter,
        CollectionFactory $collectionFactory
    )
    {
        $this->_resPostsFactory = $resPostsFactory;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;

        parent::__construct($context, $coreRegistry, $resultPageFactory, $postsFactory);
    }

    public function execute()
    {
        $status = $this->getRequest()->getParam('status', 0);
        $storeIds = $this->getRequest()->getParam('store_id', []);
        if (count($storeIds)) {
            $i = 0;
            foreach ($storeIds as $postId) {
                try {
                    $postId = (int)$postId;
                    $model = $this->_storeFactory->create();
                    $resModel = $this->_resPostsFactory->create();
                    $model->setStatus($status)->setId($postId);
                    $resModel->save($model);
                    $i++;

                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                }
            }
            if ($i > 0) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 item(s) were .')
                );
            }
        } else {
            $this->messageManager->addErrorMessage(
                __('You can not item, Please check again')
            );
        }
        $this->_redirect('*/*/index');
//        ================================
//
//        $collection = $this->filter->getCollection($this->collectionFactory->create());
//        $collectionSize = $collection->getSize();
//
//        foreach ($collection as $block) {
//            $block->delete();
//        }
//
//        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', $collectionSize));
//
//        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
//        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
//        return $resultRedirect->setPath('*/*/');

//        $collection = $this->filter->getCollection($this->collectionFactory->create());
//        $productIds = $collection->getAllIds();
//        $requestStoreId = $storeId = $this->getRequest()->getParam('store', null);
//        $filterRequest = $this->getRequest()->getParam('filters', null);
//        $status = (int) $this->getRequest()->getParam('status');
//
//        if (null === $storeId && null !== $filterRequest) {
//            $storeId = (isset($filterRequest['store_id'])) ? (int) $filterRequest['store_id'] : 0;
//        }
//
//        try {
//            $this->productAction->updateAttributes($productIds, ['status' => $status], (int) $storeId);
//            $this->messageManager->addSuccessMessage(
//                __('A total of %1 record(s) have been updated.', count($productIds))
//            );
//            $this->_productPriceIndexerProcessor->reindexList($productIds);
//        } catch (\Magento\Framework\Exception\LocalizedException $e) {
//            $this->messageManager->addErrorMessage($e->getMessage());
//        } catch (\Exception $e) {
//            $this->messageManager->addExceptionMessage(
//                $e,
//                __('Something went wrong while updating the product(s) status.')
//            );
//        }
//
//        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
//        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
//        return $resultRedirect->setPath('catalog/*/', ['store' => $requestStoreId]);
    }
}
