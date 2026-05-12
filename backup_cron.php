<?php
// Script สำหรับรันผ่าน Cron หรือ Windows Task Scheduler
// รันทุกๆ 1 วัน เวลา 00:00 น.
require_once __DIR__ . '/api/common.php';

// ตั้งรหัสลับป้องกันคนอื่นมากดรัน Backup เล่น
$secret_key = 'aura_backup_secret_2026';
if (empty($_GET['key']) || $_GET['key'] !== $secret_key) {
    http_response_code(403);
    die('Forbidden: Invalid Key');
}

$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// ป้องกันคนโหลดไฟล์ Backup ผ่าน URL
if (!file_exists($backupDir . '/.htaccess')) {
    file_put_contents($backupDir . '/.htaccess', "Order Deny,Allow\nDeny from all");
}

try {
    $backup = [
        'app_name' => 'Aura Clinic Admin System (Auto Backup)',
        'backup_version' => 1,
        'exported_at' => gmdate('c'),
        'exported_by' => 'System Auto Backup',
        'database' => DB_NAME,
        'tables' => [
            'users' => query_many('SELECT * FROM users ORDER BY id ASC'),
            'inventory' => query_many('SELECT * FROM inventory ORDER BY id ASC'),
            'appointments' => query_many('SELECT * FROM appointments ORDER BY id ASC'),
            'staff_logs' => query_many('SELECT * FROM staff_logs ORDER BY id ASC'),
        ],
    ];

    $filename = 'aura_clinic_auto_backup_' . date('Ymd_His') . '.enc';
    $filepath = $backupDir . '/' . $filename;

    file_put_contents($filepath, encrypt_backup($backup));
    
    // Auto-delete backups older than 15 days
    $files = glob($backupDir . '/aura_clinic_auto_backup_*.enc');
    $now = time();
    $deletedCount = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            // 15 days = 15 * 24 * 60 * 60 seconds = 1296000 seconds
            if ($now - filemtime($file) >= 1296000) {
                unlink($file);
                $deletedCount++;
            }
        }
    }
    
    log_action('System Auto Backup', 'system', 'ระบบทำการสำรองข้อมูลอัตโนมัติ (เข้ารหัส)' . ($deletedCount > 0 ? " (และลบไฟล์เก่า $deletedCount ไฟล์)" : ''), null);
    
    echo "Backup completed: $filename\n";
    if ($deletedCount > 0) {
        echo "Deleted $deletedCount old backup file(s).\n";
    }
} catch (Exception $e) {
    echo "Backup failed: " . $e->getMessage() . "\n";
}
