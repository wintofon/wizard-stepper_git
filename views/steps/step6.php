<?php
/**
 * File: views/steps/auto/step6.php
 * Paso 6 embebido (completo) sin JS ni AJAX.
 * Calcula y muestra resultados CNC server-side.
 */
declare(strict_types=1);

/* 1) Seguridad y flujo */
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

/* 2) Dependencias */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/debug.php';
require_once __DIR__ . '/../../src/Model/ToolModel.php';
require_once __DIR__ . '/../../src/Model/ConfigModel.php';
require_once __DIR__ . '/../../src/Utils/CNCCalculator.php';

// Compatibilidad con pasos previos que usan 'transmission_id'
if (isset($_SESSION['transmission_id']) && !isset($_SESSION['trans_id'])) {
    $_SESSION['trans_id'] = $_SESSION['transmission_id'];
}

/* 3) Validar datos esenciales */
$keys    = ['tool_id','tool_table','material_id','trans_id','thickness','rpm_min','rpm_max','feed_max','hp'];
$missing = array_filter($keys, fn($k) => !isset($_SESSION[$k]));
if ($missing) {
  ?><!DOCTYPE html>
  <html lang="es"><head><meta charset="utf-8"><title>Error en sesión</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  </head><body class="container py-5">
  <div class="alert alert-danger"><h4>Error</h4><p>Faltan datos para el cálculo:</p><ul><?php
  foreach ($missing as $m) echo "<li>".htmlspecialchars($m)."</li>";
  ?></ul><a href="step1.php" class="btn btn-primary mt-3">Volver al inicio</a></div></body></html><?php
  exit;
}

$toolId     = (int)$_SESSION['tool_id'];
$toolTable  = $_SESSION['tool_table'];
$materialId = (int)$_SESSION['material_id'];
$transId    = (int)$_SESSION['trans_id'];
$thickness  = (float)$_SESSION['thickness'];
$rpmMin     = (float)$_SESSION['rpm_min'];
$rpmMax     = (float)$_SESSION['rpm_max'];
$frMax      = (float)$_SESSION['feed_max'];
$hpAvail    = (float)$_SESSION['hp'];

/* 4) Datos herramienta */
$tool = ToolModel::getTool($pdo, $toolTable, $toolId);
if (!$tool) die("Fresa no encontrada");
 $D        = (float)$tool['diameter_mm'];
  $Z        = (int)  $tool['flute_count'];
  $shank    = (float)$tool['shank_diameter_mm'];
  $fluteLen = (float)$tool['flute_length_mm'];
  $cutLen   = (float)$tool['cut_length_mm'];
  $fullLen  = (float)$tool['full_length_mm'];

/* 5) Valores base fijos */
$fz     = 0.1;
$vc     = 150.0;
$ae     = $D * 0.5;
$passes = 1;

/* 6) Datos materiales */
$Kc11    = ConfigModel::getKc11($pdo, $materialId);
$mc      = ConfigModel::getMc($pdo, $materialId);
$coefSeg = ConfigModel::getCoefSeg($pdo, $transId);
$alpha   = 0.0;
$eta     = 0.85;

/* 7) Cálculos */
$phi   = CNCCalculator::helixAngle($ae, $D);
$hm    = CNCCalculator::chipThickness($fz, $ae, $D);
$rpm   = CNCCalculator::rpm($vc, $D);
$vf    = CNCCalculator::feed($rpm, $fz, $Z);
$ap    = $thickness / max(1, $passes);
$mmr   = CNCCalculator::mmr($ap, $vf, $ae);
$Fct   = CNCCalculator::Fct($Kc11, $hm, $mc, $ap, $Z, $coefSeg, $alpha, $phi);
[$watts, $hp] = CNCCalculator::potencia($Fct, $vc, $eta);

?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 6 – Resultados CNC</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
  <link rel="stylesheet" href="/wizard-stepper/assets/css/objects/step-common.css">
