<?php
/**
 * File: export_json.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 *
 * Called by: front-end export action (JSON variant)
 * Important session keys:
 *   - $_SESSION['csrf_token'] validates this download
 *   - All current wizard_* values are included in the JSON
 * @TODO Extend documentation.
 */
declare(strict_types=1);
// Definir BASE_URL en el mismo nivel que wizard.php
if (!getenv('BASE_URL')) {
    $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    putenv('BASE_URL=' . $base);
}
require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../includes/security.php';

session_start();
require_debug_mode();

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
// Validate request with session's CSRF token
if (!hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('CSRF fail');
}

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="wizard_config.json"');
echo json_encode($_SESSION, JSON_PRETTY_PRINT);

