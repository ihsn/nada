<?php
/**
 * Live checks for the admin permissions (roles) API against a local install.
 *
 * Creates file sessions for a site admin and a non-admin user, runs the requests with curl and
 * removes the test role it creates.
 *
 *   php tests/permissions_api_live_test.php [base_url] [admin_user_id] [staff_user_id]
 */

$base     = isset($argv[1]) ? rtrim($argv[1], '/') : 'http://localhost/nada-release/index.php';
$admin_id = isset($argv[2]) ? (int) $argv[2] : 66;
$staff_id = isset($argv[3]) ? (int) $argv[3] : 67;

$root = dirname(__DIR__);
$sessions_dir = $root . '/files/sessions';
$pdo = new PDO('mysql:host=localhost;dbname=nada_data_api;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$failures = 0;
$passes = 0;
$session_files = array();

function check($label, $ok, $detail = '')
{
	global $failures, $passes;
	if ($ok) {
		$passes++;
		echo "  ok   $label\n";
	} else {
		$failures++;
		echo "  FAIL $label" . ($detail !== '' ? " -- $detail" : '') . "\n";
	}
}

function make_session($user_id)
{
	global $pdo, $sessions_dir, $session_files;
	$user = $pdo->query('SELECT id, email, username FROM users WHERE id = ' . (int) $user_id)->fetch(PDO::FETCH_ASSOC);
	if ( ! $user) {
		fwrite(STDERR, "No user $user_id\n");
		exit(2);
	}
	$sid = bin2hex(random_bytes(16));
	$data = '__ci_last_regenerate|i:' . time() . ';'
		. 'email|s:' . strlen($user['email']) . ':"' . $user['email'] . '";'
		. 'username|s:' . strlen($user['username']) . ':"' . $user['username'] . '";'
		. 'user_id|s:' . strlen((string) $user['id']) . ':"' . $user['id'] . '";';
	$file = $sessions_dir . '/ihsn_nada' . $sid;
	file_put_contents($file, $data);
	chmod($file, 0600);
	$session_files[] = $file;

	return array('sid' => $sid, 'csrf' => bin2hex(random_bytes(16)));
}

function call($session, $method, $path, $body = null, $with_csrf = true, $extra_headers = array())
{
	global $base;
	$ch = curl_init($base . $path);
	$headers = array_merge(array('Accept: application/json'), $extra_headers);
	$cookies = array();
	if ($session) {
		$cookies[] = 'ihsn_nada=' . $session['sid'];
		if ($with_csrf) {
			$cookies[] = 'ccsrf=' . $session['csrf'];
			$headers[] = 'X-CSRF-TOKEN: ' . $session['csrf'];
		}
	}
	if ($cookies) {
		curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $cookies));
	}
	if ($method === 'POST') {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body === null ? '' : (is_string($body) ? $body : json_encode($body)));
		$headers[] = 'Content-Type: application/json';
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	$raw = curl_exec($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	curl_close($ch);

	return array(
		'status' => $status,
		'json'   => json_decode(substr($raw, $header_size), true),
		'raw'    => substr($raw, $header_size, 300),
	);
}

function expect($label, $res, $status, $message = null)
{
	$ok = $res['status'] === $status && ($message === null || (isset($res['json']['message']) && $res['json']['message'] === $message));
	check($label, $ok, 'got ' . $res['status'] . ' ' . $res['raw']);
}

function grants($role_id)
{
	global $pdo;
	$rows = $pdo->query('SELECT resource, permissions FROM role_permissions WHERE role_id = ' . (int) $role_id . ' ORDER BY resource')->fetchAll(PDO::FETCH_KEY_PAIR);
	return $rows;
}

$admin = make_session($admin_id);
$staff = make_session($staff_id);
$suffix = substr(bin2hex(random_bytes(3)), 0, 6);
$role_name = "Apitest role $suffix";
$role_id = 0;
$system_role = (int) $pdo->query('SELECT id FROM roles WHERE is_admin = 1 ORDER BY id LIMIT 1')->fetchColumn();

