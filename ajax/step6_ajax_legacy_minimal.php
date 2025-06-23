<?php
/**
 * File: step6_ajax_legacy_minimal.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 *
 * Endpoint called by assets/js/step6.js to recalculate cutting parameters.
 *
 * Inputs JSON (POST):
 *   - fz, vc, ae, passes, thickness, D, Z
 *   - params { fr_max, coef_seg, Kc11, mc, alpha, eta, vidaUtil, terminacion, potencia }
 * Output JSON:
 *   { success: bool, data: {...}, error?: string }
 * Requires CSRF token via header X-CSRF-Token matching $_SESSION['csrf_token'].
 */

// Location: C:\xampp\htdocs\wizard-stepper_git\ajax\step6_ajax_legacy_minimal.php
declare(strict_types=1);

// Ensure BASE_URL is set when running under /ajax
if (!getenv('BASE_URL')) {
    $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    putenv('BASE_URL=' . $base);
}

require_once __DIR__ . '/../src/Config/AppConfig.php';

// JSON headers
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Start session for CSRF
session_start();

// 0. CSRF validation
$token = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
$sessionToken = $_SESSION['csrf_token'] ?? null;
if (!$sessionToken || !hash_equals($sessionToken, $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
    exit;
}

// 1. Decode JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// 2. Required fields
foreach (['fz','vc','ae','passes','thickness','D','Z','params'] as $field) {
    if (!array_key_exists($field, $input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Missing parameter: {$field}"]);
        exit;
    }
}

// 3. Map core parameters
$fz        = (float)$input['fz'];
$vc        = (float)$input['vc'];
$ae        = (float)$input['ae'];
$passes    = (int)$input['passes'];
$thickness = (float)$input['thickness'];
$D         = (float)$input['D'];
$Z         = (int)$input['Z'];

// 3b. Auxiliary params
$params   = (array)$input['params'];
$frMax    = (float)($params['fr_max']   ?? PHP_INT_MAX);
$coefSeg  = (float)($params['coef_seg'] ?? 0.0);
$Kc11     = (float)($params['Kc11']     ?? 1.0);
$mc       = (float)($params['mc']       ?? 1.0);
$alpha    = (float)($params['alpha']    ?? 0.0);
$eta      = (float)($params['eta']      ?? 1.0);

// 4. CNC calculations
// 4.1 φ angle and chip thickness hm
$phi = ($D > 0) ? 2 * asin(min(1.0, $ae / $D)) : 0.0;
$hm  = ($phi !== 0.0) ? ($fz * (1 - cos($phi)) / $phi) : $fz;

// 4.2 RPM and feedrate vf
$rpm  = (int)round(($vc * 1000.0) / (M_PI * $D));
$feed = min((int)round($rpm * $fz * $Z), $frMax);

// 4.3 Depth per pass and MMR
$ap  = $thickness / max(1, $passes);
$mmr = round(($ap * $feed * $ae) / 1000.0, 2);

// 4.4 Tangential force Fct
$Fct = $Kc11
     * pow($hm, -$mc)
     * $ap
     * $fz
     * $Z
     * (1 + $coefSeg * tan($alpha));

// 4.5 Power calculations
$kW = ($Fct * $vc) / (60000.0 * $eta);
$W  = (int)round($kW * 1000.0);
$HP = round($kW * 1.341, 2);

// 5. Radar values (overrideable via params)
$vida = min(100, max(0, (int)($params['vidaUtil']    ?? 60)));
$term = min(100, max(0, (int)($params['terminacion'] ?? 40)));
$pot  = min(100, max(0, (int)($params['potencia']    ?? 80)));

// 6. Return JSON
echo json_encode([
    'success' => true,
    'data'    => [
        'fz'         => number_format($fz, 4),
        'vc'         => number_format($vc, 1),
        'hm'         => number_format($hm, 4),
        'n'          => $rpm,
        'vf'         => $feed,
        'hp'         => $HP,
        'watts'      => $W,
        'mmr'        => $mmr,
        'fc'         => round($Fct, 1),
        'ae'         => $ae,
        'ap'         => round($ap, 3),
        'etaPercent' => round($eta * 100),
        'radar'      => [$vida, $term, $pot],
    ],
], JSON_UNESCAPED_UNICODE);
