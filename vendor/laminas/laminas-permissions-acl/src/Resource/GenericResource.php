<?php

/**
 * @see       https://github.com/laminas/laminas-permissions-acl for the canonical source repository
 * @copyright https://github.com/laminas/laminas-permissions-acl/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-permissions-acl/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Permissions\Acl\Resource;

class GenericResource implements ResourceInterface
{
    /**
     * Unique id of Resource
     *
     * @var string
     */
    protected $resourceId;

    /**
     * Sets the Resource identifier
     *
     * @param  string $resourceId
     */
    public function __construct($resourceId)
    {
        $this->resourceId = (string) $resourceId;
    }

    /**
     * Defined by ResourceInterface; returns the Resource identifier
     *
     * @return string
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * Defined by ResourceInterface; returns the Resource identifier
     * Proxies to getResourceId()
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getResourceId();
    }
}
