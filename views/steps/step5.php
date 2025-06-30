<?php
/**
 * File: step5.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * @TODO Extend documentation.
 *
 * Paso 5 (Auto) – Configurar router
 *   • Protección CSRF
 *   • Control de flujo (wizard_progress)
 *   • Validación de campos:
 *       – rpm_min > 0
 *       – rpm_max > 0
 *       – rpm_min < rpm_max
 *       – feed_max > 0
 *       – hp       > 0
 *   • Guarda en sesión bajo las claves:
 *       trans_id, rpm_min, rpm_max, feed_max, hp, wizard_progress
 *   • Redirige a step6.php
 */
declare(strict_types=1);

// --------------------------------------------------
// 1) Sesión segura y control de flujo
// --------------------------------------------------
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

// --------------------------------------------------
// 2) Dependencias
// --------------------------------------------------
require_once __DIR__ . '/../../includes/db.php';     // $pdo
require_once __DIR__ . '/../../includes/debug.php';  // debug helpers

// --------------------------------------------------
// 3) CSRF token
// --------------------------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// --------------------------------------------------
// 4) Transmisiones desde BD
// --------------------------------------------------
$txList = $pdo->query("
    SELECT id, name, rpm_min, rpm_max, feed_max, hp_default
      FROM transmissions
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

// Map para validación rápida
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
// 5) Procesar POST
// --------------------------------------------------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Token de seguridad inválido. Recargá la página e intentá de nuevo.';
    }
    // Paso correcto
    if ((int)($_POST['step'] ?? 0) !== 5) {
        $errors[] = 'Paso inválido. Reiniciá el asistente.';
    }

    // Sanitización
    $id   = filter_input(INPUT_POST, 'trans_id',        FILTER_VALIDATE_INT);
    $rpmn = filter_input(INPUT_POST, 'rpm_min',        FILTER_VALIDATE_INT);
    $rpmm = filter_input(INPUT_POST, 'rpm_max',        FILTER_VALIDATE_INT);
    $feed = filter_input(INPUT_POST, 'feed_max',       FILTER_VALIDATE_FLOAT);
    $hp   = filter_input(INPUT_POST, 'hp',             FILTER_VALIDATE_FLOAT);

    // Validaciones de negocio
    if (!isset($validTx[$id]))            $errors[] = 'Elegí una transmisión válida.';
    if (!$rpmn || $rpmn <= 0)             $errors[] = 'La RPM mínima debe ser > 0.';
    if (!$rpmm || $rpmm <= 0)             $errors[] = 'La RPM máxima debe ser > 0.';
    if ($rpmn && $rpmm && $rpmn >= $rpmm) $errors[] = 'La RPM mínima debe ser menor que la máxima.';
    if (!$feed || $feed <= 0)             $errors[] = 'El avance máximo debe ser > 0.';
    if (!$hp   || $hp   <= 0)             $errors[] = 'La potencia debe ser > 0.';

    // Si todo OK, guardo y avanzo
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
// 6) Valores previos
// --------------------------------------------------
$prev = [
    'trans_id'   => $_SESSION['trans_id']   ?? '',
    'rpm_min'    => $_SESSION['rpm_min']    ?? '',
    'rpm_max'    => $_SESSION['rpm_max']    ?? '',
    'feed_max'   => $_SESSION['feed_max']   ?? '',
    'hp'         => $_SESSION['hp']         ?? '',
];
$hasPrev = (int)$prev['trans_id'] > 0;
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
    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken,ENT_QUOTES)?>">

    <!-- Transmisión -->
    <div class="mb-4">
      <label class="form-label d-block">Transmisión</label>
      <div class="btn-group" role="group">
      <?php foreach ($txList as $t):
            $tid   = (int)$t['id'];
            $chk   = $tid === (int)$prev['trans_id'];
      ?>
        <input class="btn-check" type="radio"
               name="trans_id"
               id="tx<?= $tid ?>"
               value="<?= $tid ?>"
               <?= $chk ? 'checked' : '' ?>>
        <label class="btn btn-outline-primary"
               for="tx<?= $tid ?>"
               data-rpmmin="<?= $t['rpm_min'] ?>"
               data-rpmmax="<?= $t['rpm_max'] ?>"
               data-feedmax="<?= $t['feed_max'] ?>"
               data-hpdef="<?= $t['hp_default'] ?>">
          <?= htmlspecialchars($t['name'], ENT_QUOTES) ?>
        </label>
      <?php endforeach; ?>
      </div>
    </div>

    <!-- Parámetros -->
    <div id="paramSection">
      <div class="row g-3">
        <?php
          $fields = [
            ['rpm_min','RPM mínima',1,'rpm'],
            ['rpm_max','RPM máxima',1,'rpm'],
            ['feed_max','Avance máx (mm/min)',0.1,'mm/min'],
            ['hp','Potencia (HP)',0.1,'HP'],
          ];
          foreach ($fields as [$id,$label,$step,$unit]):
        ?>
        <div class="col-md-3">
          <label for="<?= $id ?>" class="form-label"><?= $label ?></label>
          <div class="input-group has-validation">
            <input type="number"
                   class="form-control"
                   id="<?= $id ?>"
                   name="<?= $id ?>"
                   step="<?= $step ?>"
                   min="1"
                   value="<?= htmlspecialchars($prev[$id] ?? '', ENT_QUOTES) ?>"
                   required>
            <span class="input-group-text"><?= $unit ?></span>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Botón -->
    <div id="nextWrap" class="text-start mt-4" style="display:<?= $hasPrev ? 'block' : 'none' ?>">
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

  function smoothTo(el) {
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function hideParams() {
    paramSec.style.display = 'none';
    nextWrap.style.display = 'none';
    Object.values(inputs).forEach(i => { i.value = ''; i.disabled = true; });
  }
  <?php if (!$hasPrev): ?> hideParams(); <?php endif; ?>

  radios.forEach(r => r.addEventListener('change', () => {
    const d = document.querySelector(`label[for="${r.id}"]`).dataset;
    inputs.rpm_min.value  = d.rpmmin;
    inputs.rpm_max.value  = d.rpmmax;
    inputs.feed_max.value = d.feedmax;
    if (!inputs.hp.value) inputs.hp.value = d.hpdef;

    Object.values(inputs).forEach(i => i.disabled = false);
    paramSec.style.display = 'block';
    smoothTo(paramSec);
    validate({ scroll: false });
  }));

  function validate({ scroll = true } = {}) {
    let ok = true;
    const v = k => parseFloat(inputs[k].value) || 0;
    const fb = (inp, msg) => {
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
    if (ok && scroll) smoothTo(nextWrap);
    return ok;
  }

  Object.values(inputs).forEach(i => i.addEventListener('input', () => validate()));
  form.addEventListener('submit', e => {
    if (!validate()) {
      e.preventDefault();
      e.stopPropagation();
    }
  });
})();
</script>
</body></html>
