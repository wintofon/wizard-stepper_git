<?php
/**
 * File: views/steps/auto/step6.php
 * --------------------------------------------------------------
 * Paso 6 (Auto) – Resultados expertos
 * – 100 % “DOM-safe”: siempre emite <div class="step6"> … </div>
 * – Si algo falla ⇒ muestra alerta roja pero status 200 (fetch OK)
 * – <html><head>… se imprimen sólo cuando !$embedded
 */

declare(strict_types=1);

/* ───────── 0. SESIÓN SEGURA ───────── */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

/* ───────── 1. DEPENDENCIAS ───────── */
require_once dirname(__DIR__, 3) . '/src/Config/AppConfig.php';
require_once dirname(__DIR__, 3) . '/includes/db.php';            // $pdo
require_once dirname(__DIR__, 3) . '/includes/wizard_helpers.php'; // asset(), etc.

use App\Controller\ExpertResultController;
use App\Model\ToolModel;

$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

/* ───────── 2. HELPER DE ERROR (status 200) ───────── */
function step6Error(string $msg): void
{
    echo '<div class="step6 container py-4">'
       . '<div class="alert alert-danger" role="alert">'
       . htmlspecialchars($msg, ENT_QUOTES) . '</div></div>';
}

/* ───────── 3. VALIDAR SESIÓN / CLAVES ───────── */
$need = [
    'tool_table','tool_id','material','transmission_id',
    'rpm_min','rpm_max','feed_max','thickness','strategy_id','hp'
];
if ($miss = array_filter($need, fn($k) => empty($_SESSION[$k]))) {
    step6Error('Faltan datos de sesión: ' . implode(', ', $miss));
    return;
}

/* ───────── 4. OBTENER DATOS ───────── */
try {
    $toolData = ToolModel::getTool($pdo, $_SESSION['tool_table'], (int)$_SESSION['tool_id']);
    if (!$toolData) {
        step6Error('Herramienta no encontrada.'); return;
    }
    $params     = ExpertResultController::getResultData($pdo, $_SESSION);
    $jsonParams = json_encode($params,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    step6Error('Error interno: ' . $e->getMessage());
    return;
}

/* ───────── 5. HEAD / BODY (solo acceso directo) ───────── */
if (!$embedded): ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 6 – Resultados</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="<?= asset('assets/css/generic/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/step6.css') ?>">
</head>
<body>
<?php endif; ?>

<!-- ============= CONTENIDO (siempre) ============= -->
<div class="step6 container py-4">
  <h2 class="mb-4"><i data-feather="bar-chart-2"></i> Resultados</h2>

  <!-- Ejemplo mini dash: reemplazá por tu layout completo -->
  <div class="card p-3 mb-4">
    <h5 class="mb-2">Tool: <?= htmlspecialchars($toolData['tool_code']) ?></h5>
    <p class="mb-0">Vc base: <?= number_format($params['vc0'],1) ?> m/min</p>
  </div>
</div>

<!-- Params expuestos a JS -->
<script>
  window.step6Params = <?= $jsonParams ?>;
  window.step6Csrf   = '<?= $_SESSION['csrf_token'] ?? '' ?>';
  if (window.feather) requestAnimationFrame(() => window.feather.replace());
</script>

<?php if (!$embedded): ?>
<script src="<?= asset('assets/js/bootstrap.bundle.min.js') ?>" defer></script>
<script src="<?= asset('node_modules/feather-icons/dist/feather.min.js') ?>" defer></script>
</body>
</html>
<?php endif;
