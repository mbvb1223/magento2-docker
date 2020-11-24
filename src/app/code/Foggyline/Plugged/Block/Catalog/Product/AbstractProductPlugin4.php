<?php

namespace Foggyline\Plugged\Block\Catalog\Product;

class AbstractProductPlugin4
{
    public function beforeGetAddToCartUrl(
        $subject,
        $product, $additional = []
    ) {
        var_dump('Plugin4 - beforeGetAddToCartUrl');
        var_dump(get_class($subject), get_class($product), $additional);
    }

    public function afterGetAddToCartUrl($subject)
    {
//        var_dump(get_class($subject));
        var_dump('Plugin4 - afterGetAddToCartUrl');
    }

    public function aroundGetAddToCartUrl(
        $subject,
        \Closure $proceed,
        $product,
        $additional = []
    ) {
        var_dump('Plugin4 - aroundGetAddToCartUrl');
        return $proceed($product, $additional);
    }
}
