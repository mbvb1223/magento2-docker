<?php

namespace Mbvb1223\Store\Block;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use Mbvb1223\Store\Model\ResourceModel\Store\Collection as StoreCollection;
use Mbvb1223\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Mbvb1223\Store\Model\Store;

class Stores extends Template
{
    protected $_storeCollectionFactory = null;

    /**
     * Constructor
     *
     * @param Context $context
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreCollectionFactory $storeCollectionFactory,
        array $data = []
    ) {
        $this->_storeCollectionFactory = $storeCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return Store[]
     */
    public function getStores()
    {
        /** @var StoreCollection $storeCollection */
        $storeCollection = $this->_storeCollectionFactory->create();
        $storeCollection->addFieldToSelect('*')->load();

        return $storeCollection->getItems();
    }

    /**
     * @param Store $store
     * @return string
     */
    public function getPostUrl(
        Store $store
    ) {
        return '/store/post/view/id/' . $store->getId();
    }

}
