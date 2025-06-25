<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/Utils/Session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../src/Controller/ExpertResultController.php';
require_once __DIR__ . '/../../src/Utils/CNCCalculator.php';

sendSecurityHeaders('text/html; charset=UTF-8');

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
$sessionDump = '';
$missing = [];
if (($_SESSION['wizard_progress'] ?? 0) < 5) {
    header('Location: ' . asset('views/steps/manual/step4.php'));
    exit;
}

try {
    $params = ExpertResultController::getResultData($pdo, $_SESSION);
} catch (\Throwable $e) {
    error_log('[step6] ' . $e->getMessage());
    $error = $e->getMessage();
    $sessionDump = print_r($_SESSION, true);
    $requiredKeys = [
        'tool_table','tool_id','material','trans_id',
        'rpm_min','rpm_max','fr_max','thickness','hp'
    ];
    $missing = array_filter($requiredKeys, fn($k) => empty($_SESSION[$k]));
}

if ($error) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Paso 6 – Error</title>
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
    <main class="container py-4">
      <h2 class="mb-4">Paso 6 – Error</h2>
      <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php if ($missing): ?>
        <div class="alert alert-warning">
          Claves faltantes: <?= htmlspecialchars(implode(', ', $missing), ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>
      <?php if ($sessionDump): ?>
        <pre class="bg-light border p-2 small overflow-auto"><?= htmlspecialchars($sessionDump, ENT_QUOTES, 'UTF-8') ?></pre>
      <?php endif; ?>
    </main>
    </body>
    </html>
    <?php
    exit;
}

$D         = (float) $params['diameter'];
$Z         = (int)   $params['flute_count'];
$thickness = (float) $_SESSION['thickness'];
$rpmMin    = (float) $params['rpm_min'];
$rpmMax    = (float) $params['rpm_max'];
$frMax     = (float) $params['fr_max'];
$Kc11      = (float) $params['Kc11'];
$mc        = (float) $params['mc'];
$coefSeg   = (float) $params['coef_seg'];
$alpha     = (float) $params['rack_rad'];
$eta       = 0.85;

$vc0       = (float) $params['vc0'];
$fzMin     = (float) $params['fz_min0'];
$fzMax     = (float) $params['fz_max0'];
$fz0       = (float) $params['fz0'];
$ae_base   = (float) $params['ae0'];
$passes0   = (int)   $params['passes0'];

$vc_adj     = isset($_POST['vc_adj'])     ? (float) $_POST['vc_adj'] : $vc0;
$fz_adj     = isset($_POST['fz_adj'])     ? (float) $_POST['fz_adj'] : $fz0;
$ae_adj     = isset($_POST['ae_adj'])     ? (float) $_POST['ae_adj'] : $ae_base;
$passes_adj = isset($_POST['passes'])     ? (int)   $_POST['passes'] : $passes0;

$phi   = CNCCalculator::helixAngle($ae_adj, $D);
$hm    = CNCCalculator::chipThickness($fz_adj, $ae_adj, $D);
$rpm_c = CNCCalculator::rpm($vc_adj, $D);
$rpm   = (int) round(max($rpmMin, min($rpm_c, $rpmMax)));
$vf    = min(CNCCalculator::feed($rpm, $fz_adj, $Z), $frMax);
$ap    = $thickness / max(1, $passes_adj);
$mmr   = round(CNCCalculator::mmr($ap, $vf, $ae_adj), 2);
$Fct   = CNCCalculator::Fct($Kc11, $hm, $mc, $ap, $Z, $coefSeg, $alpha, $phi);
[$watts, $hp] = CNCCalculator::potencia($Fct, $vc_adj, $eta);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Paso 6 – Resultados CNC</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-…"
    crossorigin="anonymous">
