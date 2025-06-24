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

  <div class="notes-card">
    <p>En este Paso 6 embebido tienes cuatro controles deslizantes (sliders)
    cuyos valores actúan directamente sobre los cálculos CNC. A continuación se
    detalla para qué sirve cada uno y qué parámetros modifica internamente:</p>

    <ol class="mb-3">
      <li><strong>Vc – Velocidad de corte (m/min)</strong><br>
      <em>Rango:</em> ± 50&nbsp;% sobre la Vc base calculada.<br>
      <em>Qué hace:</em> ajusta la velocidad usada para dimensionar:
        <ul>
          <li>RPM del husillo: rpm&nbsp;=&nbsp;(Vc×1000)/(π×D).</li>
          <li>Feedrate Vf: Vf&nbsp;=&nbsp;rpm × fz × Z.</li>
          <li>Potencia: W&nbsp;=&nbsp;(Fct·Vc)/(60·η).</li>
        </ul>
      Efecto: subir Vc aumenta rpm, Vf y potencia; bajarlo hace lo contrario.</li>

      <li class="mt-2"><strong>fz – Avance por diente (mm/diente)</strong><br>
      <em>Rango:</em> desde el mínimo al máximo recomendado.<br>
      <em>Qué hace:</em> determina el avance por filo, afectando:
        <ul>
          <li>Espesor medio de viruta hm&nbsp;=&nbsp;chipThickness(fz, ae, D).</li>
          <li>Feedrate Vf&nbsp;=&nbsp;rpm × fz × Z.</li>
          <li>Fuerza de corte tangencial Fct.</li>
          <li>Potencia requerida.</li>
        </ul>
      Efecto: fz mayor implica más Vf y hm, pero también mayor Fct y potencia.</li>

      <li class="mt-2"><strong>ae – Ancho de pasada (mm)</strong><br>
      <em>Rango:</em> típicamente de 0.1&nbsp;mm hasta el diámetro de la herramienta.<br>
      <em>Qué hace:</em> controla la sección radial de corte:
        <ul>
          <li>Ángulo de compromiso φ&nbsp;=&nbsp;helixAngle(ae, D).</li>
          <li>Espesor hm&nbsp;=&nbsp;chipThickness(fz, ae, D).</li>
          <li>MMR&nbsp;=&nbsp;ae × Vf × ap.</li>
          <li>Fct, al modificar hm y φ.</li>
        </ul>
      Efecto: mayor ae produce viruta más gruesa y carga de corte superior.</li>

      <li class="mt-2"><strong>pasadas – Número de pasadas</strong><br>
      <em>Rango:</em> de 1 hasta un máximo calculado.<br>
      <em>Qué hace:</em> reparte el espesor del material en varias capas:
        <ul>
          <li>Profundidad de pasada ap&nbsp;=&nbsp;thickness / pasadas.</li>
          <li>MMR&nbsp;=&nbsp;ap × Vf × ae.</li>
          <li>Fct y potencia varían porque dependen de ap.</li>
        </ul>
      Efecto: más pasadas reducen la carga por pasada pero aumentan los ciclos.</li>
    </ol>

    <p>Al modificar cualquiera de estos valores el sistema recalcula φ, hm, rpm,
    Vf, ap, MMR, Fct y la potencia requerida, mostrando los resultados al
    instante para que observes cómo cada parámetro impacta el fresado CNC.</p>
  </div>

  <form id="sliders" class="my-4">
    <div class="row g-4">
      <div class="col-md-6">
        <label for="sliderVc" class="form-label">Vc (m/min)</label>
        <input type="range" class="form-range" id="sliderVc"
               min="<?= $vc * 0.5 ?>" max="<?= $vc * 1.5 ?>" step="0.1" value="<?= $vc ?>">
        <div class="text-end"><strong id="valVc"><?= number_format($vc,1) ?></strong> m/min</div>
      </div>
      <div class="col-md-6">
        <label for="sliderFz" class="form-label">fz (mm/diente)</label>
        <input type="range" class="form-range" id="sliderFz"
               min="<?= $fz * 0.5 ?>" max="<?= $fz * 1.5 ?>" step="0.001" value="<?= $fz ?>">
        <div class="text-end"><strong id="valFz"><?= number_format($fz,3) ?></strong> mm</div>
      </div>
      <div class="col-md-6">
        <label for="sliderAe" class="form-label">ae (mm)</label>
        <input type="range" class="form-range" id="sliderAe"
               min="0.1" max="<?= $D ?>" step="0.1" value="<?= $ae ?>">
        <div class="text-end"><strong id="valAe"><?= number_format($ae,1) ?></strong> mm</div>
      </div>
      <div class="col-md-6">
        <label for="sliderP" class="form-label">Pasadas</label>
        <input type="range" class="form-range" id="sliderP"
               min="1" max="10" step="1" value="<?= $passes ?>">
        <div class="text-end"><strong id="valP"><?= $passes ?></strong></div>
      </div>
    </div>
  </form>
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
                <span id="out<?= $id ?>"><?= number_format($val, $dec) ?></span>
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
<script>
(function(){
  const D = <?= $D ?>;
  const Z = <?= $Z ?>;
  const thickness = <?= $thickness ?>;
  const Kc11 = <?= $Kc11 ?>;
  const mc = <?= $mc ?>;
  const coefSeg = <?= $coefSeg ?>;
  const alpha = <?= $alpha ?>;
  const eta = <?= $eta ?>;
  const sliders = {
    vc: document.getElementById('sliderVc'),
    fz: document.getElementById('sliderFz'),
    ae: document.getElementById('sliderAe'),
    p:  document.getElementById('sliderP')
  };
  const labels = {
    vc: document.getElementById('valVc'),
    fz: document.getElementById('valFz'),
    ae: document.getElementById('valAe'),
    p:  document.getElementById('valP')
  };
  const out = {
    vc:    document.getElementById('outvc'),
    fz:    document.getElementById('outfz'),
    rpm:   document.getElementById('outrpm'),
    vf:    document.getElementById('outvf'),
    ae:    document.getElementById('outae'),
    ap:    document.getElementById('outap'),
    hm:    document.getElementById('outhm'),
    mmr:   document.getElementById('outmmr'),
    Fct:   document.getElementById('outFct'),
    watts: document.getElementById('outwatts'),
    hp:    document.getElementById('outhp')
  };

  function helixAngle(ae,D){
    if(D<=0) return 0;
    const ratio = Math.min(1, ae/D);
    return 2*Math.asin(ratio);
  }
  function chipThickness(fz,ae,D){
    const phi = helixAngle(ae,D);
    if(phi===0) return fz;
    if(Math.abs(phi)<1e-3) return D>0 ? fz*(ae/D) : fz;
    return (fz*(1-Math.cos(phi)))/phi;
  }
  const rpmFn = (vc,D)=> (vc*1000)/(Math.PI*D);
  const feed = (rpm,fz,Z)=> rpm*fz*Z;
  const mmr = (ap,feed,ae)=> ap*feed*ae;
  function FctCalc(Kc11,hm,mc,ap,Z,coefSeg,alpha,phi){
    if(hm<=0||ap<=0||Z<=0) return 0;
    const force = Kc11*Math.pow(hm,-mc)*ap*hm*Z*(1+coefSeg*Math.tan(alpha));
    return Math.cos(phi)!==0? force/Math.cos(phi): force;
  }
  function potencia(Fct,vc,eta){
    const W = (Fct*vc)/(60*eta);
    const kW = W/1000;
    const HP = kW*1.341;
    return {W,HP};
  }

  function recalc(){
    const vc = parseFloat(sliders.vc.value);
    const fz = parseFloat(sliders.fz.value);
    const ae = parseFloat(sliders.ae.value);
    const p  = parseInt(sliders.p.value,10)||1;

    labels.vc.textContent = vc.toFixed(1);
    labels.fz.textContent = fz.toFixed(3);
    labels.ae.textContent = ae.toFixed(1);
    labels.p.textContent  = p;

    const phi  = helixAngle(ae,D);
    const hm   = chipThickness(fz,ae,D);
    const rpmV = rpmFn(vc,D);
    const vf   = feed(rpmV,fz,Z);
    const ap   = thickness/p;
    const mmrV = mmr(ap,vf,ae);
    const FctV = FctCalc(Kc11,hm,mc,ap,Z,coefSeg,alpha,phi);
    const pow  = potencia(FctV,vc,eta);

    out.vc.textContent    = vc.toFixed(1);
    out.fz.textContent    = fz.toFixed(3);
    out.rpm.textContent   = rpmV.toFixed(0);
    out.vf.textContent    = vf.toFixed(0);
    out.ae.textContent    = ae.toFixed(1);
    out.ap.textContent    = ap.toFixed(2);
    out.hm.textContent    = hm.toFixed(4);
    out.mmr.textContent   = mmrV.toFixed(0);
    out.Fct.textContent   = FctV.toFixed(1);
    out.watts.textContent = pow.W.toFixed(0);
    out.hp.textContent    = pow.HP.toFixed(2);
  }

  ['vc','fz','ae','p'].forEach(k=>sliders[k].addEventListener('input',recalc));
  recalc();
})();
</script>
</body>
</html>
