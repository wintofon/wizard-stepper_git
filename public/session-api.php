<?php
/**
 * File: session-api.php
 * Dev-only helper — devuelve el contenido íntegro de $_SESSION.
 * CSRF:
 *   • GET + debug=1        →  NO exige token (solo lectura)
 *   • Cualquier otro caso  →  Valida X-CSRF-Token
 * -------------------------------------------------------------------------- */

declare(strict_types=1);

/* 0 · Base de ruta y dependencias */
if (!getenv('BASE_URL')) {
    $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    putenv('BASE_URL='.$base);
}
require_once __DIR__.'/../src/Config/AppConfig.php';
require_once __DIR__.'/../includes/security.php';

/* 1 · Sesión y flags */
session_start();
require_debug_mode();                                 // ← solo permite ?debug=1

$method   = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$isDebug  = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN) === true;

/* 2 · CSRF (solo si hace falta) */
if (!($method === 'GET' && $isDebug)) { // MOD
    $headerToken   = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken  = $_SESSION['csrf_token']       ?? null;

    if (!$sessionToken || !hash_equals($sessionToken, $headerToken)) {
        http_response_code(403);
        exit('CSRF fail');
    }
}

/* 3 · Respuesta JSON */
header('Content-Type: application/json; charset=UTF-8');
echo json_encode($_SESSION, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
