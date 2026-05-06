<?php
require_once __DIR__ . '/common.php';
$user = require_auth();

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

$filename = 'aura_clinic_backup_' . date('Ymd_His') . '.json';
log_action($user['full_name'], 'สำรองข้อมูลระบบ (' . $filename . ')');

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
