<?php

declare(strict_types=1);

namespace Laminas\Permissions\Acl;

use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Exception\ExceptionInterface;
use Laminas\Permissions\Acl\Exception\InvalidArgumentException;
use Laminas\Permissions\Acl\Exception\RuntimeException;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Throwable;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_pop;
use function array_unshift;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function strtoupper;

class Acl implements AclInterface
{
    /**
     * Rule type: allow
     */
    public const TYPE_ALLOW = 'TYPE_ALLOW';

    /**
     * Rule type: deny
     */
    public const TYPE_DENY = 'TYPE_DENY';

    /**
     * Rule operation: add
     */
    public const OP_ADD = 'OP_ADD';

    /**
     * Rule operation: remove
     */
    public const OP_REMOVE = 'OP_REMOVE';

    /**
     * Role registry
     *
     * @var Role\Registry|null
     */
    protected $roleRegistry;

    /**
     * Resource tree
     *
     * @var array
     */
    protected $resources = [];

    /** @var Role\RoleInterface|null */
    protected $isAllowedRole;

    /** @var ResourceInterface|null */
    protected $isAllowedResource;

    /** @var string|null */
    protected $isAllowedPrivilege;

    /**
     * ACL rules; whitelist (deny everything to all) by default
     *
     * @var array
     */
    protected $rules = [
        'allResources' => [
            'allRoles' => [
                'allPrivileges' => [
                    'type'   => self::TYPE_DENY,
                    'assert' => null,
                ],
                'byPrivilegeId' => [],
            ],
            'byRoleId' => [],
        ],
        'byResourceId' => [],
    ];

    /**
     * Adds a Role having an identifier unique to the registry
     *
     * The $parents parameter may be a reference to, or the string identifier for,
     * a Role existing in the registry, or $parents may be passed as an array of
     * these - mixing string identifiers and objects is ok - to indicate the Roles
     * from which the newly added Role will directly inherit.
     *
     * In order to resolve potential ambiguities with conflicting rules inherited
     * from different parents, the most recently added parent takes precedence over
     * parents that were previously added. In other words, the first parent added
     * will have the least priority, and the last parent added will have the
     * highest priority.
     *
     * @param  Role\RoleInterface|string       $role
     * @param  Role\RoleInterface|string|array $parents
     * @throws InvalidArgumentException
     * @return Acl Provides a fluent interface
     */
    public function addRole($role, $parents = null)
    {
        if (is_string($role)) {
            $role = new Role\GenericRole($role);
        } elseif (! $role instanceof Role\RoleInterface) {
            throw new InvalidArgumentException(
                'addRole() expects $role to be of type Laminas\Permissions\Acl\Role\RoleInterface'
            );
        }

        $this->getRoleRegistry()->add($role, $parents);

        return $this;
    }

    /**
     * Returns the identified Role
     *
     * The $role parameter can either be a Role or Role identifier.
     *
     * @param  Role\RoleInterface|string $role
     * @return Role\RoleInterface
     */
    public function getRole($role)
    {
        return $this->getRoleRegistry()->get($role);
    }

    /**
     * Returns true if and only if the Role exists in the registry
     *
     * The $role parameter can either be a Role or a Role identifier.
     *
     * @param  Role\RoleInterface|string $role
     * @return bool
     */
    public function hasRole($role)
    {
        return $this->getRoleRegistry()->has($role);
    }

    /**
     * Returns true if and only if $role inherits from $inherit
     *
     * Both parameters may be either a Role or a Role identifier. If
     * $onlyParents is true, then $role must inherit directly from
     * $inherit in order to return true. By default, this method looks
     * through the entire inheritance DAG to determine whether $role
     * inherits from $inherit through its ancestor Roles.
     *
     * @param  Role\RoleInterface|string    $role
     * @param  Role\RoleInterface|string    $inherit
     * @param  bool                      $onlyParents
     * @return bool
     */
    public function inheritsRole($role, $inherit, $onlyParents = false)
    {
        return $this->getRoleRegistry()->inherits($role, $inherit, $onlyParents);
    }

