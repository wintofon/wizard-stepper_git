<?php
declare(strict_types=1);

/**
 * 🔧 Reinicio total del asistente Wizard CNC
 * ▸ Destruye la sesión actual y borra cookies
 * ▸ Limpia el localStorage del cliente (vía JS)
 * ▸ Redirige automáticamente a wizard.php
 * ▸ Compatible con entornos locales (XAMPP) y producción
 */

// ---------------------------------------------------
// [A] Definir BASE_URL desde el entorno o fallback
// ---------------------------------------------------
if (!getenv('BASE_URL')) {
    $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    putenv('BASE_URL=' . $base);
}
require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../src/Utils/Session.php';

// ---------------------------------------------------
// [B] Configuración de errores (debug por query ?debug=1)
// ---------------------------------------------------
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
error_reporting($DEBUG ? E_ALL : 0);
ini_set('display_errors', $DEBUG ? '1' : '0');

if (!function_exists('dbg')) {
    function dbg(string $msg): void {
        global $DEBUG;
        if ($DEBUG) {
            error_log("[reset.php] " . $msg);
        }
    }
}
dbg('🔁 Inicio de reset.php');

// ---------------------------------------------------
// [C] Cabeceras de seguridad y anti-cache
// ---------------------------------------------------
sendSecurityHeaders('text/html; charset=UTF-8', 63072000, true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// ---------------------------------------------------
// [D] Eliminar la sesión actual de forma segura
// ---------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL . '/',
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
    dbg('🔒 Sesión iniciada');
}

$_SESSION = [];
dbg('🧹 $_SESSION vaciado');

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'] ?? '/',
        'domain'   => $params['domain'] ?? '',
        'secure'   => $params['secure'] ?? true,
        'httponly' => $params['httponly'] ?? true,
        'samesite' => $params['samesite'] ?? 'Strict'
    ]);
    dbg('🍪 Cookie de sesión eliminada');
}

session_destroy();
dbg('💥 Sesión destruida');

// ---------------------------------------------------
// [E] Regenerar sesión para prevenir fixation
// ---------------------------------------------------
session_start([
    'cookie_secure'   => !empty($_SERVER['HTTPS']),
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);
session_regenerate_id(true);
session_unset();
session_destroy();
dbg('🔄 Regeneración y destrucción final de sesión');

// ---------------------------------------------------
// [F] HTML de salida con limpieza de localStorage
// ---------------------------------------------------
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reiniciando Wizard CNC...</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?= asset('assets/css/base/reset.css') ?>">
  <script>
    // Exponer BASE_URL a JS por si es necesario en otros scripts
    window.BASE_URL = <?= json_encode(BASE_URL) ?>;
  </script>
</head>
<body>
  <div class="message-box">
    <h1>Reiniciando el asistente CNC...</h1>
    <p>Limpiando sesión y configuración local, por favor espere...</p>
  </div>
  <script>
    try {
      localStorage.clear();
      console.info('[reset] ✅ localStorage limpiado');
    } catch (e) {
      console.warn('[reset] ⚠️ No se pudo limpiar localStorage:', e);
    }

    setTimeout(() => {
      window.location.replace(`${window.BASE_URL}/wizard.php`);
    }, 200);
  </script>
</body>
</html>
<?php
exit;
