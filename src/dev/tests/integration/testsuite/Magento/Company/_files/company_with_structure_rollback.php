<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var CustomerRepositoryInterface $customerRepository */
$customerRepository = $objectManager->get(CustomerRepositoryInterface::class);

/** @var CompanyRepositoryInterface $companyRepository */
$companyRepository = $objectManager->get(CompanyRepositoryInterface::class);

/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

try {
    // Delete the customers.
    $levelOneCustomer = $customerRepository->get('veronica.costello@example.com');
    $customerRepository->delete($levelOneCustomer);

    $levelTwoCustomer = $customerRepository->get('alex.smith@example.com');
    $customerRepository->delete($levelTwoCustomer);

    // Delete the company.
    $searchCriteriaBuilder->addFilter('company_name', 'Magento');
    $searchCriteria = $searchCriteriaBuilder->create();
    $results = $companyRepository->getList($searchCriteria)->getItems();
    foreach ($results as $company) {
        $companyRepository->delete($company);
    }

     // Delete the admin customer.
    $adminCustomer = $customerRepository->get('john.doe@example.com');
    $customerRepository->delete($adminCustomer);
} catch (NoSuchEntityException $e) {
    // Db isolation is enabled
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
