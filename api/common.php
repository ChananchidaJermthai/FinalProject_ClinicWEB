<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function raw_json_response($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_json_input(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function request_method(): string {
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function require_auth(): array {
    if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
        json_response(['message' => 'กรุณาเข้าสู่ระบบก่อนใช้งาน'], 401);
    }
    return $_SESSION['user'];
}

function hash_password_value(string $password): string {
    return hash('sha256', $password);
}

function normalize_datetime(?string $value): ?string {
    if (!$value) {
        return null;
    }
    return str_replace('T', ' ', trim($value));
}

function query_one(string $sql, array $params = []): ?array {
    $stmt = get_db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function query_many(string $sql, array $params = []): array {
    $stmt = get_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function execute_query(string $sql, array $params = []): bool {
    $stmt = get_db()->prepare($sql);
    return $stmt->execute($params);
}

function log_action(string $staffName, string $actionText): void {
    $stmt = get_db()->prepare('INSERT INTO staff_logs (staff_name, action_text) VALUES (?, ?)');
    $stmt->execute([$staffName, $actionText]);
}

function validate_backup_payload($backup): ?string {
    $requiredTables = ['users', 'inventory', 'appointments', 'staff_logs'];

    if (!is_array($backup)) {
        return 'รูปแบบไฟล์สำรองข้อมูลไม่ถูกต้อง';
    }

    if (!isset($backup['tables']) || !is_array($backup['tables'])) {
        return 'ไม่พบข้อมูลตารางในไฟล์สำรองข้อมูล';
    }

    foreach ($requiredTables as $table) {
        if (!isset($backup['tables'][$table]) || !is_array($backup['tables'][$table])) {
            return 'ไฟล์สำรองข้อมูลขาดตาราง ' . $table;
        }
    }

    if (count($backup['tables']['users']) === 0) {
        return 'ไฟล์สำรองข้อมูลต้องมีข้อมูลผู้ใช้งานอย่างน้อย 1 รายการ';
    }

    return null;
}

function insert_rows(PDO $pdo, string $tableName, array $rows): void {
    foreach ($rows as $row) {
        if (!is_array($row) || empty($row)) {
            continue;
        }

        $columns = array_keys($row);
        $quotedColumns = array_map(fn($col) => "`$col`", $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO `$tableName` (" . implode(', ', $quotedColumns) . ") VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($row));
    }
}
