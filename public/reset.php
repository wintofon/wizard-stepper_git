<?php
declare(strict_types=1);

// [A] BASE + CONFIG
if (!getenv('BASE_URL')) {
    $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    putenv('BASE_URL=' . $base);
}
require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../src/Utils/Session.php';

$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Función de logging
if (!function_exists('dbg')) {
    function dbg(string $msg): void {
        global $DEBUG;
        if ($DEBUG) {
            error_log("[reset.php] " . $msg);
        }
    }
}
dbg('🚨 Iniciando RESET COMPLETO');

// [B] Cabeceras de seguridad y no-cache
sendSecurityHeaders('text/html; charset=UTF-8', 63072000, true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// [C] Iniciar sesión si aún no está activa
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL . '/',
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
dbg('🔐 Sesión activa');

// [D] Borrar todo el estado de sesión
$_SESSION = [];
session_unset();
session_destroy();
dbg('💣 Sesión destruida');

// [E] Borrar todas las cookies (no solo la de sesión)
if (headers_sent() === false) {
    foreach ($_COOKIE as $name => $value) {
        setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '', 
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => false,
            'samesite' => 'Lax'
        ]);
    }
    dbg('🍪 Todas las cookies destruidas');
}

// [F] Borrar archivos de sesión persistente en disco
$sessionFiles = ini_get('session.save_path') ?: sys_get_temp_dir();
foreach (glob("$sessionFiles/sess_*") as $file) {
    @unlink($file);
}
dbg('🧨 Archivos de sesión eliminados');

// [G] Forzar nueva sesión limpia
session_start();
session_regenerate_id(true);
session_destroy();
dbg('🔄 Nueva sesión limpia generada');

// [H] HTML de destrucción y redirección
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reiniciando Wizard CNC...</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?= asset('assets/css/base/reset.css') ?>">
  <script>
    const BASE_URL = <?= json_encode(BASE_URL) ?>;
  </script>
</head>
<body>
  <div class="message-box">
    <h1>Reiniciando Wizard CNC...</h1>
    <p>Espere un instante...</p>
  </div>

  <script>
    try {
      localStorage.clear();
      sessionStorage.clear();
      console.log("🧹 localStorage y sessionStorage limpiados");
    } catch (e) {
      console.warn("⚠️ Error limpiando almacenamiento:", e);
    }

    // Borrar todas las cookies JS
    document.cookie.split(";").forEach(function(c) {
      document.cookie = c
        .replace(/^ +/, "")
        .replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
    });

    setTimeout(() => {
      window.location.replace(BASE_URL + "/wizard.php");
    }, 200);
  </script>
</body>
</html>
<?php exit; ?>
