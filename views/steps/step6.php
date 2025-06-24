<?php
/**
 * Paso 6 – Resultados finales
 * Vista simplificada para el CNC Wizard sin AJAX.
 *
 * Requiere que la página que la incluye provea <html> y cabeceras
 * (ver wizard_layout.php). Este archivo sólo imprime el contenido del
 * formulario y los resultados calculados.
 */

declare(strict_types=1);

use App\Controller\ExpertResultController;

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Utils/Session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../src/Model/ToolModel.php';
require_once __DIR__ . '/../../src/Controller/ExpertResultController.php';
require_once __DIR__ . '/../../src/Utils/CNCCalculator.php';

startSecureSession();
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);

$required = ['tool_id', 'tool_table', 'material', 'strategy', 'hp'];
$errors   = [];
foreach ($required as $k) {
    if (empty($_SESSION[$k])) {
        $errors[] = "Falta variable de sesión: {$k}";
    }
}

$tool   = null;
$params = [];
if (!$errors) {
    $tool = ToolModel::getTool($pdo, (string)$_SESSION['tool_table'], (int)$_SESSION['tool_id']);
    if (!$tool) {
        $errors[] = 'Herramienta no encontrada.';
    } else {
        $params = ExpertResultController::getResultData($pdo, $_SESSION);
    }
}

$diameter   = (float)($params['diameter'] ?? 0.0);
$fzDefault  = (float)($params['fz0'] ?? 0.0);
$vcDefault  = (float)($params['vc0'] ?? 0.0);
$aeDefault   = $diameter > 0 ? $diameter * 0.5 : 0.0;
$passesDefault = (int)($params['passes0'] ?? 1);

$fz     = (float)(filter_input(INPUT_POST, 'fz', FILTER_VALIDATE_FLOAT) ?? $fzDefault);
$vc     = (float)(filter_input(INPUT_POST, 'vc', FILTER_VALIDATE_FLOAT) ?? $vcDefault);
$ae     = (float)(filter_input(INPUT_POST, 'ae', FILTER_VALIDATE_FLOAT) ?? $aeDefault);
$passes = (int)(filter_input(INPUT_POST, 'passes', FILTER_VALIDATE_INT) ?? $passesDefault);

/**
 * Calcula los valores finales con fórmulas CNC.
 */
function calcResults(float $fz, float $vc, float $ae, int $passes, array $p, float $thickness): array
{
    $D       = (float)$p['diameter'];
    $Z       = (int)$p['flute_count'];
    $rpmMin  = (float)$p['rpm_min'];
    $rpmMax  = (float)$p['rpm_max'];
    $frMax   = (float)$p['fr_max'];
    $coefSeg = (float)$p['coef_seg'];
    $Kc11    = (float)$p['Kc11'];
    $mc      = (float)$p['mc'];
    $alpha   = (float)$p['rack_rad'];
    $eta     = 0.85;

    $rpmCalc = CNCCalculator::rpm($vc, $D);
    $rpm     = (int) round(min(max($rpmCalc, $rpmMin), $rpmMax));
    $feed    = (int) round(min(CNCCalculator::feed($rpm, $fz, $Z), $frMax));

    $phi = CNCCalculator::helixAngle($ae, $D);
    $hm  = CNCCalculator::chipThickness($fz, $ae, $D);

    $ap  = $thickness / max(1, $passes);
    $mmr = round(CNCCalculator::mmr($ap, $feed, $ae) / 1000.0, 2);

    $Fct = CNCCalculator::Fct($Kc11, $hm, $mc, $ap, $Z, $coefSeg, $alpha, $phi);
    [$W, $HP] = CNCCalculator::potencia($Fct, $vc, $eta);

    $vida  = min(100, max(0, (int)($p['vidaUtil']    ?? 60)));
    $term  = min(100, max(0, (int)($p['terminacion'] ?? 40)));
    $pot   = min(100, max(0, (int)($p['potencia']    ?? 80)));

    return [
        'rpm'        => $rpm,
        'feed'       => $feed,
        'vc'         => $vc,
        'fz'         => $fz,
        'ae'         => $ae,
        'ap'         => round($ap, 3),
        'hm'         => round($hm, 4),
        'fc'         => round($Fct, 1),
        'hp'         => $HP,
        'watts'      => $W,
        'mmr'        => $mmr,
        'etaPercent' => round($eta * 100),
        'life'       => $vida,
        'finish'     => $term,
        'power'      => $pot,
    ];
}

