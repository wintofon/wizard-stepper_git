<?php
/**
 * File: views/steps/auto/step6.php
 * Paso 6 (Auto) – Plantilla base sobre la que seguiremos iterando.
 *
 * • Reutiliza el esqueleto de seguridad y flujo de Step 5.
 * • Verifica que el usuario haya completado el Paso 5 (wizard_progress ≥ 5).
 * • Genera/valida token CSRF (aún no procesamos POST).
 * • De momento NO consulta BD ni imprime sliders; sólo “Hola Step 6”.
 * • Compatible con modo embebido (load-step.php) y modo standalone.
 */

declare(strict_types=1);

/* 1) Sesión segura y flujo */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 4) {
    header('Location: step1.php');
    exit;
}

/* 2) Dependencias */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/debug.php';

/* 3) CSRF token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* 4) Detección de modo embebido ---------------------------------------------*/
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

/* 5) Parámetros iniciales (vacíos) ------------------------------------------*/
$jsonParams = '{}';

/* 6) Salida embebida: sólo fragmento limpio --------------------------------*/
if ($embedded) { ?>
<div class="step6 p-4" style="font-family:Arial,Helvetica,sans-serif;">
  <h2>Hola Step 6 ✅</h2>
  <p>Versión base con CSRF y sesión segura. (Embebida)</p>
</div>
<script>
  window.step6Params = <?= $jsonParams ?>;
  window.step6Csrf   = '<?= $csrfToken ?>';
</script>
<?php return; }

/* 7) Stand‑alone (debug directo en navegador) -------------------------------*/
?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8">
<title>Paso 6 – Plantilla base</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head><body class="p-4" style="font-family:Arial,Helvetica,sans-serif;">
  <h2>Hola Step 6 ✅ (stand‑alone)</h2>
  <p>Plantilla base lista para ir añadiendo controles.</p>
  <script>
    window.step6Params = <?= $jsonParams ?>;
    window.step6Csrf   = '<?= $csrfToken ?>';
  </script>
</body></html>
