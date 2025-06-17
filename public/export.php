<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/security.php';

session_start();
require_debug_mode();

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_GET['csrf_token'] ?? '');
if (!validate_csrf_token($token)) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="wizard_config.txt"');
foreach ($_SESSION as $k => $v) {
    echo "$k: " . (is_array($v) ? json_encode($v) : $v) . "\n";
}

