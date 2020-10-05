<?php

namespace Tops\CustomerSegment\Ui;

use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /**
     * @return array
     */
    public function getData()
    {
        return parent::getData();
    }

    /**
     * @param \Magento\Framework\Api\Filter $filter
     * @return null
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function getCollection()
    {
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [];
    }
}
