<?php
namespace Mbvb1223\Store\Controller\Adminhtml\Store;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Mbvb1223\Store\Controller\Adminhtml\Store;
use Mbvb1223\Store\Model\StoreFactory;
use Magento\Framework\Registry;

class Index extends Store
{
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        StoreFactory $postsFactory
    ) {
        parent::__construct($context, $coreRegistry, $resultPageFactory, $postsFactory);
    }
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('TITLE'));

        return $resultPage;
    }
}
