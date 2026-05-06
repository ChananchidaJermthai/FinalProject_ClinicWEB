<?php
require_once __DIR__ . '/common.php';
$user = require_auth();
$method = request_method();

if ($method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $date = trim($_GET['date'] ?? '');

    $sql = 'SELECT * FROM appointments WHERE 1=1';
    $params = [];

    if ($q !== '') {
        $sql .= ' AND (customer_name LIKE ? OR service_name LIKE ? OR COALESCE(phone, "") LIKE ? OR COALESCE(notes, "") LIKE ?)';
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
    }

    if ($status !== '') {
        $sql .= ' AND status = ?';
        $params[] = $status;
    }

    if ($date !== '') {
        $sql .= ' AND DATE(app_date) = ?';
        $params[] = $date;
    }

    $sql .= ' ORDER BY app_date ASC, id DESC';
    raw_json_response(query_many($sql, $params));
}

if ($method === 'POST') {
    $input = get_json_input();
    $customerName = trim($input['customer_name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $serviceName = trim($input['service_name'] ?? '');
    $appDate = normalize_datetime($input['app_date'] ?? '');
    $status = trim($input['status'] ?? 'pending');
    $notes = trim($input['notes'] ?? '');

    if ($customerName === '' || $serviceName === '' || !$appDate) {
        json_response(['message' => 'กรุณากรอกชื่อลูกค้า บริการ และวันนัดหมาย'], 400);
    }

    execute_query(
        'INSERT INTO appointments (customer_name, phone, service_name, app_date, status, notes) VALUES (?, ?, ?, ?, ?, ?)',
        [$customerName, $phone !== '' ? $phone : null, $serviceName, $appDate, $status, $notes !== '' ? $notes : null]
    );

    $id = (int)get_db()->lastInsertId();
    log_action($user['full_name'], 'เพิ่มนัดหมายใหม่ของ ' . $customerName);
    raw_json_response(query_one('SELECT * FROM appointments WHERE id = ?', [$id]), 201);
}

if ($method === 'PUT') {
    $input = get_json_input();
    $id = (int)($input['id'] ?? 0);
    $customerName = trim($input['customer_name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $serviceName = trim($input['service_name'] ?? '');
    $appDate = normalize_datetime($input['app_date'] ?? '');
    $status = trim($input['status'] ?? 'pending');
    $notes = trim($input['notes'] ?? '');

    if ($id <= 0 || $customerName === '' || $serviceName === '' || !$appDate) {
        json_response(['message' => 'กรุณากรอกชื่อลูกค้า บริการ และวันนัดหมาย'], 400);
    }

    execute_query(
        'UPDATE appointments SET customer_name = ?, phone = ?, service_name = ?, app_date = ?, status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
        [$customerName, $phone !== '' ? $phone : null, $serviceName, $appDate, $status, $notes !== '' ? $notes : null, $id]
    );

    log_action($user['full_name'], 'แก้ไขนัดหมาย ID ' . $id . ' ของ ' . $customerName);
    raw_json_response(query_one('SELECT * FROM appointments WHERE id = ?', [$id]));
}

if ($method === 'DELETE') {
    $input = get_json_input();
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        json_response(['message' => 'ไม่พบนัดหมาย'], 400);
    }

    $appointment = query_one('SELECT * FROM appointments WHERE id = ?', [$id]);
    if (!$appointment) {
        json_response(['message' => 'ไม่พบนัดหมาย'], 404);
    }

    execute_query('DELETE FROM appointments WHERE id = ?', [$id]);
    log_action($user['full_name'], 'ลบนัดหมายของ ' . $appointment['customer_name']);
    json_response(['message' => 'ลบนัดหมายเรียบร้อย']);
}

json_response(['message' => 'Method not allowed'], 405);
