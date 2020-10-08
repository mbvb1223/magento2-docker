<?php
namespace Mbvb1223\Store\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Mbvb1223\Store\Model\StoreFactory;

class Store extends Action
{
    protected $_coreRegistry;
    protected $_resultPageFactory;
    protected $_storeFactory;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        StoreFactory $postsFactory
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_storeFactory = $postsFactory;

    }
    public function execute()
    {

    }

    protected function _isAllowed()
    {
        return true;
    }
}
