<?php
require_once __DIR__ . '/common.php';
$user = require_auth();
$method = request_method();

if ($user['role'] !== 'superadmin') {
    json_response(['message' => 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้'], 403);
}

if ($method === 'GET') {
    $sql = "SELECT id, username, full_name, phone, role FROM users WHERE role = 'admin' ORDER BY id DESC";
    raw_json_response(query_many($sql));
}

if ($method === 'POST') {
    $input = get_json_input();
    $fullName = trim($input['full_name'] ?? '');
    $username = trim($input['username'] ?? '');
    $password = (string)($input['password'] ?? '');
    $phone = trim($input['phone'] ?? '');

    if ($fullName === '' || $username === '' || $password === '') {
        json_response(['message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'], 400);
    }

    $existing = query_one('SELECT id FROM users WHERE username = ?', [$username]);
    if ($existing) {
        json_response(['message' => 'Username นี้มีในระบบแล้ว'], 400);
    }

    execute_query(
        'INSERT INTO users (username, password_hash, full_name, phone, role) VALUES (?, ?, ?, ?, ?)',
        [$username, hash_password_value($password), $fullName, $phone, 'admin']
    );

    $id = (int)get_db()->lastInsertId();
    log_action($user['full_name'], $user['role'], 'เพิ่มผู้ใช้งาน', $id);
    json_response(['message' => 'เพิ่มผู้ใช้งานเรียบร้อย']);
}

if ($method === 'PUT') {
    $input = get_json_input();
    $id = (int)($input['id'] ?? 0);
    $fullName = trim($input['full_name'] ?? '');
    $username = trim($input['username'] ?? '');
    $password = (string)($input['password'] ?? '');
    $phone = trim($input['phone'] ?? '');

    if ($id <= 0 || $fullName === '' || $username === '') {
        json_response(['message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'], 400);
    }

    $existing = query_one('SELECT id FROM users WHERE username = ? AND id != ?', [$username, $id]);
    if ($existing) {
        json_response(['message' => 'Username นี้มีในระบบแล้ว'], 400);
    }

    if ($password !== '') {
        execute_query(
            'UPDATE users SET username = ?, password_hash = ?, full_name = ?, phone = ? WHERE id = ?',
            [$username, hash_password_value($password), $fullName, $phone, $id]
        );
    } else {
        execute_query(
            'UPDATE users SET username = ?, full_name = ?, phone = ? WHERE id = ?',
            [$username, $fullName, $phone, $id]
        );
    }

    log_action($user['full_name'], $user['role'], 'แก้ไขข้อมูลผู้ใช้งาน', $id);
    json_response(['message' => 'แก้ไขผู้ใช้เรียบร้อย']);
}

if ($method === 'DELETE') {
    $input = get_json_input();
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        json_response(['message' => 'ไม่พบผู้ใช้'], 400);
    }

    execute_query('DELETE FROM users WHERE id = ?', [$id]);
    log_action($user['full_name'], $user['role'], 'ลบผู้ใช้งาน', $id);
    json_response(['message' => 'ลบผู้ใช้งานเรียบร้อย']);
}

json_response(['message' => 'Method not allowed'], 405);
