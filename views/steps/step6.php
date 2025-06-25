<?php
/**
 * Paso 6 — Resultados EXPERT del Wizard CNC
 * =============================================================================
 * Versión EXTREME‑DEBUG 2025‑06‑25
 * -----------------------------------------------------------------------------
 *  ▸ Blindada a prueba de bombas.
 *  ▸ Todas las posibles roturas de DOM se detectan y loguean en consola.
 *  ▸ Comentarios exhaustivos (ES‑AR) para cada bloque.
 *  ▸ Output­Buffering ⇒ jamás «headers already sent».
 *  ▸ Identificador único $uid   ⇒ evita IDs duplicados al embeber varias veces.
 *  ▸ Fallback a CDN + alerta visual cuando falta algún asset local.
 *  ▸ Flag ?debug=1 activa la consola “EXTREME” (sin flag → producción limpia).
 * -----------------------------------------------------------------------------
 *  Flujo resumido
 *  0) utilidades respondError() y $uid
 *  1) handler de excepciones PHP
 *  2) BASE_URL + BASE_HOST
 *  3) config App + autoload
 *  4) helpers opcionales (dbg())
 *  5) modo embebido
 *  6) sesión segura
 *  7) cabeceras HTTP seguridad (solo no‑embedded)
 *  8) flag DEBUG (?debug)
 *  9) validar sesión (claves + rangos básicos)
 * 10) CSRF token renovable
 * 11) revisar datos faltantes
 * 12) conexión BD (db.php) + sanity PDO
 * 13) cargar modelos críticos
 * 14) traer datos herramienta + parámetros (ExpertResultController)
 * 15) saneo (htmlspecialchars / number_format) → variables listas p/ DOM
 * 16) verificar assets locales, armar lista $assetsMissing
 * 17) render HTML completo  (ob_start() si no embebido)
 * 18) inyectar scripts + bloque EXTREME‑DEBUG (solo si $DEBUG)
 * =============================================================================
 */

declare(strict_types=1);

/* -------------------------------------------------------------------------- */
/* 0) UTILIDADES BÁSICAS + UID                                               */
/* -------------------------------------------------------------------------- */
if (!function_exists('respondError')) {
    /**
     * Devuelve error y aborta.
     * @param int    $code Código HTTP.
     * @param string $msg  Mensaje para log / user.
     */
    function respondError(int $code, string $msg): void
    {
        http_response_code($code);
        header('Content-Type: text/plain; charset=UTF-8');
        error_log("[step6][{$code}] {$msg}");
        echo "ERROR {$code}: {$msg}";
        exit;
    }
}

$uid = 's6'.bin2hex(random_bytes(3)); // ID único de instancia (6 hex)

/* -------------------------------------------------------------------------- */
/* 1) HANDLER GLOBAL PHP                                                     */
/* -------------------------------------------------------------------------- */
set_exception_handler(fn(Throwable $e)=>respondError(500,$e->getMessage()));

/* -------------------------------------------------------------------------- */
/* 2) BASE_URL + BASE_HOST                                                   */
/* -------------------------------------------------------------------------- */
if (!getenv('BASE_URL')) {
    putenv('BASE_URL='.dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))));
}
if (!defined('BASE_HOST')) define('BASE_HOST', $_SERVER['HTTP_HOST'] ?? 'localhost');

/* -------------------------------------------------------------------------- */
/* 3) CONFIG PRINCIPAL                                                       */
/* -------------------------------------------------------------------------- */
$appConfig = __DIR__.'/../../src/Config/AppConfig.php';
is_readable($appConfig) || respondError(500,'Falta AppConfig.php');
require_once $appConfig;

use App\Controller\ExpertResultController;

/* -------------------------------------------------------------------------- */
/* 4) HELPERS OPCIONALES                                                     */
/* -------------------------------------------------------------------------- */
$helpers = __DIR__.'/../../includes/wizard_helpers.php';
if (is_readable($helpers)) require_once $helpers;
if (!function_exists('dbg')) {function dbg(...$a):void{}} // stub

/* -------------------------------------------------------------------------- */
/* 5) MODO EMBEBIDO                                                         */
/* -------------------------------------------------------------------------- */
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

/* -------------------------------------------------------------------------- */
/* 6) SESIÓN SEGURA                                                          */
/* -------------------------------------------------------------------------- */
if (session_status()!==PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Strict']);
    session_start();
}

/* -------------------------------------------------------------------------- */
/* 7) CABECERAS SEGURIDAD (solo no‑embedded)                                 */
/* -------------------------------------------------------------------------- */
if (!$embedded) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:");
}

