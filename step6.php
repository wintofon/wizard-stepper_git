<?php
session_start();

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
$fz = isset($_POST['fz']) ? (float)$_POST['fz'] : 0.05;
$vc = isset($_POST['vc']) ? (float)$_POST['vc'] : 200.0;
$ae = isset($_POST['ae']) ? (float)$_POST['ae'] : $defaults['diameter'] / 2;

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
    $D = $defaults['diameter'];
    $Z = $defaults['flutes'];
    $rpmMin = $defaults['rpm_min'];
    $rpmMax = $defaults['rpm_max'];
    $frMax  = $defaults['feed_max'];
    $coefSeg = $defaults['coef_seg'];
    $Kc11 = $defaults['Kc11'];
    $mc = $defaults['mc'];
    $alpha = $defaults['rack_rad'];
    $eta = 0.85;

    $rpmCalc = ($vc * 1000.0) / (M_PI * $D);
    $rpm = (int)round(max($rpmMin, min($rpmCalc, $rpmMax)));
    $feed = min($rpm * $fz * $Z, $frMax);

    $phi = 2 * asin(min(1.0, $ae / $D));
    $hm  = $phi !== 0.0 ? ($fz * (1 - cos($phi)) / $phi) : $fz;

    $ap  = $defaults['thickness'];
    $mmr = round(($ap * $feed * $ae) / 1000.0, 2);

    $Fct = $Kc11 * pow($hm, -$mc) * $ap * $fz * $Z * (1 + $coefSeg * tan($alpha));
    $kW  = ($Fct * $vc) / (60000.0 * $eta);
    $W   = (int)round($kW * 1000.0);
    $HP  = round($kW * 1.341, 2);

    $result = [
        'rpm'  => $rpm,
        'feed' => $feed,
        'hp'   => $HP,
        'watts'=> $W,
        'mmr'  => $mmr,
    ];
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
<form method="post">
  <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
  <label>fz (mm/diente)
    <input type="number" step="0.0001" name="fz" value="<?=htmlspecialchars($fz)?>" required>
  </label>
  <label>vc (m/min)
    <input type="number" step="0.1" name="vc" value="<?=htmlspecialchars($vc)?>" required>
  </label>
  <label>ae (mm)
    <input type="number" step="0.1" name="ae" value="<?=htmlspecialchars($ae)?>" required>
  </label>
  <div>
    <a href="step5.php" class="btn">Anterior</a>
    <?php if ($result): ?>
      <a href="step7.php" class="btn btn-primary">Siguiente</a>
    <?php else: ?>
      <button type="submit" class="btn btn-primary">Calcular</button>
    <?php endif; ?>
  </div>
</form>
<?php if ($result): ?>
<table>
<tr><th>RPM</th><td><?=number_format($result['rpm'])?></td></tr>
<tr><th>Feedrate</th><td><?=number_format($result['feed'],1)?> mm/min</td></tr>
<tr><th>Potencia</th><td><?=number_format($result['hp'],2)?> HP (<?=number_format($result['watts'])?> W)</td></tr>
<tr><th>MMR</th><td><?=number_format($result['mmr'],2)?> mm³/min</td></tr>
</table>
<?php endif; ?>
</body>
</html>
