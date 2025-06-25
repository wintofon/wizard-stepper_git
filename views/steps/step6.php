<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Paso 6 – Resultados CNC</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-…"
    crossorigin="anonymous">
</head>
<body>
<main class="container py-4">
  <h2 class="mb-4">Paso 6 – Ajustá y revisá tus resultados</h2>

  <form method="POST"
        action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
        class="mb-5">
    <!-- CSRF token -->
    <?php if (!empty($_SESSION['csrf_token'])): ?>
      <input type="hidden"
             name="csrf_token"
             value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <?php endif; ?>

    <!-- Slider Vc -->
    <?php
      $vcMin = number_format($vc0 * 0.5, 1, '.', '');
      $vcMax = number_format($vc0 * 1.5, 1, '.', '');
    ?>
    <div class="mb-4">
      <label for="vc-range" class="form-label">Vc (–50% … +50%)</label>
      <input type="range"
             id="vc-range"
             name="vc_adj"
             class="form-range"
             min="<?= $vcMin ?>"
             max="<?= $vcMax ?>"
             step="0.1"
             value="<?= htmlspecialchars($vc_adj) ?>"
             oninput="vcOutput.value = this.value">
      <output id="vcOutput"
              class="ms-2"
              for="vc-range"><?= htmlspecialchars($vc_adj) ?></output> m/min
    </div>

    <!-- Slider fz -->
    <?php
      $fzMinFmt = number_format($fzMin, 4, '.', '');
      $fzMaxFmt = number_format($fzMax, 4, '.', '');
    ?>
    <div class="mb-4">
      <label for="fz-range" class="form-label">
        fz (<?= $fzMinFmt ?> … <?= $fzMaxFmt ?>)
      </label>
      <input type="range"
             id="fz-range"
             name="fz_adj"
             class="form-range"
             min="<?= $fzMinFmt ?>"
             max="<?= $fzMaxFmt ?>"
             step="0.0001"
             value="<?= htmlspecialchars($fz_adj) ?>"
             oninput="fzOutput.value = this.value">
      <output id="fzOutput"
              class="ms-2"
              for="fz-range"><?= htmlspecialchars($fz_adj) ?></output> mm/diente
    </div>

    <!-- Slider ae -->
    <?php $aeMax = number_format($D, 1, '.', ''); ?>
    <div class="mb-4">
      <label for="ae-range" class="form-label">
        ae (0.1 … <?= $aeMax ?>)
      </label>
      <input type="range"
             id="ae-range"
             name="ae_adj"
             class="form-range"
             min="0.1"
             max="<?= $aeMax ?>"
             step="0.1"
             value="<?= htmlspecialchars($ae_adj) ?>"
             oninput="aeOutput.value = this.value">
      <output id="aeOutput"
              class="ms-2"
              for="ae-range"><?= htmlspecialchars($ae_adj) ?></output> mm
    </div>

    <!-- Slider pasadas -->
    <?php $maxPass = max(1, (int)ceil($thickness / max(0.001, $ae_adj))); ?>
    <div class="mb-4">
      <label for="passes-range" class="form-label">
        Pasadas (1 … <?= $maxPass ?>)
      </label>
      <input type="range"
             id="passes-range"
             name="passes"
             class="form-range"
             min="1"
             max="<?= $maxPass ?>"
             step="1"
             value="<?= htmlspecialchars($passes_adj) ?>"
             oninput="passesOutput.value = this.value">
      <output id="passesOutput"
              class="ms-2"
              for="passes-range"><?= htmlspecialchars($passes_adj) ?></output> pasadas
    </div>

    <button type="submit" class="btn btn-primary">Recalcular</button>
  </form>

  <div class="row row-cols-1 row-cols-md-2 g-4">
    <?php foreach ([
      ['Diámetro de corte','mm',$D],
      ['Filos (Z)','uds',$Z],
      ['fz','mm/diente',$fz_adj],
      ['Vc','m/min',$vc_adj],
      ['RPM','RPM',$rpm],
      ['Vf','mm/min',$vf],
      ['ae','mm',$ae_adj],
      ['ap','mm',$ap],
      ['hm','mm',$hm],
      ['MMR','mm³/min',$mmr],
      ['Fct','N',$Fct],
      ['Potencia W','W',$watts],
      ['Potencia HP','HP',$hp],
    ] as [$title, $unit, $value]): ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h6 class="card-title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h6>
            <p class="display-6 mb-0">
              <?= number_format(
                   $value,
                   strpos($unit, 'mm/diente') !== false ? 4 : 0
                 ) ?>
              <small class="fs-6 text-muted">
                <?= htmlspecialchars($unit, ENT_QUOTES) ?>
              </small>
            </p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"
        integrity="sha384-…"
        crossorigin="anonymous"></script>
<script>feather.replace()</script>
</body>
</html>
