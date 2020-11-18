<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Setup\Test\Unit\Fixtures;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompaniesFixtureTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Setup\Fixtures\CompaniesFixture
     */
    private $companiesFixture;

    /**
     * @var \Magento\Setup\Fixtures\FixtureModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fixtureModelMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $objectManager;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customer;

    /**
     * @var \Magento\Company\Api\Data\CompanyCustomerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $companyCustomerModel;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Company\Model\Role|\PHPUnit\Framework\MockObject\MockObject
     */
    private $role;

    /**
     * @var \Magento\Company\Api\Data\TeamInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $team;

    /**
     * @var \Magento\Company\Api\Data\CompanyInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $companyFactoryMock;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerFactoryMock;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $regionsCollectionFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->fixtureModelMock = $this->getMockBuilder(\Magento\Setup\Fixtures\FixtureModel::class)
            ->setMethods(['getValue', 'getObjectManager'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->setMethods(['get', 'create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyCustomerModel = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyCustomerInterface::class)
            ->setMethods([
                'setCustomerId', 'setCompanyId', 'setJobTitle',
                'setIsSuperUser', 'setStatus', 'setTelephone'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customer = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->setMethods(['getId', 'getWebsiteId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerRepository = $this->getMockBuilder(\Magento\Customer\Api\CustomerRepositoryInterface::class)
            ->setMethods(['getList'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilder = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
            ->setMethods(['addFilter', 'create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->role = $this->getMockBuilder(\Magento\Company\Model\Role::class)
            ->setMethods(['setRoleName', 'setCompanyId', 'setPermissions'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->team = $this->getMockBuilder(\Magento\Company\Api\Data\TeamInterface::class)
            ->setMethods(['setName', 'setDescription'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerFactoryMock = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyFactoryMock = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->regionsCollectionFactory = $this->getMockBuilder(
            \Magento\Directory\Model\ResourceModel\Region\CollectionFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->companiesFixture = $objectManagerHelper->getObject(
            \Magento\Setup\Fixtures\CompaniesFixture::class,
            [
                'fixtureModel' => $this->fixtureModelMock,
                'websiteCustomers' => null,
                'company' => null,
                'uniqueAttributesQuantity' => null,
                'companyFactory' => $this->companyFactoryMock,
                'customerFactory' => $this->customerFactoryMock,
                'regionsCollectionFactory' => $this->regionsCollectionFactory
            ]
        );
    }

    /**
     * Test execute() method.
     *
     * @param bool $addCustomers
     * @param int|null $websiteId
     * @param array $sequence
     * @param array $calls
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute($addCustomers, $websiteId, array $sequence, array $calls)
    {
        $companiesCount = 1;
        $this->fixtureModelMock->expects($this->at(0))->method('getValue')->with('companies', 0)
            ->willReturn($companiesCount);

        $this->fixtureModelMock->expects($this->at(2))->method('getValue')->with('companies', 0)
            ->willReturn($companiesCount);

        $userRolesPerCompanyCount = 2;
        $this->fixtureModelMock->expects($this->at(3))->method('getValue')->with('user_roles_per_company', 0)
            ->willReturn($userRolesPerCompanyCount);

        $customersCount = 3;
        $this->fixtureModelMock->expects($this->at(4))->method('getValue')->with('customers', 0)
            ->willReturn($customersCount);

        $this->fixtureModelMock->expects($this->at($sequence['user_roles_per_company']))->method('getValue')
            ->with('user_roles_per_company', 0)->willReturn($userRolesPerCompanyCount);

        $teamsPerCompanyCount = 1;
        $this->fixtureModelMock->expects($this->at($sequence['teams_per_company']))->method('getValue')
            ->with('teams_per_company', 0)->willReturn($teamsPerCompanyCount);

        $this->prepareObjectManager($addCustomers, $websiteId, $calls);
        $this->fixtureModelMock->expects($this->exactly($calls['fixtureModel_getObjectManager']))
            ->method('getObjectManager')->willReturn($this->objectManager);

        $this->companiesFixture->execute();
    }

    /**
     * Data provider for execute() method.
     *
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [true, null,
                [
                    'user_roles_per_company' => 9,
                    'teams_per_company' => 10,
                ],
                [
                    'storeManager_getWebsites'=> 3,
                    'website_getId' => 6,
                    'fixtureModel_getObjectManager' => 9,
                    'objectManager_get' => 5,
                    'objectManager_create' => 4,
                    'company_getId' => 1,
                    'customer_getId' => 5,
                    'customerFactory_create' => 1,
                    'companyCustomerResource_saveAdvancedCustomAttributes' => 1,
                    'companyCustomerModel_setCustomerId' => 1,
                    'companyCustomerModel_setCompanyId' => 1,
                    'companyCustomerModel_setJobTitle' => 1,
                    'companyCustomerModel_setIsSuperUser' => 1,
                    'companyCustomerModel_setStatus' => 1,
                    'companyCustomerModel_setTelephone' => 1,
                    'storeManager_getWebsite' => 1,
                    'companyStructureManager_getStructureByCustomerId' => 1,
                    'companyStructure_getData' => 0,
                    'companyStructureManager_addNode' => 1,
                    'companyStructureManager_getStructureByTeamId' => 0,
                    'companyPermissionManagement_retrieveDefaultPermissions' => 0,
                    'role_setRoleName' => 0,
                    'role_setCompanyId' => 0,
                    'role_setPermissions' => 0,
                    'roleRepository_save' => 0,
                    'userRoleManagement_assignRoles' => 0,
                    'team_setName' => 0,
                    'team_setDescription' => 0,
                    'teamRepository_create' => 0
                ]
            ],
            [false, 34,
                [
                    'user_roles_per_company' => 9,
                    'teams_per_company' => 10,
                ],
                [
                    'storeManager_getWebsites'=> 2,
                    'website_getId' => 4,
                    'fixtureModel_getObjectManager' => 17,
                    'objectManager_get' => 11,
                    'objectManager_create' => 6,
                    'company_getId' => 4,
                    'customer_getId' => 11,
                    'customerFactory_create' => 2,
                    'companyCustomerResource_saveAdvancedCustomAttributes' => 2,
                    'companyCustomerModel_setCustomerId' => 2,
                    'companyCustomerModel_setCompanyId' => 2,
                    'companyCustomerModel_setJobTitle' => 2,
                    'companyCustomerModel_setIsSuperUser' => 2,
                    'companyCustomerModel_setStatus' => 2,
                    'companyCustomerModel_setTelephone' => 2,
                    'storeManager_getWebsite' => 1,
                    'companyStructureManager_getStructureByCustomerId' => 1,
                    'companyStructure_getData' => 1,
                    'companyStructureManager_addNode' => 2,
                    'companyStructureManager_getStructureByTeamId' => 1,
                    'companyPermissionManagement_retrieveDefaultPermissions' => 1,
                    'role_setRoleName' => 1,
                    'role_setCompanyId' => 1,
                    'role_setPermissions' => 1,
                    'roleRepository_save' => 1,
                    'userRoleManagement_assignRoles' => 1,
                    'team_setName' => 1,
                    'team_setDescription' => 1,
                    'teamRepository_create' => 1
                ]
            ]
        ];
    }

    /**
     * Test execute() with Exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('There are not enough customers to populate all companies');

        $companiesCount = 1;
        $userRolesPerCompanyCount = 2;
        $customersCount = 1;
        $mapForMethodGetValue = [
            ['companies', 0, $companiesCount],
            ['user_roles_per_company', 0, $userRolesPerCompanyCount],
            ['customers', 0, $customersCount]
        ];
        $this->fixtureModelMock->expects($this->exactly(4))->method('getValue')->willReturnMap($mapForMethodGetValue);

        $companyRepository = $this->getMockBuilder(\Magento\Company\Api\CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $mapForMethodGet = [
            [\Magento\Company\Api\CompanyRepositoryInterface::class, $companyRepository]
        ];
        $this->objectManager->expects($this->once())->method('get')->willReturnMap($mapForMethodGet);
        $regionsCollection = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Region\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $region = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Region::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode', 'getRegionId'])
            ->getMock();
        $this->regionsCollectionFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($regionsCollection);
        $regionsCollection->expects($this->atLeastOnce())->method('getFirstItem')->willReturn($region);

        $this->fixtureModelMock->expects($this->once())->method('getObjectManager')
            ->willReturn($this->objectManager);

        $this->companiesFixture->execute();
    }

    /**
     * Prepare ObjectManager mock.
     *
     * @param bool $addCustomers
     * @param int|null $websiteId
     * @param array $calls
     * @return void
     */
    private function prepareObjectManager($addCustomers, $websiteId, array $calls)
    {
        $this->prepareCustomer($websiteId, $calls);

        $this->prepareRole($calls);

        $this->prepareTeam($calls);

        $mapForMethodGet = $this->getMapForMethodGet($addCustomers, $calls);
        $this->objectManager->expects($this->exactly($calls['objectManager_get']))->method('get')
            ->willReturnMap($mapForMethodGet);

        $mapForMethodCreate = $this->getMapForMethodCreate($websiteId, $calls);
        $this->objectManager->expects($this->exactly($calls['objectManager_create']))->method('create')
            ->willReturnMap($mapForMethodCreate);
    }

    /**
     * Prepare Customer mock.
     *
     * @param int|null $websiteId
     * @param array $calls
     * @return void
     */
    private function prepareCustomer($websiteId, array $calls)
    {
        $customerId = 354;
        $this->customer->expects($this->exactly($calls['customer_getId']))->method('getId')->willReturn($customerId);
        $this->customer->expects($this->exactly(2))->method('getWebsiteId')->willReturn($websiteId);
    }

    /**
     * Prepare Role mock.
     *
     * @param array $calls
     * @return void
     */
    private function prepareRole(array $calls)
    {
        $this->role->expects($this->exactly($calls['role_setRoleName']))->method('setRoleName')->willReturnSelf();
        $this->role->expects($this->exactly($calls['role_setCompanyId']))->method('setCompanyId')->willReturnSelf();
        $this->role->expects($this->exactly($calls['role_setPermissions']))->method('setPermissions')->willReturnSelf();
    }

    /**
     * Prepare Team mock.
     *
     * @param array $calls
     * @return void
     */
    private function prepareTeam(array $calls)
    {
        $this->team->expects($this->exactly($calls['team_setName']))->method('setName')->willReturnSelf();
        $this->team->expects($this->exactly($calls['team_setDescription']))->method('setDescription')->willReturnSelf();
    }

    /**
     * Retrieve map for calling get() method in ObjectManager mock.
     *
     * @param bool $addCustomers
     * @param array $calls
     * @return array
     */
    private function getMapForMethodGet($addCustomers, array $calls)
    {
        $company = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $companyId = 23;
        $company->expects($this->exactly($calls['company_getId']))->method('getId')->willReturn($companyId);

        $this->companyFactoryMock->expects($this->exactly(1))
            ->method('create')
            ->willReturn($company);

        $companyRepository = $this->getMockBuilder(\Magento\Company\Api\CompanyRepositoryInterface::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $companyRepository->expects($this->atLeastOnce())->method('save')->willReturn($company);

        $this->prepareCustomerRepository($addCustomers);

        $this->prepareSearchCriteriaBuilder();

        $companyCustomerResource = $this->getMockBuilder(\Magento\Company\Model\ResourceModel\Customer::class)
            ->setMethods(['saveAdvancedCustomAttributes'])
            ->disableOriginalConstructor()->getMock();
        $companyCustomerResource
            ->expects($this->exactly($calls['companyCustomerResource_saveAdvancedCustomAttributes']))
            ->method('saveAdvancedCustomAttributes')->willReturnSelf();

        $companyStructure = $this->getMockBuilder(\Magento\Company\Api\Data\StructureInterface::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $targetId = 737;
        $companyStructure->expects($this->exactly($calls['companyStructure_getData']))->method('getData')
            ->with(\Magento\Company\Api\Data\StructureInterface::STRUCTURE_ID)->willReturn($targetId);

        $companyStructureManager = $this->getMockBuilder(\Magento\Company\Model\Company\Structure::class)
            ->setMethods(['getStructureByCustomerId', 'addNode', 'getStructureByTeamId'])
            ->disableOriginalConstructor()->getMock();

        $companyStructureManager->expects($this->exactly($calls['companyStructureManager_getStructureByCustomerId']))
            ->method('getStructureByCustomerId')->willReturnOnConsecutiveCalls(null, $companyStructure);

        $companyStructureManager->expects($this->exactly($calls['companyStructureManager_addNode']))->method('addNode')
            ->willReturnSelf();
        $companyStructureManager->expects($this->exactly($calls['companyStructureManager_getStructureByTeamId']))
            ->method('getStructureByTeamId')->willReturn($companyStructure);

        $permission = $this->getMockBuilder(\Magento\Company\Api\Data\RoleInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $companyPermissionManagement = $this
            ->getMockBuilder(\Magento\Company\Model\PermissionManagementInterface::class)
            ->setMethods(['retrieveDefaultPermissions'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $defaultPermissions = [$permission];
        $companyPermissionManagement
            ->expects($this->exactly($calls['companyPermissionManagement_retrieveDefaultPermissions']))
            ->method('retrieveDefaultPermissions')->willReturn($defaultPermissions);

        $roleRepository = $this->getMockBuilder(\Magento\Company\Api\RoleRepositoryInterface::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $roleRepository->expects($this->exactly($calls['roleRepository_save']))->method('save')
            ->willReturn($this->role);

        $userRoleManagement = $this->getMockBuilder(\Magento\Company\Model\UserRoleManagement::class)
            ->setMethods(['assignRoles'])
            ->disableOriginalConstructor()->getMock();
        $userRoleManagement->expects($this->exactly($calls['userRoleManagement_assignRoles']))->method('assignRoles')
            ->willReturn(null);

        $teamRepository = $this->getMockBuilder(\Magento\Company\Api\TeamRepositoryInterface::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $teamId = 75;
        $teamRepository->expects($this->exactly($calls['teamRepository_create']))
            ->method('create')
            ->willReturn($teamId);

        return [
            [\Magento\Company\Api\CompanyRepositoryInterface::class, $companyRepository],
            [\Magento\Customer\Api\CustomerRepositoryInterface::class, $this->customerRepository],
            [\Magento\Framework\Api\SearchCriteriaBuilder::class, $this->searchCriteriaBuilder],
            [\Magento\Company\Model\ResourceModel\Customer::class, $companyCustomerResource],
            [\Magento\Company\Model\Company\Structure::class, $companyStructureManager],
            [\Magento\Company\Model\PermissionManagementInterface::class, $companyPermissionManagement],
            [\Magento\Company\Api\RoleRepositoryInterface::class, $roleRepository],
            [\Magento\Company\Model\UserRoleManagement::class, $userRoleManagement],
            [\Magento\Company\Api\TeamRepositoryInterface::class, $teamRepository]
        ];
    }

    /**
     * Prepare CustomerRepository mock.
     *
     * @param bool $addCustomers
     * @return void
     */
    private function prepareCustomerRepository($addCustomers)
    {
        $customerSearchResult = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerSearchResultsInterface::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        if ($addCustomers) {
            $customers = [$this->customer];
        } else {
            $customers = [];
        }
        $customerSearchResult->expects($this->exactly(1))->method('getItems')->willReturn($customers);

        $this->customerRepository->expects($this->exactly(1))->method('getList')->willReturn($customerSearchResult);
    }

    /**
     * Prepare CustomerRepository mock.
     *
     * @return void
     */
    private function prepareSearchCriteriaBuilder()
    {
        $this->searchCriteriaBuilder->expects($this->exactly(1))->method('addFilter')->willReturnSelf();

        $searchCriteria = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()->getMock();
        $this->searchCriteriaBuilder->expects($this->exactly(1))->method('create')->willReturn($searchCriteria);
    }

    /**
     * Retrieve map for calling create() method in ObjectManager mock.
     *
     * @param int|null $websiteId
     * @param array $calls
     * @return array
     */
    private function getMapForMethodCreate($websiteId, array $calls)
    {
        $website = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $website->expects($this->exactly($calls['website_getId']))->method('getId')->willReturn($websiteId);

        $storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManager::class)
            ->setMethods(['getWebsites', 'getWebsite'])
            ->disableOriginalConstructor()->getMock();

        $websites = [$website];
        $storeManager->expects($this->exactly($calls['storeManager_getWebsites']))->method('getWebsites')
            ->willReturn($websites);

        $storeManager->expects($this->exactly($calls['storeManager_getWebsite']))->method('getWebsite')
            ->willReturn($website);

        $this->customerFactoryMock->expects($this->exactly($calls['customerFactory_create']))
            ->method('create')
            ->willReturn($this->customer);
        $regionsCollection = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Region\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $region = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Region::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode', 'getRegionId'])
            ->getMock();
        $this->regionsCollectionFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($regionsCollection);
        $regionsCollection->expects($this->atLeastOnce())->method('getFirstItem')->willReturn($region);
        $region->expects($this->atLeastOnce())->method('getCode')->willReturn('Al');
        $region->expects($this->atLeastOnce())->method('getRegionId')->willReturn(1);

        $this->prepareCompanyCustomerModel($calls);

        return [
            [\Magento\Store\Model\StoreManager::class, [], $storeManager],
            [\Magento\Company\Api\Data\CompanyCustomerInterface::class, [], $this->companyCustomerModel],
            [\Magento\Company\Model\Role::class, [], $this->role],
            [\Magento\Company\Api\Data\TeamInterface::class, [], $this->team]
        ];
    }

    /**
     * Prepare CompanyCustomerModel mock.
     *
     * @param array $calls
     * @return void
     */
    private function prepareCompanyCustomerModel(array $calls)
    {
        $this->companyCustomerModel->expects($this->exactly($calls['companyCustomerModel_setCustomerId']))
            ->method('setCustomerId')->willReturnSelf();
        $this->companyCustomerModel->expects($this->exactly($calls['companyCustomerModel_setCompanyId']))
            ->method('setCompanyId')->willReturnSelf();
        $this->companyCustomerModel->expects($this->exactly($calls['companyCustomerModel_setJobTitle']))
            ->method('setJobTitle')->willReturnSelf();
        $this->companyCustomerModel->expects($this->exactly($calls['companyCustomerModel_setStatus']))
            ->method('setStatus')->willReturnSelf();
        $this->companyCustomerModel->expects($this->exactly($calls['companyCustomerModel_setTelephone']))
            ->method('setTelephone')->willReturnSelf();
    }

    /**
     * Test getActionTitle() method.
     *
     * @return void
     */
    public function testGetActionTitle()
    {
        $expected = 'Generating companies';
        $this->assertEquals($expected, $this->companiesFixture->getActionTitle());
    }

    /**
     * Test introduceParamLabels() method.
     *
     * @return void
     */
    public function testIntroduceParamLabels()
    {
        $expected = ['companies' => 'Companies'];
        $this->assertEquals($expected, $this->companiesFixture->introduceParamLabels());
    }
}
