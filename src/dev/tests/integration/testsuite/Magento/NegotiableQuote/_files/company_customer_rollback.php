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

/** @var CustomerRepositoryInterface $customerRepository */
$customerRepository = Bootstrap::getObjectManager()->get(CustomerRepositoryInterface::class);
$customerCompany = $customerRepository->get('customercompany22@example.com');
$customerRepository->delete($customerCompany);
$companyAdmin = $customerRepository->get('email@companyquote.com');
if ($companyAdmin->getExtensionAttributes()->getCompanyAttributes()) {
    $companyId = $companyAdmin->getExtensionAttributes()
        ->getCompanyAttributes()
        ->getCompanyId();

    if ($companyId) {
        /** @var CompanyRepositoryInterface $companyRepository */
        $companyRepository = Bootstrap::getObjectManager()->get(CompanyRepositoryInterface::class);
        $companyRepository->deleteById($companyId);
    }
}
$customerRepository->delete($companyAdmin);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
