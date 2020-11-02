<?php
namespace Mbvb1223\Store\Model\System\Config;

use Magento\Framework\Option\ArrayInterface;

class Status implements ArrayInterface
{
    const ENABLED  = 1;
    const DISABLED = 2;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            self::ENABLED => __('Enabled'),
            self::DISABLED => __('Disabled'),
        ];
    }

}
