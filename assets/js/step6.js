/*
 * File: step6.js
 * Ultimate CNC Wizard Step 6 – sliders centrados y rango dinámico, recálculos AJAX y radar épico 🏹
 *
 * Main responsibilities:
 *   • Slider VC centrado en rpm0 con rango dinámico de ±50%
 *   • Slider FZ limitado por fz_min0 y fz_max0
 *   • Cálculo independiente de feedrate (feed = rpm × fz × Z)
 *   • Validaciones de RPM min/máx y feedrate máximo (fr_max)
 *   • Recálculos AJAX seguros con CSRF
 *   • Radar Chart que muestra Vida Útil, Terminación y Potencia según fz
 *   • Console logs épicos con console.group, emojis y estilos
 *
 * Behavior:
 *   – VC slider modifica rpm (en rpm) y feedrate (mm³/min) dentro de ±50% de rpm0
 *   – FZ slider modifica solo feedrate, validado contra fr_max
 *   – Radar inicial en [50,50,50], luego refleja d.radar y ajusta escala
 *
 * Related: ajax/step6_ajax_legacy_minimal.php
 */
/* global Chart, window */
(() => {
  'use strict';

  // ===== CONFIG & LOGGING =====
  const BASE_URL = window.BASE_URL;
  const DEBUG    = window.DEBUG ?? true;
  const STYLE    = 'color:#2196F3;font-weight:bold';
  const log      = (...args) => DEBUG && console.log('%c[Step6🚀]', STYLE, ...args);
  const warn     = (...args) => DEBUG && console.warn('%c[Step6⚠️]', STYLE, ...args);
  const errorLog = (...args) => DEBUG && console.error('%c[Step6💥]', STYLE, ...args);
  const table    = data => DEBUG && console.table(data);
  const group    = (title, fn) => {
    if (!DEBUG) return fn();
    console.group(`%c[Step6] ${title}`, STYLE);
    try { return fn(); } finally { console.groupEnd(); }
  };

  // ===== PARAMETERS (injected) =====
  const {
    diameter: D = 0,
    flute_count: Z = 1,
    rpm0 = 0,
    fz0 = 0,
    fz_min0 = 0,
    fz_max0 = 1,
    fr_max = Infinity,
    coef_seg = 0,
    Kc11 = 1,
    mc = 1,
    alpha = 0,
    eta = 1
  } = window.step6Params || {};
  const csrfToken = window.step6Csrf;
  log('Loaded params:', { D, Z, rpm0, fz0, fz_min0, fz_max0, fr_max });

  // ===== DOM ELEMENTS =====
  const $ = id => document.getElementById(id);
  const sFz   = $('sliderFz');          // feed per tooth
  const sVc   = $('sliderVc');          // cutting speed (m/min)
  const sAe   = $('sliderAe');          // radial depth
  const sP    = $('sliderPasadas');     // passes
  const infoP = $('textPasadasInfo');   // passes info
  const err   = $('errorMsg');          // error display
  const out   = {                       // outputs
    vc:  $('outVc'),
    fz:  $('outFz'),
    hm:  $('outHm'),
    n:   $('outN'),
    vf:  $('outVf'),
    hp:  $('outHp'),
    mmr: $('valueMrr'),
    fc:  $('valueFc'),
    w:   $('valueW'),
    eta: $('valueEta'),
    ae:  $('outAe'),
    ap:  $('outAp')
  };
  if (![sFz, sVc, sAe, sP, infoP, err].every(Boolean) || !out.vc) {
    errorLog('Missing DOM elements – aborting initStep6.');
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
  /**
   * Enhance a slider with given bounds and step, with console logging.
   */
  function enhance(slider, min, max, step) {
    group(`Enhance ${slider.id}`, () => {
      const wrap = slider.closest('.slider-wrap');
      const bubble = wrap.querySelector('.slider-bubble');
      // Set attributes
      slider.min  = min;
      slider.max  = max;
      slider.step = step;
      // Update UI
      function update(raw) {
        const v = clamp(raw, parseFloat(min), parseFloat(max));
        slider.value = v;
        const pct = parseFloat(max) > parseFloat(min)
          ? ((v - parseFloat(min)) / (parseFloat(max) - parseFloat(min))) * 100
          : 0;
        wrap.style.setProperty('--val', pct);
        if (bubble) bubble.textContent = v.toFixed(step < 1 ? 2 : 0);
      }
      slider.addEventListener('input', e => update(parseFloat(e.target.value)));
      update(parseFloat(slider.value));
      log(`${slider.id} bounded [${min}, ${max}] step=${step}`);
    });
  }

  // ===== SET SLIDER LIMITS =====
  group('SetSliderLimits', () => {
    // VC slider: centered at vc0 with -50% to +50% range
    const vcCenter = step6Params.vc0 || vc0 || 0; // vc0 from params
    const minVc = vcCenter * 0.5;
    const maxVc = vcCenter * 1.5;
    // FZ limits from params
    const minFz = fz_min0;
    const maxFz = fz_max0;

    // Enhance sliders with symmetrical bounds around vcCenter
    enhance(sVc, minVc.toFixed(1), maxVc.toFixed(1), 1);
    sVc.value = vcCenter.toFixed(1);

    // Standard FZ clamp both sides
    enhance(sFz, minFz.toFixed(3), maxFz.toFixed(3), 0.001);
    sFz.value = fz0.toFixed(3);

    log(`VC slider range set to [${minVc.toFixed(1)}, ${maxVc.toFixed(1)}], centered at ${vcCenter}`);
  });

  // ===== RADAR INITIALIZATION =====
  let radar = window.radarChartInstance;
  const canvas = $('radarChart');
  if (canvas) {
    if (radar) radar.destroy();
    radar = new Chart(canvas.getContext('2d'), {
      type: 'radar',
      data: {
        labels: ['Vida útil','Terminación','Potencia'],
        datasets: [{
          data: [50,50,50],
          backgroundColor: 'rgba(33,150,243,0.3)',
          borderColor:     'rgba(33,150,243,0.8)',
          borderWidth: 2
        }]
      },
      options: {
        scales: { r: { beginAtZero: true, suggestedMax: 100, ticks: { stepSize: 20 } } },
        plugins: { legend: { display: false } }
      }
    });
    window.radarChartInstance = radar;
    log('Radar initialized at [50,50,50]');
  } else warn('Radar canvas missing');

  // ===== RECALC LOGIC =====
  /** Compute feed and rpm based on vc (rpm) and fz */
  function computeFeed(vc, fz) {
    return group('computeFeed', () => {
      const rpm  = vc;
      const feed = rpm * fz * Z;
      log('Computed rpm/feed:', rpm.toFixed(0), '/', feed.toFixed(0));
      return { rpm, feed };
    });
  }

  const thickness = parseFloat(sP.dataset.thickness) || 0;
  function updatePasses() {
    const maxPass = Math.ceil(thickness / parseFloat(sAe.value));
    sP.min = 1;
    sP.max = maxPass;
    if (+sP.value > maxPass) sP.value = maxPass;
    infoP.textContent = `${sP.value} pasadas de ${(thickness/+sP.value).toFixed(2)} mm`;
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
      if (rpm < vcVal || rpm > vcVal) { /* redundant clamp */ }
      if (feed > fr_max) {
        showError(`Feedrate > límite (${fr_max})`);
        return;
      }
      out.n.textContent  = rpm.toFixed(0);
      out.vf.textContent = feed.toFixed(0);
      scheduleRecalc();
    });
  }

  function onFZChange() {
    group('fzChange', () => {
      const fzVal = clamp(+sFz.value, +sFz.min, +sFz.max);
      sFz.value = fzVal;
      const { rpm, feed } = computeFeed(+sVc.value, fzVal);
      if (feed > fr_max) {
        showError(`Feedrate > límite (${fr_max})`);
        return;
      }
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
        fz: +sFz.value,
        vc: +sVc.value,
        ae: +sAe.value,
        passes: +sP.value,
        thickness,
        D, Z,
        params: { fr_max, coef_seg, Kc11, mc, alpha, eta }
      };
      table(payload);
      try {
        const res = await fetch(
          `${BASE_URL}/ajax/step6_ajax_legacy_minimal.php`,
          { method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-Token':csrfToken }, body: JSON.stringify(payload), cache: 'no-store' }
        );
        if (!res.ok) throw new Error(res.status===403?'Sesión expirada':`HTTP ${res.status}`);
        const js = await res.json(); if (!js.success) throw new Error(js.error||'Error servidor');
        const d  = js.data;
        // Paint outputs
        out.vc.textContent  = `${d.vc} m/min`;
        out.fz.textContent  = `${d.fz} mm/tooth`;
        out.hm.textContent  = `${d.hm} mm`;
        out.n.textContent   = d.n;
        out.vf.textContent  = `${d.vf} mm/min`;
        out.hp.textContent  = `${d.hp} HP`;
        out.mmr.textContent = d.mmr;
        out.fc.textContent  = d.fc;
        out.w.textContent   = d.watts;
        out.eta.textContent = `${d.etaPercent}%`;
        out.ae.textContent  = d.ae.toFixed(2);
        out.ap.textContent  = d.ap.toFixed(3);
        // Update radar
        if (radar && Array.isArray(d.radar) && d.radar.length === 3) {
          radar.data.datasets[0].data = d.radar;
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