$result = [];
if (!$errors && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = calcResults($fz, $vc, $ae, $passes, $params, (float)$_SESSION['thickness']);
} elseif (!$errors) {
    $result = calcResults($fz, $vc, $ae, $passes, $params, (float)$_SESSION['thickness']);
}

$csrfToken = generateCsrfToken();
?>
<form action="wizard.php?step=6" method="post" class="container py-4">
  <h2 class="step-title"><i data-feather="bar-chart-2"></i> Resultados</h2>
  <p class="step-desc">Ajustá los parámetros y revisá los datos de corte.</p>

  <?php if ($DEBUG && $errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?>
        <div><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <label for="fz" class="form-label">fz (mm/tooth)</label>
      <input type="number" step="0.0001" required class="form-control" name="fz" id="fz"
             value="<?= htmlspecialchars(number_format($fz,4,'.','')) ?>">
    </div>
    <div class="col-md-3">
      <label for="vc" class="form-label">vc (m/min)</label>
      <input type="number" step="0.1" required class="form-control" name="vc" id="vc"
             value="<?= htmlspecialchars(number_format($vc,1,'.','')) ?>">
    </div>
    <div class="col-md-3">
      <label for="ae" class="form-label">ae (mm)</label>
      <input type="number" step="0.1" required class="form-control" name="ae" id="ae"
             value="<?= htmlspecialchars(number_format($ae,1,'.','')) ?>">
    </div>
    <div class="col-md-3">
      <label for="passes" class="form-label">Pasadas</label>
      <input type="number" step="1" min="1" required class="form-control" name="passes" id="passes"
             value="<?= htmlspecialchars((string)$passes) ?>">
    </div>
  </div>

  <div class="d-flex justify-content-between">
    <a href="wizard.php?step=5" class="btn btn-outline-secondary">
      <i data-feather="arrow-left" class="me-1"></i> Volver
    </a>
    <button type="submit" class="btn btn-primary">
      Siguiente <i data-feather="arrow-right" class="ms-1"></i>
    </button>
  </div>
</form>

<?php if ($result): ?>
  <div class="container my-4">
    <h3 class="h5 mb-3">Parámetros calculados</h3>
    <div class="row">
      <div class="col-md-6">
        <table class="table table-sm">
          <tr><th>RPM</th><td><?= number_format($result['rpm']) ?></td></tr>
          <tr><th>Feedrate</th><td><?= number_format($result['feed']) ?> mm/min</td></tr>
          <tr><th>Vc</th><td><?= number_format($result['vc'],1) ?> m/min</td></tr>
          <tr><th>fz</th><td><?= number_format($result['fz'],4) ?> mm/tooth</td></tr>
          <tr><th>Ae</th><td><?= number_format($result['ae'],1) ?> mm</td></tr>
          <tr><th>Ap</th><td><?= number_format($result['ap'],3) ?> mm</td></tr>
          <tr><th>hm</th><td><?= number_format($result['hm'],4) ?> mm</td></tr>
        </table>
      </div>
      <div class="col-md-6">
        <table class="table table-sm">
          <tr><th>Fc</th><td><?= $result['fc'] ?> N</td></tr>
          <tr><th>Potencia</th><td><?= $result['hp'] ?> HP</td></tr>
          <tr><th>W</th><td><?= $result['watts'] ?> W</td></tr>
          <tr><th>MMR</th><td><?= $result['mmr'] ?> mm³/min</td></tr>
          <tr><th>η</th><td><?= $result['etaPercent'] ?> %</td></tr>
        </table>
      </div>
    </div>
    <div class="row mt-3">
      <div class="col-md-4">
        <table class="table table-sm">
          <tr><th>Vida útil</th><td><?= $result['life'] ?> %</td></tr>
          <tr><th>Terminación</th><td><?= $result['finish'] ?> %</td></tr>
          <tr><th>Potencia</th><td><?= $result['power'] ?> %</td></tr>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>
