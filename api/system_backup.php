<?php
require_once __DIR__ . '/common.php';
$user = require_auth();

if ($user['role'] !== 'superadmin') {
    json_response(['message' => 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้'], 403);
}

$backup = [
    'app_name' => 'Aura Clinic Admin System',
    'backup_version' => 1,
    'exported_at' => gmdate('c'),
    'exported_by' => $user,
    'database' => DB_NAME,
    'tables' => [
        'users' => query_many('SELECT * FROM users ORDER BY id ASC'),
        'inventory' => query_many('SELECT * FROM inventory ORDER BY id ASC'),
        'appointments' => query_many('SELECT * FROM appointments ORDER BY id ASC'),
        'staff_logs' => query_many('SELECT * FROM staff_logs ORDER BY id ASC'),
    ],
];

$filename = 'aura_clinic_backup_' . date('Ymd_His') . '.enc';
log_action($user['full_name'], $user['role'], 'สำรองข้อมูลระบบ (เข้ารหัส)', null);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo encrypt_backup($backup);
exit;
