<?php
/**
 * File: session-api.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 *
 * Called by: debugging tools to inspect the session
 * Important session keys returned: entire $_SESSION array
 * Requires header X-CSRF-Token matching $_SESSION['csrf_token']
 * @TODO Extend documentation.
 */
declare(strict_types=1);
// Alinear BASE_URL con el valor definido por el wizard
if (!getenv('BASE_URL')) {
    $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    putenv('BASE_URL=' . $base);
}
require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../includes/security.php';

session_start();
require_debug_mode();

// Validate CSRF token sent via header. Avoid undefined index warnings
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
// Only allow reading the session when the CSRF token matches
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('CSRF fail');
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($_SESSION);

