<?php
/**
 * File: restore_progress.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 *
 * Called by: JS helper to restore wizard progress from localStorage
 * Important POST params:
 *   - progress  Desired wizard step number
 * Writes session key:
 *   - $_SESSION['wizard_progress']
 * @TODO Extend documentation.
 */
declare(strict_types=1);

/**
 * api/restore_progress.php
 *
 * Recibe POST ‘progress=<n>’ (int). Valida que 0 ≤ n ≤ 6.
 * Si es válido, fija $_SESSION['wizard_progress'] = n y devuelve { success:true }.
 * En caso contrario, { success:false, error:"…", dbg:"…" }.
 */

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../includes/debug.php';

function dbgLocal(string $msg): void {
    dbg("[restore_progress.php] $msg");
}

// (A) Iniciar sesión
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL . '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
    dbgLocal("Sesión iniciada");
}

// (B) Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    dbgLocal("Método inválido: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode([
        'success' => false,
        'error'   => 'invalid_method',
        'dbg'     => 'Se esperaba POST'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// (C) Validar parámetro “progress”
$progressRaw = filter_input(INPUT_POST, 'progress', FILTER_VALIDATE_INT);
if ($progressRaw === false || $progressRaw === null) {
    dbgLocal("progress inválido: " . var_export($_POST['progress'] ?? null, true));
    echo json_encode([
        'success' => false,
        'error'   => 'invalid_progress',
        'dbg'     => 'No es un entero válido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($progressRaw < 0 || $progressRaw > 6) {
    dbgLocal("progress fuera de rango: $progressRaw");
    echo json_encode([
        'success' => false,
        'error'   => 'out_of_range',
        'dbg'     => 'progress debe estar entre 0 y 6'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// (D) Fijar en sesión y devolver éxito
// store the chosen step so wizard.php can resume correctly
$_SESSION['wizard_progress'] = $progressRaw;
session_regenerate_id(true);
dbgLocal("wizard_progress restaurado a $progressRaw");
echo json_encode([
    'success' => true,
    'dbg'     => "wizard_progress asignado a $progressRaw"
], JSON_UNESCAPED_UNICODE);
exit;