try {
	echo "Auth and CSRF\n";
	expect('GET without session', call(null, 'GET', '/api/admin/permissions/roles'), 401, 'SESSION-REQUIRED');
	$res = call(null, 'GET', '/api/admin/permissions/roles', null, false, array('X-API-KEY: not-a-session-' . $suffix));
	check('API key alone is refused', $res['status'] !== 200, 'got ' . $res['status']);
	expect('GET as non-admin', call($staff, 'GET', '/api/admin/permissions/roles'), 403, 'ACCESS_DENIED');
	expect('POST without CSRF token', call($admin, 'POST', '/api/admin/permissions/roles', array('role' => $role_name), false), 403, 'CSRF-TOKEN-INVALID');

	$res = call($admin, 'GET', '/api/admin/permissions/roles');
	expect('GET roles as admin', $res, 200);
	$admin_row = null;
	foreach ((array) $res['json']['roles'] as $r) {
		if ($r['id'] === $system_role) {
			$admin_row = $r;
		}
	}
	check('roles include user_count and is_system', $admin_row && $admin_row['is_system'] === true && is_int($admin_row['user_count']));

	$res = call($admin, 'GET', '/api/admin/permissions/resources');
	expect('GET resources', $res, 200);
	$resource = null;
	$perms = array();
	foreach ((array) $res['json']['resources'] as $r) {
		if (count($r['permissions']) >= 2) {
			$resource = $r['resource'];
			$perms = array_column($r['permissions'], 'permission');
			break;
		}
	}
	check('found a resource with two or more permissions', $resource !== null);

	echo "Create\n";
	expect('empty name', call($admin, 'POST', '/api/admin/permissions/roles', array('role' => '  ')), 400, 'ROLE_REQUIRED');
	expect('name with markup', call($admin, 'POST', '/api/admin/permissions/roles', array('role' => '<b>x</b>')), 400, 'INVALID_ROLE_NAME');
	expect('name as array', call($admin, 'POST', '/api/admin/permissions/roles', array('role' => array('x'))), 400, 'INVALID_ROLE_NAME');
	expect('description with HTML', call($admin, 'POST', '/api/admin/permissions/roles', array('role' => $role_name, 'description' => 'a <img src=x onerror=alert(1)>')), 400, 'INVALID_DESCRIPTION');
	expect('description too long', call($admin, 'POST', '/api/admin/permissions/roles', array('role' => $role_name, 'description' => str_repeat('a', 256))), 400, 'DESCRIPTION_TOO_LONG');
	expect('negative weight', call($admin, 'POST', '/api/admin/permissions/roles', array('role' => $role_name, 'weight' => -1)), 400, 'INVALID_WEIGHT');
	expect('fractional weight', call($admin, 'POST', '/api/admin/permissions/roles', array('role' => $role_name, 'weight' => 5.5)), 400, 'INVALID_WEIGHT');
	expect('invalid JSON', call($admin, 'POST', '/api/admin/permissions/roles', '{bad'), 400, 'INVALID_JSON_INPUT');

	$res = call($admin, 'POST', '/api/admin/permissions/roles', array('role' => $role_name, 'description' => 'Test role', 'weight' => 3));
	expect('create role', $res, 201);
	$role_id = isset($res['json']['role']['id']) ? (int) $res['json']['role']['id'] : 0;
	check('created role has an id and no users', $role_id > 0 && $res['json']['role']['user_count'] === 0);
	expect('duplicate name', call($admin, 'POST', '/api/admin/permissions/roles', array('role' => strtoupper($role_name))), 409, 'ROLE_EXISTS');

	echo "Update\n";
	expect('empty update', call($admin, 'POST', "/api/admin/permissions/roles/$role_id", array()), 400, 'NOTHING_TO_UPDATE');
	expect('rename to an existing name', call($admin, 'POST', "/api/admin/permissions/roles/$role_id", array('role' => 'admin')), 409, 'ROLE_EXISTS');

	$renamed = "$role_name x";
	$res = call($admin, 'POST', "/api/admin/permissions/roles/$role_id", array(
		'role' => $renamed,
		'description' => 'Changed',
		'resources' => array($resource => array($perms[0], $perms[1], $perms[0])),
	));
	expect('save details and permissions together', $res, 200);
	$g = grants($role_id);
	check('name saved', $res['json']['role']['name'] === $renamed && $res['json']['role']['description'] === 'Changed');
	check('permissions saved without duplicates', isset($g[$resource]) && $g[$resource] === $perms[0] . ',' . $perms[1], json_encode($g));
	check('response returns the saved grants', isset($res['json']['resources'][$resource]) && count($res['json']['resources'][$resource]) === 2);

	expect('unknown resource', call($admin, 'POST', "/api/admin/permissions/roles/$role_id", array('resources' => array('no_such_resource' => array('view')))), 400, 'INVALID_RESOURCE');
	expect('unknown permission', call($admin, 'POST', "/api/admin/permissions/roles/$role_id", array('resources' => array($resource => array('no_such_permission')))), 400, 'INVALID_PERMISSION');
	expect('permissions not a list', call($admin, 'POST', "/api/admin/permissions/roles/$role_id", array('resources' => array($resource => 'view'))), 400, 'INVALID_PERMISSION');
	expect('rejected rename with bad grants', call($admin, 'POST', "/api/admin/permissions/roles/$role_id", array('role' => 'Should not stick', 'resources' => array('no_such_resource' => array('x')))), 400, 'INVALID_RESOURCE');
	$name_now = $pdo->query("SELECT name FROM roles WHERE id = $role_id")->fetchColumn();
	check('nothing changed after rejected requests', $name_now === $renamed && grants($role_id) === $g, $name_now);

	expect('details-only update keeps grants', call($admin, 'POST', "/api/admin/permissions/roles/$role_id", array('weight' => 7)), 200);
	check('grants still there', grants($role_id) === $g);

	expect('legacy permissions endpoint', call($admin, 'POST', "/api/admin/permissions/roles/$role_id/permissions", array('resources' => array($resource => array($perms[1])))), 200);
	check('legacy endpoint replaced grants', grants($role_id) === array($resource => $perms[1]), json_encode(grants($role_id)));
	expect('clear all permissions', call($admin, 'POST', "/api/admin/permissions/roles/$role_id", array('resources' => new stdClass())), 200);
	check('grants cleared', grants($role_id) === array());

	echo "System roles\n";
	expect('rename admin role', call($admin, 'POST', "/api/admin/permissions/roles/$system_role", array('role' => 'nope')), 403, 'ROLE_LOCKED');
	expect('change admin role permissions', call($admin, 'POST', "/api/admin/permissions/roles/$system_role/permissions", array('resources' => array())), 403, 'ROLE_LOCKED');
	expect('delete admin role', call($admin, 'POST', "/api/admin/permissions/roles/$system_role/delete"), 403, 'ROLE_LOCKED');
	$pdo->exec("UPDATE roles SET is_admin = 1, is_locked = 0 WHERE id = $role_id");
	expect('unlocked admin role still protected', call($admin, 'POST', "/api/admin/permissions/roles/$role_id", array('weight' => 1)), 403, 'ROLE_LOCKED');
	expect('unlocked admin role cannot be deleted', call($admin, 'POST', "/api/admin/permissions/roles/$role_id/delete"), 403, 'ROLE_LOCKED');
	$pdo->exec("UPDATE roles SET is_admin = 0, is_locked = 0 WHERE id = $role_id");

	echo "Delete\n";
	expect('missing role', call($admin, 'GET', '/api/admin/permissions/roles/99999999'), 404, 'NOT_FOUND');
	$pdo->exec("INSERT INTO user_roles (user_id, role_id) VALUES ($staff_id, $role_id)");
	$res = call($admin, 'GET', "/api/admin/permissions/roles/$role_id");
	check('user_count reflects assignments', isset($res['json']['role']['user_count']) && $res['json']['role']['user_count'] === 1, $res['raw']);
	expect('non-admin cannot delete', call($staff, 'POST', "/api/admin/permissions/roles/$role_id/delete"), 403, 'ACCESS_DENIED');
	expect('delete role', call($admin, 'POST', "/api/admin/permissions/roles/$role_id/delete"), 200);
	expect('deleted role not found', call($admin, 'GET', "/api/admin/permissions/roles/$role_id"), 404);
	$left = (int) $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role_id = $role_id")->fetchColumn()
		+ (int) $pdo->query("SELECT COUNT(*) FROM role_permissions WHERE role_id = $role_id")->fetchColumn();
	check('assignments and grants removed', $left === 0, "$left rows left");

	$logs = $pdo->query("SELECT keyword FROM sitelogs WHERE section = 'admin-roles' AND keyword LIKE '%id=$role_id %' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
	check('audit entries written', count($logs) >= 5, count($logs) . ' entries');
	check('audit records the permission diff', (bool) preg_grep('/added: [^;]*' . preg_quote("$resource.{$perms[0]}", '/') . '/', $logs));
	check('audit records users affected by delete', (bool) preg_grep('/removed from 1 user/', $logs));
	$role_id = 0;
}
finally {
	if ($role_id) {
		$pdo->exec("DELETE FROM user_roles WHERE role_id = $role_id");
		$pdo->exec("DELETE FROM role_permissions WHERE role_id = $role_id");
		$pdo->exec("DELETE FROM roles WHERE id = $role_id");
	}
	foreach ($session_files as $file) {
		@unlink($file);
	}
}

echo ($failures ? "FAILED: $failures" : 'OK') . " ($passes passed)\n";
exit($failures ? 1 : 0);
