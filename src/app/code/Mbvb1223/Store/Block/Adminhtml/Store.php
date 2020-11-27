<?php

namespace Mbvb1223\Store\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

class Store extends Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_store';
        $this->_blockGroup = 'Mbvb1223_Store';
        $this->_headerText = __('STORE HEADER');
        $this->_addButtonLabel = __('Create New STORE');

        parent::_construct();
    }
}
