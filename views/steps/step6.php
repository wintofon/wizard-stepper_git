<?php
/**
 * File: views/steps/auto/step6.php
 * -----------------------------------------------------------------------------
 * Paso 5 (Auto) – Configuración del router CNC
 * -----------------------------------------------------------------------------
 * RESPONSABILIDAD
 *   • Mostrar un formulario con las transmisiones disponibles                            
 *   • Validar la selección del usuario y los parámetros numéricos ingresados            
 *   • Guardar la configuración en sesión y avanzar a Paso 6                             
 *                                                                                       
 * PUNTOS CRÍTICOS                                                                        
 *   1) Seguridad de sesión + cabeceras                                                   
 *   2) Protección CSRF                                                                   
 *   3) Validaciones servidor ↔ cliente (JS)                                              
 *   4) Persistencia de valores previos (para UX)                                         
 *                                                                                       
 * NOTA: los estilos y el JS de Bootstrap se cargan vía CDN para simplicidad.             
 */

declare(strict_types=1);

/* -------------------------------------------------------------------------- */
/* 1)  SESIÓN SEGURA Y CONTROL DE FLUJO    - corregido                                    */
/* -------------------------------------------------------------------------- */
// Si la sesión aún no está activa, se crea con cookies seguras.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,      // sólo cookie en HTTPS
        'cookie_httponly' => true,      // inaccesible para JS
        'cookie_samesite' => 'Strict',  // bloquea CSRF por navegação cruzada
    ]);
}

// Para llegar a Paso 4 el usuario debe haber completado hasta Paso 5.
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 5) {
    header('Location: step1.php');
    exit;
}

/* -------------------------------------------------------------------------- */
/* 2)  DEPENDENCIAS                         - corregido                                   */
/* -------------------------------------------------------------------------- */
// BASE_URL (por si el front-controller no lo definió)
if (!defined('BASE_URL') && !getenv('BASE_URL')) {
    putenv('BASE_URL=' . rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/'));
}

require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../includes/wizard_helpers.php';
require_once __DIR__ . '/../../includes/db.php';       // → $pdo (PDO)
require_once __DIR__ . '/../../includes/debug.php';    // dbg() helper silencioso

use App\Controller\ExpertResultController;
use App\Model\ToolModel;



/* -------------------------------------------------------------------------- */
/* 3)  MODO EMBEBIDO (load-step.php)   _ CORREGUIDO                                        */
/* -------------------------------------------------------------------------- */
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;




/* -------------------------------------------------------------------------- */
/* 4)  TOKEN CSRF                          corregido                                    */
/* -------------------------------------------------------------------------- */
/* 4) TOKEN CSRF (se emite, no se valida acá) */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* 5) SOLO validar CSRF si realmente llega un POST con datos esperados --------
 * Nota: en este paso 6 no debería ocurrir.   */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!hash_equals($csrfToken, (string)$_POST['csrf_token'])) {
        // devolver JSON de error si es llamada AJAX, o redirigir con mensaje
        http_response_code(403);
        exit('Sesión expirada: recargá la página.');
    }
}



/* -------------------------------------------------------------------------- */
/* 5)  VALIDAR QUE EXISTA CONTEXTO PREVIO (PASOS 1-5)    corregido                      */
/* -------------------------------------------------------------------------- */
$requiredKeys = [
    'tool_table', 'tool_id', 'material', 'transmission_id',
    'rpm_min', 'rpm_max', 'feed_max', 'thickness',
    'strategy_id', 'hp'
];


















/* -------------------------------------------------------------------------- */
/* 4)  CARGAR TRANSMISIONES DESDE BD                                           */
/* -------------------------------------------------------------------------- */
$txList = $pdo->query(
    'SELECT id, name, rpm_min, rpm_max, feed_max, hp_default
       FROM transmissions
   ORDER BY name'
)->fetchAll(PDO::FETCH_ASSOC);

