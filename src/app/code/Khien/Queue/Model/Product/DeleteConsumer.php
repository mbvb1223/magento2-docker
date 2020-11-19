<?php

namespace Khien\Queue\Model\Product;

class DeleteConsumer
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function processMessage(\Magento\Catalog\Api\Data\ProductInterface $data)
    {
        var_dump($data->getId());
    }
}
