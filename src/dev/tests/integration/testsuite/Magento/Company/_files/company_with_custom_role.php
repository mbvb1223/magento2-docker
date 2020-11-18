<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Company\Api\Data\RoleInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;

$user = Bootstrap::getObjectManager()->create(\Magento\User\Model\User::class);
$user->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);

/** @var $repository \Magento\Customer\Api\CustomerRepositoryInterface */
$repository = Bootstrap::getObjectManager()->create(\Magento\Customer\Api\CustomerRepositoryInterface::class);
$customer = Bootstrap::getObjectManager()->create(\Magento\Customer\Model\Customer::class);
/** @var Magento\Customer\Model\Customer $customer */
$customer->setWebsiteId(1)
    ->setEmail('customrole@company.com')
    ->setPassword('password')
    ->setFirstname('John')
    ->setLastname('Smith');
$customer->isObjectNew(true);
$customer->save();
$customer = $repository->get('customrole@company.com');

/** @var CompanyRepositoryInterface $companyRepository */
$companyRepository = Bootstrap::getObjectManager()->create(CompanyRepositoryInterface::class);
/** @var CompanyInterface $company */
$company = Bootstrap::getObjectManager()->create(CompanyInterface::class);
$company->setCompanyName('Company with role');
$company->setStatus(CompanyInterface::STATUS_APPROVED);
$company->setCompanyEmail('customrole@company.com');
$company->setComment('comment');
$company->setSuperUserId($customer->getId());
$company->setSalesRepresentativeId($user->getId());
$company->setCustomerGroupId(1);
$company->setCountryId('TV');
$company->setCity('City');
$company->setStreet(['avenue, 30']);
$company->setPostcode('postcode');
$company->setTelephone('123456');
$companyRepository->save($company);
$company = $companyRepository->get($company->getId());
$company->getExtensionAttributes()->getQuoteConfig()->setIsQuoteEnabled(true);
$companyRepository->save($company);

/** @var RoleInterface $role */
$role = Bootstrap::getObjectManager()->create(RoleInterface::class);
$role->setCompanyId($company->getId());
$role->setRoleName('custom company role');
/** @var \Magento\Company\Api\RoleRepositoryInterface $roleRepository */
$roleRepository = Bootstrap::getObjectManager()->get(\Magento\Company\Api\RoleRepositoryInterface::class);
$roleRepository->save($role);