</head>
<body>
<main class="container py-4">
  <h2 class="mb-4">Paso 6 – Ajustá y revisá tus resultados</h2>

  <form method="POST"
        action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
        class="mb-5">
    <!-- CSRF token -->
    <?php if (!empty($_SESSION['csrf_token'])): ?>
      <input type="hidden"
             name="csrf_token"
             value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <?php endif; ?>

    <!-- Slider Vc -->
    <?php
      $vcMin = number_format($vc0 * 0.5, 1, '.', '');
      $vcMax = number_format($vc0 * 1.5, 1, '.', '');
    ?>
    <div class="mb-4">
      <label for="vc-range" class="form-label">Vc (–50% … +50%)</label>
      <input type="range"
             id="vc-range"
             name="vc_adj"
             class="form-range"
             min="<?= $vcMin ?>"
             max="<?= $vcMax ?>"
             step="0.1"
             value="<?= htmlspecialchars($vc_adj) ?>"
             oninput="vcOutput.value = this.value">
      <output id="vcOutput"
              class="ms-2"
              for="vc-range"><?= htmlspecialchars($vc_adj) ?></output> m/min
    </div>

    <!-- Slider fz -->
    <?php
      $fzMinFmt = number_format($fzMin, 4, '.', '');
      $fzMaxFmt = number_format($fzMax, 4, '.', '');
    ?>
    <div class="mb-4">
      <label for="fz-range" class="form-label">
        fz (<?= $fzMinFmt ?> … <?= $fzMaxFmt ?>)
      </label>
      <input type="range"
             id="fz-range"
             name="fz_adj"
             class="form-range"
             min="<?= $fzMinFmt ?>"
             max="<?= $fzMaxFmt ?>"
             step="0.0001"
             value="<?= htmlspecialchars($fz_adj) ?>"
             oninput="fzOutput.value = this.value">
      <output id="fzOutput"
              class="ms-2"
              for="fz-range"><?= htmlspecialchars($fz_adj) ?></output> mm/diente
    </div>

    <!-- Slider ae -->
    <?php $aeMax = number_format($D, 1, '.', ''); ?>
    <div class="mb-4">
      <label for="ae-range" class="form-label">
        ae (0.1 … <?= $aeMax ?>)
      </label>
      <input type="range"
             id="ae-range"
             name="ae_adj"
             class="form-range"
             min="0.1"
             max="<?= $aeMax ?>"
             step="0.1"
             value="<?= htmlspecialchars($ae_adj) ?>"
             oninput="aeOutput.value = this.value">
      <output id="aeOutput"
              class="ms-2"
              for="ae-range"><?= htmlspecialchars($ae_adj) ?></output> mm
    </div>

    <!-- Slider pasadas -->
    <?php $maxPass = max(1, (int)ceil($thickness / max(0.001, $ae_adj))); ?>
    <div class="mb-4">
      <label for="passes-range" class="form-label">
        Pasadas (1 … <?= $maxPass ?>)
      </label>
      <input type="range"
             id="passes-range"
             name="passes"
             class="form-range"
             min="1"
             max="<?= $maxPass ?>"
             step="1"
             value="<?= htmlspecialchars($passes_adj) ?>"
             oninput="passesOutput.value = this.value">
      <output id="passesOutput"
              class="ms-2"
              for="passes-range"><?= htmlspecialchars($passes_adj) ?></output> pasadas
    </div>

    <button type="submit" class="btn btn-primary">Recalcular</button>
  </form>

  <div class="row row-cols-1 row-cols-md-2 g-4">
    <?php foreach ([
      ['Diámetro de corte','mm',$D],
      ['Filos (Z)','uds',$Z],
      ['fz','mm/diente',$fz_adj],
      ['Vc','m/min',$vc_adj],
      ['RPM','RPM',$rpm],
      ['Vf','mm/min',$vf],
      ['ae','mm',$ae_adj],
      ['ap','mm',$ap],
      ['hm','mm',$hm],
      ['MMR','mm³/min',$mmr],
      ['Fct','N',$Fct],
      ['Potencia W','W',$watts],
      ['Potencia HP','HP',$hp],
    ] as [$title, $unit, $value]): ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h6 class="card-title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h6>
            <p class="display-6 mb-0">
              <?= number_format(
                   $value,
                   strpos($unit, 'mm/diente') !== false ? 4 : 0
                 ) ?>
              <small class="fs-6 text-muted">
                <?= htmlspecialchars($unit, ENT_QUOTES) ?>
              </small>
            </p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"
        integrity="sha384-…"
        crossorigin="anonymous"></script>
<script>feather.replace()</script>
</body>
</html>
