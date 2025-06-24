<?php
/**
 * File: views/steps/auto/step6.php
 * Paso 6 embebido (completo) sin JS ni AJAX.
 * Calcula y muestra resultados CNC server-side usando 4 sliders.
 */
declare(strict_types=1);

// 1) Seguridad y flujo
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 5) {
    header('Location: step1.php');
    exit;
}

// 2) Dependencias
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/debug.php';
require_once __DIR__ . '/../../src/Model/ToolModel.php';
require_once __DIR__ . '/../../src/Model/ConfigModel.php';
require_once __DIR__ . '/../../src/Utils/CNCCalculator.php';

// 3) Validar sesión
$required = ['tool_id','tool_table','material_id','trans_id','thickness','rpm_min','rpm_max','feed_max','hp'];
$missing  = array_filter($required, fn($k) => !isset($_SESSION[$k]));
if ($missing) {
    echo "<p class='alert alert-danger'>Faltan datos: " . implode(', ', $missing) . "</p>";
    exit;
}

// 4) Carga de valores base
$toolId    = (int) $_SESSION['tool_id'];
$toolTable = $_SESSION['tool_table'];
$materialId= (int) $_SESSION['material_id'];
$transId   = (int) $_SESSION['trans_id'];
$thickness = (float) $_SESSION['thickness'];
$rpmMin    = (float) $_SESSION['rpm_min'];
$rpmMax    = (float) $_SESSION['rpm_max'];
$frMax     = (float) $_SESSION['feed_max'];
$hpAvail   = (float) $_SESSION['hp'];

// 5) Datos herramienta
$tool = ToolModel::getTool($pdo, $toolTable, $toolId) ?: [];
$D        = (float) ($tool['diameter_mm']       ?? 0);
$Z        = (int)   ($tool['flute_count']        ?? 1);

// 6) Parámetros calculados por ExpertResultController
$params   = App\Controller\ExpertResultController::getResultData($pdo, $_SESSION);
$vc0      = (float) $params['vc0'];
$fz0      = (float) $params['fz0'];
$ae0      = (float) $params['ae_slot'];
$passes0  = 1;
$fz_min   = (float) $params['fz_min0'];
$fz_max   = (float) $params['fz_max0'];

// 7) Leer overrides del POST
$vc_adj     = isset($_POST['vc_adj'])     ? (float)$_POST['vc_adj']     : $vc0;
$fz_adj     = isset($_POST['fz_adj'])     ? (float)$_POST['fz_adj']     : $fz0;
$ae_adj     = isset($_POST['ae_adj'])     ? (float)$_POST['ae_adj']     : $ae0;
$passes_adj = isset($_POST['passes'])     ? (int)$_POST['passes']       : $passes0;

// 8) Datos materiales
$Kc11    = ConfigModel::getKc11($pdo, $materialId);
$mc      = ConfigModel::getMc($pdo, $materialId);
$coefSeg = ConfigModel::getCoefSeg($pdo, $transId);
$alpha   = 0.0;
$eta     = 0.85;

// 9) Recalcular CNC
$phi   = CNCCalculator::helixAngle($ae_adj, $D);
$hm    = CNCCalculator::chipThickness($fz_adj, $ae_adj, $D);
$rpm   = CNCCalculator::rpm($vc_adj, $D);
$vf    = min(CNCCalculator::feed($rpm, $fz_adj, $Z), $frMax);
$ap    = $thickness / max(1, $passes_adj);
$mmr   = CNCCalculator::mmr($ap, $vf, $ae_adj);
$Fct   = CNCCalculator::Fct($Kc11, $hm, $mc, $ap, $Z, $coefSeg, $alpha, $phi);
[$watts, $hp] = CNCCalculator::potencia($Fct, $vc_adj, $eta);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 6 – Resultados CNC</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<main class="container py-4">
  <h2>Paso 6 – Ajusta y revisa tus resultados</h2>
  <form method="POST" class="mb-5">

    <!-- Slider Vc -->
    <div class="mb-4">
      <label class="form-label">Vc (±50%)</label>
      <input type="range" name="vc_adj" class="form-range"
        min="<?= number_format($vc0 * 0.5,1,'.','') ?>"
        max="<?= number_format($vc0 * 1.5,1,'.','') ?>"
        step="0.1" value="<?= $vc_adj ?>"
        oninput="this.nextElementSibling.value = this.value">
      <output class="ms-2"><?= $vc_adj ?></output> m/min
    </div>

    <!-- Slider fz -->
    <div class="mb-4">
      <label class="form-label">fz (<?= number_format($fz_min,4) ?>…<?= number_format($fz_max,4) ?>)</label>
      <input type="range" name="fz_adj" class="form-range"
        min="<?= number_format($fz_min,4,'.','') ?>"
        max="<?= number_format($fz_max,4,'.','') ?>"
        step="0.0001" value="<?= $fz_adj ?>"
        oninput="this.nextElementSibling.value = this.value">
      <output class="ms-2"><?= $fz_adj ?></output> mm/diente
    </div>

    <!-- Slider ae -->
    <div class="mb-4">
      <label class="form-label">ae (0.1…<?= number_format($D,1) ?>)</label>
      <input type="range" name="ae_adj" class="form-range"
        min="0.1" max="<?= number_format($D,1,'.','') ?>"
        step="0.1" value="<?= $ae_adj ?>"
        oninput="this.nextElementSibling.value = this.value">
      <output class="ms-2"><?= $ae_adj ?></output> mm
    </div>

    <!-- Slider pasadas -->
    <?php $maxPass = max(1, (int)ceil($thickness / $ae_adj)); ?>
    <div class="mb-4">
      <label class="form-label">Pasadas (1…<?= $maxPass ?>)</label>
      <input type="range" name="passes" class="form-range"
        min="1" max="<?= $maxPass ?>" step="1"
        value="<?= $passes_adj ?>"
        oninput="this.nextElementSibling.value = this.value">
      <output class="ms-2"><?= $passes_adj ?></output> pasadas
    </div>

    <button class="btn btn-primary">Recalcular</button>
  </form>

  <div class="row row-cols-1 row-cols-md-2 g-4">
    <?php
      $cards = [
        ['Diámetro corte','mm',$D],
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
      ];
      foreach ($cards as [$title, $unit, $value]) : ?>
        <div class="col">
          <div class="card">
            <div class="card-body">
              <h6 class="card-title"><?= htmlspecialchars($title,ENT_QUOTES) ?></h6>
              <p class="display-6">
                <?= number_format($value, ($unit==='mm/diente'?4:0)) ?>
                <small><?= htmlspecialchars($unit,ENT_QUOTES) ?></small>
              </p>
            </div>
          </div>
        </div>
    <?php endforeach; ?>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/feather-icons"></script>
<script>feather.replace()</script>
</body>
</html>
