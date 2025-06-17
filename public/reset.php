<?php
declare(strict_types=1);
/**
 * File: reset.php
 * ---------------------------------------------------------------
 * ▸ Destruye completamente la sesión del Wizard CNC
 * ▸ Elimina todas las variables de sesión y cookies asociadas
 * ▸ Envía cabeceras de seguridad y anti-caching
 * ▸ Limpia localStorage en el cliente y redirige a index.php
 * ---------------------------------------------------------------
 */

// -------------------------------------------
// [1] CONFIGURACIÓN DE ERRORES Y DEBUG
// -------------------------------------------
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

/**
 * Función de logging para desarrollo.
 * En producción no mostrará nada.
 */
if (!function_exists('dbg')) {
    function dbg(string $msg): void {
        global $DEBUG;
        if ($DEBUG) {
            error_log("[reset.php] " . $msg);
        }
    }
}
dbg('🔧 reset.php iniciado');

// -------------------------------------------
// [2] CABECERAS DE SEGURIDAD Y NO-CACHING
// -------------------------------------------
header('Content-Type: text/html; charset=UTF-8');
header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=()');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// -------------------------------------------
// [3] INICIALIZAR SESIÓN DE FORMA SEGURA
// -------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',        // Ajustar si se requiere dominio específico
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
    dbg('🔒 Sesión iniciada para destrucción');
}

// -------------------------------------------
// [4] ELIMINAR VARIABLES DE SESIÓN
// -------------------------------------------
$_SESSION = [];
dbg('🗑️ Arreglo $_SESSION borrado');

// -------------------------------------------
// [5] DESTRUIR COOKIE DE SESIÓN EN EL CLIENTE
// -------------------------------------------
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires'  => time() - 42000,
            'path'     => $params["path"]    ?? '/',
            'domain'   => $params["domain"]  ?? '',
            'secure'   => $params["secure"]  ?? true,
            'httponly' => $params["httponly"] ?? true,
            'samesite' => $params["samesite"] ?? 'Strict',
        ]
    );
    dbg('🍪 Cookie de sesión destruida');
}

// -------------------------------------------
// [6] DESTRUIR LA SESIÓN
// -------------------------------------------
session_destroy();
dbg('💣 Sesión destruida completamente');

// -------------------------------------------
// [7] FORZAR NUEVA SESIÓN (prevención de session fixation)
// -------------------------------------------
session_start([
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);
session_regenerate_id(true);
session_unset();
session_destroy();
dbg('🔄 Sesión regenerada y destruida nuevamente para mayor seguridad');

// -------------------------------------------
// [8] HTML + JS PARA BORRAR localStorage Y REDIRIGIR
// -------------------------------------------
echo <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reiniciando Wizard CNC...</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/base/reset.css">
</head>
<body>
  <div class="message-box">
    <h1>Reiniciando Wizard CNC...</h1>
    <p>Espere un instante, por favor.</p>
  </div>
  <script>
    try {
      // Borrar **todos** los items de localStorage
      localStorage.clear();
    } catch(e) {
      console.warn('No se pudo limpiar localStorage:', e);
    }
    // Redirigir a index.php tras un breve retardo (200ms)
    setTimeout(function() {
      window.location.replace('../index.php');
    }, 200);
  </script>
</body>
</html>
HTML;
exit;
