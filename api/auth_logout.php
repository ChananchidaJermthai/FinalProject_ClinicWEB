<?php
require_once __DIR__ . '/common.php';
$user = require_auth();
log_action($user['full_name'], $user['role'], 'ออกจากระบบ (' . $user['username'] . ')');
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
json_response(['message' => 'ออกจากระบบเรียบร้อย']);
