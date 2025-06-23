<?php
/**
 * File: views/steps/auto/step6.php
 * ---------------------------------------------------------------------------
 * Paso 6 (Auto) – Resultados expertos (vista embebida-safe)
 * ---------------------------------------------------------------------------
 *  • NO rompe el DOM: siempre imprime <div class="step6">, incluso ante error.
 *  • Renderiza <html><head>…</html> solo si !$embedded (acceso directo).
 *  • Emite token CSRF pero NO lo valida aquí (no hay POST esperado).
 *  • Captura fallas (BD, JSON, params) con renderStep6Error() en pantalla.
 */

declare(strict_types=1);

/* ------------------------------------------------------------------ */
/* 0) SESIÓN SEGURA                                                   */
/* ------------------------------------------------------------------ */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

/* ------------------------------------------------------------------ */
/* 1) DEPENDENCIAS                                                    */
/* ------------------------------------------------------------------ */
if (!defined('BASE_URL') && !getenv('BASE_URL')) {
    putenv('BASE_URL=' . rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/'));
}
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../includes/wizard_helpers.php';
require_once __DIR__ . '/../../includes/db.php';        // $pdo
require_once __DIR__ . '/../../includes/debug.php';     // dbg() (stub si no existe)

use App\Controller\ExpertResultController;
use App\Model\ToolModel;

/* ------------------------------------------------------------------ */
/* 2) FLAGS                                                           */
/* ------------------------------------------------------------------ */
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

/* ------------------------------------------------------------------ */
/* 3) HELPER – imprime error SIN cortar el DOM                         */
/* ------------------------------------------------------------------ */
function renderStep6Error(string $msg, int $code = 500): void
{
    http_response_code($code);
    echo '<div class="step6 container py-4">'
       . '<div class="alert alert-danger my-3">' . htmlspecialchars($msg) . '</div>'
       . '</div>';
}

/* ------------------------------------------------------------------ */
/* 4) TOKEN CSRF (emitir)                                             */
/* ------------------------------------------------------------------ */
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

/* ------------------------------------------------------------------ */
/* 5) VALIDAR CONTEXTO PREVIO                                         */
/* ------------------------------------------------------------------ */
$need = ['tool_table','tool_id','material','transmission_id',
         'rpm_min','rpm_max','feed_max','thickness','strategy_id','hp'];
if ($miss = array_filter($need, fn($k) => empty($_SESSION[$k]))) {
    renderStep6Error('Faltan datos en sesión: ' . implode(', ', $miss), 400);
    return;
}

/* ------------------------------------------------------------------ */
/* 6) OBTENER DATOS HERRAMIENTA + PARÁMS                              */
/* ------------------------------------------------------------------ */
try {
    $toolData = ToolModel::getTool($pdo, $_SESSION['tool_table'], (int)$_SESSION['tool_id']);
    if (!$toolData) {
        renderStep6Error('Herramienta no encontrada.', 404); return;
    }
    $params = ExpertResultController::getResultData($pdo, $_SESSION);
    $jsonParams = json_encode($params,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    dbg('step6 error: ' . $e->getMessage());
    renderStep6Error('Error interno al cargar los parámetros.'); return;
}

/* ------------------------------------------------------------------ */
/* 7) (Opcional) OTROS QUERIES – transmisiones, etc.                  */
/* ------------------------------------------------------------------ */
// … tu lógica adicional …

/* ------------------------------------------------------------------ */
/* 8)  SALIDA HTML                                                    */
/* ------------------------------------------------------------------ */
if (!$embedded): ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Paso 6 – Resultados</title>
  <!-- CSS global -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/step-common.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/step6.css') ?>">
  <script>window.BASE_URL=<?= json_encode(BASE_URL) ?>;</script>
</head>
<body>
<?php endif; ?>

<div class="step6 container py-4"><!-- Siempre presente -->
  <h2 class="mb-4">Dashboard de parámetros</h2>
  <!-- acá va tu HTML de resultados, sliders, radar, etc. -->
  <pre class="bg-light p-3">JSON params: <?= htmlspecialchars($jsonParams, ENT_QUOTES) ?></pre>
</div>

<script>
  /* Exponer params a JS */
  window.step6Params = <?= $jsonParams ?>;
  window.step6Csrf   = '<?= $csrfToken ?>';
  /* Inits JS específicos (feather.replace, Chart, etc.) */
  if (window.feather) requestAnimationFrame(() => window.feather.replace());
</script>

<?php if (!$embedded): ?>
<script src="<?= asset('assets/js/bootstrap.bundle.min.js') ?>" defer></script>
<script src="<?= asset('node_modules/feather-icons/dist/feather.min.js') ?>" defer></script>
</body></html>
<?php endif;
