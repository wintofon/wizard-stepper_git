<?php
/**
 * Ubicación: public/api/expertResult.php
 *
 * Endpoint JSON para recálculos de parámetros de corte.
 * Recibe (POST JSON):
 *   fz, vc, passes, D, Z, rpmMin, rpmMax, frMax,
 *   thickness, ae, ap_slot, coefSeg, Kc11, mc, alpha, phi, eta, mmrBase
 *
 * Responde JSON con:
 *   { fzFinal, vc, passes, debug }
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../src/Utils/CNCCalculator.php';

use App\Utils\CNCCalculator;

$data = json_decode(file_get_contents('php://input'), true);

try {
    // 1) Leer parámetros del POST JSON
    $fz      = (float)$data['fz'];
    $vc      = (float)$data['vc'];
    $passes  = (int)$data['passes'];
    $D       = (float)$data['D'];
    $Z       = (int)$data['Z'];
    $rpmMin  = (float)$data['rpmMin'];
    $rpmMax  = (float)$data['rpmMax'];
    $frMax   = (float)$data['frMax'];
    $thick   = (float)$data['thickness'];
    $ae      = (float)$data['ae'];
    $ap_slot = (float)$data['ap_slot'];
    $coefSeg = (float)$data['coefSeg'];
    $Kc11    = (float)$data['Kc11'];
    $mc      = (float)$data['mc'];
    $alpha   = (float)$data['alpha'];
    $phi     = (float)$data['phi'];
    $eta     = (float)$data['eta'];
    $mmrBase = (float)$data['mmrBase'];

    // 2) Calcular profundidad de corte real
    $ap = round($thick / $passes, 3);

    // 3) Calcular RPM y feed, con límites
    $rpmCalc = CNCCalculator::rpm($vc, $D);
    $rpm     = round(min(max($rpmCalc, $rpmMin), $rpmMax));
    $feedRaw = CNCCalculator::feed($rpm, $fz, $Z);
    $feed    = min($feedRaw, $frMax);

    // 4) fzFinal
    $fzFinal = $fz;

    // 5) Calcular MMR
    $mmr = round(CNCCalculator::mmr($ap, $feed, $ae), 2);

    // 6) Calcular Fct y potencia
    $Fct = CNCCalculator::Fct($Kc11, $fzFinal, $mc, $ap, $Z, $coefSeg, $alpha, $phi);
    [$W, $HP] = CNCCalculator::potencia($Fct, $vc, $eta);

    // 7) Debug string
    $debug =
      "MMR (varía): {$mmr} mm³/min\n" .
      "fz_final:   " . number_format($fzFinal, 4) . " mm/diente\n" .
      "RPM:        {$rpm}\n" .
      "Feedrate:   " . round($feed) . " mm/min\n" .
      "Fct′:       " . number_format($Fct, 1) . " N\n" .
      "Potencia:   {$W} W ({$HP} HP)";

    // 8) Responder JSON
    echo json_encode([
      'fzFinal' => number_format($fzFinal, 4),
      'vc'      => number_format($vc, 1),
      'passes'  => $passes,
      'debug'   => $debug
    ]);
} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
