<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../includes/security.php';

session_start();
require_debug_mode();

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('CSRF fail');
}

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="wizard_config.json"');
echo json_encode($_SESSION, JSON_PRETTY_PRINT);

