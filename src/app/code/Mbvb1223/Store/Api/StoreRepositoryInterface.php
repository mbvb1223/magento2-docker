<?php

namespace Mbvb1223\Store\Api;


use Mbvb1223\Store\Api\Data\StoreInterface;

/**
 * Interface StoreRepositoryInterface
 * @package Mbvb1223\Store\Api
 */
interface StoreRepositoryInterface
{
    /**
     * @param string $id
     * @return StoreInterface
     */
    public function getById($id);
}