</head>
<body>
<main class="container py-4">
  <h2 class="step-title"><i data-feather="activity"></i> Resultados completos CNC</h2>
  <p class="step-desc">Parámetros calculados según tu configuración y datos adicionales.</p>
  <section class="mb-4">
    <p>En este Paso 6 embebido tienes cuatro controles deslizantes (sliders) cuyos valores actúan directamente sobre los cálculos CNC. A continuación se explica para qué sirve cada uno y qué parámetros modifica internamente:</p>
    <ol>
      <li><strong>Vc – Velocidad de corte (m/min)</strong>
        <p>Rango: ± 50&nbsp;% sobre la Vc base calculada (por ejemplo, de –50&nbsp;% a +50&nbsp;% de 150&nbsp;m/min → 75&nbsp;…&nbsp;225&nbsp;m/min).</p>
        <p>Qué hace: ajusta la velocidad de corte real usada para dimensionar:</p>
        <ul>
          <li>RPM del husillo: <code>rpm = (Vc×1000)/(π×D)</code></li>
          <li>Feedrate Vf: <code>Vf = rpm × fz × Z</code> (mm/min)</li>
          <li>Potencia: <code>W = (Fct·Vc)/(60·η)</code></li>
        </ul>
        <p>Efecto: subir Vc aumenta rpm, Vf y potencia; bajarlo hace lo contrario.</p>
      </li>
      <li><strong>fz – Avance por diente (mm/diente)</strong>
        <p>Rango: desde el mínimo (<code>fz_min</code>) hasta el máximo (<code>fz_max</code>) recomendado por la herramienta/material.</p>
        <p>Qué hace: determina cuánto avanza la herramienta por cada filo y revolución, afectando:</p>
        <ul>
          <li>Espesor medio de viruta <code>hm = chipThickness(fz, ae, D)</code></li>
          <li>Feedrate Vf: <code>Vf = rpm × fz × Z</code></li>
          <li>Fuerza de corte tangencial Fct (depende de hm)</li>
          <li>Potencia requerida usando Fct y Vc</li>
        </ul>
        <p>Efecto: fz mayor → mayor Vf y hm, pero también puede disparar Fct y potencia.</p>
      </li>
      <li><strong>ae – Ancho de pasada (mm)</strong>
        <p>Rango: típicamente de 0.1&nbsp;mm hasta el diámetro de la herramienta (D), por defecto el 50&nbsp;% de D.</p>
        <p>Qué hace: controla la sección radial de corte, incidiendo en:</p>
        <ul>
          <li>Ángulo de compromiso φ: <code>φ = helixAngle(ae, D)</code></li>
          <li>Espesor hm: <code>hm = chipThickness(fz, ae, D)</code></li>
          <li>MMR: <code>MMR = ae × Vf × ap</code></li>
          <li>Fct: al modificar hm y φ</li>
        </ul>
        <p>Efecto: ae mayor → viruta más gruesa y más MMR, pero también más carga de corte.</p>
      </li>
      <li><strong>pasadas – Número de pasadas</strong>
        <p>Rango: de 1 hasta un máximo aproximado <code>ceil(thickness/ae)</code>.</p>
        <p>Qué hace: reparte el espesor del material (<code>thickness</code>) en varias capas:</p>
        <ul>
          <li>Profundidad de pasada <code>ap = thickness / pasadas</code></li>
          <li>MMR: <code>MMR = ap × Vf × ae</code></li>
          <li>Fct y potencia: varían porque dependen de ap</li>
        </ul>
        <p>Efecto: más pasadas → ap menor, menor Fct y potencia por pasada, pero más ciclos.</p>
      </li>
    </ol>
    <p>Al mover cualquiera de estos sliders el sistema recalcula φ, hm, rpm, Vf, ap, MMR, Fct y la potencia, mostrando todos esos valores en las fichas de resultados para visualizar el impacto de cada parámetro.</p>
  </section>
  <div class="row row-cols-1 row-cols-md-2 g-4">
    <?php
      $rows = [
      ['D','Diámetro de corte','mm',$D,3],
        ['shank','Diámetro de vástago','mm',$shank,3],
        ['fluteLen','Longitud de filo','mm',$fluteLen,3],
        ['cutLen','Longitud de corte','mm',$cutLen,3],
        ['fullLen','Longitud total','mm',$fullLen,3],
        ['Z','Número de filos','uds',$Z,0],
        // — Cálculos base —
        ['fz','Avance por diente','mm/diente',$fz,4],
        ['vc','Velocidad de corte','m/min',$vc,1],
        ['rpm','Velocidad del husillo','RPM',$rpm,0],
        ['vf','Feedrate','mm/min',$vf,0],
        ['ae','Ancho de pasada','mm',$ae,2],
        ['ap','Profundidad de pasada','mm',$ap,2],
        ['hm','Espesor viruta medio','mm',$hm,4],
        ['mmr','Remoción material','mm³/min',$mmr,0],
        ['Fct','Fuerza de corte','N',$Fct,1],
        ['watts','Potencia requerida','W',$watts,0],
        ['hp','Potencia (HP)','HP',$hp,2],
        // — Datos adicionales —
        ['Kc11','Coef. Kc11','N/mm²',$Kc11,1],
        ['mc','Exponente mc','',''.$mc,2],
        ['coefSeg','Coef. seguridad','',''.$coefSeg,2],
        ['phi','Ángulo compromiso','rad',$phi,3],
        ['rpmMin','RPM mín.','RPM',$rpmMin,0],
        ['rpmMax','RPM máx.','RPM',$rpmMax,0],
        ['frMax','Feedrate máx.','mm/min',$frMax,0],
        ['hpAvail','Potencia disp.','HP',$hpAvail,2],
      ];
      foreach ($rows as [$id, $label, $unit, $val, $dec]) : ?>
        <div class="col">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h6 class="card-title text-muted mb-1"><?= $label ?></h6>
              <div class="display-6 fw-bold text-primary">
                <?= number_format($val, $dec) ?>
                <?php if ($unit): ?><small class="fs-6 text-muted"><?= $unit ?></small><?php endif; ?>
              </div>
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
