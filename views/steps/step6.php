<?php
/**
 * File: step5.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * @TODO Extend documentation.
 */
/**
 * Paso 5 (Auto) – Configurar router
 * Protegido con CSRF, controla flujo y valida:
 *   – rpm_min > 0
 *   – rpm_max > 0
 *   – rpm_min < rpm_max
 *   – feed_max > 0
 *   – hp       > 0
 * Después guarda en sesión y avanza a step6.php
 */
// declare(strict_types=1);

/* 1) Sesión segura y flujo */
// if (session_status() !== PHP_SESSION_ACTIVE) {
 //    session_start([
     //    'cookie_secure'   => true,
    //     'cookie_httponly' => true,
     //    'cookie_samesite' => 'Strict',
//     ]);
// }
// if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 5) {
//     header('Location: step1.php');
 //    exit;
// }
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

/**
 * File: step5.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * @TODO Extend documentation.
 */
/**
 * Paso 5 (Auto) – Configurar router
 * Protegido con CSRF, controla flujo y valida:
 *   – rpm_min > 0
 *   – rpm_max > 0
 *   – rpm_min < rpm_max
 *   – feed_max > 0
 *   – hp       > 0
 * Después guarda en sesión y avanza a step6.php
 */
declare(strict_types=1);

/* 1) Sesión segura y flujo */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 4) {
    header('Location: step1.php');
    exit;
}

/* 2) Dependencias */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/debug.php';

/* 3) CSRF token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* 4) Transmisiones desde BD */
$txList = $pdo->query("
    SELECT id, name, rpm_min, rpm_max, feed_max, hp_default
      FROM transmissions
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

$validTx = [];
foreach ($txList as $t) {
    $validTx[(int)$t['id']] = [
        'rpm_min'  => (int)$t['rpm_min'],
        'rpm_max'  => (int)$t['rpm_max'],
        'feed_max' => (float)$t['feed_max'],
        'hp_def'   => (float)$t['hp_default'],
    ];
}

/* 5) Procesar POST */
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Token de seguridad inválido. Recargá la página e intentá de nuevo.';
    }
    if ((int)($_POST['step'] ?? 0) !== 5) {
        $errors[] = 'Paso inválido. Reiniciá el asistente.';
    }

    $id   = filter_input(INPUT_POST, 'transmission_id', FILTER_VALIDATE_INT);
    $rpmn = filter_input(INPUT_POST, 'rpm_min',         FILTER_VALIDATE_INT);
    $rpmm = filter_input(INPUT_POST, 'rpm_max',         FILTER_VALIDATE_INT);
    $feed = filter_input(INPUT_POST, 'feed_max',        FILTER_VALIDATE_FLOAT);
    $hp   = filter_input(INPUT_POST, 'hp',              FILTER_VALIDATE_FLOAT);

    if (!isset($validTx[$id]))           $errors[] = 'Elegí una transmisión válida.';
    if (!$rpmn || $rpmn <= 0)            $errors[] = 'La RPM mínima debe ser > 0.';
    if (!$rpmm || $rpmm <= 0)            $errors[] = 'La RPM máxima debe ser > 0.';
    if ($rpmn && $rpmm && $rpmn >= $rpmm)$errors[] = 'La RPM mínima debe ser menor que la máxima.';
    if (!$feed || $feed <= 0)            $errors[] = 'El avance máximo debe ser > 0.';
    if (!$hp   || $hp   <= 0)            $errors[] = 'La potencia debe ser > 0.';

    if (!$errors) {
        $_SESSION += [
            'transmission_id' => $id,
            'rpm_min'         => $rpmn,
            'rpm_max'         => $rpmm,
            'feed_max'        => $feed,
            'hp'              => $hp,
            'wizard_progress' => 5,
        ];
        session_write_close();
        header('Location: step6.php');
        exit;
    }
}

/* 6) Valores previos */
$prev = [
    'transmission_id' => $_SESSION['transmission_id'] ?? '',
    'rpm_min'         => $_SESSION['rpm_min']        ?? '',
    'rpm_max'         => $_SESSION['rpm_max']        ?? '',
    'feed_max'        => $_SESSION['feed_max']       ?? '',
    'hp'              => $_SESSION['hp']             ?? '',
];
$hasPrev = (int)$prev['transmission_id'] > 0;
?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8">
<title>Paso 5 – Configurá tu router</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php
  $styles = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'assets/css/objects/step-common.css',
    'assets/css/components/_step5.css',
  ];
  $embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;
  include __DIR__ . '/../partials/styles.php';
?>
<?php if (!$embedded): ?>
<script>
  window.BASE_URL = <?= json_encode(BASE_URL) ?>;
  window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
