<?php
/**
 * File: views/steps/step6.php
 * Descripción: Paso 6 – Resultados expertos del Wizard CNC
 *
 * 🔧 Ajustes clave (2025‑07‑13):
 *   1. En modo embebido sólo se imprime el <div class="step6"> y el script
 *      window.step6Params.
 *   2. Doctype, <html>, <head>, <body>, footer y parciales se encierran en
 *      `if (!$embedded)` para no contaminar el DOM.
 *   3. Los <script src> externos se cargan una única vez desde wizard_stepper.js.
 *   4. feather.replace() se ejecuta mediante requestAnimationFrame una sola vez.
 *   5. Cualquier parcial adicional debe sumarse al mismo condicional.
 *   6. Se eliminaron líneas en blanco extra para conservar el minidiff.
 *
 * 👉 Si necesitás debuggear, usá ?debug=1 en la URL y se activan trazas extra.
 */

declare(strict_types=1);

//if (!getenv('BASE_URL')) {
 //   // Sube 3 niveles: /views/steps/step6.php → /wizard-stepper_git
  //  putenv(
  //      'BASE_URL=' . rtrim(
    //        dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))),
   //         '/'
   //     )
  //  );
//}
//require_once __DIR__ . '/../../src/Config/AppConfig.php';

//use App\Controller\ExpertResultController;

// ────────────────────────────────────────────────────────────────
// Utilidades / helpers
// ────────────────────────────────────────────────────────────────
//require_once __DIR__ . '/../../includes/wizard_helpers.php';

// ────────────────────────────────────────────────────────────────
// ¿Vista embebida por load-step.php?
// ────────────────────────────────────────────────────────────────
//$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

// ────────────────────────────────────────────────────────────────
// Sesión segura (siempre antes de imprimir cabeceras)
// ────────────────────────────────────────────────────────────────
//if (session_status() !== PHP_SESSION_ACTIVE) {
//    session_set_cookie_params([
//        'lifetime' => 0,
 //       'path'     => '/',
  //      'secure'   => true,
  //      'httponly' => true,
  //      'samesite' => 'Strict'
  //  ]);
  //  session_start();
//}

//if (!$embedded) {
    /* Cabeceras de seguridad */
 //   header('Content-Type: text/html; charset=UTF-8');
 //   header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
 //   header('X-Frame-Options: DENY');
 //   header('X-Content-Type-Options: nosniff');
 //   header('Referrer-Policy: no-referrer');
 //   header("Permissions-Policy: geolocation=(), microphone=()");
 //   header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
 //   header('Pragma: no-cache');
 //   header(
  //      "Content-Security-Policy: default-src 'self';"
  //      . " script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;"
   //     . " style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;"
 //   );
//}

// ────────────────────────────────────────────────────────────────
// Debug opcional
// ────────────────────────────────────────────────────────────────
//$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
//if ($DEBUG && is_readable(__DIR__ . '/../../includes/debug.php')) {
//    require_once __DIR__ . '/../../includes/debug.php';
//}

// ────────────────────────────────────────────────────────────────
// Normalizar nombres en sesión
// ────────────────────────────────────────────────────────────────
//$_SESSION['material'] = $_SESSION['material_id']     ?? ($_SESSION['material']   ?? null);
//$_SESSION['trans_id'] = $_SESSION['transmission_id'] ?? ($_SESSION['trans_id']   ?? null);
//$_SESSION['fr_max']   = $_SESSION['feed_max']        ?? ($_SESSION['fr_max']     ?? null);
//$_SESSION['strategy'] = $_SESSION['strategy_id']     ?? ($_SESSION['strategy']   ?? null);

// ────────────────────────────────────────────────────────────────
// CSRF token
// ────────────────────────────────────────────────────────────────
//if (empty($_SESSION['csrf_token'])) {
  //  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
//}
//$csrfToken = $_SESSION['csrf_token'];
//if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 //   if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
  //      respondError(200, 'Error CSRF: petición no autorizada.');
  //  }
//}

