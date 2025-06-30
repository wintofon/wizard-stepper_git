<?php

function respondError(int $code, string $msg): never {
    http_response_code($code);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    } else {
        echo '<div class="step-error alert alert-danger m-3">' .
             htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') .
             '</div>';
    }
    exit;
}

try {
    session_start();

    // Simple rate limiting: allow max 5 POSTs per minute per session
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_SESSION['step6_requests'])) {
            $_SESSION['step6_requests'] = [];
        }
        $now = time();
        // Remove timestamps older than 60 seconds
        $_SESSION['step6_requests'] = array_filter(
            $_SESSION['step6_requests'],
            static fn(int $ts): bool => ($now - $ts) < 60
        );
        if (count($_SESSION['step6_requests']) >= 5) {
            respondError(429, 'Demasiadas peticiones');
        }
        $_SESSION['step6_requests'][] = $now;
    }

// Basic CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

// Default parameters (could come from previous steps)
$defaults = [
    'diameter'   => 10.0,   // mm
    'flutes'     => 2,
    'rpm_min'    => 1000,
    'rpm_max'    => 24000,
    'feed_max'   => 3000,   // mm/min
    'thickness'  => 5.0,    // mm
    'Kc11'       => 1800.0, // N/mm^2
    'mc'         => 0.25,
    'coef_seg'   => 1.2,
    'rack_rad'   => deg2rad(5.0),
];

// Get inputs or defaults
$fz      = isset($_POST['fz']) ? (float)$_POST['fz'] : 0.05;
$vc      = isset($_POST['vc']) ? (float)$_POST['vc'] : 200.0;
$ae      = isset($_POST['ae']) ? (float)$_POST['ae'] : $defaults['diameter'] / 2;
$passes  = isset($_POST['passes']) ? (int)$_POST['passes'] : 1;
$mcVal   = isset($_POST['mc']) ? (float)$_POST['mc'] : $defaults['mc'];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Token de seguridad inválido.';
    }
    if ($fz <= 0) $errors[] = 'fz debe ser > 0';
    if ($vc <= 0) $errors[] = 'vc debe ser > 0';
    if ($ae <= 0) $errors[] = 'ae debe ser > 0';
}

