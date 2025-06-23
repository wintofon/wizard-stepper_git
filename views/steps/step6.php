<?php declare(strict_types=1);

/**
 * Paso 6 (mini) – Resultados calculados
 * Requiere que el paso 5 haya guardado los datos en sesión.
 */

/* 1) Sesión segura y flujo */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 5) {
    header('Location: step1.php');
    exit;
}


/* 2) Dependencias */
require_once __DIR__ . '/../../includes/db.php';                 // → $pdo


/* 3) CSRF */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* 4) Datos calculados */
$tool = ToolModel::getTool($pdo, $_SESSION['tool_table'], $_SESSION['tool_id']) ?? [];
$par  = ExpertResultController::getResultData($pdo, $_SESSION);

$code = htmlspecialchars($tool['tool_code'] ?? '—');
$name = htmlspecialchars($tool['name']      ?? '—');
$rpm  = number_format($par['rpm0'],  0, '.', '');
$vf   = number_format($par['feed0'], 0, '.', '');
$vc   = number_format($par['vc0'],   1, '.', '');
$fz   = number_format($par['fz0'],   4, '.', '');
?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8">
<title>Paso 6 – Resultados</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/objects/step-common.css">
<link rel="stylesheet" href="assets/css/components/_step6.css"><!-- tu hoja mínima -->
</head><body>

hola
</body></html>
