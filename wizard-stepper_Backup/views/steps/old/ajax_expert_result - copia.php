<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Leer JSON entrante
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['fz'],$data['vc'],$data['passes'])) {
    http_response_code(400);
    echo json_encode(['error'=>'Parámetros inválidos']);
    exit;
}

// Extraer parámetros
$fz     = (float)$data['fz'];
$vc     = (float)$data['vc'];
$passes = (int)$data['passes'];

// Variables opcionales para fórmulas
$D       = (float)($data['D']       ?? 10);
$Z       = (int)  ($data['Z']       ?? 1);
$frMax   = (float)($data['frMax']   ?? PHP_INT_MAX);
$thick   = (float)($data['thickness'] ?? 1);
$ae      = (float)($data['ae']      ?? 1);
$coefSeg = (float)($data['coefSeg'] ?? 0);
$Kc11    = (float)($data['Kc11']    ?? 1);
$mc      = (float)($data['mc']      ?? 1);
$alpha   = (float)($data['alpha']   ?? 0);
$phi     = (float)($data['phi']     ?? 0);
$eta     = (float)($data['eta']     ?? 1);

// 1) RPM = (Vc*1000) / (π*D)
$rpmCalc = ($vc*1000.0)/(pi()*$D);
$rpm     = round($rpmCalc);

// 2) Feed = rpm * fz * Z
$feedRaw = $rpm * $fz * $Z;
$feed    = min($feedRaw, $frMax);

// 3) Potencia (simplificada)
$kW = ($vc * ($fz*$Z*$rpm))/(60000.0 * $eta); // aproximación
$W  = round($kW*1000.0);
$HP = round($kW*1.341,2);

// 4) MMR = (ap * feed * ae) / 1000
$ap  = $thick / max(1,$passes);
$mmr = round(($ap * $feed * $ae)/1000.0,2);

// 5) Fct′ = [Kc11·fz^(–mc)·ap·fz·Z·(1+coefSeg·tan(alpha))]/cos(phi)
$numer = $Kc11 * pow($fz, -$mc) * $ap * $fz * $Z * (1 + $coefSeg * tan($alpha));
$Fct   = $phi===0 ? $numer : $numer / cos($phi);

// Datos radar (ejemplo fijo o pueden venir en $data)
$vidaUtil    = min(100,max(0,(int)($data['vidaUtil']    ?? 50)));
$terminacion = min(100,max(0,(int)($data['terminacion'] ?? 50)));
$potencia    = min(100,max(0,(int)($data['potencia']    ?? 50)));

echo json_encode([
    'fz'    => number_format($fz,4),
    'vc'    => number_format($vc,1),
    'n'     => $rpm,
    'vf'    => round($feed),
    'hp'    => $HP,
    'mmr'   => $mmr,
    'fc'    => round($Fct,1),
    'radar' => [$vidaUtil, $terminacion, $potencia]
]);
