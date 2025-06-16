<?php
// File: C:\xampp\htdocs\wizard-stepper\ajax\step6_ajax.php

declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
file_put_contents('debug.log', file_get_contents('php://input'));

// 1) Leer y validar payload principal
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

// 2) Validar constantes en 'params'
$p = $data['params'];
$requiredP = [
    'diameter','flute_count','Z','rpm_min','rpm_max','fr_max',
    'ap0','passes0','ap_slot','ae_slot',
    'Kc11','mc','coef_seg','alpha','phi','eta','fz0'
];
foreach ($requiredP as $k) {
    if (!array_key_exists($k, $p)) {
        http_response_code(400);
        echo json_encode(['error' => "Missing param {$k}"]);
        exit;
    }
}

// 3) Extraer valores
$fz     = (float)$data['fzCurrent'];
$vc     = (float)$data['vcCurrent'];
$passes = max(1, (int)$data['passes']);

// Constantes
$d       = (float)$p['diameter'];
// ahora buscamos flute_count o Z
$Z       = (int)$p['flute_count'];
$rpmMin  = (float)$p['rpm_min'];
$rpmMax  = (float)$p['rpm_max'];
$frMax   = (float)$p['fr_max'];
$ap0     = (float)$p['ap0'];
$passes0 = (int)$p['passes0'];
$ae      = (float)$p['ae_slot'];
$Kc11    = (float)$p['Kc11'];
$mc      = (float)$p['mc'];
$coef    = (float)$p['coef_seg'];
$alpha   = (float)$p['alpha'];
$phi     = (float)$p['phi'];
$eta     = (float)$p['eta'];

// 4) Espesor real y ap
$realT = $ap0 * $passes0;
$ap    = $realT / $passes;

// 5) RPM limitado
$rpmRaw = ($vc * 1000) / (M_PI * $d);
$rpm    = min(max($rpmRaw, $rpmMin), $rpmMax);

// 6) Debugeo Z
file_put_contents('debug.log', "Computed Z={$Z}\n", FILE_APPEND);

// 7) Avance (feedrate) correcto
$feedRaw = $rpm * $fz * $Z;
$feed    = min($feedRaw, $frMax);

// 8) MMR
$mmr = ($ap * $feed * $ae) / 1000;

// 9) Fuerza de corte
$Fc = ($Kc11
     * pow(max(1e-6, $fz), -$mc)
     * $ap
     * $fz
     * $Z
     * (1 + $coef * tan($alpha)))
     / cos($phi);

// 10) Potencia
$vc_ms = $vc / 60;
$watts = ($Fc * $vc_ms) / $eta;
$hp    = $watts / 745.7;

// 11) Radar (opcional)
$ru = max(5, 100 - (($fz / (float)$p['fz0'] - 1) * 40));
$te = max(5, 100 - ($watts / ($watts + 700)) * 100);
$po = min(100, ($watts / 5000) * 100);

// 12) Respuesta JSON
echo json_encode([
    'rpm'        => round($rpm, 0),
    'feed'       => round($feed, 0),
    'mmr'        => round($mmr, 2),
    'fc'         => round($Fc, 1),
    'watts'      => round($watts, 0),
    'hp'         => round($hp, 2),
    'etaPercent' => round($eta * 100, 0),
    'radar'      => [round($ru, 0), round($te, 0), round($po, 0)]
], JSON_UNESCAPED_UNICODE);