    /**
     * Removes the Role from the registry
     *
     * The $role parameter can either be a Role or a Role identifier.
     *
     * @param  Role\RoleInterface|string $role
     * @return Acl Provides a fluent interface
     */
    public function removeRole($role)
    {
        $this->getRoleRegistry()->remove($role);

        if ($role instanceof Role\RoleInterface) {
            $roleId = $role->getRoleId();
        } else {
            $roleId = $role;
        }

        foreach ($this->rules['allResources']['byRoleId'] as $roleIdCurrent => $rules) {
            if ($roleId === $roleIdCurrent) {
                unset($this->rules['allResources']['byRoleId'][$roleIdCurrent]);
            }
        }
        foreach ($this->rules['byResourceId'] as $resourceIdCurrent => $visitor) {
            if (array_key_exists('byRoleId', $visitor)) {
                foreach ($visitor['byRoleId'] as $roleIdCurrent => $rules) {
                    if ($roleId === $roleIdCurrent) {
                        unset($this->rules['byResourceId'][$resourceIdCurrent]['byRoleId'][$roleIdCurrent]);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Removes all Roles from the registry
     *
     * @return Acl Provides a fluent interface
     */
    public function removeRoleAll()
    {
        $this->getRoleRegistry()->removeAll();

        foreach ($this->rules['allResources']['byRoleId'] as $roleIdCurrent => $rules) {
            unset($this->rules['allResources']['byRoleId'][$roleIdCurrent]);
        }
        foreach ($this->rules['byResourceId'] as $resourceIdCurrent => $visitor) {
            foreach ($visitor['byRoleId'] as $roleIdCurrent => $rules) {
                unset($this->rules['byResourceId'][$resourceIdCurrent]['byRoleId'][$roleIdCurrent]);
            }
        }

        return $this;
    }

    /**
     * Adds a Resource having an identifier unique to the ACL
     *
     * The $parent parameter may be a reference to, or the string identifier for,
     * the existing Resource from which the newly added Resource will inherit.
     *
     * @param  ResourceInterface|string $resource
     * @param  ResourceInterface|string $parent
     * @throws InvalidArgumentException
     * @return Acl Provides a fluent interface
     */
    public function addResource($resource, $parent = null)
    {
        if (is_string($resource)) {
            $resource = new Resource\GenericResource($resource);
        } elseif (! $resource instanceof ResourceInterface) {
            throw new InvalidArgumentException(
                'addResource() expects $resource to be of type Laminas\Permissions\Acl\Resource\ResourceInterface'
            );
        }

        $resourceId = $resource->getResourceId();

        if ($this->hasResource($resourceId)) {
            throw new InvalidArgumentException("Resource id '$resourceId' already exists in the ACL");
        }

        $resourceParent = null;

        if (null !== $parent) {
            try {
                if ($parent instanceof ResourceInterface) {
                    $resourceParentId = $parent->getResourceId();
                } else {
                    $resourceParentId = $parent;
                }
                $resourceParent = $this->getResource($resourceParentId);
            } catch (Throwable $e) {
                throw new InvalidArgumentException(sprintf(
                    'Parent Resource id "%s" does not exist',
                    $resourceParentId
                ), 0, $e);
            }
            $this->resources[$resourceParentId]['children'][$resourceId] = $resource;
        }

        $this->resources[$resourceId] = [
            'instance' => $resource,
            'parent'   => $resourceParent,
            'children' => [],
        ];

        return $this;
    }

    /**
     * Returns the identified Resource
     *
     * The $resource parameter can either be a Resource or a Resource identifier.
     *
     * @param  ResourceInterface|string $resource
     * @throws InvalidArgumentException
     * @return ResourceInterface
     */
    public function getResource($resource)
    {
        if ($resource instanceof ResourceInterface) {
            $resourceId = $resource->getResourceId();
        } else {
            $resourceId = (string) $resource;
        }

        if (! $this->hasResource($resource)) {
            throw new InvalidArgumentException("Resource '$resourceId' not found");
        }

        return $this->resources[$resourceId]['instance'];
    }

    /**
     * Returns true if and only if the Resource exists in the ACL
     *
     * The $resource parameter can either be a Resource or a Resource identifier.
     *
     * @param  ResourceInterface|string $resource
     * @return bool
     */
    public function hasResource($resource)
    {
        if ($resource instanceof ResourceInterface) {
            $resourceId = $resource->getResourceId();
        } else {
            $resourceId = (string) $resource;
        }

        return isset($this->resources[$resourceId]);
    }

    /**
     * Returns true if and only if $resource inherits from $inherit
     *
     * Both parameters may be either a Resource or a Resource identifier. If
     * $onlyParent is true, then $resource must inherit directly from
     * $inherit in order to return true. By default, this method looks
     * through the entire inheritance tree to determine whether $resource
     * inherits from $inherit through its ancestor Resources.
     *
     * @param  ResourceInterface|string $resource
     * @param  ResourceInterface|string $inherit
     * @param  bool                              $onlyParent
     * @throws InvalidArgumentException
     * @return bool
     */
    public function inheritsResource($resource, $inherit, $onlyParent = false)
    {
        try {
            $resourceId = $this->getResource($resource)->getResourceId();
            $inheritId  = $this->getResource($inherit)->getResourceId();
        } catch (ExceptionInterface $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        if (null !== $this->resources[$resourceId]['parent']) {
            $parentId = $this->resources[$resourceId]['parent']->getResourceId();
            if ($inheritId === $parentId) {
                return true;
            } elseif ($onlyParent) {
                return false;
            }
        } else {
            return false;
        }

        while (null !== $this->resources[$parentId]['parent']) {
            $parentId = $this->resources[$parentId]['parent']->getResourceId();
            if ($inheritId === $parentId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes a Resource and all of its children
     *
     * The $resource parameter can either be a Resource or a Resource identifier.
     *
     * @param  ResourceInterface|string $resource
     * @throws InvalidArgumentException
     * @return Acl Provides a fluent interface
     */
    public function removeResource($resource)
    {
        try {
            $resourceId = $this->getResource($resource)->getResourceId();
        } catch (ExceptionInterface $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        $resourcesRemoved = [$resourceId];
        if (null !== ($resourceParent = $this->resources[$resourceId]['parent'])) {
            unset($this->resources[$resourceParent->getResourceId()]['children'][$resourceId]);
        }
        foreach ($this->resources[$resourceId]['children'] as $childId => $child) {
            $this->removeResource($childId);
            $resourcesRemoved[] = $childId;
        }

        foreach ($resourcesRemoved as $resourceIdRemoved) {
            foreach ($this->rules['byResourceId'] as $resourceIdCurrent => $rules) {
                if ($resourceIdRemoved === $resourceIdCurrent) {
                    unset($this->rules['byResourceId'][$resourceIdCurrent]);
                }
            }
        }

        unset($this->resources[$resourceId]);

        return $this;
    }

    /**
     * Removes all Resources
     *
     * @return Acl Provides a fluent interface
     */
    public function removeResourceAll()
    {
        foreach ($this->resources as $resourceId => $resource) {
            unset($this->rules['byResourceId'][$resourceId]);
        }

        $this->resources = [];

        return $this;
    }

    /**
     * Adds an "allow" rule to the ACL
     *
     * @param  Role\RoleInterface|string|array|null         $roles
     * @param  ResourceInterface|string|array|null $resources
     * @param  string|array|null                            $privileges
     * @return Acl Provides a fluent interface
     */
    public function allow(
        $roles = null,
        $resources = null,
        $privileges = null,
        ?AssertionInterface $assert = null
    ) {
        return $this->setRule(self::OP_ADD, self::TYPE_ALLOW, $roles, $resources, $privileges, $assert);
    }

    /**
     * Adds a "deny" rule to the ACL
     *
     * @param  Role\RoleInterface|string|array|null         $roles
     * @param  ResourceInterface|string|array|null $resources
     * @param  string|array|null                            $privileges
     * @return Acl Provides a fluent interface
     */
    public function deny(
        $roles = null,
        $resources = null,
        $privileges = null,
        ?AssertionInterface $assert = null
    ) {
        return $this->setRule(self::OP_ADD, self::TYPE_DENY, $roles, $resources, $privileges, $assert);
    }

    /**
     * Removes "allow" permissions from the ACL
     *
     * @param  Role\RoleInterface|string|array|null         $roles
     * @param  ResourceInterface|string|array|null $resources
     * @param  string|array|null                            $privileges
     * @return Acl Provides a fluent interface
     */
    public function removeAllow($roles = null, $resources = null, $privileges = null)
    {
        return $this->setRule(self::OP_REMOVE, self::TYPE_ALLOW, $roles, $resources, $privileges);
    }

    /**
     * Removes "deny" restrictions from the ACL
     *
     * @param  Role\RoleInterface|string|array|null         $roles
     * @param  ResourceInterface|string|array|null $resources
     * @param  string|array|null                            $privileges
     * @return Acl Provides a fluent interface
     */
    public function removeDeny($roles = null, $resources = null, $privileges = null)
    {
        return $this->setRule(self::OP_REMOVE, self::TYPE_DENY, $roles, $resources, $privileges);
    }

    /**
     * Performs operations on ACL rules
     *
     * The $operation parameter may be either OP_ADD or OP_REMOVE, depending on whether the
     * user wants to add or remove a rule, respectively:
     *
     * OP_ADD specifics:
     *
     *      A rule is added that would allow one or more Roles access to [certain $privileges
     *      upon] the specified Resource(s).
     *
     * OP_REMOVE specifics:
     *
     *      The rule is removed only in the context of the given Roles, Resources, and privileges.
     *      Existing rules to which the remove operation does not apply would remain in the
     *      ACL.
     *
     * The $type parameter may be either TYPE_ALLOW or TYPE_DENY, depending on whether the
     * rule is intended to allow or deny permission, respectively.
     *
     * The $roles and $resources parameters may be references to, or the string identifiers for,
     * existing Resources/Roles, or they may be passed as arrays of these - mixing string identifiers
     * and objects is ok - to indicate the Resources and Roles to which the rule applies. If either
     * $roles or $resources is null, then the rule applies to all Roles or all Resources, respectively.
     * Both may be null in order to work with the default rule of the ACL.
     *
     * The $privileges parameter may be used to further specify that the rule applies only
     * to certain privileges upon the Resource(s) in question. This may be specified to be a single
     * privilege with a string, and multiple privileges may be specified as an array of strings.
     *
     * If $assert is provided, then its assert() method must return true in order for
     * the rule to apply. If $assert is provided with $roles, $resources, and $privileges all
     * equal to null, then a rule having a type of:
     *
     *      TYPE_ALLOW will imply a type of TYPE_DENY, and
     *
     *      TYPE_DENY will imply a type of TYPE_ALLOW
     *
     * when the rule's assertion fails. This is because the ACL needs to provide expected
     * behavior when an assertion upon the default ACL rule fails.
     *
     * @param  string                                        $operation
     * @param  string                                        $type
     * @param  Role\RoleInterface|string|array|null          $roles
     * @param  ResourceInterface|string|array|null  $resources
     * @param  string|array|null                             $privileges
     * @throws InvalidArgumentException
     * @return Acl Provides a fluent interface
     */
    public function setRule(
        $operation,
        $type,
        $roles = null,
        $resources = null,
        $privileges = null,
        ?AssertionInterface $assert = null
    ) {
        // ensure that the rule type is valid; normalize input to uppercase
        $type = strtoupper($type);
        if (self::TYPE_ALLOW !== $type && self::TYPE_DENY !== $type) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported rule type; must be either "%s" or "%s"',
                self::TYPE_ALLOW,
                self::TYPE_DENY
            ));
        }

        // ensure that all specified Roles exist; normalize input to array of Role objects or null
        if (! is_array($roles)) {
            $roles = [$roles];
        } elseif (! $roles) {
            $roles = [null];
        }
        $rolesTemp = $roles;
        $roles     = [];
        foreach ($rolesTemp as $role) {
            if (null !== $role) {
                $roles[] = $this->getRoleRegistry()->get($role);
            } else {
                $roles[] = null;
            }
        }
        unset($rolesTemp);

        // ensure that all specified Resources exist; normalize input to array of Resource objects or null
        if (! is_array($resources)) {
            if (null === $resources && $this->resources) {
                $resources = array_keys($this->resources);
                // Passing a null resource; make sure "global" permission is also set!
                if (! in_array(null, $resources)) {
                    array_unshift($resources, null);
                }
            } else {
                $resources = [$resources];
            }
        } elseif (! $resources) {
            $resources = [null];
        }
        $resourcesTemp = $resources;
        $resources     = [];
        foreach ($resourcesTemp as $resource) {
            if (null !== $resource) {
                $resourceObj            = $this->getResource($resource);
                $resourceId             = $resourceObj->getResourceId();
                $children               = $this->getChildResources($resourceObj);
                $resources              = array_merge($resources, $children);
                $resources[$resourceId] = $resourceObj;
            } else {
                $resources[] = null;
            }
        }
        unset($resourcesTemp);

        // normalize privileges to array
        if (null === $privileges) {
            $privileges = [];
        } elseif (! is_array($privileges)) {
            $privileges = [$privileges];
        }

        switch ($operation) {
            // add to the rules
            case self::OP_ADD:
                foreach ($resources as $resource) {
                    foreach ($roles as $role) {
                        $rules =& $this->getRules($resource, $role, true);
                        if (! $privileges) {
                            $rules['allPrivileges']['type']   = $type;
                            $rules['allPrivileges']['assert'] = $assert;
                            if (! isset($rules['byPrivilegeId'])) {
                                $rules['byPrivilegeId'] = [];
                            }
                        } else {
                            foreach ($privileges as $privilege) {
                                $rules['byPrivilegeId'][$privilege]['type']   = $type;
                                $rules['byPrivilegeId'][$privilege]['assert'] = $assert;
                            }
                        }
                    }
                }
                break;

            // remove from the rules
            case self::OP_REMOVE:
                foreach ($resources as $resource) {
                    foreach ($roles as $role) {
                        $rules =& $this->getRules($resource, $role);
                        if (null === $rules) {
                            continue;
                        }
                        if (! $privileges) {
                            if (null === $resource && null === $role) {
                                if ($type === $rules['allPrivileges']['type']) {
                                    $rules = [
                                        'allPrivileges' => [
                                            'type'   => self::TYPE_DENY,
                                            'assert' => null,
                                        ],
                                        'byPrivilegeId' => [],
                                    ];
                                }
                                continue;
                            }

                            if (isset($rules['allPrivileges']['type']) && $type === $rules['allPrivileges']['type']) {
                                unset($rules['allPrivileges']);
                            }
                        } else {
                            foreach ($privileges as $privilege) {
                                if (
                                    isset($rules['byPrivilegeId'][$privilege])
                                    && $type === $rules['byPrivilegeId'][$privilege]['type']
                                ) {
                                    unset($rules['byPrivilegeId'][$privilege]);
                                }
                            }
                        }
                    }
                }
                break;

            default:
                throw new InvalidArgumentException(sprintf(
                    'Unsupported operation; must be either "%s" or "%s"',
                    self::OP_ADD,
                    self::OP_REMOVE
                ));
        }

        return $this;
    }

    /**
     * Returns all child resources from the given resource.
     *
     * @return ResourceInterface[]
     */
    protected function getChildResources(ResourceInterface $resource)
    {
        $return = [];
        $id     = $resource->getResourceId();

        $children = $this->resources[$id]['children'];
        foreach ($children as $child) {
            $childReturn                          = $this->getChildResources($child);
            $childReturn[$child->getResourceId()] = $child;

            $return = array_merge($return, $childReturn);
        }

        return $return;
    }

    /**
     * Returns true if and only if the Role has access to the Resource
     *
     * The $role and $resource parameters may be references to, or the string identifiers for,
     * an existing Resource and Role combination.
     *
     * If either $role or $resource is null, then the query applies to all Roles or all Resources,
     * respectively. Both may be null to query whether the ACL has a "blacklist" rule
     * (allow everything to all). By default, Laminas\Permissions\Acl creates a "whitelist" rule (deny
     * everything to all), and this method would return false unless this default has
     * been overridden (i.e., by executing $acl->allow()).
     *
     * If a $privilege is not provided, then this method returns false if and only if the
     * Role is denied access to at least one privilege upon the Resource. In other words, this
     * method returns true if and only if the Role is allowed all privileges on the Resource.
     *
     * This method checks Role inheritance using a depth-first traversal of the Role registry.
     * The highest priority parent (i.e., the parent most recently added) is checked first,
     * and its respective parents are checked similarly before the lower-priority parents of
     * the Role are checked.
     *
     * @param  Role\RoleInterface|string            $role
     * @param  ResourceInterface|string    $resource
     * @param  string                               $privilege
     * @return bool
     */
    public function isAllowed($role = null, $resource = null, $privilege = null)
    {
        // reset role & resource to null
        $this->isAllowedRole      = null;
        $this->isAllowedResource  = null;
        $this->isAllowedPrivilege = null;

        if (null !== $role) {
            // keep track of originally called role
            $this->isAllowedRole = $role;
            $role                = $this->getRoleRegistry()->get($role);
            if (! $this->isAllowedRole instanceof Role\RoleInterface) {
                $this->isAllowedRole = $role;
            }
        }

        if (null !== $resource) {
            // keep track of originally called resource
            $this->isAllowedResource = $resource;
            $resource                = $this->getResource($resource);
            if (! $this->isAllowedResource instanceof ResourceInterface) {
                $this->isAllowedResource = $resource;
            }
        }

        if (null === $privilege) {
            // query on all privileges
            do {
                // depth-first search on $role if it is not 'allRoles' pseudo-parent
                if (null !== $role && null !== ($result = $this->roleDFSAllPrivileges($role, $resource, $privilege))) {
                    return $result;
                }

                // look for rule on 'allRoles' pseudo-parent
                if (null !== ($rules = $this->getRules($resource, null))) {
                    foreach ($rules['byPrivilegeId'] as $privilege => $rule) {
                        if (self::TYPE_DENY === $this->getRuleType($resource, null, $privilege)) {
                            return false;
                        }
                    }
                    $ruleTypeAllPrivileges = $this->getRuleType($resource, null, null);
                    if (null !== $ruleTypeAllPrivileges) {
                        return self::TYPE_ALLOW === $ruleTypeAllPrivileges;
                    }
                }

                // try next Resource
                $resource = $this->resources[$resource->getResourceId()]['parent'];
            } while (true); // loop terminates at 'allResources' pseudo-parent
        } else {
            $this->isAllowedPrivilege = $privilege;
            // query on one privilege
            do {
                // depth-first search on $role if it is not 'allRoles' pseudo-parent
                if (null !== $role && null !== ($result = $this->roleDFSOnePrivilege($role, $resource, $privilege))) {
                    return $result;
                }

                // look for rule on 'allRoles' pseudo-parent
                if (null !== ($ruleType = $this->getRuleType($resource, null, $privilege))) {
                    return self::TYPE_ALLOW === $ruleType;
                } elseif (null !== ($ruleTypeAllPrivileges = $this->getRuleType($resource, null, null))) {
                    $result = self::TYPE_ALLOW === $ruleTypeAllPrivileges;
                    if ($result || null === $resource) {
                        return $result;
                    }
                }

                // try next Resource
                $resource = $this->resources[$resource->getResourceId()]['parent'];
            } while (true); // loop terminates at 'allResources' pseudo-parent
        }
    }

    /**
     * Returns the Role registry for this ACL
     *
     * If no Role registry has been created yet, a new default Role registry
     * is created and returned.
     *
     * @return Role\Registry
     */
    protected function getRoleRegistry()
    {
        if (null === $this->roleRegistry) {
            $this->roleRegistry = new Role\Registry();
        }
        return $this->roleRegistry;
    }

    /**
     * Performs a depth-first search of the Role DAG, starting at $role, in order to find a rule
     * allowing/denying $role access to all privileges upon $resource
     *
     * This method returns true if a rule is found and allows access. If a rule exists and denies access,
     * then this method returns false. If no applicable rule is found, then this method returns null.
     *
     * @return bool|null
     */
    protected function roleDFSAllPrivileges(Role\RoleInterface $role, ?ResourceInterface $resource = null)
    {
        $dfs = [
            'visited' => [],
            'stack'   => [],
        ];

        if (null !== ($result = $this->roleDFSVisitAllPrivileges($role, $resource, $dfs))) {
            return $result;
        }

        // This comment is needed due to a strange php-cs-fixer bug
        while (null !== ($role = array_pop($dfs['stack']))) {
            if (! isset($dfs['visited'][$role->getRoleId()])) {
                if (null !== ($result = $this->roleDFSVisitAllPrivileges($role, $resource, $dfs))) {
                    return $result;
                }
            }
        }
    }

    /**
     * Visits an $role in order to look for a rule allowing/denying $role access to all privileges upon $resource
     *
     * This method returns true if a rule is found and allows access. If a rule exists and denies access,
     * then this method returns false. If no applicable rule is found, then this method returns null.
     *
     * This method is used by the internal depth-first search algorithm and may modify the DFS data structure.
     *
     * @param  array &$dfs
     * @return bool|void
     * @throws RuntimeException
     */
    protected function roleDFSVisitAllPrivileges(
        Role\RoleInterface $role,
        ?ResourceInterface $resource = null,
        &$dfs = null
    ) {
        if (null === $dfs) {
            throw new RuntimeException('$dfs parameter may not be null');
        }

        if (null !== ($rules = $this->getRules($resource, $role))) {
            foreach ($rules['byPrivilegeId'] as $privilege => $rule) {
                if (self::TYPE_DENY === ($ruleTypeOnePrivilege = $this->getRuleType($resource, $role, $privilege))) {
                    return false;
                }
            }
            if (null !== ($ruleTypeAllPrivileges = $this->getRuleType($resource, $role, null))) {
                return self::TYPE_ALLOW === $ruleTypeAllPrivileges;
            }
        }

        $dfs['visited'][$role->getRoleId()] = true;
        foreach ($this->getRoleRegistry()->getParents($role) as $roleParent) {
            $dfs['stack'][] = $roleParent;
        }
    }

    /**
     * Performs a depth-first search of the Role DAG, starting at $role, in order to find a rule
     * allowing/denying $role access to a $privilege upon $resource
     *
     * This method returns true if a rule is found and allows access. If a rule exists and denies access,
     * then this method returns false. If no applicable rule is found, then this method returns null.
     *
     * @param  string|null                     $privilege
     * @return bool|void
     * @throws RuntimeException
     */
    protected function roleDFSOnePrivilege(
        Role\RoleInterface $role,
        ?ResourceInterface $resource = null,
        $privilege = null
    ) {
        if (null === $privilege) {
            throw new RuntimeException('$privilege parameter may not be null');
        }

        $dfs = [
            'visited' => [],
            'stack'   => [],
        ];

        if (null !== ($result = $this->roleDFSVisitOnePrivilege($role, $resource, $privilege, $dfs))) {
            return $result;
        }

        // This comment is needed due to a strange php-cs-fixer bug
        while (null !== ($role = array_pop($dfs['stack']))) {
            if (! isset($dfs['visited'][$role->getRoleId()])) {
                if (null !== ($result = $this->roleDFSVisitOnePrivilege($role, $resource, $privilege, $dfs))) {
                    return $result;
                }
            }
        }
    }

    /**
     * Visits an $role in order to look for a rule allowing/denying $role access to a $privilege upon $resource
     *
     * This method returns true if a rule is found and allows access. If a rule exists and denies access,
     * then this method returns false. If no applicable rule is found, then this method returns null.
     *
     * This method is used by the internal depth-first search algorithm and may modify the DFS data structure.
     *
     * @param  string|null                     $privilege
     * @param  array                           $dfs
     * @return bool|void
     * @throws RuntimeException
     */
    protected function roleDFSVisitOnePrivilege(
        Role\RoleInterface $role,
        ?ResourceInterface $resource = null,
        $privilege = null,
        &$dfs = null
    ) {
        if (null === $privilege) {
            throw new RuntimeException('$privilege parameter may not be null');
        }

        if (null === $dfs) {
            throw new RuntimeException('$dfs parameter may not be null');
        }

        if (null !== ($ruleTypeOnePrivilege = $this->getRuleType($resource, $role, $privilege))) {
            return self::TYPE_ALLOW === $ruleTypeOnePrivilege;
        } elseif (null !== ($ruleTypeAllPrivileges = $this->getRuleType($resource, $role, null))) {
            return self::TYPE_ALLOW === $ruleTypeAllPrivileges;
        }

        $dfs['visited'][$role->getRoleId()] = true;
        foreach ($this->getRoleRegistry()->getParents($role) as $roleParent) {
            $dfs['stack'][] = $roleParent;
        }
    }

    /**
     * Returns the rule type associated with the specified Resource, Role, and privilege
     * combination.
     *
     * If a rule does not exist or its attached assertion fails, which means that
     * the rule is not applicable, then this method returns null. Otherwise, the
     * rule type applies and is returned as either TYPE_ALLOW or TYPE_DENY.
     *
     * If $resource or $role is null, then this means that the rule must apply to
     * all Resources or Roles, respectively.
     *
     * If $privilege is null, then the rule must apply to all privileges.
     *
     * If all three parameters are null, then the default ACL rule type is returned,
     * based on whether its assertion method passes.
     *
     * @param  null|string                      $privilege
     * @return string|null
     */
    protected function getRuleType(
        ?ResourceInterface $resource = null,
        ?Role\RoleInterface $role = null,
        $privilege = null
    ) {
        // Pull all rules for the specified $resource and $role
        if (null === ($rules = $this->getRules($resource, $role))) {
            // No rules discovered
            return null;
        }

        // Follow $privilege
        $rule = null;
        if (null === $privilege && isset($rules['allPrivileges'])) {
            // No privilege specified, but allPrivileges rule exists
            $rule = $rules['allPrivileges'];
        }

        if (null !== $privilege && isset($rules['byPrivilegeId'][$privilege])) {
            // Privilege specified, and found in ruleset
            $rule = $rules['byPrivilegeId'][$privilege];
        }

        if (null === $rule) {
            // No rule identified
            return null;
        }

        // Was a custom assertion supplied? Use it to retrieve the rule type.
        if ($rule['assert']) {
            /** @var AssertionInterface $assertion */
            $assertion      = $rule['assert'];
            $assertionValue = $assertion->assert(
                $this,
                $this->isAllowedRole instanceof Role\RoleInterface ? $this->isAllowedRole : $role,
                $this->isAllowedResource instanceof ResourceInterface ? $this->isAllowedResource : $resource,
                $this->isAllowedPrivilege
            );
        } else {
            $assertionValue = true;
        }

        if ($assertionValue) {
            return $rule['type'];
        }

        if (null !== $resource || null !== $role || null !== $privilege) {
            return null;
        }

        if (self::TYPE_ALLOW === $rule['type']) {
            return self::TYPE_DENY;
        }

        return self::TYPE_ALLOW;
    }

    /**
     * Returns the rules associated with a Resource and a Role, or null if no such rules exist
     *
     * If either $resource or $role is null, this means that the rules returned are for all Resources or all Roles,
     * respectively. Both can be null to return the default rule set for all Resources and all Roles.
     *
     * If the $create parameter is true, then a rule set is first created and then returned to the caller.
     *
     * @param  bool $create
     * @return array|null
     */
    protected function &getRules(
        ?ResourceInterface $resource = null,
        ?Role\RoleInterface $role = null,
        $create = false
    ) {
        // create a reference to null
        $null    = null;
        $nullRef =& $null;

        // follow $resource
        do {
            if (null === $resource) {
                $visitor =& $this->rules['allResources'];
                break;
            }
            $resourceId = $resource->getResourceId();
            if (! isset($this->rules['byResourceId'][$resourceId])) {
                if (! $create) {
                    return $nullRef;
                }
                $this->rules['byResourceId'][$resourceId] = [];
            }
            $visitor =& $this->rules['byResourceId'][$resourceId];
        } while (false);

        // follow $role
        if (null === $role) {
            if (! isset($visitor['allRoles'])) {
                if (! $create) {
                    return $nullRef;
                }
                $visitor['allRoles']['byPrivilegeId'] = [];
            }
            return $visitor['allRoles'];
        }
        $roleId = $role->getRoleId();
        if (! isset($visitor['byRoleId'][$roleId])) {
            if (! $create) {
                return $nullRef;
            }
            $visitor['byRoleId'][$roleId]['byPrivilegeId'] = [];
        }
        return $visitor['byRoleId'][$roleId];
    }

    /**
     * @return array of registered roles
     */
    public function getRoles()
    {
        return array_keys($this->getRoleRegistry()->getRoles());
    }

    /**
     * @return array of registered resources
     */
    public function getResources()
    {
        return array_keys($this->resources);
    }
}
