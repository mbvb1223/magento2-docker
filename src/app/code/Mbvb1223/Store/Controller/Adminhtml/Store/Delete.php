<?php
namespace Mbvb1223\Store\Controller\Adminhtml\Store;

use Mbvb1223\Store\Controller\Adminhtml\Store;

class Delete extends Store
{
    public function execute()
    {
        $postId = (int) $this->getRequest()->getParam('id');

        if ($postId) {
            /** @var $postModel \Mbvb1223\Store\Model\Store */
            $postModel = $this->_storeFactory->create();
            $postModel->load($postId);

            // Check this news exists or not
            if (!$postModel->getId()) {
                $this->messageManager->addError(__('This news no longer exists.'));
            } else {
                try {
                    // Delete news
                    $postModel->delete();
                    $this->messageManager->addSuccess(__('The news has been deleted.'));

                    // Redirect to grid page
                    $this->_redirect('*/*/');
                    return;
                } catch (\Exception $e) {
                    $this->messageManager->addError($e->getMessage());
                    $this->_redirect('*/*/edit', ['id' => $postModel->getId()]);
                }
            }
        }
    }
}
