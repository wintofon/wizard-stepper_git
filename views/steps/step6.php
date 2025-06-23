<?php
/**
 * File: views/steps/auto/step6.php
 * Iteración 0 – Punto de partida hiper-mínimo (2025-06-23)
 *
 * • Sólo verifica que el usuario venga del paso 5 (wizard_progress ≥ 5).
 * • Soporta modo embebido (cargado por load-step.php) y modo standalone.
 * • No incluye BD ni lógica pesada todavía.
 * • Imprime “Hola Step 6” y expone un objeto vacío window.step6Params.
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








// 4 ) Fragmento limpio para modo embebido -----------------------------------
if ($embedded) { ?>
<div class="step6 p-4" style="font-family:Arial,Helvetica,sans-serif;">
  <h2>Hola Step 6 ✅</h2>
  <p>Versión mínima embebida sin dependencias.</p>
</div>

<?php return; }

// 5 ) Stand-alone (debug directo) -------------------------------------------
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8">
<title>Step 6 – Hola mínimo</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head><body class="p-4" style="font-family:Arial,Helvetica,sans-serif;">
  <h2>Hola Step 6 ✅ (stand-alone)</h2>
  <p>Si ves esto directamente, el wrapper funciona y no contamina el DOM embebido.</p>

</body></html>