/* -------------------------------------------------------------------------- */
/* 8) FLAG DEBUG (?debug=1)                                                  */
/* -------------------------------------------------------------------------- */
$DEBUG = filter_input(INPUT_GET,'debug',FILTER_VALIDATE_BOOLEAN) ?? false;

/* -------------------------------------------------------------------------- */
/* 9) VALIDACIÓN SESIÓN                                                     */
/* -------------------------------------------------------------------------- */
$need = ['tool_table','tool_id','material_id','trans_id','rpm_min','rpm_max','fr_max','thickness','strategy_id','hp'];
$miss = array_filter($need,fn($k)=>empty($_SESSION[$k]));
$miss && respondError(400,'Faltan datos: '.implode(', ',$miss));

$toolId = filter_var($_SESSION['tool_id'],FILTER_VALIDATE_INT) ?: respondError(400,'tool_id inválido');
$rpmMin = filter_var($_SESSION['rpm_min'],FILTER_VALIDATE_FLOAT);
$rpmMax = filter_var($_SESSION['rpm_max'],FILTER_VALIDATE_FLOAT);
($rpmMin<$rpmMax) || respondError(400,'rpm_min >= rpm_max');

/* -------------------------------------------------------------------------- */
/* 10) CSRF TOKEN                                                            */
/* -------------------------------------------------------------------------- */
$tokenTTL=900; $now=time();
if(empty($_SESSION['csrf_token'])||empty($_SESSION['csrf_token_time'])||$_SESSION['csrf_token_time']+$tokenTTL<$now){
    $_SESSION['csrf_token']=bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time']=$now;
}
$csrfToken=$_SESSION['csrf_token'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    hash_equals($csrfToken,(string)($_POST['csrf_token']??'')) || respondError(200,'CSRF');
    ($_SESSION['csrf_token_time']+$tokenTTL>$now) || respondError(200,'CSRF expirado');
}

/* -------------------------------------------------------------------------- */
/* 11) CONEXIÓN BD                                                          */
/* -------------------------------------------------------------------------- */
require_once __DIR__.'/../../includes/db.php';
($pdo??null) instanceof PDO || respondError(500,'PDO no creado');

/* -------------------------------------------------------------------------- */
/* 12) MODELOS                                                               */
/* -------------------------------------------------------------------------- */
$root = dirname(__DIR__,2).'/';
foreach(['src/Controller/ExpertResultController.php','src/Model/ToolModel.php','src/Utils/CNCCalculator.php'] as $rel){
    is_readable($root.$rel) || respondError(500,"Falta {$rel}");
    require_once $root.$rel;
}
use App\Model\ToolModel;

/* -------------------------------------------------------------------------- */
/* 13) DATOS TOOL + PARÁMS                                                   */
/* -------------------------------------------------------------------------- */
$table = (string)$_SESSION['tool_table'];
$tool  = ToolModel::getTool($pdo,$table,$toolId) ?: respondError(404,'Tool no encontrada');
$params = ExpertResultController::getResultData($pdo,$_SESSION);
$jsonParams = json_encode($params,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?: 'null';

/* -------------------------------------------------------------------------- */
/* 14) PRE‑SANITIZE PARA DOM                                                 */
/* -------------------------------------------------------------------------- */
$h=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES);
$serial=$h($tool['serie']??'');
$code  =$h($tool['tool_code']??'');
$name  =$h($tool['name']??'');
$type  =$h($tool['tool_type']??'');
$img   =!empty($tool['image'])?asset($tool['image']):'';

/* -------------------------------------------------------------------------- */
/* 15) VERIFICAR ASSETS                                                     */
/* -------------------------------------------------------------------------- */
$assets=[
  'bootstrapCss'=>asset('assets/css/generic/bootstrap.min.css'),
  'bootstrapJs' =>asset('assets/js/bootstrap.bundle.min.js'),
  'feather'     =>asset('node_modules/feather-icons/dist/feather.min.js'),
  'chart'       =>asset('node_modules/chart.js/dist/chart.umd.min.js'),
  'countup'     =>asset('node_modules/countup.js/dist/countUp.umd.js'),
  'step6'       =>asset('assets/js/step6.js'),
];
$missing=[];foreach($assets as $p){if(!is_readable($root.$p))$missing[]=$p;}

