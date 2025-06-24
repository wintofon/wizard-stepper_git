<?php
/**
 * File: views/steps/step6.php
 * Descripción: Paso 6 – Resultados embebidos CNC (todo server-side)
 *   Reemplaza AJAX/JS por un formulario POST que recalcula en PHP.
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
    echo '<div class="alert alert-danger">Faltan: '.implode(', ',$miss).'</div>'; exit;
}

// 4) Leer POST o defaults
$fz     = $_POST['fz']     ?? ($_SESSION['fz0']     ?? 0.1);
$vc     = $_POST['vc']     ?? ($_SESSION['vc0']     ?? 100.0);
$ae     = $_POST['ae']     ?? ($_SESSION['ae_slot'] ?? 1.0);
$passes = $_POST['passes'] ?? 1;

// 5) Cargar datos básicos en variables
$tool   = ToolModel::getTool($pdo, $_SESSION['tool_table'], (int)$_SESSION['tool_id']);
$D      = (float)$tool['diameter_mm'];
$Z      = (int)$tool['flute_count'];
$th     = (float)$_SESSION['thickness'];
$frMax  = (float)$_SESSION['feed_max'];
$Kc11   = ConfigModel::getKc11($pdo, (int)$_SESSION['material_id']);
$mc     = ConfigModel::getMc($pdo, (int)$_SESSION['material_id']);
$coef   = ConfigModel::getCoefSeg($pdo, (int)$_SESSION['trans_id']);
$eta    = 0.85;

// 6) Cálculos
$phi  = CNCCalculator::helixAngle($ae, $D);
$hm   = CNCCalculator::chipThickness($fz, $ae, $D);
$rpm  = CNCCalculator::rpm($vc, $D);
$vf   = CNCCalculator::feed($rpm, $fz, $Z);
$ap   = $th/ max(1,$passes);
$mmr  = CNCCalculator::mmr($ap, $vf, $ae);
$Fct  = CNCCalculator::Fct($Kc11, $hm, $mc, $ap, $Z, $coef, 0.0, $phi);
list($watts, $hpOut) = CNCCalculator::potencia($Fct, $vc, $eta);

?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Paso 6 – Resultados CNC</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head><body><main class="container py-4">
<h2>Resultados CNC</h2>
<form method="POST" class="row g-3">
  <div class="col-md-3">
    <label>fz (mm/diente)</label>
    <input type="number" name="fz" step="0.0001" value="<?=htmlspecialchars($fz)?>" class="form-control" required>
  </div>
  <div class="col-md-3">
    <label>Vc (m/min)</label>
    <input type="number" name="vc" step="0.1" value="<?=htmlspecialchars($vc)?>" class="form-control" required>
  </div>
  <div class="col-md-3">
    <label>Ae (mm)</label>
    <input type="number" name="ae" step="0.1" value="<?=htmlspecialchars($ae)?>" class="form-control" required>
  </div>
  <div class="col-md-3">
    <label>Pasadas</label>
    <input type="number" name="passes" min="1" value="<?=htmlspecialchars($passes)?>" class="form-control" required>
  </div>
  <div class="col-12 text-end">
    <button type="submit" class="btn btn-primary">Recalcular</button>
  </div>
</form>
<hr>
<div class="row row-cols-1 row-cols-md-2 g-4">
<?php foreach([
  ['Fz',$fz,'mm/diente','2'],
  ['Vc',$vc,'m/min','1'],
  ['RPM',$rpm,'rpm','0'],
  ['Vf',$vf,'mm/min','0'],
  ['Ae',$ae,'mm','1'],
  ['Ap',$ap,'mm','2'],
  ['hm',$hm,'mm','4'],
  ['MMR',$mmr,'mm³/min','0'],
  ['Fc',$Fct,'N','1'],
  ['Pot (W)',$watts,'W','0'],
  ['Pot (HP)',$hpOut,'HP','2'],
] as [$lbl,$val,$unit,$dec]): ?>
  <div class="col">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title"><?=htmlspecialchars($lbl)?></h6>
        <p class="display-6"><?=number_format((float)$val,(int)$dec)?> <small><?=$unit?></small></p>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
</main></body></html>
