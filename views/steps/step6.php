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

