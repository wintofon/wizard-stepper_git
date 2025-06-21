<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../src/Utils/CNCCalculator.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    $fz      = (float)$data['fz'];
    $vc      = (float)$data['vc'];
    $passes  = 1; // fijo
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

    $ap = round($thick / $passes, 3);

    $rpmCalc = CNCCalculator::rpm($vc, $D);
    $rpm = round(min(max($rpmCalc, $rpmMin), $rpmMax));
    $feedRaw = CNCCalculator::feed($rpm, $fz, $Z);
    $feed = min($feedRaw, $frMax);

    $fzFinal = $fz;
    $mmr = round(CNCCalculator::mmr($ap, $feed, $ae), 2);
    $Fct = CNCCalculator::Fct($Kc11, $fzFinal, $mc, $ap, $Z, $coefSeg, $alpha, $phi);
    [$W, $HP] = CNCCalculator::potencia($Fct, $vc, $eta);

    $debug =
      "MMR (varía): {$mmr} mm³/min\n" .
      "fz_final:   " . number_format($fzFinal, 4) . " mm/diente\n" .
      "RPM:        {$rpm}\n" .
      "Feedrate:   " . round($feed) . " mm/min\n" .
      "Fct′:       " . number_format($Fct, 1) . " N\n" .
      "Potencia:   {$W} W ({$HP} HP)";

    echo json_encode([
      'fzFinal' => number_format($fzFinal, 4),
      'vc'      => number_format($vc, 1),
      'passes'  => $passes,
      'debug'   => $debug
    ]);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
