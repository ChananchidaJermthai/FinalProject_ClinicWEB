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
    'SELECT id, username, password_hash, full_name, phone, role, is_active, failed_attempts, locked_until, (locked_until > NOW()) AS is_locked, TIMESTAMPDIFF(MINUTE, NOW(), locked_until) AS lock_minutes_left FROM users WHERE username = ? LIMIT 1',
    [$username]
);

if (!$user || (int)$user['is_active'] !== 1) {
    json_response(['message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'], 401);
}

if ($user['locked_until'] && $user['is_locked']) {
    $minutesLeft = max(1, (int)$user['lock_minutes_left']);
    json_response(['message' => "บัญชีถูกล็อค กรุณาลองใหม่ในอีก $minutesLeft นาที"], 401);
}

if ($user['password_hash'] !== hash_password_value($password)) {
    $failed = (int)$user['failed_attempts'] + 1;
    if ($failed >= 5) {
        execute_query('UPDATE users SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?', [$failed, $user['id']]);
        json_response(['message' => 'รหัสผ่านผิดเกิน 5 ครั้ง บัญชีถูกล็อค 10 นาที'], 401);
    } else {
        execute_query('UPDATE users SET failed_attempts = ? WHERE id = ?', [$failed, $user['id']]);
        $attemptsLeft = 5 - $failed;
        json_response(['message' => "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (เหลือโอกาส $attemptsLeft ครั้ง)"], 401);
    }
}

// Password correct, reset attempts and generate OTP
$otp = str_pad((string)rand(0, 999999), 6, '0', STR_PAD_LEFT);
execute_query('UPDATE users SET failed_attempts = 0, locked_until = NULL, otp_code = ?, otp_expires = DATE_ADD(NOW(), INTERVAL 2 MINUTE) WHERE id = ?', [$otp, $user['id']]);

$_SESSION['temp_login_id'] = $user['id'];

// Mock sending OTP to phone
$maskedPhone = $user['phone'] ? substr_replace($user['phone'], 'XXXX', 2, 4) : 'ไม่ระบุ';

json_response([
    'require_otp' => true,
    'message' => "ระบบได้ส่ง OTP ไปที่เบอร์ $maskedPhone แล้ว (OTP สำหรับทดสอบคือ: $otp)"
]);
