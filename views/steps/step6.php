<?php
/**
 * File: views/steps/auto/step6.php
 * Iteración 1 – Skeleton + sesión / JSON  (2025-06-23)
 *
 * ▶ Propósito: confirmar que leer valores de sesión y exponer window.step6Params
 *   no contamine el DOM cuando el paso se carga embebido.
 * ▶ Sin conexión a BD ni dependencias externas todavía.
 * ▶ Próxima iteración (si todo va bien): sliders simples y escucha JS.
 */

declare(strict_types=1);

// ────────────────────────────────────────────────
// Detectar modo embebido
// ────────────────────────────────────────────────
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

// ────────────────────────────────────────────────
// Sesión segura (antes de headers)
// ────────────────────────────────────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ────────────────────────────────────────────────
// Construir objeto de parámetros (placeholders por ahora)
// ────────────────────────────────────────────────
$paramKeys = ['vc0','fz0','rpm0','feed0','material_name'];
$params    = [];
foreach ($paramKeys as $k) {
    if (isset($_SESSION[$k])) $params[$k] = $_SESSION[$k];
}
$jsonParams = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($jsonParams === false) $jsonParams = '{}';

// CSRF token placeholder (aún sin POST en esta iteración)
$csrfToken = $_SESSION['csrf_token'] ?? '';

// ────────────────────────────────────────────────
// MODO EMBEBIDO – sólo fragmento limpio
// ────────────────────────────────────────────────
if ($embedded) {
    ?>
    <div class="step6 p-4">
        <h2 class="step-title">Step 6 – Iteración 1 ✅</h2>
        <p class="step-desc">Lectura de sesión y JSON expuesto:</p>
        <?php if ($params): ?>
          <pre class="bg-light p-2 border rounded-1 small mb-0"><?php echo htmlspecialchars(json_encode($params, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?></pre>
        <?php else: ?>
          <div class="text-muted">(Sesión vacía – sin parámetros aún).</div>
        <?php endif; ?>
    </div>
    <script>
      window.step6Params = <?= $jsonParams ?>;
      window.step6Csrf   = '<?= $csrfToken ?>';
    </script>
    <?php
    return; // ← nada más en modo embebido
}

// ────────────────────────────────────────────────
// MODO STANDALONE – envoltorio HTML mínimo
// ────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Step 6 – Iteración 1</title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:2rem;background:#f4f4f4}
    .step6{max-width:640px;margin:auto;background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.1)}
    pre{white-space:pre-wrap;word-break:break-all}
  </style>
</head>
<body>
  <div class="step6">
    <h2>Step 6 – Iteración 1 ✅</h2>
    <p>Lectura de sesión y JSON expuesto:</p>
    <?php if ($params): ?>
      <pre class="bg-light p-2 border rounded-1 small mb-0"><?php echo htmlspecialchars(json_encode($params, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?></pre>
    <?php else: ?>
      <div class="text-muted">(Sesión vacía – sin parámetros aún).</div>
    <?php endif; ?>
  </div>
  <script>
    window.step6Params = <?= $jsonParams ?>;
    window.step6Csrf   = '<?= $csrfToken ?>';
  </script>
</body>
</html>
