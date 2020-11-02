<?php
namespace Mbvb1223\Store\Controller\Adminhtml\Store;

use Mbvb1223\Store\Controller\Adminhtml\Store;

class NewAction extends Store
{
    /**
     * Create new news action
     *
     * @return void
     */
    public function execute()
    {
        return $this->_forward('edit');
    }
}
