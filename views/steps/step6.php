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
div class="content-main">
  <div class="container py-4">
    <h2 class="step-title"><i data-feather="bar-chart-2"></i> Resultados</h2>
    <p class="step-desc">Ajustá los parámetros y revisá los datos de corte.</p>
  <!-- BLOQUE CENTRAL -->
  <div class="row gx-3 mb-4 cards-grid">
    <div class="col-12 col-lg-4 mb-3 area-tool">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3">
          <span>#<?= $serialNumber ?> – <?= $toolCode ?></span>
        </div>
        <div class="card-body text-center p-4">
          <?php if ($imageURL): ?>
            <img src="<?= htmlspecialchars($imageURL, ENT_QUOTES) ?>"
                 alt="Imagen principal herramienta"
                 class="tool-image mx-auto d-block">
          <?php else: ?>
            <div class="text-secondary">Sin imagen disponible</div>
          <?php endif; ?>
          <div class="tool-name mt-3"><?= $toolName ?></div>
          <div class="tool-type"><?= $toolType ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- AJUSTES / RESULTADOS / RADAR -->
  <div class="row gx-3 mb-4 cards-grid">
    <!-- Ajustes -->
    <div class="col-12 col-lg-4 mb-3 area-sliders">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"><h5 class="mb-0">Ajustes</h5></div>
        <div class="card-body p-4">
          <!-- fz -->
          <div class="mb-4 px-2">
            <label for="sliderFz" class="form-label">fz (mm/tooth)</label>
            <div class="slider-wrap">
              <input type="range" id="sliderFz" class="form-range"
                     min="<?= number_format($fzMinDb,4,'.','') ?>"
                     max="<?= number_format($fzMaxDb,4,'.','') ?>"
                     step="0.0001"
                     value="<?= number_format($baseFz,4,'.','') ?>">
              <span class="slider-bubble"></span>
            </div>
            <div class="text-end small text-secondary mt-1">
              <span><?= number_format($fzMinDb,4,'.','') ?></span> –
              <strong id="valFz"><?= number_format($baseFz,4,'.','') ?></strong> –
              <span><?= number_format($fzMaxDb,4,'.','') ?></span>
            </div>
          </div>
          <!-- Vc -->
          <div class="mb-4 px-2">
            <label for="sliderVc" class="form-label">Vc (m/min)</label>
            <div class="slider-wrap">
              <input type="range" id="sliderVc" class="form-range"
                     min="<?= number_format($vcMinDb,1,'.','') ?>"
                     max="<?= number_format($vcMaxDb,1,'.','') ?>"
                     step="0.1"
                     value="<?= number_format($baseVc,1,'.','') ?>">
              <span class="slider-bubble"></span>
            </div>
            <div class="text-end small text-secondary mt-1">
              <span><?= number_format($vcMinDb,1,'.','') ?></span> –
              <strong id="valVc"><?= number_format($baseVc,1,'.','') ?></strong> –
              <span><?= number_format($vcMaxDb,1,'.','') ?></span>
            </div>
          </div>
          <!-- ae -->
          <div class="mb-4 px-2">
            <label for="sliderAe" class="form-label">
              ae (mm) <small>(ancho de pasada)</small>
            </label>
            <div class="slider-wrap">
              <input type="range" id="sliderAe" class="form-range"
                     min="0.1"
                     max="<?= number_format($diameterMb,1,'.','') ?>"
                     step="0.1"
                     value="<?= number_format($diameterMb * 0.5,1,'.','') ?>">
              <span class="slider-bubble"></span>
            </div>
            <div class="text-end small text-secondary mt-1">
              <span>0.1</span> –
              <strong id="valAe"><?= number_format($diameterMb * 0.5,1,'.','') ?></strong> –
              <span><?= number_format($diameterMb,1,'.','') ?></span>
            </div>
          </div>
          <!-- Pasadas -->
          <div class="mb-4 px-2">
            <label for="sliderPasadas" class="form-label">Pasadas</label>
            <div class="slider-wrap">
              <input type="range" id="sliderPasadas" class="form-range"
                     min="1" max="1" step="1"
                     value="1"
                     data-thickness="<?= htmlspecialchars((string)$thickness, ENT_QUOTES) ?>">
              <span class="slider-bubble"></span>
            </div>
            <div id="textPasadasInfo" class="small text-secondary mt-1">
              1 pasada de <?= number_format($thickness, 2) ?> mm
            </div>
            <div id="errorMsg" class="text-danger mt-2 small"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Resultados -->
    <div class="col-12 col-lg-4 mb-3 area-results">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"><h5 class="mb-0">Resultados</h5></div>
        <div class="card-body p-4">
          <div class="results-compact mb-4 d-flex gap-2">
            <div class="result-box text-center flex-fill">
              <div class="param-label">
                Feedrate<br><small>(<span class="param-unit">mm/min</span>)</small>
              </div>
              <div id="outVf" class="fw-bold display-6"><?= $outVf ?></div>
            </div>
            <div class="result-box text-center flex-fill">
              <div class="param-label">
                Cutting speed<br><small>(<span class="param-unit">RPM</span>)</small>
              </div>
              <div id="outN" class="fw-bold display-6"><?= $outN ?></div>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>Vc</small>
            <div><span id="outVc" class="fw-bold"><?= $outVc ?></span> <span class="param-unit">m/min</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>fz</small>
            <div><span id="outFz" class="fw-bold">--</span> <span class="param-unit">mm/tooth</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>Ap</small>
            <div><span id="outAp" class="fw-bold">--</span> <span class="param-unit">mm</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>Ae</small>
            <div><span id="outAe" class="fw-bold">--</span> <span class="param-unit">mm</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>hm</small>
            <div><span id="outHm" class="fw-bold">--</span> <span class="param-unit">mm</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <small>Hp</small>
            <div><span id="outHp" class="fw-bold">--</span> <span class="param-unit">HP</span></div>
          </div>
          <!-- Métricas secundarias -->
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="param-label">
              MMR<br><small>(<span class="param-unit">mm³/min</span>)</small>
            </div>
            <div id="valueMrr" class="fw-bold">--</div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="param-label">
              Fc<br><small>(<span class="param-unit">N</span>)</small>
            </div>
            <div id="valueFc" class="fw-bold">--</div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="param-label">
              Potencia<br><small>(<span class="param-unit">W</span>)</small>
            </div>
            <div id="valueW" class="fw-bold">--</div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="param-label">
              η<br><small>(<span class="param-unit">%</span>)</small>
            </div>
            <div id="valueEta" class="fw-bold">--</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Radar Chart -->
    <div class="col-12 col-lg-4 mb-3 area-radar">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"><h5 class="mb-0">Distribución Radar</h5></div>
        <div class="card-body p-4 d-flex justify-content-center align-items-center">
          <canvas id="radarChart" width="300" height="300"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- ESPECIFICACIONES / CONFIGURACIÓN / NOTAS -->
  <div class="row gx-3 mb-4 cards-grid">
    <!-- Especificaciones -->
    <div class="col-12 col-lg-4 mb-3">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"
             data-bs-toggle="collapse"
             data-bs-target="#specCollapse"
             aria-expanded="true">
          <h5 class="mb-0">Especificaciones Técnicas</h5>
        </div>
        <div id="specCollapse" class="collapse show">
          <div class="card-body p-4">
            <div class="row gx-0 align-items-center">
              <div class="col-12 col-lg-7 px-2 mb-4 mb-lg-0">
                <ul class="spec-list mb-0 px-2">
                  <li><span>Diámetro de corte (d1):</span>
                      <span><?= number_format($diameterMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Diámetro del vástago:</span>
                      <span><?= number_format($shankMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Longitud de corte:</span>
                      <span><?= number_format($cutLenMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Longitud de filo:</span>
                      <span><?= number_format($fluteLenMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Longitud total:</span>
                      <span><?= number_format($fullLenMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Número de filos (Z):</span><span><?= $fluteCountMb ?></span></li>
                  <li><span>Tipo de punta:</span><span><?= $toolType ?></span></li>
                  <li><span>Recubrimiento:</span><span><?= $coatingMb ?></span></li>
                  <li><span>Material fabricación:</span><span><?= $materialMb ?></span></li>
                  <li><span>Marca:</span><span><?= $brandMb ?></span></li>
                  <li><span>País de origen:</span><span><?= $madeInMb ?></span></li>
                </ul>
              </div>
              <div class="col-12 col-lg-5 px-2 d-flex justify-content-center align-items-center">
                <?php if ($vectorURL): ?>
                  <img src="<?= htmlspecialchars($vectorURL, ENT_QUOTES) ?>"
                       alt="Imagen vectorial herramienta"
                       class="vector-image mx-auto d-block">
                <?php else: ?>
                  <div class="text-secondary">Sin imagen vectorial</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Configuración -->
    <div class="col-12 col-lg-4 mb-3">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"
             data-bs-toggle="collapse"
             data-bs-target="#configCollapse"
             aria-expanded="true">
          <h5 class="mb-0">Configuración de Usuario</h5>
        </div>
        <div id="configCollapse" class="collapse show">
          <div class="card-body p-4">
            <div class="config-section mb-3">
              <div class="config-section-title">Material</div>
              <div class="config-item">
                <div class="label-static">Categoría padre:</div>
                <div class="value-static"><?= $materialParent ?></div>
              </div>
              <div class="config-item">
                <div class="label-static">Material a mecanizar:</div>
                <div class="value-static"><?= $materialName ?></div>
              </div>
            </div>
            <div class="section-divider"></div>
            <div class="config-section mb-3">
              <div class="config-section-title">Estrategia</div>
              <div class="config-item">
                <div class="label-static">Categoría padre estr.:</div>
                <div class="value-static"><?= $strategyParent ?></div>
              </div>
              <div class="config-item">
                <div class="label-static">Estrategia de corte:</div>
                <div class="value-static"><?= $strategyName ?></div>
              </div>
            </div>
            <div class="section-divider"></div>
            <div class="config-section">
              <div class="config-section-title">Máquina</div>
              <div class="config-item">
                <div class="label-static">Espesor del material:</div>
                <div class="value-static"><?= number_format($thickness,2) ?> <span class="param-unit">mm</span></div>
              </div>
              <div class="config-item">
                <div class="label-static">Tipo de transmisión:</div>
                <div class="value-static"><?= $transName ?></div>
              </div>
              <div class="config-item">
                <div class="label-static">Feedrate máximo:</div>
                <div class="value-static"><?= number_format($frMax,0) ?> <span class="param-unit">mm/min</span></div>
              </div>
              <div class="config-item">
                <div class="label-static">RPM mínima:</div>
                <div class="value-static"><?= number_format($rpmMin,0) ?> <span class="param-unit">rev/min</span></div>
              </div>
              <div class="config-item">
                <div class="label-static">RPM máxima:</div>
                <div class="value-static"><?= number_format($rpmMax,0) ?> <span class="param-unit">rev/min</span></div>
              </div>
              <div class="config-item">
                <div class="label-static">Potencia disponible:</div>
                <div class="value-static"><?= number_format($powerAvail,1) ?> <span class="param-unit">HP</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Notas -->
    <div class="col-12 col-lg-4 mb-3">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"><h5 class="mb-0">Notas Adicionales</h5></div>
        <div class="card-body p-4">
          <?php if ($notesArray): ?>
            <ul class="notes-list mb-0">
              <?php foreach ($notesArray as $note): ?>
                <li class="mb-2 d-flex align-items-start">
                  <i data-feather="file-text" class="me-2"></i>
                  <div><?= htmlspecialchars($note, ENT_QUOTES) ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <div class="text-secondary">No hay notas adicionales para esta herramienta.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
</div><!-- .content-main -->

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
