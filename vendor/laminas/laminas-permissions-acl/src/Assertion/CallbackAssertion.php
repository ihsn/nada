<?php

declare(strict_types=1);

namespace Laminas\Permissions\Acl\Assertion;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Exception\InvalidArgumentException;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

use function call_user_func;
use function is_callable;

class CallbackAssertion implements AssertionInterface
{
    /** @var callable */
    protected $callback;

    /**
     * @param callable $callback The assertion callback
     */
    public function __construct($callback)
    {
        if (! is_callable($callback)) {
            throw new InvalidArgumentException('Invalid callback provided; not callable');
        }
        $this->callback = $callback;
    }

    /**
     * Returns true if and only if the assertion conditions are met.
     *
     * This method is passed the ACL, Role, Resource, and privilege to which the
     * authorization query applies.
     *
     * If the $role, $resource, or $privilege parameters are null, it means
     * that the query applies to all Roles, Resources, or privileges,
     * respectively.
     *
     * @param string            $privilege
     * @return bool
     */
    public function assert(
        Acl $acl,
        ?RoleInterface $role = null,
        ?ResourceInterface $resource = null,
        $privilege = null
    ) {
        return (bool) call_user_func($this->callback, $acl, $role, $resource, $privilege);
    }
}
