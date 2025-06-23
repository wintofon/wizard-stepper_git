<?php
/**
 * File: views/steps/auto/step6.php
 * Iteración 2 – Skeleton con cabeceras y nonce (2025-06-23)
 *
 * ▸ Se incorpora la misma rutina de cabeceras de seguridad usada en step1/step5
 *   (sendSecurityHeaders + CSP con nonce) para descartar que el fallo venga de
 *   políticas HTTP.
 * ▸ Sigue sin BD ni JS externos; sólo lee la sesión y muestra JSON.
 * ▸ Próxima iteración (si esto funciona): añadir un slider simple + Feather.
 */

declare(strict_types=1);

// ───────────────────────── 1) Dependencias utilitarias ────────────────
require_once __DIR__ . '/../../../src/Utils/Session.php';

// ───────────────────────── 2) Sesión segura ───────────────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

// ───────────────────────── 3) Cabeceras de seguridad ─────────────────
sendSecurityHeaders('text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$nonce = base64_encode(random_bytes(16));
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$nonce'; style-src 'self' 'unsafe-inline';");

// ───────────────────────── 4) Detectar modo embebido ──────────────────
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

// ───────────────────────── 5) Construir parámetros JSON  ──────────────
$paramKeys = ['vc0','fz0','rpm0','feed0','material_name'];
$params = [];
foreach ($paramKeys as $k) if (isset($_SESSION[$k])) $params[$k] = $_SESSION[$k];
$jsonParams = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
$csrfToken  = $_SESSION['csrf_token'] ?? '';

// =====================================================================
// ================ 6) SALIDA EMBEBIDA – fragmento limpio ===============
// =====================================================================
if ($embedded) { ?>
<div class="step6 p-4">
  <h2 class="step-title">Step 6 – Iteración 2 ✅</h2>
  <p class="step-desc">Con cabeceras CSP y nonce aplicadas.</p>
  <?php if ($params): ?>
    <pre class="bg-light p-2 border rounded-1 small mb-0"><?php echo htmlspecialchars(json_encode($params, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?></pre>
  <?php else: ?>
    <div class="text-muted">(Sesión vacía – sin parámetros aún).</div>
  <?php endif; ?>
</div>
<script nonce="<?= $nonce ?>">
  window.step6Params = <?= $jsonParams ?>;
  window.step6Csrf   = '<?= $csrfToken ?>';
</script>
<?php return; }

// =====================================================================
// ================ 7) SALIDA STANDALONE – HTML mínimo =================
// =====================================================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Step 6 – Iteración 2</title>
  <style nonce="<?= $nonce ?>">
    body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:2rem;background:#f4f4f4}
    .step6{max-width:640px;margin:auto;background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.1)}
    pre{white-space:pre-wrap;word-break:break-all}
  </style>
</head>
<body>
  <div class="step6">
    <h2>Step 6 – Iteración 2 ✅</h2>
    <p>Con cabeceras CSP y nonce aplicadas.</p>
    <?php if ($params): ?>
      <pre class="bg-light p-2 border rounded-1 small mb-0"><?php echo htmlspecialchars(json_encode($params, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?></pre>
    <?php else: ?>
      <div class="text-muted">(Sesión vacía – sin parámetros aún).</div>
    <?php endif; ?>
  </div>
  <script nonce="<?= $nonce ?>">
    window.step6Params = <?= $jsonParams ?>;
    window.step6Csrf   = '<?= $csrfToken ?>';
  </script>
</body>
</html>
