<?php
require_once __DIR__ . '/common.php';
$user = require_auth();

if ($user['role'] !== 'superadmin') {
    json_response(['message' => 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้'], 403);
}
raw_json_response(query_many('SELECT * FROM staff_logs ORDER BY created_at DESC, id DESC LIMIT 30'));
