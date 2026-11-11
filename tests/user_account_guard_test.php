<?php

$root = dirname(__DIR__);
define('BASEPATH', $root . '/system/');
define('APPPATH', $root . '/application/');

require APPPATH . 'libraries/User_account_guard.php';

function assert_test($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class Fake_acl_manager
{
    public $current;
    private $admin_ids;
    private $roles;

    public function __construct($current_id, array $admin_ids, array $roles)
    {
        $this->current = $current_id ? (object) array('id' => $current_id) : null;
        $this->admin_ids = $admin_ids;
        $this->roles = $roles;
    }

    public function current_user()
    {
        return $this->current;
    }

    public function user_is_admin($user = null)
    {
        if (empty($user)) {
            $user = $this->current;
        }
        if (!$user) {
            throw new Exception('acl_manager::User not set');
        }
        return in_array((int) $user->id, $this->admin_ids, true);
    }

    public function get_roles()
    {
        return $this->roles;
    }
}

class Fake_ion_auth
{
    private $ids;

    public function __construct(array $ids)
    {
        $this->ids = $ids;
    }

    public function get_user($id)
    {
        return in_array((int) $id, $this->ids, true) ? (object) array('id' => (int) $id) : false;
    }
}

const ADMIN_CALLER = 10;
const STAFF_CALLER = 20;
const OTHER_ADMIN  = 30;
const REGULAR_USER = 40;

function guard_for($caller_id)
{
    $roles = array(
        array('id' => 1, 'name' => 'admin', 'is_admin' => 1),
        array('id' => 2, 'name' => 'editor', 'is_admin' => 0),
        array('id' => 3, 'name' => 'viewer', 'is_admin' => 0),
    );
    $acl = new Fake_acl_manager($caller_id, array(ADMIN_CALLER, OTHER_ADMIN), $roles);
    $auth = new Fake_ion_auth(array(ADMIN_CALLER, STAFF_CALLER, OTHER_ADMIN, REGULAR_USER));

    return new User_account_guard(array('acl_manager' => $acl, 'ion_auth' => $auth));
}

$checked = 0;
function check($condition, $message)
{
    global $checked;
    assert_test($condition, $message);
    $checked++;
}

// Site admin signed in.
$admin = guard_for(ADMIN_CALLER);
check($admin->caller_is_admin(), 'admin caller is admin');
check(count($admin->assignable_roles()) === 3, 'admin may assign every role');
check($admin->filter_assignable_role_ids(array(1, 2, 'x', 2, 99, -1)) === array(1, 2), 'admin role filter keeps known ids once');
check($admin->filter_assignable_role_ids('1') === array(), 'non-array role ids are rejected');
check(!$admin->target_is_protected_admin(OTHER_ADMIN), 'admins are not blocked from admin accounts');
check(!$admin->may_change_user_account(ADMIN_CALLER), 'bulk actions skip own account (admin)');
check($admin->may_change_user_account(OTHER_ADMIN), 'admin may bulk-change another admin');
check($admin->may_change_user_account(REGULAR_USER), 'admin may bulk-change a regular user');
check(!$admin->may_change_user_account(0), 'user id 0 is rejected');
check(!$admin->may_change_user_account('abc'), 'non-numeric user id is rejected');
check($admin->would_remove_own_admin_role(ADMIN_CALLER, array(2)), 'admin dropping own admin role is caught');
check(!$admin->would_remove_own_admin_role(ADMIN_CALLER, array(1, 2)), 'admin keeping own admin role is allowed');
check(!$admin->would_remove_own_admin_role(REGULAR_USER, array(2)), 'own-role rule only applies to own account');
check($admin->may_change_roles(ADMIN_CALLER), 'admin may change own roles');
check($admin->may_change_roles(OTHER_ADMIN), 'admin may change another admin\'s roles');

// Staff member with user edit rights but no admin role.
$staff = guard_for(STAFF_CALLER);
check(!$staff->caller_is_admin(), 'staff caller is not admin');
check(count($staff->assignable_roles()) === 2, 'staff may not assign admin roles');
check($staff->filter_assignable_role_ids(array(1, 2, 3)) === array(2, 3), 'staff role filter drops admin role');
check($staff->role_ids_include_admin(array(2, '1')), 'admin role detected in a list');
check(!$staff->role_ids_include_admin(array(2, 3)), 'non-admin roles are not admin');
check($staff->target_is_protected_admin(OTHER_ADMIN), 'admin account is protected from staff');
check(!$staff->target_is_protected_admin(REGULAR_USER), 'regular account is not protected');
check(!$staff->target_is_protected_admin(999), 'unknown account is not reported as admin');
check(!$staff->may_change_user_account(OTHER_ADMIN), 'staff may not bulk-change an admin');
check(!$staff->may_change_user_account(STAFF_CALLER), 'bulk actions skip own account (staff)');
check($staff->may_change_user_account(REGULAR_USER), 'staff may bulk-change a regular user');
check(!$staff->would_remove_own_admin_role(STAFF_CALLER, array()), 'own-admin-role rule ignores non-admins');
check(!$staff->may_change_roles(STAFF_CALLER), 'staff may not change own roles');
check(!$staff->may_change_roles(OTHER_ADMIN), 'staff may not change an admin\'s roles');
check($staff->may_change_roles(REGULAR_USER), 'staff may change a regular user\'s roles');

// Nobody signed in.
$anon = guard_for(0);
check($anon->current_user_id() === 0, 'no caller has id 0');
check(!$anon->caller_is_admin(), 'no caller is not admin');
check($anon->target_is_protected_admin(OTHER_ADMIN), 'admin account protected when nobody is signed in');
check(!$anon->may_change_user_account(OTHER_ADMIN), 'admin account cannot be bulk-changed when nobody is signed in');

echo "OK: {$checked} user account guard checks passed\n";
