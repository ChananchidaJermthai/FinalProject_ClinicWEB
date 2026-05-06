<?php
require_once __DIR__ . '/common.php';
$user = require_auth();

if (request_method() !== 'PUT') {
    json_response(['message' => 'Method not allowed'], 405);
}

$input = get_json_input();
$id = (int)($input['id'] ?? 0);
$quantity = (int)($input['quantity'] ?? 0);

if ($id <= 0) {
    json_response(['message' => 'ไม่พบรหัสสินค้า'], 400);
}

execute_query(
    'UPDATE inventory SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
    [$quantity, $id]
);

log_action($user['full_name'], 'อัปเดต stock ID ' . $id . ' เป็น ' . $quantity);
json_response(['message' => 'อัปเดต stock เรียบร้อย']);