</script>
<?php endif; ?>
</head><body>
<main class="container py-4">
  <h2 class="step-title"><i data-feather="cpu"></i> Configurá tu router</h2>
  <p class="step-desc">Ingresá los datos de tu máquina para calcular parámetros.</p>

  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0">
      <?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e,ENT_QUOTES).'</li>'; ?>
    </ul></div>
  <?php endif; ?>

  <form id="routerForm" method="post" class="needs-validation" novalidate>
    <input type="hidden" name="step" value="5">
    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">

    <!-- Transmisión -->
    <div class="mb-4">
      <label class="form-label d-block">Transmisión</label>
      <div class="btn-group" role="group">
      <?php foreach ($txList as $t):
            $tid=(int)$t['id']; $chk=$tid===$prev['transmission_id']; ?>
        <input class="btn-check" type="radio" name="transmission_id"
               id="tx<?=$tid?>" value="<?=$tid?>" <?=$chk?'checked':''?>>
        <label class="btn btn-outline-primary" for="tx<?=$tid?>"
               data-rpmmin="<?=$t['rpm_min']?>" data-rpmmax="<?=$t['rpm_max']?>"
               data-feedmax="<?=$t['feed_max']?>" data-hpdef="<?=$t['hp_default']?>">
          <?=htmlspecialchars($t['name'])?>
        </label>
      <?php endforeach; ?>
      </div>
    </div>

    <!-- Parámetros -->
    <div id="paramSection">
      <div class="row g-3">
        <?php
          $fields=[
            ['rpm_min','RPM mínima',1,'rpm'],
            ['rpm_max','RPM máxima',1,'rpm'],
            ['feed_max','Avance máx (mm/min)',0.1,'mm/min'],
            ['hp','Potencia (HP)',0.1,'HP'],
          ];
          foreach($fields as [$id,$label,$step,$unit]): ?>
        <div class="col-md-3">
          <label for="<?=$id?>" class="form-label"><?=$label?></label>
          <div class="input-group has-validation">
            <input type="number" class="form-control" id="<?=$id?>" name="<?=$id?>"
                   step="<?=$step?>" min="1" value="<?=htmlspecialchars((string)$prev[$id])?>" required>
            <span class="input-group-text"><?=$unit?></span>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Botón -->
    <div id="nextWrap" class="text-end mt-4" style="display:<?=$hasPrev?'block':'none'?>">
      <button class="btn btn-primary btn-lg">
        Siguiente <i data-feather="arrow-right" class="ms-1"></i>
      </button>
    </div>
  </form>
</main>

<script>
(() => {
  const radios   = document.querySelectorAll('.btn-check');
  const paramSec = document.getElementById('paramSection');
  const nextWrap = document.getElementById('nextWrap');
  const form     = document.getElementById('routerForm');
  const inputs   = {
    rpm_min : document.getElementById('rpm_min'),
    rpm_max : document.getElementById('rpm_max'),
    feed_max: document.getElementById('feed_max'),
    hp      : document.getElementById('hp')
  };

  /* Ocultar todo hasta elegir transmisión */
  const hideParams = () => {
    paramSec.style.display = 'none';
    nextWrap.style.display = 'none';
    Object.values(inputs).forEach(i => { i.value=''; i.disabled=true; });
  };
  <?php if(!$hasPrev): ?> hideParams(); <?php endif; ?>

  /* Mostrar parámetros y validar */
  radios.forEach(r => r.addEventListener('change', () => {
    const d = document.querySelector(`label[for="${r.id}"]`).dataset;
    inputs.rpm_min.value  = d.rpmmin;
    inputs.rpm_max.value  = d.rpmmax;
    inputs.feed_max.value = d.feedmax;
    if(!inputs.hp.value)  inputs.hp.value = d.hpdef;

    Object.values(inputs).forEach(i => i.disabled=false);
    paramSec.style.display = 'block';
    validate();
  }));

  /* Validación en vivo */
  function validate() {
    let ok = true;
    const v  = k => parseFloat(inputs[k].value) || 0;
    const fb = (inp,msg) => {
      const feedback = inp.parentElement.querySelector('.invalid-feedback');
      feedback.textContent = msg;
      inp.classList.toggle('is-invalid', !!msg);
      if (msg) ok = false;
    };

    fb(inputs.rpm_min , v('rpm_min')  > 0 ? '' : 'Debe ser > 0');
    fb(inputs.rpm_max , v('rpm_max')  > 0 ? '' : 'Debe ser > 0');
    fb(inputs.feed_max, v('feed_max') > 0 ? '' : 'Debe ser > 0');
    fb(inputs.hp      , v('hp')       > 0 ? '' : 'Debe ser > 0');

    if (v('rpm_min') && v('rpm_max') && v('rpm_min') >= v('rpm_max')) {
      fb(inputs.rpm_min,'RPM min < max');
      fb(inputs.rpm_max,'RPM min < max');
    }

    nextWrap.style.display = ok ? 'block' : 'none';
    return ok;
  }

  Object.values(inputs).forEach(i => i.addEventListener('input', validate));
  form.addEventListener('submit', e => { if(!validate()){ e.preventDefault(); e.stopPropagation(); } });
})();
</script>
</body></html>

