<?php
declare(strict_types=1);
use App\Controller\ExpertResultController;
use ToolModel;
use CNCCalculator;

$root = dirname(__DIR__, 2);
require_once $root . '/src/Config/AppConfig.php';
require_once $root . '/src/Utils/Session.php';
require_once $root . '/vendor/autoload.php';
require_once $root . '/includes/db.php';

startSecureSession();
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);

$required = ['tool_id','tool_table','material','strategy','hp'];
$missing = array_filter($required, fn($k)=>empty($_SESSION[$k]));
if ($missing && $DEBUG) {
    echo '<div class="alert alert-danger">Faltan datos de sesión: ' .
         htmlspecialchars(implode(', ', $missing), ENT_QUOTES, 'UTF-8') . '</div>';
}

$tool = null;
$params = [];
$values = ['fz'=>0,'vc'=>0,'ae'=>0];
$results = null;

if (!$missing) {
    $tool = ToolModel::getTool($pdo, (string)$_SESSION['tool_table'], (int)$_SESSION['tool_id']);
    $params = ExpertResultController::getResultData($pdo, $_SESSION);
    $diameter = (float)($tool['diameter_mm'] ?? 0.0);
    $values = [
        'fz' => (float)$params['fz0'],
        'vc' => (float)$params['vc0'],
        'ae' => $diameter > 0 ? $diameter * 0.5 : 0.0,
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (int)($_POST['step'] ?? 0) === 6) {
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            if ($DEBUG) echo '<div class="alert alert-danger">CSRF inválido</div>';
        } else {
            $values['fz'] = (float)($_POST['fz'] ?? $values['fz']);
            $values['vc'] = (float)($_POST['vc'] ?? $values['vc']);
            $values['ae'] = (float)($_POST['ae'] ?? $values['ae']);

            $D = $diameter;
            $Z = (int)($tool['flute_count'] ?? 1);
            $rpmMin = (float)$params['rpm_min'];
            $rpmMax = (float)$params['rpm_max'];
            $frMax  = (float)$params['fr_max'];
            $thk    = (float)$_SESSION['thickness'];
            $coefSeg= (float)$params['coef_seg'];
            $Kc11   = (float)$params['Kc11'];
            $mc     = (float)$params['mc'];
            $alpha  = (float)$params['rack_rad'];
            $eta    = 0.85;

            $phi  = CNCCalculator::helixAngle($values['ae'], $D);
            $hm   = CNCCalculator::chipThickness($values['fz'], $values['ae'], $D);
            $rpmC = CNCCalculator::rpm($values['vc'], $D);
            $rpm  = (int)round(min(max($rpmC, $rpmMin), $rpmMax));
            $feed = min(CNCCalculator::feed($rpm, $values['fz'], $Z), $frMax);
            $ap   = $thk;
            $mmr  = round(CNCCalculator::mmr($ap, $feed, $values['ae']), 2);
            $Fct  = CNCCalculator::Fct($Kc11, $hm, $mc, $ap, $Z, $coefSeg, $alpha, $phi);
            [$W,$HP] = CNCCalculator::potencia($Fct, $values['vc'], $eta);

            $results = [
                'rpm'  => $rpm,
                'feed' => (int)round($feed),
                'vc'   => number_format($values['vc'],1,'.',''),
                'fz'   => number_format($values['fz'],4,'.',''),
                'ae'   => number_format($values['ae'],1,'.',''),
                'ap'   => number_format($ap,3,'.',''),
                'hm'   => number_format($hm,4,'.',''),
                'hp'   => $HP,
                'fc'   => round($Fct,1),
                'mmr'  => $mmr,
            ];
        }
    }
}
$csrf = generateCsrfToken();
?>
<div class="container py-4 step6">
  <form method="post" action="<?= asset('wizard.php?step=6') ?>" class="mb-4">
    <input type="hidden" name="step" value="6">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label" for="fz">fz (mm/tooth)</label>
        <input type="number" step="0.0001" min="0" class="form-control" id="fz" name="fz" value="<?= htmlspecialchars(number_format($values['fz'],4,'.','')) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="vc">vc (m/min)</label>
        <input type="number" step="0.1" min="0" class="form-control" id="vc" name="vc" value="<?= htmlspecialchars(number_format($values['vc'],1,'.','')) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="ae">ae (mm)</label>
        <input type="number" step="0.1" min="0" class="form-control" id="ae" name="ae" value="<?= htmlspecialchars(number_format($values['ae'],1,'.','')) ?>" required>
      </div>
    </div>
    <div class="d-flex justify-content-between mt-4">
      <a href="<?= asset('wizard.php?step=5') ?>" class="btn btn-secondary">Volver</a>
      <button type="submit" class="btn btn-primary">Siguiente</button>
    </div>
  </form>

  <?php if ($results): ?>
  <div class="card mt-3">
    <div class="card-header">Resultados</div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-6">Feedrate:</div><div class="col-6"><?= $results['feed'] ?> mm/min</div>
        <div class="col-6">RPM:</div><div class="col-6"><?= $results['rpm'] ?></div>
        <div class="col-6">Vc:</div><div class="col-6"><?= $results['vc'] ?> m/min</div>
        <div class="col-6">fz:</div><div class="col-6"><?= $results['fz'] ?> mm/tooth</div>
        <div class="col-6">Ap:</div><div class="col-6"><?= $results['ap'] ?> mm</div>
        <div class="col-6">Ae:</div><div class="col-6"><?= $results['ae'] ?> mm</div>
        <div class="col-6">hm:</div><div class="col-6"><?= $results['hm'] ?> mm</div>
        <div class="col-6">Hp:</div><div class="col-6"><?= $results['hp'] ?> HP</div>
        <div class="col-6">Fc:</div><div class="col-6"><?= $results['fc'] ?> N</div>
        <div class="col-6">MMR:</div><div class="col-6"><?= $results['mmr'] ?> mm³/min</div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($DEBUG): ?>
    <pre class="mt-3 bg-light p-2 border"><code><?= htmlspecialchars(print_r(['values'=>$values,'results'=>$results], true)) ?></code></pre>
  <?php endif; ?>
</div>
