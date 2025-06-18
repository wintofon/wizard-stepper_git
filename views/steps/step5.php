<?php
/**
 * Paso 5 (Auto) – Configurar router
 * Lee transmisiones, valida datos y avanza al Paso 6.
 */
declare(strict_types=1);

/* ──────────────────────────── 1) Sesión segura ──────────────────────────── */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 4) {
    header('Location: step1.php'); exit;
}

/* ─────────────────────────── 2) Dependencias BD ─────────────────────────── */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/debug.php';

/* ─────────────────────────── 3) CSRF token ──────────────────────────────── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* ─────────────────────────── 4) Transmisiones ───────────────────────────── */
$txList = $pdo->query("
    SELECT id, name, rpm_min, rpm_max, feed_max, hp_default
      FROM transmissions
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

$validTransmissions = [];
foreach ($txList as $t) {
    $validTransmissions[(int)$t['id']] = [
        'rpm_min'  => (int)$t['rpm_min'],
        'rpm_max'  => (int)$t['rpm_max'],
        'feed_max' => (float)$t['feed_max'],
        'hp_def'   => (float)$t['hp_default'],
    ];
}

/* ─────────────────────────── 5) Procesar POST ───────────────────────────── */
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* 5.1) CSRF */
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Token de seguridad inválido. Recargá la página e intentá de nuevo.';
    }
    /* 5.2) Paso correcto */
    if ((int)($_POST['step'] ?? 0) !== 5) {
        $errors[] = 'Paso inválido. Reiniciá el wizard.';
    }
    /* 5.3) Inputs */
    $id   = filter_input(INPUT_POST, 'transmission_id', FILTER_VALIDATE_INT);
    $rpmn = filter_input(INPUT_POST, 'rpm_min',         FILTER_VALIDATE_INT);
    $rpmm = filter_input(INPUT_POST, 'rpm_max',         FILTER_VALIDATE_INT);
    $feed = filter_input(INPUT_POST, 'feed_max',        FILTER_VALIDATE_FLOAT);
    $hp   = filter_input(INPUT_POST, 'hp',              FILTER_VALIDATE_FLOAT);

    /* 5.4) Reglas */
    if (!isset($validTransmissions[$id]))              $errors[] = 'Elegí una transmisión válida.';
    if (!$rpmn || $rpmn <= 0)                          $errors[] = 'La RPM mínima debe ser > 0.';
    if (!$rpmm || $rpmm <= 0)                          $errors[] = 'La RPM máxima debe ser > 0.';
    if ($rpmn && $rpmm && $rpmn >= $rpmm)              $errors[] = 'La RPM mínima debe ser menor que la máxima.';
    if (!$feed || $feed <= 0)                          $errors[] = 'El avance máximo debe ser > 0.';
    if (!$hp   || $hp   <= 0)                          $errors[] = 'La potencia debe ser > 0.';

    /* 5.5) Guardar y seguir */
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
        header('Location: step6.php'); exit;
    }
}

