<?php

namespace Mbvb1223\Store\Model;

use Magento\Cms\Model\Page\IdentityMap;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Mbvb1223\Store\Api\StoreRepositoryInterface;
use Mbvb1223\Store\Model\ResourceModel\Store as ResourceStore;

class StoreRepository implements StoreRepositoryInterface
{
    /**
     * @var StoreFactory
     */
    private $storeFactory;

    /**
     * @var IdentityMap|mixed
     */
    private $identityMap;
    private $resource;

    /**
     * StoreRepository constructor.
     *
     * @param StoreFactory $storeFactory
     * @param IdentityMap|null $identityMap
     */
    public function __construct(
        ResourceStore $resource,
        StoreFactory $storeFactory,
        ?IdentityMap $identityMap = null
    ) {
        $this->storeFactory = $storeFactory;
        $this->resource = $resource;

        $this->identityMap = $identityMap ?? ObjectManager::getInstance()->get(IdentityMap::class);
    }

    public function getById($id)
    {
        $store = $this->storeFactory->create();
        $this->resource->load($store, $id);
        if (!$store->getId()) {
            throw new NoSuchEntityException(__('The CMS block with the "%1" ID doesn\'t exist.', $id));
        }

        return $store;
    }
}
