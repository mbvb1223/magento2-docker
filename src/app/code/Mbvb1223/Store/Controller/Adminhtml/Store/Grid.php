<?php
namespace Mbvb1223\Store\Controller\Adminhtml\Store;

use Mbvb1223\Store\Block\Adminhtml\Store;

class Grid extends Store
{
    public function execute()
    {
        return $this->_resultPageFactory->create();
    }
}
