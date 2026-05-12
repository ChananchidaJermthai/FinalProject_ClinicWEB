<?php
require_once __DIR__ . '/common.php';
$user = require_auth();

if ($user['role'] !== 'superadmin') {
    json_response(['message' => 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้'], 403);
}

if (request_method() !== 'POST') {
    json_response(['message' => 'Method not allowed'], 405);
}

$input = get_json_input();
if (isset($input['encrypted_backup'])) {
    $backup = decrypt_backup($input['encrypted_backup']);
    if ($backup === null) {
        json_response(['message' => 'รหัสผ่านหรือรูปแบบไฟล์เข้ารหัสไม่ถูกต้อง ไม่สามารถกู้คืนได้'], 400);
    }
} else {
    $backup = $input['backup'] ?? null;
}
$validationError = validate_backup_payload($backup);

if ($validationError !== null) {
    json_response(['message' => $validationError], 400);
}

$pdo = get_db();

try {
    $pdo->beginTransaction();

    foreach (['staff_logs', 'appointments', 'inventory', 'users'] as $tableName) {
        $pdo->exec("DELETE FROM `$tableName`");
    }

    foreach (['users', 'inventory', 'appointments', 'staff_logs'] as $tableName) {
        insert_rows($pdo, $tableName, $backup['tables'][$tableName]);
    }

    $pdo->commit();

    log_action(
        $user['full_name'],
        $user['role'],
        'กู้คืนข้อมูลระบบ (จากไฟล์เข้ารหัส)',
        null
    );

    json_response([
        'message' => 'กู้คืนข้อมูลระบบเรียบร้อย',
        'restored_tables' => [
            'users' => count($backup['tables']['users']),
            'inventory' => count($backup['tables']['inventory']),
            'appointments' => count($backup['tables']['appointments']),
            'staff_logs' => count($backup['tables']['staff_logs']),
        ],
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['message' => 'ไม่สามารถกู้คืนข้อมูลระบบได้: ' . $e->getMessage()], 500);
}
