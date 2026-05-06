<?php
require_once __DIR__ . '/common.php';
require_auth();

$totalAppointments = query_one('SELECT COUNT(*) AS count FROM appointments');
$pendingAppointments = query_one("SELECT COUNT(*) AS count FROM appointments WHERE status = 'pending'");
$totalInventoryItems = query_one('SELECT COUNT(*) AS count FROM inventory');
$lowStockItems = query_one('SELECT COUNT(*) AS count FROM inventory WHERE quantity <= min_quantity');

json_response([
    'totalAppointments' => (int)($totalAppointments['count'] ?? 0),
    'pendingAppointments' => (int)($pendingAppointments['count'] ?? 0),
    'totalInventoryItems' => (int)($totalInventoryItems['count'] ?? 0),
    'lowStockItems' => (int)($lowStockItems['count'] ?? 0),
]);
