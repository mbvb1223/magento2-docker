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
//        $page = $this->storeFactory->create();
//        $page->load($id);
//        if (!$page->getId()) {
//            throw new NoSuchEntityException(__('The CMS page with the "%1" ID doesn\'t exist.', $id));
//        }
//        $this->identityMap->add($page);
//
//        return $page;

        $block = $this->storeFactory->create();
        $this->resource->load($block, $id);
        if (!$block->getId()) {
            throw new NoSuchEntityException(__('The CMS block with the "%1" ID doesn\'t exist.', $id));
        }

        var_dump($block);die();
        return $block;
    }
}
