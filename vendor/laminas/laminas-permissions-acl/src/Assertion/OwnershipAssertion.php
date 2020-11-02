<?php

/**
 * @see       https://github.com/laminas/laminas-permissions-acl for the canonical source repository
 * @copyright https://github.com/laminas/laminas-permissions-acl/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-permissions-acl/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Permissions\Acl\Assertion;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\ProprietaryInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

/**
 * Makes sure that some Resource is owned by certain Role.
 */
class OwnershipAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null, ResourceInterface $resource = null, $privilege = null)
    {
        //Assert passes if role or resource is not proprietary
        if (! $role instanceof ProprietaryInterface || ! $resource instanceof ProprietaryInterface) {
            return true;
        }

        //Assert passes if resources does not have an owner
        if ($resource->getOwnerId() === null) {
            return true;
        }

        return ($resource->getOwnerId() === $role->getOwnerId());
    }
}
