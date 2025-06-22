<?php
/**
 * File: paso_minimo_ajax.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 *
 * Called by: legacy demo page for minimum pass calculations
 * Important JSON fields: fz, vc, passes, D, Z, thickness, ae, frMax, etc.
 * Writes no session data
 * @TODO Extend documentation.
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$data = json_decode(file_get_contents('php://input'), true); // parameters from JS
if (!isset($data['fz'],$data['vc'],$data['passes'])) {
    http_response_code(400);
    echo json_encode(['error'=>'Parámetros inválidos']);
    exit;
}

$fz     = (float)$data['fz'];
$vc     = (float)$data['vc'];
$passes = (int)$data['passes'];

// Parámetros de ejemplo
$D       = (float)($data['D'] ?? 10);
$Z       = (int)($data['Z'] ?? 1);
$thick   = (float)($data['thickness'] ?? 1);
$ae      = (float)($data['ae'] ?? 1);
$frMax   = (float)($data['frMax'] ?? PHP_INT_MAX);
$coefSeg = (float)($data['coefSeg'] ?? 0);
$Kc11    = (float)($data['Kc11'] ?? 1);
$mc      = (float)($data['mc'] ?? 1);
$alpha   = (float)($data['alpha'] ?? 0);
$phi     = (float)($data['phi'] ?? 0);
$eta     = (float)($data['eta'] ?? 1);

// Cálculos inline (igual CNCCalculator)
$rpm     = round( ($vc*1000)/(pi()*$D) );
$feed    = min(round($rpm * $fz * $Z), $frMax);
$ap      = $thick / max(1,$passes);
$mmr     = round(($ap * $feed * $ae)/1000,2);
$Fct     = ( $Kc11 * pow($fz,-$mc) * $ap * $fz * $Z * (1 + $coefSeg*tan($alpha)) )
           / ($phi===0?1:cos($phi));
$kW      = ($Fct * $vc)/(60000 * $eta);
$W       = round($kW*1000);
$HP      = round($kW*1.341,2);

// Radar de prueba
$vida    = min(100,max(0,(int)($data['vidaUtil']    ?? 60)));
$term    = min(100,max(0,(int)($data['terminacion'] ?? 40)));
$pot     = min(100,max(0,(int)($data['potencia']    ?? 80)));

echo json_encode([
    'fz'    => number_format($fz,4),
    'vc'    => number_format($vc,1),
    'n'     => $rpm,
    'vf'    => $feed,
    'hp'    => $HP,
    'mmr'   => $mmr,
    'fc'    => round($Fct,1),
    'radar' => [$vida, $term, $pot]
]);
