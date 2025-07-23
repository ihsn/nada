<?php

declare(strict_types=1);

namespace Laminas\Permissions\Acl\Role;

interface RoleInterface
{
    /**
     * Returns the string identifier of the Role
     *
     * @return string
     */
    public function getRoleId();
}
