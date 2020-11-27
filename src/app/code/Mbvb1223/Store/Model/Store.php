<?php

namespace Mbvb1223\Store\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Mbvb1223\Store\Api\Data\StoreInterface;

class Store extends AbstractModel implements StoreInterface, IdentityInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'mbvb1223_store_store';

    /**
     * Post Initialization
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mbvb1223\Store\Model\ResourceModel\Store');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::STORE_ID);
    }

    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    public function getName()
    {
        return $this->getData(self::NAME);
    }

    public function getCode()
    {
        return $this->getData(self::CODE);
    }

    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    public function setCode($code)
    {
        return $this->setData(self::CODE, $code);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