// ────────────────────────────────────────────────────────────────
// Validar claves requeridas
// ────────────────────────────────────────────────────────────────
//$requiredKeys = [
//    'tool_table','tool_id','material','trans_id',
//    'rpm_min','rpm_max','fr_max','thickness',
 //   'strategy','hp'
//];
//$missing = array_filter($requiredKeys, fn($k) => empty($_SESSION[$k]));
//if ($missing) {
 //   if ($embedded) {
 //       echo '<div class="alert alert-danger m-4">Faltan datos esenciales en sesión: <strong>'
          //  . implode(', ', $missing)
   //         . '</strong></div>';
    //    return;
   // } else {
     //   echo "<!DOCTYPE html><html lang='es'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'><title>Error de sesión</title></head><body><main class='container py-5'><div class='alert alert-danger'><h4 class='alert-heading'>Faltan datos esenciales</h4><p>Revisá los pasos anteriores. Faltan: <strong>"
     //       . implode(', ', $missing)
      //      . "</strong></p><hr><a href='" . BASE_URL . "/wizard/index.php' class='btn btn-primary'>Volver al inicio</a></div></main></body></html>";
     //   exit;
  //  }
//}

// ────────────────────────────────────────────────────────────────
// Conexión BD
// ────────────────────────────────────────────────────────────────
//$dbFile = __DIR__ . '/../../includes/db.php';
//if (!is_readable($dbFile)) {
//    respondError(200, 'Error interno: falta el archivo de conexión a la BD.');
//}
//require_once $dbFile;           //-> $pdo
//if (!isset($pdo) || !($pdo instanceof PDO)) {
//    respondError(200, 'Error interno: no hay conexión a la base de datos.');
//}
// ────────────────────────────────────────────────────────────────
// Cargar modelos y utilidades
// ────────────────────────────────────────────────────────────────
//$root = dirname(__DIR__, 2) . '/';
//foreach ([
//    'src/Controller/ExpertResultController.php',
//    'src/Model/ToolModel.php',
//    'src/Model/ConfigModel.php',
 //   'src/Utils/CNCCalculator.php'
//] as $rel) {
//    if (!is_readable($root.$rel)) {
 //       respondError(200, "Error interno: falta {$rel}");
 //   }
  //  require_once $root.$rel;
//v}

// ────────────────────────────────────────────────────────────────
// Datos herramienta y parámetros base
// ────────────────────────────────────────────────────────────────
//$toolTable = (string)$_SESSION['tool_table'];
//$toolId    = (int)$_SESSION['tool_id'];
//$toolData  = ToolModel::getTool($pdo, $toolTable, $toolId) ?: null;
//if (!$toolData) {
//    respondError(200, 'Herramienta no encontrada.');
//}

//$params     = ExpertResultController::getResultData($pdo, $_SESSION);
//$jsonParams = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
//if ($jsonParams === false) {
//    respondError(200, 'Error interno: no se pudo serializar parámetros técnicos.');
//}

// ────────────────────────────────────────────────────────────────
// Variables de salida (HTML / JS)
// ────────────────────────────────────────────────────────────────
// 
//$serialNumber  = htmlspecialchars($toolData['serie']       ?? '', ENT_QUOTES);
//$toolCode      = htmlspecialchars($toolData['tool_code']   ?? '', ENT_QUOTES);
//$toolName      = htmlspecialchars($toolData['name']        ?? 'N/A', ENT_QUOTES);
//$toolType      = htmlspecialchars($toolData['tool_type']   ?? 'N/A', ENT_QUOTES);
//$imageURL      = !empty($toolData['image'])             ? asset($toolData['image'])            : '';
//$vectorURL     = !empty($toolData['image_dimensions'])   ? asset($toolData['image_dimensions']) : '';

