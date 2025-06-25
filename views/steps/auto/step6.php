<?php
/**
 * File: views/steps/auto/step6.php
 * Paso 6 (Auto) – Ajuste de parámetros CNC.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../src/Utils/Session.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../src/Controller/ExpertResultController.php';
require_once __DIR__ . '/../../../src/Utils/CNCCalculator.php';

sendSecurityHeaders('text/html; charset=UTF-8');

// Sesión segura y flujo
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL . '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}
$error = null;
if (($_SESSION['wizard_progress'] ?? 0) < 5) {
    header('Location: ' . asset('views/steps/auto/step4.php'));
    exit;
}

// Obtener datos base
try {
    $params = ExpertResultController::getResultData($pdo, $_SESSION);
} catch (\Throwable $e) {
    error_log('[auto/step6] ' . $e->getMessage());
    $error = $e->getMessage();
}

if ($error) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="utf-8">
      <title>Paso 6 – Error</title>
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
    <main class="container py-4">
      <h2 class="step-title"><i data-feather="sliders"></i> Error</h2>
      <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </main>
    </body>
    </html>
    <?php
    exit;
}

$D         = (float) $params['diameter'];
$Z         = (int)   $params['flute_count'];
$thickness = (float) $_SESSION['thickness'];

$vc_base     = (float) $params['vc0'];
$fz_base     = (float) $params['fz0'];
$ae_base     = (float) $params['ae0'];
$passes_base = (int)   $params['passes0'];

// Valores actuales (POST o base)
$vc     = isset($_POST['vc'])     ? (float) $_POST['vc']     : $vc_base;
$fz     = isset($_POST['fz'])     ? (float) $_POST['fz']     : $fz_base;
$ae     = isset($_POST['ae'])     ? (float) $_POST['ae']     : $ae_base;
$passes = isset($_POST['passes']) ? (int)   $_POST['passes'] : $passes_base;

$maxPasses = max(1, (int) ceil($thickness / max($ae, 0.1)));
$ap        = $thickness / max(1, $passes);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 6 – Ajuste CNC</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<main class="container py-4">
  <h2 class="step-title"><i data-feather="sliders"></i> Ajuste de parámetros CNC</h2>

  <form method="POST" class="mb-4">
    <!-- Vc -->
    <div class="mb-3">
      <label for="vc" class="form-label">Vc: <?= number_format($vc, 1) ?> m/min</label>
      <input
        type="range"
        class="form-range"
        id="vc"
        name="vc"
        min="<?= number_format($vc_base * 0.5, 1, '.', '') ?>"
        max="<?= number_format($vc_base * 1.5, 1, '.', '') ?>"
        step="0.1"
        value="<?= htmlspecialchars((string) $vc) ?>"
        onchange="this.form.submit()"
      >
    </div>

    <!-- fz -->
    <div class="mb-3">
      <label for="fz" class="form-label">fz: <?= number_format($fz, 4) ?> mm/diente</label>
      <input
        type="range"
        class="form-range"
        id="fz"
        name="fz"
        min="<?= number_format($params['fz_min0'], 4, '.', '') ?>"
        max="<?= number_format($params['fz_max0'], 4, '.', '') ?>"
        step="0.0001"
        value="<?= htmlspecialchars((string) $fz) ?>"
        onchange="this.form.submit()"
      >
    </div>

    <!-- ae -->
    <div class="mb-3">
      <label for="ae" class="form-label">ae: <?= number_format($ae, 2) ?> mm</label>
      <input
        type="range"
        class="form-range"
        id="ae"
        name="ae"
        min="0.1"
        max="<?= number_format($D, 2, '.', '') ?>"
        step="0.1"
        value="<?= htmlspecialchars((string) $ae) ?>"
        onchange="this.form.submit()"
      >
    </div>

    <!-- pasadas -->
    <div class="mb-3">
      <label for="passes" class="form-label">Pasadas: <?= $passes ?></label>
      <input
        type="range"
        class="form-range"
        id="passes"
        name="passes"
        min="1"
        max="<?= $maxPasses ?>"
        step="1"
        value="<?= $passes ?>"
        onchange="this.form.submit()"
      >
      <div class="small text-muted">1–<?= $maxPasses ?> pasadas, ap = <?= number_format($ap, 2) ?> mm</div>
    </div>

    <noscript><button type="submit">Recalcular</button></noscript>
  </form>
</main>
<script src="https://cdn.jsdelivr.net/npm/feather-icons"></script>
<script>feather.replace()</script>
</body>
</html>
