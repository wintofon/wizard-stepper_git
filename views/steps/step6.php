<?php
/**
 * File: views/steps/auto/step6.php
 * Paso 6 embebido (completo) sin JS ni AJAX.
 * Calcula y muestra resultados CNC server-side.
 */
declare(strict_types=1);

/* 1) Seguridad y flujo */
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


?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 6 – Resultados CNC</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/wizard-stepper/assets/css/objects/step-common.css">
</head>
<body>
<main class="container py-4">
  <h2 class="step-title"><i data-feather="activity"></i> Resultados preliminares CNC</h2>
  <p class="step-desc">Parámetros calculados según tu configuración.</p>
  <div class="row row-cols-1 row-cols-md-2 g-4">
    <?php
      $rows = [
        ['fz','Avance por diente','mm/diente',$fz,4],
        ['vc','Velocidad de corte','m/min',$vc,1],
        ['rpm','Velocidad del husillo','RPM',$rpm,0],
        ['vf','Feedrate','mm/min',$vf,0],
        ['ae','Ancho de pasada','mm',$ae,2],
        ['ap','Profundidad de pasada','mm',$ap,2],
        ['hm','Espesor viruta medio','mm',$hm,4],
        ['mmr','Remoción material','mm³/min',$mmr,0],
        ['Fct','Fuerza de corte','N',$Fct,1],
        ['watts','Potencia requerida','W',$watts,0],
        ['hp','Potencia (HP)','HP',$hp,2],
      ];
      foreach ($rows as [$id, $label, $unit, $val, $dec]): ?>
        <div class="col">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h6 class="card-title text-muted mb-1"><?=$label?></h6>
              <div class="display-6 fw-bold text-primary"><?=number_format($val,$dec)?> <small class="fs-6 text-muted"><?=$unit?></small></div>
            </div>
          </div>
        </div>
    <?php endforeach; ?>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/feather-icons"></script>
<script>feather.replace()</script>
</body>
</html>