// Mapeamos IDs → data para validar rápido el POST
$validTx = [];
foreach ($txList as $t) {
    $validTx[(int)$t['id']] = [
        'rpm_min'  => (int)$t['rpm_min'],
        'rpm_max'  => (int)$t['rpm_max'],
        'feed_max' => (float)$t['feed_max'],
        'hp_def'   => (float)$t['hp_default'],
    ];
}

dbg('⚙️ Transmisiones cargadas: '.count($txList));

/* -------------------------------------------------------------------------- */
/* 5)  PROCESAR ENVÍO DEL FORMULARIO                                           */
/* -------------------------------------------------------------------------- */
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* 5.1) Validar token CSRF */
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Token de seguridad inválido. Recargá la página e intentá de nuevo.';
    }

    /* 5.2) Controlar que el campo oculto step valga 5 */
    if ((int)($_POST['step'] ?? 0) !== 5) {
        $errors[] = 'Paso inválido. Reiniciá el asistente.';
    }

    /* 5.3) Sanitizar / validar input numérico */
    $id   = filter_input(INPUT_POST, 'transmission_id', FILTER_VALIDATE_INT);
    $rpmn = filter_input(INPUT_POST, 'rpm_min',         FILTER_VALIDATE_INT);
    $rpmm = filter_input(INPUT_POST, 'rpm_max',         FILTER_VALIDATE_INT);
    $feed = filter_input(INPUT_POST, 'feed_max',        FILTER_VALIDATE_FLOAT);
    $hp   = filter_input(INPUT_POST, 'hp',              FILTER_VALIDATE_FLOAT);

    /* 5.4) Reglas de negocio */
    if (!isset($validTx[$id]))           $errors[] = 'Elegí una transmisión válida.';
    if (!$rpmn || $rpmn <= 0)            $errors[] = 'La RPM mínima debe ser > 0.';
    if (!$rpmm || $rpmm <= 0)            $errors[] = 'La RPM máxima debe ser > 0.';
    if ($rpmn && $rpmm && $rpmn >= $rpmm) $errors[] = 'La RPM mínima debe ser menor que la máxima.';
    if (!$feed || $feed <= 0)            $errors[] = 'El avance máximo debe ser > 0.';
    if (!$hp   || $hp   <= 0)            $errors[] = 'La potencia debe ser > 0.';

    /* 5.5) En caso de OK, guardar en sesión y avanzar */
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
        dbg('✅ Parámetros router guardados, redirigiendo a Step 6');
        header('Location: step6.php');
        exit;
    }
}

/* -------------------------------------------------------------------------- */
/* 6)  VALORES PREVIOS PARA RE-RENDER                                         */
/* -------------------------------------------------------------------------- */
$prev = [
    'transmission_id' => $_SESSION['transmission_id'] ?? '',
    'rpm_min'         => $_SESSION['rpm_min']        ?? '',
    'rpm_max'         => $_SESSION['rpm_max']        ?? '',
    'feed_max'        => $_SESSION['feed_max']       ?? '',
    'hp'              => $_SESSION['hp']             ?? '',
];
$hasPrev = (int)$prev['transmission_id'] > 0;

/* -------------------------------------------------------------------------- */
/* 7)  RENDER HTML                                                            */
/* -------------------------------------------------------------------------- */
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;
?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8">
<title>Paso 5 – Configurá tu router</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php
  /* 7.1) Cargar CSS */
  $styles = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'assets/css/objects/step-common.css',
    'assets/css/components/_step5.css',
  ];
  include __DIR__ . '/../partials/styles.php';
?>
<?php if (!$embedded): ?>
<script><!-- Exponer BASE_URL sólo cuando no está embebido -->
  window.BASE_URL  = <?= json_encode(BASE_URL) ?>;
  window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
</script>
<?php endif; ?>
</head><body>

<!-- Placeholder simple de contenido -->
<main class="container py-4">
  <h1 class="display-6">Hola Step 5 ✅</h1>
  <p>Falta integrar el formulario; este archivo es sólo plantilla comentada.</p>
</main>

</body></html>
