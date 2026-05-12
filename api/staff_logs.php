<?php
require_once __DIR__ . '/common.php';
$user = require_auth();

if ($user['role'] !== 'superadmin') {
    json_response(['message' => 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้'], 403);
}
$id = trim($_GET['id'] ?? '');
$username = trim($_GET['username'] ?? '');
$date = trim($_GET['date'] ?? '');

$sql = 'SELECT * FROM staff_logs WHERE 1=1';
$params = [];

if ($id !== '') {
    $sql .= ' AND key_result_id = ?';
    $params[] = $id;
}
if ($username !== '') {
    $sql .= ' AND staff_name LIKE ?';
    $params[] = '%' . $username . '%';
}
if ($date !== '') {
    $sql .= ' AND DATE(created_at) = ?';
    $params[] = $date;
}

$sql .= ' ORDER BY created_at DESC, id DESC LIMIT 100';

raw_json_response(query_many($sql, $params));
