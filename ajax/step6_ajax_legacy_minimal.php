<?php
/**
 *  Paso 6 – AJAX • Re-cálculo de parámetros
 *  -------------------------------------------------------------------------
 *  ▸ Endpoint exclusivo para peticiones `fetch()` en assets/js/step6.js
 *  ▸ Entrada  : JSON            (ver $requiredKeys)
 *  ▸ Salida   : { success, data, error? }
 *  ▸ Seguridad:  • Sólo XMLHttpRequest
 *                • Token CSRF por cabecera  X-CSRF-Token
 *                • Política  no-cache
 *  ▸ Ubicación: /ajax/step6_ajax_legacy_minimal.php
 *  -------------------------------------------------------------------------
 */

declare(strict_types=1);

/* ───────────────────────────────── 0. Debug opcional ──────────────────── */
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
error_reporting($DEBUG ? E_ALL : 0);
ini_set('display_errors', $DEBUG ? '1' : '0');

/* ─────────────────────────────── 1. Cabeceras base ────────────────────── */
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/* ─────────────────── 2. Cargar config + sesión segura ─────────────────── */
if (!getenv('BASE_URL')) {
    // Permite que funcione aunque el script se llame desde /ajax
    $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    putenv('BASE_URL=' . $base);
}
require_once __DIR__ . '/../src/Config/AppConfig.php';

session_start();

/* ───────────────────────────── 3. Helper genérico ─────────────────────── */
function json_error(string $msg, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ───────────────── 4. Validar origen (debe ser XMLHttpRequest) ────────── */
if (
    empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest'
) {
    json_error('Invalid request (XHR required)', 400);
}

/* ───────────────────────── 5. Validar / refrescar CSRF ────────────────── */
$headerToken  = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
$sessionToken = $_SESSION['csrf_token'] ?? null;

if ($sessionToken === null || !hash_equals($sessionToken, $headerToken)) {
    json_error('CSRF fail', 403);
}

/* ─────────────────────── 6. Leer cuerpo JSON seguro ───────────────────── */
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    json_error('JSON inválido');
}

/* ─────────────────────── 7. Campos obligatorios ──────────────────────── */
$requiredKeys = ['fz','vc','ae','passes','thickness','D','Z','params'];
foreach ($requiredKeys as $k) {
    if (!array_key_exists($k, $input)) {
        json_error("Falta parámetro: {$k}");
    }
}

/* ─────────────────────── 8. Sanitizar / castear ───────────────────────── */
$fz        = (float) $input['fz'];
$vc        = (float) $input['vc'];
$ae        = (float) $input['ae'];
$passes    = (int)   $input['passes'];
$thickness = (float) $input['thickness'];
$D         = (float) $input['D'];          // diámetro herramienta
$Z         = (int)   $input['Z'];          // Nº filos

$params    = (array) ($input['params'] ?? []);

$frMax   = (float)($params['fr_max']   ?? INF);
$coefSeg = (float)($params['coef_seg'] ?? 0.0);
$Kc11    = (float)($params['Kc11']     ?? 1.0);
$mc      = (float)($params['mc']       ?? 1.0);
$alpha   = (float)($params['alpha']    ?? 0.0);
$eta     = (float)($params['eta']      ?? 1.0);

/* ───────────────────────────── 9. Cálculos CNC ────────────────────────── */
/** 9.1  Espesor medio hm (hon & DeVries) */
$phi = 2 * asin(min(1.0, $ae / $D));
$hm  = ($phi !== 0.0) ? ($fz * (1 - cos($phi)) / $phi) : $fz;

/** 9.2  RPM y avance */
$rpm  = (int) round(($vc * 1000) / (M_PI * $D));
$feed = min((int) round($rpm * $fz * $Z), $frMax);

/** 9.3  Profundidad de pasada (ap) y Material Removal Rate */
$ap  = $thickness / max(1, $passes);
$mmr = round(($ap * $feed * $ae) / 1000, 2);   // cm³/min → cc/min

/** 9.4  Fuerza Fct (Kienzle) */
$Fct = $Kc11 * pow($hm, -$mc) * $ap * $fz * $Z
     * (1 + $coefSeg * tan($alpha));

/** 9.5  Potencia */
$kW  = ($Fct * $vc) / (60_000 * $eta);
$W   = (int) round($kW * 1000);
$HP  = round($kW * 1.341, 2);

/* ─────────────────────────── 10. Dataset Radar ───────────────────────── */
$vida = min(100, max(0, (int)($params['vidaUtil']    ?? 60)));
$term = min(100, max(0, (int)($params['terminacion'] ?? 40)));
$pot  = min(100, max(0, (int)($params['potencia']    ?? 80)));

/* ─────────────────────────── 11. Respuesta JSON ──────────────────────── */
$data = [
    'fz'   => number_format($fz,4),
    'vc'   => number_format($vc,1),
    'hm'   => number_format($hm,4),
    'n'    => $rpm,
    'vf'   => $feed,
    'hp'   => $HP,
    'watts'=> $W,
    'mmr'  => $mmr,
    'fc'   => round($Fct,1),

    // → nuevos en UI
    'ae'   => $ae,
    'ap'   => round($ap,3),

    'etaPercent' => round($eta * 100),
    'radar'      => [$vida,$term,$pot],
];

if ($DEBUG) file_put_contents('/tmp/step6.log', json_encode($data, JSON_PRETTY_PRINT));

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