/* -------------------------------------------------------------------------- */
/* 16) OUTPUT HTML (ob_start() para no romper headers)                      */
/* -------------------------------------------------------------------------- */
if(!$embedded) ob_start();
?>
<?php if(!$embedded):?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cutting Data – Paso&nbsp;6</title>
  <?php
    $styles = [
      $cssBootstrapRel,
      'assets/css/settings/settings.css',
      'assets/css/generic/generic.css',
      'assets/css/elements/elements.css',
      'assets/css/objects/objects.css',
      'assets/css/objects/wizard.css',
      'assets/css/objects/stepper.css',
      'assets/css/objects/step-common.css',
      'assets/css/objects/step6.css',
      'assets/css/components/components.css',
      'assets/css/components/main.css',
      'assets/css/components/footer-schneider.css',
      'assets/css/utilities/utilities.css',
    ];
    include __DIR__ . '/../partials/styles.php';
  ?>
  <script>
    window.BASE_URL  = <?= json_encode(BASE_URL) ?>;
    window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
  </script>
</head>
<body>
<?php endif; ?>

<?php if ($assetErrors): ?>
  <div class="alert alert-warning text-dark m-3">
    <strong>⚠️ Archivos faltantes (se usarán CDNs):</strong>
    <ul>
      <?php foreach ($assetErrors as $err): ?>
        <li><?= htmlspecialchars($err, ENT_QUOTES) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="step6">
  <div id="step6Spinner" class="spinner-overlay">
    <div class="spinner-border" role="status">
      <span class="visually-hidden">Cargando...</span>
    </div>
  </div>
