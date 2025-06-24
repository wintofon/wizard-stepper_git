<?php
/**
 * File: views/steps/auto/step6.php
 * Paso 6 embebido 100% sin AJAX ni JS externo.
 * Calcula todo en PHP usando valores de sesión y base de datos.
 */
declare(strict_types=1);

// CABECERAS Y SESIÓN SEGURA
// if (!defined('WIZARD_EMBEDDED')) {
//     header('Content-Type: text/html; charset=UTF-8');
//     header('Cache-Control: no-store, no-cache, must-revalidate');
//     session_start([
 //        'cookie_secure'   => true,
  //       'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
  //   ]);
// }

require_once __DIR__ . '/../../includes/db.php';
// require_once __DIR__ . '/../../src/Model/ToolModel.php';
// require_once __DIR__ . '/../../src/Utils/CNCCalculator.php';
// require_once __DIR__ . '/../../src/Model/ConfigModel.php';

// VALIDAR SESIÓN
$keys = ['tool_id', 'tool_table', 'material_id', 'trans_id', 'thickness', 'rpm_min', 'rpm_max', 'fr_max'];
foreach ($keys as $k) if (!isset($_SESSION[$k])) die("Falta ".$k);

$toolId     = (int)$_SESSION['tool_id'];
$toolTable  = $_SESSION['tool_table'];
$materialId = (int)$_SESSION['material_id'];
$transId    = (int)$_SESSION['trans_id'];
$thickness  = (float)$_SESSION['thickness'];
$rpmMin     = (float)$_SESSION['rpm_min'];
$rpmMax     = (float)$_SESSION['rpm_max'];
$frMax      = (float)$_SESSION['fr_max'];

// DATOS HERRAMIENTA
$tool = ToolModel::getTool($pdo, $toolTable, $toolId);
if (!$tool) die("Fresa no encontrada");
$D = (float)$tool['diameter_mm'];
$Z = (int)$tool['flute_count'];

// VALORES BASE
$fz = 0.1;           // mm/diente
$vc = 150.0;         // m/min
$ae = $D * 0.5;      // ancho de pasada
$passes = 1;         // cantidad de pasadas

// DATOS MATERIALES
$Kc11    = ConfigModel::getKc11($pdo, $materialId);
$mc      = ConfigModel::getMc($pdo, $materialId);
$coefSeg = ConfigModel::getCoefSeg($pdo, $transId);
$alpha   = 0.0;       // rad
$eta     = 0.85;      // eficiencia

// CÁLCULOS
$phi = CNCCalculator::helixAngle($ae, $D);
$hm  = CNCCalculator::chipThickness($fz, $ae, $D);
$rpm = CNCCalculator::rpm($vc, $D);
$vf  = CNCCalculator::feed($rpm, $fz, $Z);
$ap  = $thickness / max(1, $passes);
$mmr = CNCCalculator::mmr($ap, $vf, $ae);
$Fct = CNCCalculator::Fct($Kc11, $hm, $mc, $ap, $Z, $coefSeg, $alpha, $phi);
[$watts, $hp] = CNCCalculator::potencia($Fct, $vc, $eta);

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Paso 6 Embebido</title>
  <link rel="stylesheet" href="/wizard-stepper/assets/css/step6.css">
</head>
<body class="step6">
  <div class="container mt-4">
    <h1>Resultados Paso 6 (Server-Side)</h1>
    <ul>
      <li>fz: <?= number_format($fz,4) ?> mm/diente</li>
      <li>vc: <?= number_format($vc,1) ?> m/min</li>
      <li>ae: <?= number_format($ae,2) ?> mm</li>
      <li>ap: <?= number_format($ap,2) ?> mm</li>
      <li>hm: <?= number_format($hm,4) ?> mm</li>
      <li>rpm: <?= round($rpm) ?> rev/min</li>
      <li>feedrate: <?= round($vf) ?> mm/min</li>
      <li>MMR: <?= round($mmr) ?> mm³/min</li>
      <li>Fuerza de corte: <?= round($Fct,1) ?> N</li>
      <li>Potencia: <?= $watts ?> W / <?= $hp ?> HP</li>
    </ul>
  </div>
</body>
</html>
