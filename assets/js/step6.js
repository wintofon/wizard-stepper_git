/*
 * File: step6.js
 * Ultimate CNC Wizard Step 6 – límites estrictos y lógica rebelde ⚔️
 *
 * Main responsibilities:
 *   • Sliders de vc y fz con límites bidireccionales:
 *       – vc no puede sobrepasar RPM mínimo/máximo
 *       – fz no puede sobrepasar valores mínimos/máximos de paso por diente
 *   • Cálculo de feedrate independiente (solo fz modifica el avance)
 *   • Recálculos AJAX con CSRF para datos precisos
 *   • Radar Chart reflexivo: Vida Útil, Terminación y Potencia según fz;
 *     y actualización de Potencia vía slider de feedrate (mm³/min).
 *   • Console logs épicos en cada paso con `console.group`
 *
 * Behavior:
 *   – Aumentar fz: ↑Potencia, ↑Vida Útil, ↓Terminación
 *   – Disminuir fz: ↓Potencia, ↓Vida Útil, ↑Terminación
 *   – Slider vc ajusta RPM y feedrate simultáneamente (ambos tienen restricciones)
 *
 * Related files: ajax/step6_ajax_legacy_minimal.php
 */
/* global Chart, window */
(() => {
  'use strict';

  // ===== CONFIG & LOGGING =====
  const BASE_URL = window.BASE_URL;
  const DEBUG    = window.DEBUG ?? true;
  const STYLE    = 'color:#9c27b0;font-weight:bold';
  const log      = (...args) => DEBUG && console.log('%c[Step6🚀]', STYLE, ...args);
  const warn     = (...args) => DEBUG && console.warn('%c[Step6⚠️]', STYLE, ...args);
  const errorLog = (...args) => DEBUG && console.error('%c[Step6💥]', STYLE, ...args);
  const table    = data => DEBUG && console.table(data);
  const group    = (title, fn) => {
    if (!DEBUG) return fn();
    console.group(`%c[Step6] ${title}`, STYLE);
    try { return fn(); } finally { console.groupEnd(); }
  };

  // ===== PARAMETERS =====
  const {
    diameter: D = 0,
    flute_count: Z = 1,
    rpm_min = 0,
    rpm_max = 0,
    fr_max = Infinity,
    coef_seg = 0,
    Kc11 = 1,
    mc = 1,
    alpha = 0,
    eta = 1
  } = window.step6Params || {};
  const csrfToken = window.step6Csrf;
  log('Loaded params:', { D, Z, rpm_min, rpm_max, fr_max });

  // ===== DOM ELEMENTS =====
  const $ = id => document.getElementById(id);
  const sFz       = $('sliderFz');
  const sVc       = $('sliderVc');
  const sAe       = $('sliderAe');
  const sP        = $('sliderPasadas');
  const infoP     = $('textPasadasInfo');
  const err       = $('errorMsg');
  const out       = {
    vc:    $('outVc'),    fz:    $('outFz'),    hm: $('outHm'),
    n:     $('outN'),     vf:    $('outVf'),    hp: $('outHp'),
    mmr:   $('valueMrr'), fc:    $('valueFc'),   w:  $('valueW'),
    eta:   $('valueEta'), ae:    $('outAe'),     ap: $('outAp')
  };
  if (![sFz, sVc, sAe, sP, infoP, err].every(Boolean) || !out.vc) {
    errorLog('DOM elements missing. Aborting initStep6.');
    return;
  }

  // ===== UTILITIES =====
  function showError(msg) {
    err.textContent = msg;
    err.style.display = 'block';
    warn(msg);
  }

  function clearError() {
    err.style.display = 'none';
    err.textContent = '';
  }

  function clamp(val, min, max) {
    return Math.min(Math.max(val, min), max);
  }

  // ===== SLIDER ENHANCEMENT =====
  function enhance(slider, min, max, precision) {
    group(`Enhance ${slider.id}`, () => {
      const wrap   = slider.closest('.slider-wrap');
      const bubble = wrap.querySelector('.slider-bubble');
      slider.min   = min;
      slider.max   = max;
      slider.step  = precision;

      function update(raw) {
        const v = clamp(raw, parseFloat(min), parseFloat(max));
        slider.value = v;
        const pct = ((v - parseFloat(min)) / (parseFloat(max) - parseFloat(min))) * 100;
        wrap.style.setProperty('--val', pct);
        if (bubble) bubble.textContent = v.toFixed(precision < 1 ? 2 : 0);
      }

      slider.addEventListener('input', e => update(parseFloat(e.target.value)));
      update(parseFloat(slider.value));
      log(`${slider.id} bounded [${min}, ${max}] with step ${precision}`);
    });
  }

  // ===== SET SLIDER LIMITS =====
  group('SetSliderLimits', () => {
    // VC slider limits by rpm
    const minVc = (rpm_min * Math.PI * D) / 1000;
    const maxVc = (rpm_max * Math.PI * D) / 1000;
    // FZ slider limits from params
    const fzParams = window.step6Params || {};
    const minFz = fzParams.fz_min0 ?? 0.001;
    const maxFz = fzParams.fz_max0 ?? 1;

    enhance(sVc, minVc, maxVc, 1);
    enhance(sFz, minFz, maxFz, 0.001);

    log('Slider limits set: VC:[minVc,maxVc], FZ:[minFz,maxFz]');
  });

  // ===== RADAR INITIALIZATION =====
  let radar = window.radarChartInstance;
  const canvas = $('radarChart');
  if (canvas) {
    if (radar) radar.destroy();
    radar = new Chart(canvas.getContext('2d'), {
      type: 'radar',
      data: {
        labels: ['Vida útil', 'Terminación', 'Potencia'],
        datasets: [{
          data: [50, 50, 50],
          backgroundColor: 'rgba(156,39,176,0.3)',
          borderColor:     'rgba(156,39,176,0.8)',
          borderWidth: 2
        }]
      },
      options: {
        scales: {
          r: {
            beginAtZero: true,
            suggestedMax: 100,
            ticks: { stepSize: 20 }
          }
        },
        plugins: { legend: { display: false } }
      }
    });
    window.radarChartInstance = radar;
    log('Radar initialized with neutral start values');
  } else warn('Radar canvas element not found');

  // ===== RECALCULATION LOGIC =====
  function computeFeed(vc, fz) {
    return group('computeFeed', () => {
      const rpm  = (vc * 1000) / (Math.PI * D);
      const feed = rpm * fz * Z;
      log('Computed:', { rpm: rpm.toFixed(0), feed: feed.toFixed(2) });
      return { rpm, feed };
    });
  }

  const thickness = parseFloat(sP.dataset.thickness) || 0;
  function updatePasses() {
    const maxP = Math.ceil(thickness / parseFloat(sAe.value));
    sP.min = 1;
    sP.max = maxP;
    if (+sP.value > maxP) sP.value = maxP;
    infoP.textContent = `${sP.value} pasadas de ${(thickness / +sP.value).toFixed(2)} mm`;
  }

  let timer;
  function scheduleRecalc() {
    clearError();
    clearTimeout(timer);
    timer = setTimeout(recalc, 300);
  }

  function onVCChange() {
    group('vcChange', () => {
      const vcVal = clamp(+sVc.value, +sVc.min, +sVc.max);
      sVc.value = vcVal;
      const { rpm, feed } = computeFeed(vcVal, +sFz.value);
      if (rpm < rpm_min || rpm > rpm_max) {
        showError(`RPM fuera de rango: ${rpm.toFixed(0)}`);
        return;
      }
      if (feed > fr_max) {
        showError(`Feed > límite (${fr_max})`);
        return;
      }
      // Update output
      out.n.textContent = rpm.toFixed(0);
      out.vf.textContent = feed.toFixed(0);
      scheduleRecalc();
    });
  }

  function onFZChange() {
    group('fzChange', () => {
      const fzVal = clamp(+sFz.value, +sFz.min, +sFz.max);
      sFz.value = fzVal;
      const { rpm, feed } = computeFeed(+sVc.value, fzVal);
      // Only feed validation
      if (feed > fr_max) {
        showError(`Feed > límite (${fr_max})`);
        return;
      }
      // Update feed display
      out.fz.textContent = fzVal.toFixed(3);
      out.vf.textContent = feed.toFixed(0);
      scheduleRecalc();
    });
  }

  sVc.addEventListener('input', onVCChange);
  sFz.addEventListener('input', onFZChange);
  sAe.addEventListener('input', () => { updatePasses(); scheduleRecalc(); });
  sP.addEventListener('input', () => { updatePasses(); scheduleRecalc(); });

  async function recalc() {
    return group('recalc', async () => {
      const payload = {
        fz:     +sFz.value,
        vc:     +sVc.value,
        ae:     +sAe.value,
        passes: +sP.value,
        thickness,
        D, Z,
        params: { fr_max, coef_seg, Kc11, mc, alpha, eta }
      };
      table(payload);
      try {
        const res = await fetch(`${BASE_URL}/ajax/step6_ajax_legacy_minimal.php`, {
          method:  'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
          body:    JSON.stringify(payload),
          cache:   'no-store'
        });
        if (!res.ok) throw new Error(res.status === 403 ? 'Sesión expirada' : `HTTP ${res.status}`);
        const js = await res.json();
        if (!js.success) throw new Error(js.error || 'Error en servidor');
        const d = js.data;
        // Paint core results
        out.vc.textContent    = `${d.vc} m/min`;
        out.fz.textContent    = `${d.fz} mm/tooth`;
        out.hm.textContent    = `${d.hm} mm`;
        out.n.textContent     = d.n;
        out.vf.textContent    = `${d.vf} mm/min`;
        out.hp.textContent    = `${d.hp} HP`;
        out.mmr.textContent   = d.mmr;
        out.fc.textContent    = d.fc;
        out.w.textContent     = d.watts;
        out.eta.textContent   = `${d.etaPercent}%`;
        out.ae.textContent    = d.ae.toFixed(2);
        out.ap.textContent    = d.ap.toFixed(3);

        // Radar logic: update life/polish/power based on d.radar
        if (radar && Array.isArray(d.radar) && d.radar.length === 3) {
          radar.data.datasets[0].data        = d.radar;
          const maxRadar = Math.max(...d.radar, 100) * 1.1;
          radar.options.scales.r.suggestedMax = maxRadar;
          radar.update();
          log('Radar updated:', d.radar);
        }
      } catch (err) {
        errorLog('recalc error:', err);
        showError(err.message);
      }
    });
  }

  // ===== INITIALIZATION =====
  log('initStep6 started');
  updatePasses();
  recalc();
  window.addEventListener('error', e => showError(e.message));
})();
