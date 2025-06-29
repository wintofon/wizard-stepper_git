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

/* 4) Transmisiones desde BD
 *     Ordenadas por coeficiente de seguridad de mayor a menor
 *     y, en caso de empate, por ID ascendente
 */
$txList = $pdo->query(
    "SELECT id, name, rpm_min, rpm_max, feed_max, hp_default
      FROM transmissions
     ORDER BY coef_security DESC, id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

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
   <h2 class="step-title">Seleccione los parámetros de router</h2>
      <label class="form-label d-block">Transmisión</label>
      <div class="btn-group" role="group">
      <?php foreach ($txList as $t):
            $tid=(int)$t['id']; $chk=$tid===$prev['transmission_id']; ?>
        <input class="btn-check" type="radio" name="transmission_id"
               id="tx<?=$tid?>" value="<?=$tid?>" <?=$chk?'checked':''?>>
        <label class="btn btn-outline-primary" for="tx<?=$tid?>"
               data-rpmmin="<?=$t['rpm_min']?>" data-rpmmax="<?=$t['rpm_max']?>"
               data-feedmax="<?=$t['feed_max']?>" data-hpdef="<?=$t['hp_default']?>">
          <?php endforeach; ?>
      </div>
    </div>

    <!-- Título parámetros -->
    <h5 class="step-subtitle">Seleccione los parámetros de router</h5>

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
    </div>="<?=$t['hp_default']?>">
          <?=htmlspecialchars($t['name'])?>
        </label>
      <?php endforeach; ?>
      </div>
    </div>

    <!-- Título parámetros (misma clase que step-title) -->
    <h2 class="step-title">Seleccione los parámetros de router</h2>

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
    <div id="nextWrap" class="text-start mt-4" style="display:<?= $hasPrev ? 'block' : 'none' ?>">
      <button class="btn btn-primary btn-lg">
        Siguiente <i data-feather="arrow-right" class="ms-1"></
