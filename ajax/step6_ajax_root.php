<?php
// File: C:\xampp\htdocs\wizard-stepper_git\ajax\step6_ajax.php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

$input = file_get_contents('php://input');
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'No data received']);
    exit;
}

$data = json_decode($input, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

foreach (['fzCurrent','vcCurrent','passes','params'] as $k) {
    if (!array_key_exists($k, $data)) {
        http_response_code(400);
        echo json_encode(['error' => "Missing field {$k}"]);
        exit;
    }
}

$fz    = (float)$data['fzCurrent'];
$vc    = (float)$data['vcCurrent'];
$passes= max(1,(int)$data['passes']);
$p     = $data['params'];

// Extrae constantes de $p...
$d   = max(1e-6, (float)($p['diameter'] ?? 0));
$Z   = (int)($p['flute_count'] ?? 1);
$rpmMin = (float)($p['rpm_min'] ?? 0);
$rpmMax = (float)($p['rpm_max'] ?? INF);
$frMax  = (float)($p['fr_max'] ?? INF);
$apSlot = (float)($p['ap_slot'] ?? 1);
$aeSlot = (float)($p['ae_slot'] ?? 1);
$Kc11   = (float)($p['Kc11'] ?? 0);
$mc     = (float)($p['mc'] ?? 0);
$coef   = (float)($p['coef_seg'] ?? 1);
$alpha  = (float)($p['alpha'] ?? 0);
$phi    = (float)($p['phi'] ?? 0);
$eta    = max(1e-6, (float)($p['eta'] ?? 0.85));

// realThickness = ap0 * passes0
$realT = ((float)($p['ap0'] ?? 1)) * ((int)($p['passes0'] ?? 1));

// 1) rpmRaw y limitado
$rpmRaw = ($vc*1000)/(M_PI*$d);
$rpm    = min(max($rpmRaw,$rpmMin),$rpmMax);

// 2) feed
$feed = min($rpm*$fz*$Z,$frMax);

// 3) ap
$ap = $realT/$passes;

// 4) mmr
$mmr = ($ap*$feed*$aeSlot)/1000;

// 5) Fc
$Fc = ($Kc11 * pow(max(1e-6,$fz), -$mc) * $ap * $fz * $Z * (1+$coef*tan($alpha))) / cos($phi);

// 6) watts
$watts = ($Fc * $vc)/(60*$eta);

// 7) hp
$hp = $watts/745.7;

// radar
$ru = max(5,100-(( $fz/ (float)$p['fz0'] -1)*40));
$te = max(5,100-($watts/($watts+700))*100);
$po = min(100,($watts/5000)*100);

echo json_encode([
    'rpm'        => round($rpm,0),
    'feed'       => round($feed,0),
    'mmr'        => round($mmr,2),
    'fc'         => round($Fc,1),
    'watts'      => round($watts,0),
    'hp'         => round($hp,2),
    'etaPercent' => round($eta*100,0),
    'radar'      => [round($ru,0),round($te,0),round($po,0)]
], JSON_UNESCAPED_UNICODE);
