<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
for ($i = 1; $i <= 3; $i++) {
    try {
        $product = $productRepository->get('simple_product_' . $i, false, null, true);
        $productRepository->delete($product);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        //Nothing to delete
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
