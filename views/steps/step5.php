```php
<?php
/**
 * File: step5.php
 *
 * Paso 5 (Auto) – Configurar router CNC
 *   • Protección CSRF
 *   • Control de flujo (wizard_progress)
 *   • Validación de campos:
 *       – rpm_min > 0
 *       – rpm_max > 0
 *       – rpm_min < rpm_max
 *       – feed_max > 0
 *       – hp > 0
 *   • Guarda en sesión: trans_id, rpm_min, rpm_max, feed_max, hp, wizard_progress
 *   • Redirige a step6.php
 */
declare(strict_types=1);

// --------------------------------------------------
// 1) Sesión segura y control de flujo
// --------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,     // Solo HTTPS
        'cookie_httponly' => true,     // No accesible desde JS
        'cookie_samesite' => 'Strict', // Previene CSRF
    ]);
}
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 4) {
    header('Location: step1.php');
    exit;
}

// --------------------------------------------------
// 2) Dependencias (BD, debug)
// --------------------------------------------------
require_once __DIR__ . '/../../includes/db.php';     // instancia $pdo
require_once __DIR__ . '/../../includes/debug.php';  // helpers de debug

// --------------------------------------------------
// 3) Generar/recuperar CSRF token
// --------------------------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// --------------------------------------------------
// 4) Obtener transmisiones desde la BD
//    – Incluye coef_security (nombre real en la tabla)
//    – Ordena por coef_security DESC, luego por id ASC
// --------------------------------------------------
$txList = $pdo->query("SELECT id, name, rpm_min, rpm_max, feed_max, hp_default, coef_security
    FROM transmissions
    ORDER BY coef_security DESC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Map para validación rápida en POST
$validTx = [];
foreach ($txList as $t) {
    $validTx[(int)$t['id']] = [
        'rpm_min'  => (int)$t['rpm_min'],
        'rpm_max'  => (int)$t['rpm_max'],
        'feed_max' => (float)$t['feed_max'],
        'hp_def'   => (float)$t['hp_default'],
    ];
}

// --------------------------------------------------
// 5) Procesar formulario POST
// --------------------------------------------------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 5.1) Verificar CSRF
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Token de seguridad inválido. Recargá la página e intentá de nuevo.';
    }
    // 5.2) Verificar paso
    if ((int)($_POST['step'] ?? 0) !== 5) {
        $errors[] = 'Paso inválido. Reiniciá el asistente.';
    }

    // 5.3) Recuperar valores del POST
    $id   = filter_input(INPUT_POST, 'trans_id',    FILTER_VALIDATE_INT);
    $rpmn = filter_input(INPUT_POST, 'rpm_min',     FILTER_VALIDATE_INT);
    $rpmm = filter_input(INPUT_POST, 'rpm_max',     FILTER_VALIDATE_INT);
    $feed = filter_input(INPUT_POST, 'feed_max',    FILTER_VALIDATE_FLOAT);
    $hp   = filter_input(INPUT_POST, 'hp',          FILTER_VALIDATE_FLOAT);

    // 5.4) Validaciones de negocio
    if (!isset($validTx[$id]))            $errors[] = 'Elegí una transmisión válida.';
    if (!$rpmn || $rpmn <= 0)             $errors[] = 'La RPM mínima debe ser > 0.';
    if (!$rpmm || $rpmm <= 0)             $errors[] = 'La RPM máxima debe ser > 0.';
    if ($rpmn && $rpmm && $rpmn >= $rpmm) $errors[] = 'La RPM mínima debe ser menor que la máxima.';
    if (!$feed || $feed <= 0)             $errors[] = 'El avance máximo debe ser > 0.';
    if (!$hp   || $hp   <= 0)             $errors[] = 'La potencia debe ser > 0.';

    // 5.5) Si no hay errores, guardar en sesión y avanzar
    if (empty($errors)) {
        $_SESSION += [
            'trans_id'        => $id,
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

// --------------------------------------------------
// 6) Preparar valores previos para el formulario
// --------------------------------------------------
$prev = [
    'trans_id' => $_SESSION['trans_id'] ?? '',
    'rpm_min'  => $_SESSION['rpm_min']   ?? '',
    'rpm_max'  => $_SESSION['rpm_max']   ?? '',
    'feed_max' => $_SESSION['feed_max']  ?? '',
    'hp'       => $_SESSION['hp']        ?? '',
];
$hasPrev = (int)$prev['trans_id'] > 0;
?>
```
