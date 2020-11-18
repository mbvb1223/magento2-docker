<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Company\Model\Company\Structure as StructureManager;
use Magento\Company\Model\ResourceModel\Structure\Tree as StructureTree;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\User\Model\User;

/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var CustomerInterfaceFactory $customerFactory */
$customerFactory = $objectManager->get(CustomerInterfaceFactory::class);

/** @var CustomerRepositoryInterface $customerRepository */
$customerRepository = $objectManager->get(CustomerRepositoryInterface::class);

/** @var CompanyInterfaceFactory $companyFactory */
$companyFactory = $objectManager->get(CompanyInterfaceFactory::class);

/** @var CompanyRepositoryInterface $companyRepository */
$companyRepository = $objectManager->get(CompanyRepositoryInterface::class);

/** @var DataObjectHelper $dataObjectHelper */
$dataObjectHelper = $objectManager->get(DataObjectHelper::class);

/*
 * Create a merchant user to serve as the sales rep for the company.
 */
/** @var User $user */
$salesRep = $objectManager->create(User::class);
$salesRep->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);

/*
 * Create a customer to serve as the admin for the company.
 */
/** @var CustomerInterface $adminCustomer */
$adminCustomer = $customerFactory->create();
$dataObjectHelper->populateWithArray(
    $adminCustomer,
    [
        'firstname' => 'John',
        'lastname' => 'Doe',
        'email' => 'john.doe@example.com',
        'website_id' => 1,
    ],
    CustomerInterface::class
);
$customerRepository->save($adminCustomer, 'password');
$adminCustomer = $customerRepository->get('john.doe@example.com');

/*
 * Create a company with the admin and sales rep created above.
 */
 /** @var CompanyInterface $company */
$company = $companyFactory->create();
$dataObjectHelper->populateWithArray(
    $company,
    [
        'company_name' => 'Magento',
        'status' => CompanyInterface::STATUS_APPROVED,
        'company_email' => 'company@example.com',
        'comment' => 'Comment',
        'super_user_id' => $adminCustomer->getId(),
        'sales_representative_id' => $salesRep->getId(),
        'customer_group_id' => 1,
        'country_id' => 'US',
        'region_id' => 1,
        'city' => 'City',
        'street' => '123 Street',
        'postcode' => 'Postcode',
        'telephone' => '5555555555'
    ],
    CompanyInterface::class
);
$companyRepository->save($company);

// Load the company we just created with its companyId populated
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
$searchCriteriaBuilder->addFilter('company_name', 'Magento');
$searchCriteria = $searchCriteriaBuilder->create();
$results = $companyRepository->getList($searchCriteria)->getItems();
$company = reset($results);

/*
 * Create a customer one level below the company admin in the company hierarchy.
 */
$levelOneCustomer = $customerFactory->create();
$dataObjectHelper->populateWithArray(
    $levelOneCustomer,
    [
        'firstname' => 'Veronica',
        'lastname' => 'Costello',
        'email' => 'veronica.costello@example.com',
        'website_id' => 1,
        'extension_attributes' => [
            'company_attributes' => [
                'company_id' => $company->getId(),
                'status' => 1,
                'job_title' => 'Sales Rep'
            ]
        ]
    ],
    CustomerInterface::class
);
$customerRepository->save($levelOneCustomer, 'password');
$levelOneCustomer = $customerRepository->get('veronica.costello@example.com');

/*
 * Create a customer two levels below the company admin in the company hierarchy.
 */
$levelTwoCustomer = $customerFactory->create();
$dataObjectHelper->populateWithArray(
    $levelTwoCustomer,
    [
        'firstname' => 'Alex',
        'lastname' => 'Smith',
        'email' => 'alex.smith@example.com',
        'website_id' => 1,
        'extension_attributes' => [
            'company_attributes' => [
                'company_id' => $company->getId(),
                'status' => 1,
                'job_title' => 'Sales Rep'
            ]
        ]
    ],
    CustomerInterface::class
);
$customerRepository->save($levelTwoCustomer, 'password');
$levelTwoCustomer = $customerRepository->get('alex.smith@example.com');

/*
 * Move the levelTwoCustomer so that they are a subordinate of the levelOneCustomer.
 */
/** @var StructureManager $structureManager */
$objectManager->removeSharedInstance(StructureTree::class);
$structureManager = $objectManager->create(StructureManager::class);

$levelOneCustomerStructure = $structureManager->getStructureByCustomerId($levelOneCustomer->getId());
$levelTwoCustomerStructure = $structureManager->getStructureByCustomerId($levelTwoCustomer->getId());
$structureManager->moveNode($levelTwoCustomerStructure->getId(), $levelOneCustomerStructure->getId(), true);
