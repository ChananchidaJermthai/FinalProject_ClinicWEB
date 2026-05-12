<?php
require_once __DIR__ . '/common.php';

if (request_method() !== 'POST') {
    json_response(['message' => 'Method not allowed'], 405);
}

$input = get_json_input();
$otp = trim($input['otp'] ?? '');

if ($otp === '') {
    json_response(['message' => 'กรุณากรอกรหัส OTP'], 400);
}

if (empty($_SESSION['temp_login_id'])) {
    json_response(['message' => 'ไม่พบข้อมูลการล็อกอิน กรุณาเข้าสู่ระบบใหม่'], 401);
}

$userId = $_SESSION['temp_login_id'];

$user = query_one(
    'SELECT id, username, full_name, role, otp_code, otp_expires, (otp_expires < NOW()) AS is_expired FROM users WHERE id = ? LIMIT 1',
    [$userId]
);

if (!$user) {
    json_response(['message' => 'ไม่พบผู้ใช้งาน'], 401);
}

if ($user['otp_code'] !== $otp) {
    json_response(['message' => 'รหัส OTP ไม่ถูกต้อง'], 401);
}

if ($user['is_expired']) {
    json_response(['message' => 'รหัส OTP หมดอายุแล้ว'], 401);
}

// Clear OTP
execute_query('UPDATE users SET otp_code = NULL, otp_expires = NULL WHERE id = ?', [$userId]);
unset($_SESSION['temp_login_id']);

$sessionUser = [
    'id' => (int)$user['id'],
    'username' => $user['username'],
    'full_name' => $user['full_name'],
    'role' => $user['role'],
];

$_SESSION['user'] = $sessionUser;
log_action($sessionUser['full_name'], $sessionUser['role'], 'เข้าสู่ระบบ (' . $sessionUser['username'] . ')');

json_response(['user' => $sessionUser]);
