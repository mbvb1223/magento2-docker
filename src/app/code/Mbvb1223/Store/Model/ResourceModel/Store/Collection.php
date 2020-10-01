<?php

namespace Mbvb1223\Store\Model\ResourceModel\Store;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Remittance File Collection Constructor
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mbvb1223\Store\Model\Store', 'Mbvb1223\Store\Model\ResourceModel\Store');
    }
}