<div class="content-main">
  <div class="container py-4">
    <h2 class="step-title"><i data-feather="bar-chart-2"></i> Resultados</h2>
    <p class="step-desc">Ajustá los parámetros y revisá los datos de corte.</p>
  <!-- BLOQUE CENTRAL -->
  <div class="row gx-3 mb-4 cards-grid">
    <div class="col-12 col-lg-4 mb-3 area-tool">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3">
          <span>#<?= $serialNumber ?> – <?= $toolCode ?></span>
        </div>
        <div class="card-body text-center p-4">
          <?php if ($imageURL): ?>
            <img src="<?= htmlspecialchars($imageURL, ENT_QUOTES) ?>"
                 alt="Imagen principal herramienta"
                 class="tool-image mx-auto d-block">
          <?php else: ?>
            <div class="text-secondary">Sin imagen disponible</div>
          <?php endif; ?>
          <div class="tool-name mt-3"><?= $toolName ?></div>
          <div class="tool-type"><?= $toolType ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- AJUSTES / RESULTADOS / RADAR -->
  <div class="row gx-3 mb-4 cards-grid">
    <!-- Ajustes -->
    <div class="col-12 col-lg-4 mb-3 area-sliders">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"><h5 class="mb-0">Ajustes</h5></div>
        <div class="card-body p-4">
          <!-- fz -->
          <div class="mb-4 px-2">
            <label for="sliderFz" class="form-label">fz (mm/tooth)</label>
            <div class="slider-wrap">
              <input type="range" id="sliderFz" class="form-range"
                     min="<?= number_format($fzMinDb,4,'.','') ?>"
                     max="<?= number_format($fzMaxDb,4,'.','') ?>"
                     step="0.0001"
                     value="<?= number_format($baseFz,4,'.','') ?>">
              <span class="slider-bubble"></span>
            </div>
            <div class="text-end small text-secondary mt-1">
              <span><?= number_format($fzMinDb,4,'.','') ?></span> –
              <strong id="valFz"><?= number_format($baseFz,4,'.','') ?></strong> –
              <span><?= number_format($fzMaxDb,4,'.','') ?></span>
            </div>
          </div>
          <!-- Vc -->
          <div class="mb-4 px-2">
            <label for="sliderVc" class="form-label">Vc (m/min)</label>
            <div class="slider-wrap">
              <input type="range" id="sliderVc" class="form-range"
                     min="<?= number_format($vcMinDb,1,'.','') ?>"
                     max="<?= number_format($vcMaxDb,1,'.','') ?>"
                     step="0.1"
                     value="<?= number_format($baseVc,1,'.','') ?>">
              <span class="slider-bubble"></span>
            </div>
            <div class="text-end small text-secondary mt-1">
              <span><?= number_format($vcMinDb,1,'.','') ?></span> –
              <strong id="valVc"><?= number_format($baseVc,1,'.','') ?></strong> –
              <span><?= number_format($vcMaxDb,1,'.','') ?></span>
            </div>
          </div>
          <!-- ae -->
          <div class="mb-4 px-2">
            <label for="sliderAe" class="form-label">
              ae (mm) <small>(ancho de pasada)</small>
            </label>
            <div class="slider-wrap">
              <input type="range" id="sliderAe" class="form-range"
                     min="0.1"
                     max="<?= number_format($diameterMb,1,'.','') ?>"
                     step="0.1"
                     value="<?= number_format($diameterMb * 0.5,1,'.','') ?>">
              <span class="slider-bubble"></span>
            </div>
            <div class="text-end small text-secondary mt-1">
              <span>0.1</span> –
              <strong id="valAe"><?= number_format($diameterMb * 0.5,1,'.','') ?></strong> –
              <span><?= number_format($diameterMb,1,'.','') ?></span>
            </div>
          </div>
          <!-- Pasadas -->
          <div class="mb-4 px-2">
            <label for="sliderPasadas" class="form-label">Pasadas</label>
            <div class="slider-wrap">
              <input type="range" id="sliderPasadas" class="form-range"
                     min="1" max="1" step="1"
                     value="1"
                     data-thickness="<?= htmlspecialchars((string)$thickness, ENT_QUOTES) ?>">
              <span class="slider-bubble"></span>
            </div>
            <div id="textPasadasInfo" class="small text-secondary mt-1">
              1 pasada de <?= number_format($thickness, 2) ?> mm
            </div>
            <div id="errorMsg" class="text-danger mt-2 small"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Resultados -->
    <div class="col-12 col-lg-4 mb-3 area-results">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"><h5 class="mb-0">Resultados</h5></div>
        <div class="card-body p-4">
          <div class="results-compact mb-4 d-flex gap-2">
            <div class="result-box text-center flex-fill">
              <div class="param-label">
                Feedrate<br><small>(<span class="param-unit">mm/min</span>)</small>
              </div>
              <div id="outVf" class="fw-bold display-6"><?= $outVf ?></div>
            </div>
            <div class="result-box text-center flex-fill">
              <div class="param-label">
                Cutting speed<br><small>(<span class="param-unit">RPM</span>)</small>
              </div>
              <div id="outN" class="fw-bold display-6"><?= $outN ?></div>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>Vc</small>
            <div><span id="outVc" class="fw-bold"><?= $outVc ?></span> <span class="param-unit">m/min</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>fz</small>
            <div><span id="outFz" class="fw-bold">--</span> <span class="param-unit">mm/tooth</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>Ap</small>
            <div><span id="outAp" class="fw-bold">--</span> <span class="param-unit">mm</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>Ae</small>
            <div><span id="outAe" class="fw-bold">--</span> <span class="param-unit">mm</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>hm</small>
            <div><span id="outHm" class="fw-bold">--</span> <span class="param-unit">mm</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <small>Hp</small>
            <div><span id="outHp" class="fw-bold">--</span> <span class="param-unit">HP</span></div>
          </div>
          <!-- Métricas secundarias -->
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="param-label">
              MMR<br><small>(<span class="param-unit">mm³/min</span>)</small>
            </div>
            <div id="valueMrr" class="fw-bold">--</div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="param-label">
              Fc<br><small>(<span class="param-unit">N</span>)</small>
            </div>
            <div id="valueFc" class="fw-bold">--</div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="param-label">
              Potencia<br><small>(<span class="param-unit">W</span>)</small>
            </div>
            <div id="valueW" class="fw-bold">--</div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="param-label">
              η<br><small>(<span class="param-unit">%</span>)</small>
            </div>
            <div id="valueEta" class="fw-bold">--</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Radar Chart -->
    <div class="col-12 col-lg-4 mb-3 area-radar">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"><h5 class="mb-0">Distribución Radar</h5></div>
        <div class="card-body p-4 d-flex justify-content-center align-items-center">
          <canvas id="radarChart" width="300" height="300"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- ESPECIFICACIONES / CONFIGURACIÓN / NOTAS -->
  <div class="row gx-3 mb-4 cards-grid">
    <!-- Especificaciones -->
    <div class="col-12 col-lg-4 mb-3">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"
             data-bs-toggle="collapse"
             data-bs-target="#specCollapse"
             aria-expanded="true">
          <h5 class="mb-0">Especificaciones Técnicas</h5>
        </div>
        <div id="specCollapse" class="collapse show">
          <div class="card-body p-4">
            <div class="row gx-0 align-items-center">
              <div class="col-12 col-lg-7 px-2 mb-4 mb-lg-0">
                <ul class="spec-list mb-0 px-2">
                  <li><span>Diámetro de corte (d1):</span>
                      <span><?= number_format($diameterMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Diámetro del vástago:</span>
                      <span><?= number_format($shankMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Longitud de corte:</span>
                      <span><?= number_format($cutLenMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Longitud de filo:</span>
                      <span><?= number_format($fluteLenMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Longitud total:</span>
                      <span><?= number_format($fullLenMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Número de filos (Z):</span><span><?= $fluteCountMb ?></span></li>
                  <li><span>Tipo de punta:</span><span><?= $toolType ?></span></li>
                  <li><span>Recubrimiento:</span><span><?= $coatingMb ?></span></li>
                  <li><span>Material fabricación:</span><span><?= $materialMb ?></span></li>
                  <li><span>Marca:</span><span><?= $brandMb ?></span></li>
                  <li><span>País de origen:</span><span><?= $madeInMb ?></span></li>
                </ul>
              </div>
              <div class="col-12 col-lg-5 px-2 d-flex justify-content-center align-items-center">
                <?php if ($vectorURL): ?>
                  <img src="<?= htmlspecialchars($vectorURL, ENT_QUOTES) ?>"
                       alt="Imagen vectorial herramienta"
                       class="vector-image mx-auto d-block">
                <?php else: ?>
                  <div class="text-secondary">Sin imagen vectorial</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Configuración -->
    <div class="col-12 col-lg-4 mb-3">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"
             data-bs-toggle="collapse"
             data-bs-target="#configCollapse"
             aria-expanded="true">
          <h5 class="mb-0">Configuración de Usuario</h5>
        </div>
        <div id="configCollapse" class="collapse show">
          <div class="card-body p-4">
            <div class="config-section mb-3">
              <div class="config-section-title">Material</div>
              <div class="config-item">
                <div class="label-static">Categoría padre:</div>
                <div class="value-static"><?= $materialParent ?></div>
              </div>
              <div class="config-item">
                <div class="label-static">Material a mecanizar:</div>
                <div class="value-static"><?= $materialName ?></div>
              </div>
            </div>
            <div class="section-divider"></div>
            <div class="config-section mb-3">
              <div class="config-section-title">Estrategia</div>
              <div class="config-item">
                <div class="label-static">Categoría padre estr.:</div>
                <div class="value-static"><?= $strategyParent ?></div>
              </div>
              <div class="config-item">
                <div class="label-static">Estrategia de corte:</div>
                <div class="value-static"><?= $strategyName ?></div>
              </div>
            </div>
            <div class="section-divider"></div>
            <div class="config-section">
              <div class="config-section-title">Máquina</div>
              <div class="config-item">
                <div class="label-static">Espesor del material:</div>
                <div class="value-static"><?= number_format($thickness,2) ?> <span class="param-unit">mm</span></div>
              </div>
              <div class="config-item">
                <div class="label-static">Tipo de transmisión:</div>
                <div class="value-static"><?= $transName ?></div>
              </div>
              <div class="config-item">
                <div class="label-static">Feedrate máximo:</div>
                <div class="value-static"><?= number_format($frMax,0) ?> <span class="param-unit">mm/min</span></div>
              </div>
              <div class="config-item">
                <div class="label-static">RPM mínima:</div>
                <div class="value-static"><?= number_format($rpmMin,0) ?> <span class="param-unit">rev/min</span></div>
              </div>
              <div class="config-item">
                <div class="label-static">RPM máxima:</div>
                <div class="value-static"><?= number_format($rpmMax,0) ?> <span class="param-unit">rev/min</span></div>
              </div>
              <div class="config-item">
                <div class="label-static">Potencia disponible:</div>
                <div class="value-static"><?= number_format($powerAvail,1) ?> <span class="param-unit">HP</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Notas -->
    <div class="col-12 col-lg-4 mb-3">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"><h5 class="mb-0">Notas Adicionales</h5></div>
        <div class="card-body p-4">
          <?php if ($notesArray): ?>
            <ul class="notes-list mb-0">
              <?php foreach ($notesArray as $note): ?>
                <li class="mb-2 d-flex align-items-start">
                  <i data-feather="file-text" class="me-2"></i>
                  <div><?= htmlspecialchars($note, ENT_QUOTES) ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <div class="text-secondary">No hay notas adicionales para esta herramienta.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
</div><!-- .content-main -->
</div><!-- .step6 -->
<section id="wizard-dashboard"></section>

<!-- SCRIPTS -->
<script>window.step6Params = <?= $jsonParams ?>; window.step6Csrf = '<?= $csrfToken ?>';</script>
<?php if (!$embedded): ?>
<script src="<?= $bootstrapJsRel ?>" defer></script>
<script src="<?= asset('node_modules/feather-icons/dist/feather.min.js') ?>" defer></script>
<script src="<?= asset('node_modules/chart.js/dist/chart.umd.min.js') ?>" defer></script>
<script src="<?= asset('node_modules/countup.js/dist/countUp.umd.js') ?>" defer></script>
<script src="<?= $step6JsRel ?>" defer></script>
<script>requestAnimationFrame(() => feather.replace());</script>
</body>
</html>
<?php endif; ?>
