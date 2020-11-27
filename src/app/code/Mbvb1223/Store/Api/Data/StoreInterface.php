<?php

namespace Mbvb1223\Store\Api\Data;

interface StoreInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const STORE_ID = 'store_id';
    const NAME = 'name';
    const STATUS = 'status_id';
    const CODE = 'code';
    const CREATED_AT = 'created_at';
    /**#@-*/


    /**
     * @return string|null
     */
    public function getName();

    /**
     * @return string|null
     */
    public function getCode();

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code);

    /**
     * @param int $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Set ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);
}