//$diameterMb    = (float)($toolData['diameter_mm']       ?? 0);
//$shankMb       = (float)($toolData['shank_diameter_mm'] ?? 0);
//$fluteLenMb    = (float)($toolData['flute_length_mm']   ?? 0);
//$cutLenMb      = (float)($toolData['cut_length_mm']     ?? 0);
//$fullLenMb     = (float)($toolData['full_length_mm']    ?? 0);
//$fluteCountMb  = (int)  ($toolData['flute_count']        ?? 0);
//$coatingMb     = htmlspecialchars($toolData['coated']    ?? 'N/A', ENT_QUOTES);
//$materialMb    = htmlspecialchars($toolData['material']  ?? 'N/A', ENT_QUOTES);
//$brandMb       = htmlspecialchars($toolData['brand']     ?? 'N/A', ENT_QUOTES);
//$madeInMb      = htmlspecialchars($toolData['made_in']   ?? 'N/A', ENT_QUOTES);

//$baseVc  = (float)$params['vc0'];
//$vcMinDb = (float)$params['vc_min0'];
//$vcMaxDb = (float)($params['vc_max0'] ?? $baseVc * 1.25);
//$baseFz  = (float)$params['fz0'];
//$fzMinDb = (float)$params['fz_min0'];
//$fzMaxDb = (float)$params['fz_max0'];
//$apSlot  = (float)$params['ap_slot'];
//$aeSlot  = (float)$params['ae_slot'];
//$rpmMin  = (float)$params['rpm_min'];
//$rpmMax  = (float)$params['rpm_max'];
//$frMax   = (float)$params['fr_max'];
//$baseRpm = (int)  $params['rpm0'];
//$baseFeed= (float)$params['feed0'];
//$baseMmr = (float)$params['mmr_base'];

// Valores mostrados en el dash compacto
//$outVf = number_format($baseFeed, 0, '.', '');
//$outN  = number_format($baseRpm, 0, '.', '');
//$outVc = number_format($baseVc,   1, '.', '');

//$materialName   = (string)($_SESSION['material_name']   ?? 'Genérico Fibrofácil (MDF)');
//$materialParent = (string)($_SESSION['material_parent'] ?? 'Maderas Naturales');
//$strategyName   = (string)($_SESSION['strategy_name']   ?? 'Grabado en V / 2.5D');
//$strategyParent = (string)($_SESSION['strategy_parent'] ?? 'Fresado');
//$thickness      = (float)$_SESSION['thickness'];
//$powerAvail     = (float)$_SESSION['hp'];

// Nombre de transmisión
//try {
 //   $transName = $pdo->prepare('SELECT name FROM transmissions WHERE id = ?');
 //   $transName->execute([(int)$_SESSION['trans_id']]);
  //  $transName = $transName->fetchColumn() ?: 'N/D';
//} catch (Throwable $e) {
 //   $transName = 'N/D';
//}

//$notesArray = $params['notes'] ?? [];

// ────────────────────────────────────────────────────────────────
// Assets locales
// ────────────────────────────────────────────────────────────────
//$cssBootstrapRel = asset('assets/css/generic/bootstrap.min.css');
//$bootstrapJsRel  = asset('assets/js/bootstrap.bundle.min.js');
//$featherLocal    = $root.'node_modules/feather-icons/dist/feather.min.js';
//$chartJsLocal    = $root.'node_modules/chart.js/dist/chart.umd.min.js';
//$countUpLocal    = $root.'node_modules/countup.js/dist/countUp.umd.js';
//$step6JsRel      = asset('assets/js/step6.js');

//$assetErrors = [];
//if (!is_readable($root.'assets/css/generic/bootstrap.min.css'))
//    $assetErrors[] = 'Bootstrap CSS no encontrado localmente.';
//if (!is_readable($root.'assets/js/bootstrap.bundle.min.js'))
//    $assetErrors[] = 'Bootstrap JS no encontrado localmente.';
//if (!file_exists($featherLocal))
//    $assetErrors[] = 'Feather Icons JS faltante.';
//if (!file_exists($chartJsLocal))
 //   $assetErrors[] = 'Chart.js faltante.';
//if (!file_exists($countUpLocal))
 //   $assetErrors[] = 'CountUp.js faltante.';

// =====================================================================
// =========================  COMIENZA SALIDA  ==========================
// =====================================================================

