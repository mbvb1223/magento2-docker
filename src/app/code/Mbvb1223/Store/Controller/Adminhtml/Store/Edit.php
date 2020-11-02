<?php
namespace Mbvb1223\Store\Controller\Adminhtml\Store;

use Mbvb1223\Store\Controller\Adminhtml\Store;

class Edit extends Store
{
    /**
     * @return void
     */
    public function execute()
    {
        $postId = $this->getRequest()->getParam('store_id');

        $model = $this->_storeFactory->create();

        if ($postId) {
            $model->load($postId);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This news no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        // Restore previously entered form data from session
        $data = $this->_session->getNewsData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->_coreRegistry->register('mbvb1223_store', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
//        $resultPage->setActiveMenu('Mbvb1223_Store::store_menu');
        $resultPage->getConfig()->getTitle()->prepend(__('SSSSSSSSSSSSSSSSS'));

        return $resultPage;
    }
}
