<!-- public/partials/step7_expert_sliders.php -->
<div class="card mb-4 p-3">

<!-- Fz Slider -->
<div class="mb-4">
  <div class="d-flex justify-content-between px-2 mb-1 small text-muted">
    <span>Fz min</span>
    <span>Fz max</span>
  </div>
  <label>fz (mm/diente × coefSeg): <span id="fz_value">–</span></label>
  <div class="slider-container d-flex align-items-center gap-2">
    <span id="fz_min_label" style="min-width: 6ch;">– mm</span>
    <input type="range" id="fz_slider" step="0.0001" class="form-range flex-grow-1" />
    <span id="fz_max_label" style="min-width: 6ch;">– mm</span>
  </div>
</div>


  <!-- Vc Slider -->
  <div class="mb-4">
    <div class="d-flex justify-content-between px-2 mb-1 small text-muted">
      <span>Vc -25 %</span>
      <span>Vc +25 %</span>
    </div>
    <label>Vc (m/min): <span id="vc_value">–</span></label>
    <div class="slider-container d-flex align-items-center gap-2">
      <span id="vc_min_label" style="min-width: 6ch;">–</span>
      <input type="range" id="vc_slider" step="0.1" class="form-range flex-grow-1" />
      <span id="vc_max_label" style="min-width: 6ch;">–</span>
    </div>
  </div>

  <!-- Pasadas Slider -->
  <div class="mb-3">
    <div class="mb-1 small text-muted">
      Total Ap (espesor total del material): <span id="material_thickness">–</span>
    </div>
    <label>Cantidad de pasadas: <span id="pass_value">–</span></label>
    <input type="range" id="pass_slider" min="1" max="10" step="1" class="form-range" />
  </div>
<!-- AE Slider -->
<div class="mb-4">
  <div class="d-flex justify-content-between px-2 mb-1 small text-muted">
    <span>0.1 mm</span>
    <span id="ae_max_label">–</span>
  </div>
  <label>Ancho de pasada ae (mm): <span id="ae_value">–</span></label>
  <div class="slider-container d-flex align-items-center gap-2">
    <input type="range" id="ae_slider" step="0.01" class="form-range flex-grow-1" />
  </div>
  <div class="form-text small text-muted mt-1" id="ae_notice">–</div>
</div>

</div>

