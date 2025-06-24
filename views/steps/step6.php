<?php
/**
 * File: views/steps/step6.php
 * Descripción: Paso 6 – Resultados embebidos CNC (todo server-side)
 * Reemplaza AJAX/JS por un formulario POST que recalcula en PHP.
 */
declare(strict_types=1);

// 1) Seguridad y flujo
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([ 'cookie_secure'=>true, 'cookie_httponly'=>true, 'cookie_samesite'=>'Strict' ]);
}
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 5) {
    header('Location: step1.php'); exit;
}

// 2) Dependencias
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../src/Model/ToolModel.php';
require_once __DIR__ . '/../../src/Model/ConfigModel.php';
require_once __DIR__ . '/../../src/Utils/CNCCalculator.php';

// 3) Validar sesión keys
$keys = ['tool_table','tool_id','material_id','trans_id','thickness','rpm_min','rpm_max','feed_max','hp'];
$miss = array_filter($keys, fn($k)=> !isset($_SESSION[$k]));
if ($miss) {
    echo '<div class="alert alert-danger container mt-4">Faltan datos en sesión: <strong>'.implode(', ',$miss).'</strong></div>'; exit;
}

// 4) Leer POST o valores base desde controlador
$fz     = isset($_POST['fz'])    ? (float)$_POST['fz']    : (float)($_SESSION['fz0']     ?? 0.1);
$vc     = isset($_POST['vc'])    ? (float)$_POST['vc']    : (float)($_SESSION['vc0']     ?? 100.0);
$ae     = isset($_POST['ae'])    ? (float)$_POST['ae']    : (float)($_SESSION['ae_slot'] ?? 1.0);
$passes = isset($_POST['passes'])? max(1,(int)$_POST['passes']) : 1;

// 5) Cargar datos básicos
go {
    $tool   = ToolModel::getTool($pdo, $_SESSION['tool_table'], (int)$_SESSION['tool_id']);
    $D      = (float)$tool['diameter_mm'];
    $Z      = (int)$tool['flute_count'];
    $th     = (float)$_SESSION['thickness'];
    $frMax  = (float)$_SESSION['feed_max'];
    $rpmMin = (float)$_SESSION['rpm_min'];
    $rpmMax = (float)$_SESSION['rpm_max'];
    $Kc11   = ConfigModel::getKc11($pdo, (int)$_SESSION['material_id']);
    $mc     = ConfigModel::getMc($pdo, (int)$_SESSION['material_id']);
    $coef   = ConfigModel::getCoefSeg($pdo, (int)$_SESSION['trans_id']);
    $eta    = 0.85;
}

// 6) Cálculos CNC
$phi   = CNCCalculator::helixAngle($ae, $D);
$hm    = CNCCalculator::chipThickness($fz, $ae, $D);
$rpm   = CNCCalculator::rpm($vc, $D);
$vf    = CNCCalculator::feed($rpm, $fz, $Z);
$ap    = $th / max(1, $passes);
$mmr   = CNCCalculator::mmr($ap, $vf, $ae);
$Fct   = CNCCalculator::Fct($Kc11, $hm, $mc, $ap, $Z, $coef, 0.0, $phi);
list($watts, $hpOut) = CNCCalculator::potencia($Fct, $vc, $eta);

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Paso 6 – Resultados CNC</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<main class="container py-4">
  <h2 class="mb-4">Resultados CNC</h2>
  <form method="POST" class="row g-3 mb-4">
    <div class="col-md-3">
      <label class="form-label">fz (mm/diente)</label>
      <input type="number" name="fz" step="0.0001" value="<?=htmlspecialchars($fz)?>" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Vc (m/min)</label>
      <input type="number" name="vc" step="0.1" min="<?=htmlspecialchars($rpmMin * M_PI * $D / 1000,1)?>" max="<?=htmlspecialchars($rpmMax * M_PI * $D / 1000,1)?>" value="<?=htmlspecialchars($vc)?>" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Ae (mm)</label>
      <input type="number" name="ae" step="0.1" min="0.1" max="<?=htmlspecialchars($D,1)?>" value="<?=htmlspecialchars($ae)?>" class="form-control" required>
    </div>
    <div class="col-md-2">
      <label class="form-label">Pasadas</label>
      <input type="number" name="passes" min="1" max="<?=ceil($th/$ae)?>" value="<?=htmlspecialchars($passes)?>" class="form-control" required>
    </div>
    <div class="col-md-1 d-grid">
      <button type="submit" class="btn btn-primary mt-4">Recalcular</button>
    </div>
  </form>
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php foreach([
      ['fz',$fz,'mm/diente',4],
      ['Vc',$vc,'m/min',1],
      ['RPM',$rpm,'rpm',0],
      ['Feedrate',$vf,'mm/min',0],
      ['Ae',$ae,'mm',1],
      ['Ap',$ap,'mm',2],
      ['hm',$hm,'mm',4],
      ['MMR',$mmr,'mm³/min',0],
      ['Fc',$Fct,'N',1],
      ['Potencia',$watts,'W',0],
      ['Potencia',$hpOut,'HP',2],
      ['Límite Feed_max',$frMax,'mm/min',0],
      ['RPM Min',$rpmMin,'rpm',0],
      ['RPM Max',$rpmMax,'rpm',0],
    ] as [$lbl,$val,$unit,$dec]): ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <div class="card-body text-center">
            <h6 class="text-muted mb-2"><?=htmlspecialchars($lbl)?></h6>
            <div class="display-6 fw-bold"><?=number_format((float)$val,(int)$dec)?> <small><?=$unit?></small></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</main>
</body>
</html>
