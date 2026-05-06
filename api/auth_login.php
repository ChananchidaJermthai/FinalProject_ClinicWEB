<?php
require_once __DIR__ . '/common.php';

if (request_method() !== 'POST') {
    json_response(['message' => 'Method not allowed'], 405);
}

$input = get_json_input();
$username = trim($input['username'] ?? '');
$password = (string)($input['password'] ?? '');

if ($username === '' || $password === '') {
    json_response(['message' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน'], 400);
}

$user = query_one(
    'SELECT id, username, password_hash, full_name, role, is_active FROM users WHERE username = ? LIMIT 1',
    [$username]
);

if (!$user || (int)$user['is_active'] !== 1 || $user['password_hash'] !== hash_password_value($password)) {
    json_response(['message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'], 401);
}

$sessionUser = [
    'id' => (int)$user['id'],
    'username' => $user['username'],
    'full_name' => $user['full_name'],
    'role' => $user['role'],
];

$_SESSION['user'] = $sessionUser;
log_action($sessionUser['full_name'], 'เข้าสู่ระบบ (' . $sessionUser['username'] . ')');

json_response(['user' => $sessionUser]);
