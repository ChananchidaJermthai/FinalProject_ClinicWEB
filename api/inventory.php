<?php
require_once __DIR__ . '/common.php';
$user = require_auth();
$method = request_method();

if ($method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    $onlyLowStock = ($_GET['lowStock'] ?? '') === 'true';

    $sql = 'SELECT * FROM inventory WHERE 1=1';
    $params = [];

    if ($q !== '') {
        $sql .= ' AND (item_name LIKE ? OR unit LIKE ?)';
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
    }

    if ($onlyLowStock) {
        $sql .= ' AND quantity <= min_quantity';
    }

    $sql .= ' ORDER BY item_name ASC';
    raw_json_response(query_many($sql, $params));
}

if ($method === 'POST') {
    $input = get_json_input();
    $itemName = trim($input['item_name'] ?? '');
    $unit = trim($input['unit'] ?? '');
    $quantity = (int)($input['quantity'] ?? 0);
    $minQuantity = (int)($input['min_quantity'] ?? 0);

    if ($itemName === '' || $unit === '') {
        json_response(['message' => 'กรุณากรอกชื่อรายการและหน่วย'], 400);
    }

    execute_query(
        'INSERT INTO inventory (item_name, quantity, unit, min_quantity) VALUES (?, ?, ?, ?)',
        [$itemName, $quantity, $unit, $minQuantity]
    );

    $id = (int)get_db()->lastInsertId();
    log_action($user['full_name'], 'เพิ่มสินค้าใหม่: ' . $itemName);
    raw_json_response(query_one('SELECT * FROM inventory WHERE id = ?', [$id]), 201);
}

if ($method === 'PUT') {
    $input = get_json_input();
    $id = (int)($input['id'] ?? 0);
    $itemName = trim($input['item_name'] ?? '');
    $unit = trim($input['unit'] ?? '');
    $quantity = (int)($input['quantity'] ?? 0);
    $minQuantity = (int)($input['min_quantity'] ?? 0);

    if ($id <= 0 || $itemName === '' || $unit === '') {
        json_response(['message' => 'กรุณากรอกข้อมูลสินค้าให้ครบ'], 400);
    }

    execute_query(
        'UPDATE inventory SET item_name = ?, quantity = ?, unit = ?, min_quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
        [$itemName, $quantity, $unit, $minQuantity, $id]
    );

    log_action($user['full_name'], 'แก้ไขสินค้า ID ' . $id . ': ' . $itemName);
    raw_json_response(query_one('SELECT * FROM inventory WHERE id = ?', [$id]));
}

if ($method === 'DELETE') {
    $input = get_json_input();
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        json_response(['message' => 'ไม่พบรหัสสินค้า'], 400);
    }

    $item = query_one('SELECT * FROM inventory WHERE id = ?', [$id]);
    if (!$item) {
        json_response(['message' => 'ไม่พบรายการสินค้า'], 404);
    }

    execute_query('DELETE FROM inventory WHERE id = ?', [$id]);
    log_action($user['full_name'], 'ลบสินค้า: ' . $item['item_name']);
    json_response(['message' => 'ลบข้อมูลสินค้าเรียบร้อย']);
}

json_response(['message' => 'Method not allowed'], 405);
