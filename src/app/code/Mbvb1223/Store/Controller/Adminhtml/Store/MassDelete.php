<?php
namespace Mbvb1223\Store\Controller\Adminhtml\Store;

use Mbvb1223\Store\Controller\Adminhtml\Store;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Mbvb1223\Store\Model\StoreFactory;
use Mbvb1223\Store\Model\ResourceModel\StoreFactory as resPostsFactory;

class MassDelete extends Store
{
    protected $_resStoreFactory;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        StoreFactory $storeFactory,
        resPostsFactory $resStoreFactory
    ) {
        parent::__construct($context, $coreRegistry, $resultPageFactory, $storeFactory);
        $this->_resStoreFactory = $resStoreFactory;
    }

    public function execute()
    {
        $postIds = $this->getRequest()->getParam('store_id', array());
        $model = $this->_storeFactory->create();
        $resModel = $this->_resStoreFactory->create();
        if(count($postIds))
        {
            $i = 0;
            foreach ($postIds as $postId) {
                try {
                    $resModel->load($model,$postId);
                    $resModel->delete($model);
                    $i++;
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                }
            }
            if ($i > 0) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 item(s) were deleted.', $i)
                );
            }
        }
        else
        {
            $this->messageManager->addErrorMessage(
                __('You can not delete item(s), Please check again %1')
            );
        }
        $this->_redirect('*/*/index');
    }
}

