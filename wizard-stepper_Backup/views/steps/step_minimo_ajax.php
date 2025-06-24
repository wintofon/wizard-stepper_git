<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['fz'],$data['vc'],$data['passes'])) {
    http_response_code(400);
    echo json_encode(['error'=>'Parámetros inválidos']);
    exit;
}

$fz     = (float)$data['fz'];
$vc     = (float)$data['vc'];
$passes = (int)$data['passes'];
$D      = (float)($data['D'] ?? 10);
$Z      = (int)($data['Z'] ?? 1);
$th    = (float)($data['thickness'] ?? 1);
$ae     = (float)($data['ae'] ?? 1);
$frMax  = (float)($data['frMax'] ?? PHP_INT_MAX);
$coef   = (float)($data['coefSeg'] ?? 0);
$Kc11   = (float)($data['Kc11'] ?? 1);
$mc     = (float)($data['mc'] ?? 1);
$alpha  = (float)($data['alpha'] ?? 0);
$phi    = (float)($data['phi'] ?? 0);
$eta    = (float)($data['eta'] ?? 1);

$rpm   = round(($vc*1000)/(pi()*$D));
$feed  = min(round($rpm*$fz*$Z), $frMax);
$ap    = $th/max(1,$passes);
$mmr   = round(($ap*$feed*$ae)/1000,2);
$Fct   = ($Kc11*pow($fz,-$mc)*$ap*$fz*$Z*(1+$coef*tan($alpha)))
         /($phi===0?1:cos($phi));
$kW    = ($Fct*$vc)/(60000*$eta);
$W     = round($kW*1000);
$HP    = round($kW*1.341,2);

$vida   = min(100,max(0,(int)($data['vidaUtil']    ?? 60)));
$term   = min(100,max(0,(int)($data['terminacion'] ?? 40)));
$pot    = min(100,max(0,(int)($data['potencia']    ?? 80)));

echo json_encode([
  'fz'         => number_format($fz,4),
  'vc'         => number_format($vc,1),
  'n'          => $rpm,
  'vf'         => $feed,
  'hp'         => $HP,
  'watts'      => $W,
  'mmr'        => $mmr,
  'fc'         => round($Fct,1),
  'etaPercent' => round($eta*100),
  'radar'      => [$vida,$term,$pot]
]);
