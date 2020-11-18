<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var \Magento\Framework\Registry $registry */
$registry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var $company \Magento\Company\Model\Company */
$companyCollection = Bootstrap::getObjectManager()
    ->create(\Magento\Company\Model\ResourceModel\Company\Collection::class);
foreach ($companyCollection as $company) {
    $company->delete();
}

/** @var $company \Magento\Company\Model\Company */
$structureCollection = Bootstrap::getObjectManager()
    ->create(\Magento\Company\Model\ResourceModel\Structure\Collection::class);
foreach ($structureCollection as $structure) {
    $structure->delete();
}

/** @var $company \Magento\Company\Model\Company */
$roleCollection = Bootstrap::getObjectManager()
    ->create(\Magento\Company\Model\ResourceModel\Role\Collection::class);
foreach ($roleCollection as $role) {
    $role->delete();
}

/** @var $company \Magento\Company\Model\Company */
$teamCollection = Bootstrap::getObjectManager()
    ->create(\Magento\Company\Model\ResourceModel\Team\Collection::class);
foreach ($teamCollection as $team) {
    $team->delete();
}
/** @var $customer \Magento\Customer\Model\Customer*/
$customer = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Customer\Model\Customer::class
);

$customer->load(1);
if ($customer->getId()) {
    $customer->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
