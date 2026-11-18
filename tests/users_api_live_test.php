<?php
/**
 * Live checks for the admin users API write endpoints against a local install.
 *
 * Creates file sessions for a site admin and a non-admin user manager, runs the requests with curl and
 * removes the test account it creates.
 *
 *   php tests/users_api_live_test.php [base_url] [admin_user_id] [staff_user_id]
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

function call($session, $method, $path, $body = null, $with_csrf = true)
{
	global $base;
	$ch = curl_init($base . $path);
	$headers = array('Accept: application/json');
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
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body === null ? '' : json_encode($body));
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
		'status'  => $status,
		'headers' => substr($raw, 0, $header_size),
		'json'    => json_decode(substr($raw, $header_size), true),
		'raw'     => substr($raw, $header_size, 300),
	);
}

function expect($label, $res, $status, $message = null)
{
	$ok = $res['status'] === $status && ($message === null || (isset($res['json']['message']) && $res['json']['message'] === $message));
	check($label, $ok, 'got ' . $res['status'] . ' ' . $res['raw']);
}

$admin_role = (int) $pdo->query('SELECT id FROM roles WHERE is_admin = 1 ORDER BY id LIMIT 1')->fetchColumn();
$plain_role = (int) $pdo->query('SELECT id FROM roles WHERE is_admin = 0 ORDER BY id LIMIT 1')->fetchColumn();
$staff_roles = $pdo->query('SELECT role_id FROM user_roles WHERE user_id = ' . $staff_id)->fetchAll(PDO::FETCH_COLUMN);

$admin = make_session($admin_id);
$staff = make_session($staff_id);
$suffix = substr(bin2hex(random_bytes(3)), 0, 6);
$test_email = "apitest_$suffix@example.org";
$test_id = 0;

try {
	echo "Auth and CSRF\n";
	expect('POST without session', call(null, 'POST', '/api/admin/users', array()), 401, 'SESSION-REQUIRED');
	expect('POST without CSRF token', call($admin, 'POST', '/api/admin/users', array(), false), 403, 'CSRF-TOKEN-INVALID');
	expect('GET list as admin', call($admin, 'GET', '/api/admin/users?ps=1'), 200);

	echo "Create\n";
	$valid = array(
		'username' => "apitest$suffix", 'email' => $test_email,
		'password' => 'Xy7!pass-Word9', 'password_confirm' => 'Xy7!pass-Word9',
		'first_name' => 'Api', 'last_name' => 'Test', 'country' => 'Kenya',
		'role_ids' => array($plain_role), 'active' => true,
	);
	$res = call($admin, 'POST', '/api/admin/users', array_merge($valid, array('first_name' => '<b>x</b>')));
	expect('HTML in first name refused', $res, 400, 'VALIDATION_FAILED');
	check('error names the field', isset($res['json']['errors']['first_name']));

	expect('password mismatch refused', call($admin, 'POST', '/api/admin/users', array_merge($valid, array('password_confirm' => 'nope'))), 400, 'VALIDATION_FAILED');
	expect('empty body refused', call($admin, 'POST', '/api/admin/users', null), 400, 'VALIDATION_FAILED');

	$res = call($admin, 'POST', '/api/admin/users', $valid);
	expect('create user', $res, 201);
	$test_id = isset($res['json']['user']['id']) ? (int) $res['json']['user']['id'] : 0;
	check('created user has requested role', $test_id && in_array($plain_role, array_column($res['json']['user']['roles'], 'id')));
	check('no password fields returned', strpos(json_encode($res['json']), 'password') === false);

	expect('duplicate email refused', call($admin, 'POST', '/api/admin/users', array_merge($valid, array('username' => "other$suffix"))), 400, 'VALIDATION_FAILED');

	echo "Update\n";
	$res = call($admin, 'POST', "/api/admin/users/$test_id", array('first_name' => 'Renamed', 'company' => 'ACME'));
	expect('partial update', $res, 200);
	check('update applied', isset($res['json']['user']['first_name']) && $res['json']['user']['first_name'] === 'Renamed');
	expect('empty update refused', call($admin, 'POST', "/api/admin/users/$test_id", array()), 400, 'NOTHING_TO_UPDATE');
	expect('admin cannot disable own account', call($admin, 'POST', "/api/admin/users/$admin_id", array('active' => false)), 400, 'CANNOT_DISABLE_OWN_ACCOUNT');
	expect('admin cannot drop own admin role', call($admin, 'POST', "/api/admin/users/$admin_id", array('role_ids' => array())), 400, 'CANNOT_REMOVE_OWN_ADMIN_ROLE');
	expect('update missing user', call($admin, 'POST', '/api/admin/users/99999999', array('first_name' => 'x')), 404);

	echo "Non-admin user manager\n";
	expect('staff cannot edit an admin', call($staff, 'POST', "/api/admin/users/$admin_id", array('first_name' => 'x')), 403, 'PROTECTED_ACCOUNT');
	expect('staff cannot delete an admin', call($staff, 'POST', "/api/admin/users/$admin_id/delete"), 403);
	expect('staff cannot change own roles', call($staff, 'POST', "/api/admin/users/$staff_id", array('role_ids' => array())), 403, 'CANNOT_CHANGE_ROLES');
	expect('staff may resend own roles unchanged', call($staff, 'POST', "/api/admin/users/$staff_id", array('role_ids' => array_map('intval', $staff_roles))), 200);
	expect('staff cannot hand out admin role', call($staff, 'POST', '/api/admin/users/batch', array('action' => 'assign_roles', 'user_ids' => array($test_id), 'role_ids' => array($admin_role))), 400, 'NO_ASSIGNABLE_ROLES');
	$res = call($staff, 'POST', '/api/admin/users/batch', array('action' => 'deactivate', 'user_ids' => array($admin_id, $test_id)));
	expect('staff batch runs', $res, 200);
	check('staff batch skips admin', isset($res['json']['skipped'][0]['reason']) && $res['json']['skipped'][0]['reason'] === 'protected_admin');
	expect('staff cannot mint admin key', call($staff, 'POST', "/api/admin/users/$admin_id/api_keys", array()), 403, 'PROTECTED_ACCOUNT');

	echo "API keys\n";
	$res = call($admin, 'POST', "/api/admin/users/$test_id/api_keys", array('name' => 'ci key', 'expires_in_days' => 30));
	expect('create key', $res, 201);
	$key_id = isset($res['json']['key']['id']) ? (int) $res['json']['key']['id'] : 0;
	check('secret returned once', ! empty($res['json']['secret']) && strlen($res['json']['secret']) > 20);
	check('no-store header', stripos($res['headers'], 'Cache-Control: no-store') !== false);
	$row = $pdo->query("SELECT created_by FROM api_keys WHERE id = $key_id")->fetch(PDO::FETCH_ASSOC);
	check('created_by is the caller', $row && (int) $row['created_by'] === $admin_id);
	$res = call($admin, 'GET', "/api/admin/users/$test_id/api_keys");
	check('list never has secret', $res['status'] === 200 && strpos(json_encode($res['json']), '"secret"') === false);
	expect('key name with HTML refused', call($admin, 'POST', "/api/admin/users/$test_id/api_keys", array('name' => '<i>x</i>')), 400);
	expect('key lifetime over max refused', call($admin, 'POST', "/api/admin/users/$test_id/api_keys", array('expires_in_days' => 9999)), 400);
	$res = call($admin, 'POST', "/api/admin/users/api_keys/$key_id/extend", array('months' => 3));
	expect('extend key', $res, 200);
	check('extended beyond 30 days', isset($res['json']['key']['expires_at']) && $res['json']['key']['expires_at'] > time() + 100 * 86400);
	expect('extend months out of range', call($admin, 'POST', "/api/admin/users/api_keys/$key_id/extend", array('months' => 99)), 400);
	expect('revoke key', call($admin, 'POST', "/api/admin/users/api_keys/$key_id/revoke"), 200);
	expect('revoke twice refused', call($admin, 'POST', "/api/admin/users/api_keys/$key_id/revoke"), 400, 'ALREADY_REVOKED');
	expect('delete key', call($admin, 'POST', "/api/admin/users/api_keys/$key_id/delete"), 200);
	expect('deleted key gone', call($admin, 'POST', "/api/admin/users/api_keys/$key_id/revoke"), 404);

	echo "Batch and disabled sessions\n";
	call($admin, 'POST', "/api/admin/users/$test_id/api_keys", array());
	$res = call($admin, 'POST', '/api/admin/users/batch', array('action' => 'deactivate', 'user_ids' => array($test_id, $admin_id, 99999999)));
	expect('admin batch deactivate', $res, 200);
	$reasons = isset($res['json']['skipped']) ? array_column($res['json']['skipped'], 'reason', 'id') : array();
	check('batch changed test user', isset($res['json']['changed']) && $res['json']['changed'] === array($test_id));
	check('batch skipped own account', isset($reasons[$admin_id]) && $reasons[$admin_id] === 'own_account');
	check('batch reported missing user', isset($reasons[99999999]) && $reasons[99999999] === 'not_found');
	expect('invalid batch action', call($admin, 'POST', '/api/admin/users/batch', array('action' => 'drop', 'user_ids' => array(1))), 400, 'INVALID_ACTION');

	$test_session = make_session($test_id);
	expect('disabled account session is signed out', call($test_session, 'GET', '/api/admin/users'), 401, 'SESSION-REQUIRED');

	echo "Delete\n";
	expect('admin cannot delete self', call($admin, 'POST', "/api/admin/users/$admin_id/delete"), 400, 'CANNOT_DELETE_OWN_ACCOUNT');
	expect('delete user', call($admin, 'POST', "/api/admin/users/$test_id/delete"), 200);
	expect('deleted user not found', call($admin, 'GET', "/api/admin/users/$test_id"), 404);
	$left = 0;
	foreach (array('meta', 'user_roles', 'api_keys') as $table) {
		$left += (int) $pdo->query("SELECT COUNT(*) FROM $table WHERE user_id = $test_id")->fetchColumn();
	}
	check('roles, profile and keys removed', $left === 0, "$left rows left");
	$logs = (int) $pdo->query("SELECT COUNT(*) FROM sitelogs WHERE section = 'admin-users' AND keyword LIKE '%user_id=$test_id%'")->fetchColumn();
	check('audit entries written', $logs >= 4, "$logs entries");
	$test_id = 0;
}
finally {
	if ($test_id) {
		foreach (array('meta', 'user_roles', 'api_keys') as $table) {
			$pdo->exec("DELETE FROM $table WHERE user_id = $test_id");
		}
		$pdo->exec("DELETE FROM users WHERE id = $test_id");
	}
	$pdo->exec("UPDATE users SET active = 1 WHERE id IN ($admin_id, $staff_id)");
	foreach ($session_files as $file) {
		@unlink($file);
	}
}

echo ($failures ? "FAILED: $failures" : 'OK') . " ($passes passed)\n";
exit($failures ? 1 : 0);
