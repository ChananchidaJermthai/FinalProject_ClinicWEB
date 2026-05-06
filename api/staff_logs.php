<?php
require_once __DIR__ . '/common.php';
require_auth();
raw_json_response(query_many('SELECT * FROM staff_logs ORDER BY created_at DESC, id DESC LIMIT 30'));