/* ─────────────────────── 6) Valores previos (si hay) ────────────────────── */
$prev = [
    'transmission_id' => $_SESSION['transmission_id'] ?? '',
    'rpm_min'         => $_SESSION['rpm_min']        ?? '',
    'rpm_max'         => $_SESSION['rpm_max']        ?? '',
    'feed_max'        => $_SESSION['feed_max']       ?? '',
    'hp'              => $_SESSION['hp']             ?? '',
];
$hasPrev = (int)$prev['transmission_id'] > 0;
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8">
<title>Paso 5 – Configurá tu router</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/wizard-stepper_git/assets/css/steps/step5.css">
</head><body>
<main class="container py-4">
  <h2 class="mb-4">Paso 5 – Configurá tu router</h2>

  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0">
      <?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e,ENT_QUOTES).'</li>'; ?>
    </ul></div>
  <?php endif; ?>

  <form id="routerForm" method="post" class="needs-validation" novalidate>
    <input type="hidden" name="step" value="5">
    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">

    <!-- 1) Transmisión -->
    <div class="mb-4">
      <label class="form-label d-block">Transmisión</label>
      <div class="btn-group" role="group">
      <?php foreach ($txList as $t):
            $tid=(int)$t['id']; $chk=$tid===$prev['transmission_id']; ?>
        <input class="btn-check" type="radio" name="transmission_id" id="tx<?=$tid?>" value="<?=$tid?>" <?=$chk?'checked':''?>>
        <label class="btn btn-outline-primary" for="tx<?=$tid?>"
               data-rpmmin="<?=$t['rpm_min']?>" data-rpmmax="<?=$t['rpm_max']?>"
               data-feedmax="<?=$t['feed_max']?>" data-hpdef="<?=$t['hp_default']?>">
          <?=htmlspecialchars($t['name'])?>
        </label>
      <?php endforeach; ?>
      </div>
    </div>

    <!-- 2) Parámetros -->
    <div id="paramSection">
      <div class="row g-3">
        <?php
          $fields=[
            ['rpm_min','RPM mínima',1],
            ['rpm_max','RPM máxima',1],
            ['feed_max','Avance máx (mm/min)',0.1],
            ['hp','Potencia (HP)',0.1],
          ];
          foreach($fields as [$id,$label,$step]):
            $val=htmlspecialchars((string)$prev[$id]); ?>
        <div class="col-md-3">
          <label for="<?=$id?>" class="form-label"><?=$label?></label>
          <input type="number" class="form-control" id="<?=$id?>" name="<?=$id?>" step="<?=$step?>" min="1" value="<?=$val?>" required>
          <div class="invalid-feedback"></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 3) Siguiente -->
    <div id="nextBtnWrap" class="text-end mt-4" style="display:<?=$hasPrev?'block':'none'?>">
      <button id="btn-next" class="btn btn-primary btn-lg">Siguiente →</button>
    </div>
  </form>
</main>

<script>
(() => {
  const radios      = document.querySelectorAll('.btn-check');
  const paramSec    = document.getElementById('paramSection');
  const nextWrap    = document.getElementById('nextBtnWrap');
  const form        = document.getElementById('routerForm');
  const inputs = {
    rpm_min:  document.getElementById('rpm_min'),
    rpm_max:  document.getElementById('rpm_max'),
    feed_max: document.getElementById('feed_max'),
    hp:       document.getElementById('hp')
  };

  /* Mostrar/ocultar sección al elegir transmisión */
  const hideParams = () => {
    paramSec.style.display = 'none';
    nextWrap.style.display = 'none';
    Object.values(inputs).forEach(i => { i.value=''; i.disabled=true; });
  };
  <?php if(!$hasPrev): ?> hideParams(); <?php endif; ?>

  radios.forEach(r => r.addEventListener('change', () => {
    const lbl = document.querySelector(`label[for="${r.id}"]`);
    const d   = lbl.dataset;
    inputs.rpm_min.value  = d.rpmmin;
    inputs.rpm_max.value  = d.rpmmax;
    inputs.feed_max.value = d.feedmax;
    if(!inputs.hp.value)  inputs.hp.value = d.hpdef;

    Object.values(inputs).forEach(i => i.disabled=false);
    paramSec.style.display = 'block';
    nextWrap.style.display = 'block';
    validate();                // chequea en vivo
  }));

  /* Validación en vivo */
  function validate() {
    let ok = true;

    const v = k => parseFloat(inputs[k].value);
    const setErr = (inp,msg) => {
      const fb = inp.nextElementSibling;
      fb.textContent = msg;
      if(msg){ inp.classList.add('is-invalid'); ok=false; }
      else   { inp.classList.remove('is-invalid'); }
    };

    setErr(inputs.rpm_min , v('rpm_min')  > 0 ? '' : 'Debe ser > 0');
    setErr(inputs.rpm_max , v('rpm_max')  > 0 ? '' : 'Debe ser > 0');
    setErr(inputs.feed_max, v('feed_max') > 0 ? '' : 'Debe ser > 0');
    setErr(inputs.hp      , v('hp')       > 0 ? '' : 'Debe ser > 0');

    if(v('rpm_min') && v('rpm_max') && v('rpm_min') >= v('rpm_max')) {
      setErr(inputs.rpm_min,'RPM min < max');
      setErr(inputs.rpm_max,'RPM min < max');
    }
    return ok;
  }

  Object.values(inputs).forEach(i => i.addEventListener('input', validate));

  form.addEventListener('submit', e => { if(!validate()){ e.preventDefault(); e.stopPropagation(); } });
})();
</script>
</body></html>
