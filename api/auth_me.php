<?php
require_once __DIR__ . '/common.php';
$user = require_auth();
json_response(['user' => $user]);
