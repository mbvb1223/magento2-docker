<?php

namespace Foggyline\Plugged\Block\Catalog\Product;

class AbstractProductPlugin1
{
    public function beforeGetAddToCartUrl(
        $subject,
        $product,
        $additional = ['Plugin1']
    )
    {
        var_dump('Plugin1 - beforeGetAddToCartUrl');
        $additional['aassdf'] = 'Plugin1 changed';
        var_dump(get_class($subject), get_class($product), $additional);
    }

    public function afterGetAddToCartUrl(
        $subject,
        $result,
        $product,
        $additional = ['Plugin1']
    )
    {
//        var_dump(get_class($subject));
        var_dump('Plugin1 - afterGetAddToCartUrl');
        var_dump(get_class($subject));
        var_dump('asdf', $result);
        var_dump('$product', get_class($product));
        var_dump('$additional', $additional);
    }

    public function aroundGetAddToCartUrl(
        $subject,
        \Closure $proceed,
        $product,
        $additional = ['Plugin1']
    )
    {
        var_dump('Plugin1 - aroundGetAddToCartUrl');
        return $proceed($product, $additional);
    }
}