$result = null;
try {
if (!$errors) {
    $D = $defaults['diameter'];
    $Z = $defaults['flutes'];
    $rpmMin = $defaults['rpm_min'];
    $rpmMax = $defaults['rpm_max'];
    $frMax  = $defaults['feed_max'];
    $coefSeg = $defaults['coef_seg'];
    $Kc11 = $defaults['Kc11'];
    $mc = $mcVal;
    $alpha = $defaults['rack_rad'];
    $eta = 0.85;

    $rpmCalc = ($vc * 1000.0) / (M_PI * $D);
    $rpm = (int)round(max($rpmMin, min($rpmCalc, $rpmMax)));
    $feed = min($rpm * $fz * $Z, $frMax);
    $feedRamp = $Z > 0 ? $feed / $Z : $feed;

    $phi = 2 * asin(min(1.0, $ae / $D));
    $hm  = $phi !== 0.0 ? ($fz * (1 - cos($phi)) / $phi) : $fz;

    $ap  = $defaults['thickness'] / max(1, $passes);
    $mmr = round(($ap * $feed * $ae) / 1000.0, 2);

    $Fct = $Kc11 * pow($hm, -$mc) * $ap * $fz * $Z * (1 + $coefSeg * tan($alpha));
    $kW  = ($Fct * $vc) / (60000.0 * $eta);
    $W   = (int)round($kW * 1000.0);
    $HP  = round($kW * 1.341, 2);

    $result = [
        'rpm'       => $rpm,
        'feed'      => $feed,
        'feed_ramp' => $feedRamp,
        'hp'        => $HP,
        'watts'     => $W,
        'mmr'       => $mmr,
        'hm'        => $hm,
        'ap'        => $ap,
    ];
}
} catch (Throwable $e) {
    $errors[] = 'Error interno: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Paso 6 – Resultados</title>
<style>
body{font-family:Arial,sans-serif;margin:2rem;}
label{display:block;margin-top:1rem;}
input{padding:0.4rem;margin-top:0.2rem;width:100%;}
.btn{padding:0.4rem 0.8rem;margin-top:1rem;display:inline-block;text-decoration:none;border:1px solid #000;background:#eee;color:#000;}
.btn-primary{background:#007bff;border-color:#007bff;color:#fff;}
table{border-collapse:collapse;margin-top:1rem;}
th,td{border:1px solid #ccc;padding:0.4rem;}
th{text-align:left;background:#f0f0f0;}
.error{color:#c00;margin-top:1rem;}
</style>
</head>
<body>
<h1>Paso 6 – Resultados</h1>
<?php if ($errors): ?>
<div class="error">
  <?php foreach ($errors as $e) echo htmlspecialchars($e)."<br>"; ?>
</div>
<?php endif; ?>
<form method="post" id="calcForm">
  <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
  <label>Vc (m/min)
    <input type="range" id="sliderVc" min="50" max="400" step="1" value="<?=htmlspecialchars($vc)?>">
    <span id="valVc"></span>
  </label>
  <label>fz (mm/diente)
    <input type="range" id="sliderFz" min="0.01" max="0.20" step="0.001" value="<?=htmlspecialchars($fz)?>">
    <span id="valFz"></span>
  </label>
  <label>ae (mm)
    <input type="range" id="sliderAe" min="0.1" max="<?=$defaults['diameter']?>" step="0.1" value="<?=htmlspecialchars($ae)?>">
    <span id="valAe"></span>
  </label>
  <label>Pasadas
    <input type="range" id="sliderPasadas" min="1" max="10" step="1" value="<?=$passes?>">
    <span id="valPasadas"></span>
  </label>
  <label>mc
    <input type="range" id="sliderMc" name="mc" min="0.1" max="1" step="0.01" value="<?=htmlspecialchars($mcVal)?>">
    <span id="valMc"></span>
  </label>
  <div>
    <a href="step5.php" class="btn">Anterior</a>
    <button type="submit" class="btn btn-primary">Calcular</button>
  </div>
</form>
<?php if ($result): ?>
<table>
<tr><th>RPM</th><td><span id="outRpm"><?=number_format($result['rpm'])?></span></td></tr>
<tr><th>Feedrate</th><td><span id="outFeed"><?=number_format($result['feed'],1)?></span> mm/min</td></tr>
<tr><th>Feedrate en rampa</th><td><span id="valueRampVf"><?=number_format($result['feed_ramp'],1)?></span> mm/min</td></tr>
<tr><th>Chip thickness</th><td><span id="outHm"><?=number_format($result['hm'],4)?></span> mm</td></tr>
<tr><th>ap</th><td><span id="outAp"><?=number_format($result['ap'],3)?></span> mm</td></tr>
<tr><th>Potencia</th><td><span id="outHp"><?=number_format($result['hp'],2)?></span> HP (<span id="outWatts"><?=number_format($result['watts'])?></span> W)</td></tr>
<tr><th>MMR</th><td><span id="outMmr"><?=number_format($result['mmr'],2)?></span> mm³/min</td></tr>
</table>
<?php endif; ?>
<script>
const D = <?= $defaults['diameter'] ?>;
const Z = <?= $defaults['flutes'] ?>;
const rpmMin = <?= $defaults['rpm_min'] ?>;
const rpmMax = <?= $defaults['rpm_max'] ?>;
const feedMax = <?= $defaults['feed_max'] ?>;
const thickness = <?= $defaults['thickness'] ?>;
const Kc11 = <?= $defaults['Kc11'] ?>;
const coefSeg = <?= $defaults['coef_seg'] ?>;
const alpha = <?= $defaults['rack_rad'] ?>;
const eta = 0.85;

const vcInput = document.getElementById('sliderVc');
const fzInput = document.getElementById('sliderFz');
const aeInput = document.getElementById('sliderAe');
const pInput  = document.getElementById('sliderPasadas');
const mcInput = document.getElementById('sliderMc');
const valVc = document.getElementById('valVc');
const valFz = document.getElementById('valFz');
const valAe = document.getElementById('valAe');
const valP  = document.getElementById('valPasadas');
const valMc = document.getElementById('valMc');

function showVals(){
  try {
    valVc.textContent = vcInput.value;
    valFz.textContent = fzInput.value;
    valAe.textContent = aeInput.value;
    valP.textContent  = pInput.value;
    valMc.textContent = mcInput.value;
  } catch (e) {
    console.error('showVals error', e);
  }
}

function recalc(){
  try {
    const vc = parseFloat(vcInput.value);
    const fz = parseFloat(fzInput.value);
    const ae = parseFloat(aeInput.value);
    const passes = parseInt(pInput.value,10);
    const mcVal = parseFloat(mcInput.value);

    const rpmCalc = (vc * 1000) / (Math.PI * D);
    const rpm = Math.max(rpmMin, Math.min(rpmCalc, rpmMax));
    const feed = Math.min(rpm * fz * Z, feedMax);
    const rampFeed = Z > 0 ? feed / Z : feed;
    const phi = 2 * Math.asin(Math.min(1, ae / D));
    const hm = phi !== 0 ? (fz * (1 - Math.cos(phi)) / phi) : fz;
    const ap = thickness / Math.max(1, passes);
    const mmr = (ap * feed * ae) / 1000;
    const Fct = Kc11 * Math.pow(hm, -mcVal) * ap * fz * Z * (1 + coefSeg * Math.tan(alpha));
    const kW = (Fct * vc) / (60000 * eta);
    const watts = Math.round(kW * 1000);
    const hp = (kW * 1.341).toFixed(2);

    document.getElementById('outRpm').textContent = Math.round(rpm);
    document.getElementById('outFeed').textContent = feed.toFixed(1);
    document.getElementById('valueRampVf').textContent = rampFeed.toFixed(1);
    document.getElementById('outHm').textContent = hm.toFixed(4);
    document.getElementById('outAp').textContent = ap.toFixed(3);
    document.getElementById('outMmr').textContent = mmr.toFixed(2);
    document.getElementById('outHp').textContent = hp;
    document.getElementById('outWatts').textContent = watts;
  } catch (e) {
    console.error('recalc error', e);
  }
}

[vcInput, fzInput, aeInput, pInput, mcInput].forEach(el => {
  el.addEventListener('input', () => {
    try {
      showVals();
      recalc();
    } catch (e) {
      console.error('input handler error', e);
    }
  });
});

try {
  showVals();
  recalc();
} catch (e) {
  console.error('init error', e);
}

window.addEventListener('error', e => {
  console.error('unhandled error', e.error || e.message);
});
</script>
<script 
  type="module" 
  defer 
  src="assets/js/step6.js"
  onload="console.info('[step6] module loaded 👍'); window.step6?.init?.();"
  onerror="console.error('❌ step6.js failed to load');">
</script>
</body>
</html>
<?php
} catch (Throwable $e) {
    respondError(500, 'Error inesperado');
} finally {
    // cleanup opcional
}
?>
