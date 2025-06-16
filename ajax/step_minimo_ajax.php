<?php
/**
 * Ubicación: C:\xampp\htdocs\wizard-stepper_git\ajax\step_minimo_ajax.php
 *
 * Endpoint AJAX legacy (paso 6). Ahora:
 * - Valida CSRF vía header X-CSRF-Token
 * - Toma todos los parámetros desde el JSON (no usa defaults internos)
 * - Responde formato { success, data, error }
 */

declare(strict_types=1);

// Cabeceras JSON
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Iniciar sesión para CSRF
session_start();

// 0. CSRF: validar token enviado en header X-CSRF-Token
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfHeader)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

// 1. Leer entrada JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'JSON inválido']);
    exit;
}

// 2. Campos obligatorios
foreach (['fz','vc','ae','passes','thickness','D','Z','params'] as $f) {
    if (!array_key_exists($f, $input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Falta parámetro: $f"]);
        exit;
    }
}

// 3. Mapear parámetros
$fz        = (float)$input['fz'];
$vc        = (float)$input['vc'];
$ae        = (float)$input['ae'];
$passes    = (int)  $input['passes'];
$thickness = (float)$input['thickness'];
$D         = (float)$input['D'];
$Z         = (int)  $input['Z'];

// 3b. Parametros auxiliares dentro de params
$params  = (array)$input['params'];
$frMax   = (float)($params['fr_max']   ?? PHP_INT_MAX);
$coefSeg = (float)($params['coef_seg'] ?? 0.0);
$Kc11    = (float)($params['Kc11']     ?? 1.0);
$mc      = (float)($params['mc']       ?? 1.0);
$alpha   = (float)($params['alpha']    ?? 0.0);
$eta     = (float)($params['eta']      ?? 1.0);

// 4. Cálculos CNC
// 4.1 Ángulo φ y espesor hm
$phi = 2 * asin(min(1.0, $ae / $D));
$hm  = ($phi !== 0.0)
     ? ($fz * (1 - cos($phi)) / $phi)
     : $fz;

// 4.2 RPM y avance Vf
$rpm  = (int) round(($vc * 1000.0) / (M_PI * $D));
$feed = min((int) round($rpm * $fz * $Z), $frMax);

// 4.3 Profundidad de pasada y MMR
$ap  = $thickness / max(1, $passes);
$mmr = round(($ap * $feed * $ae) / 1000.0, 2);

// 4.4 Fuerza tangencial Fct
$Fct = $Kc11
     * pow($hm, -$mc)
     * $ap
     * $fz
     * $Z
     * (1 + $coefSeg * tan($alpha));

// 4.5 Potencia
$kW = ($Fct * $vc) / (60000.0 * $eta);
$W  = (int) round($kW * 1000.0);
$HP = round($kW * 1.341, 2);

// 5. Radar de ejemplo (o parámetros en params)
$vida = min(100, max(0, (int)($params['vidaUtil']    ?? 60)));
$term = min(100, max(0, (int)($params['terminacion'] ?? 40)));
$pot  = min(100, max(0, (int)($params['potencia']    ?? 80)));

// 6. Respuesta
echo json_encode([
    'success' => true,
    'data'    => [
        'fz'          => number_format($fz,4),
        'vc'          => number_format($vc,1),
        'hm'          => number_format($hm,4),
        'n'           => $rpm,
        'vf'          => $feed,
        'hp'          => $HP,
        'watts'       => $W,
        'mmr'         => $mmr,
        'fc'          => round($Fct,1),

        // ← Aquí añadimos ae y ap
        'ae'          => $ae,
        'ap'          => round($ap,3),

        'etaPercent'  => round($eta * 100),
        'radar'       => [$vida, $term, $pot],
    ],
], JSON_UNESCAPED_UNICODE);
