/*
 * File: step6.js
 * Epic CNC Wizard Step 6 – sliders centrados y rango ±50%, recálculos y radar dinámico 🏹
 *
 * Main responsibilities:
 *   • Slider VC centrado en rpm0 ±50% (rango dinámico)
 *   • Slider FZ limitado según fz_min0 y fz_max0
 *   • Cálculo de feedrate independiente con validaciones
 *   • Recálculos AJAX seguros con CSRF
 *   • Radar Chart reflexivo de VidaÚtil, Terminación y Potencia según fz
 *   • Epic console logs via console.group, emojis, estilos
 *
 * Behavior:
 *   – Aumentar fz: ↑Potencia, ↑VidaÚtil, ↓Terminación
 *   – Disminuir fz: ↓Potencia, ↓VidaÚtil, ↑Terminación
 *   – VC slider alterará rpm y feedrate dentro de ±50% del valor base
 *
 * Related: ajax/step6_ajax_legacy_minimal.php
 */
/* global Chart, window */
(() => {
  'use strict';

  // ===== CONFIG & LOGGING =====
  const BASE_URL = window.BASE_URL;
  const DEBUG    = window.DEBUG ?? true;
  const STYLE    = 'color:#2196f3;font-weight:bold';
  const log      = (...a) => DEBUG && console.log('%c[Step6🚀]', STYLE, ...a);
  const warn     = (...a) => DEBUG && console.warn('%c[Step6⚠️]', STYLE, ...a);
  const errorLog = (...a) => DEBUG && console.error('%c[Step6💥]', STYLE, ...a);
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
  log('Loaded params:', {D, Z, rpm0, fz0, fz_min0, fz_max0, fr_max});

  // ===== DOM ELEMENTS =====
  const $ = id => document.getElementById(id);
  const sFz   = $('sliderFz');
  const sVc   = $('sliderVc');
  const sAe   = $('sliderAe');
  const sP    = $('sliderPasadas');
  const infoP = $('textPasadasInfo');
  const err   = $('errorMsg');
  const out   = {
    vc:    $('outVc'),    fz:    $('outFz'),    hm: $('outHm'),
    n:     $('outN'),     vf:    $('outVf'),    hp: $('outHp'),
    mmr:   $('valueMrr'), fc:    $('valueFc'),   w:  $('valueW'),
    eta:   $('valueEta'), ae:    $('outAe'),     ap: $('outAp')
  };
  if (![sFz, sVc, sAe, sP, infoP, err].every(Boolean) || !out.vc) {
    errorLog('Missing DOM elements – aborting Step6.');
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
  function enhance(slider, min, max, step) {
    group(`Enhance ${slider.id}`, () => {
      const wrap = slider.closest('.slider-wrap');
      const bubble = wrap.querySelector('.slider-bubble');
      slider.min  = min;
      slider.max  = max;
      slider.step = step;
      function update(raw) {
        const v = clamp(raw, parseFloat(min), parseFloat(max));
        slider.value = v;
        const pct = (v - parseFloat(min)) / (parseFloat(max) - parseFloat(min)) * 100;
        wrap.style.setProperty('--val', pct);
        if (bubble) bubble.textContent = v.toFixed(step < 1 ? 2 : 0);
      }
      slider.addEventListener('input', e => update(parseFloat(e.target.value)));
      update(parseFloat(slider.value));
      log(`${slider.id} bounded [${min},${max}] step ${step}`);
    });
  }

  // ===== SET SLIDER LIMITS =====
  group('SetSliderLimits', () => {
    // VC: center at rpm0, ±50%
    const vcMin = rpm0 * 0.5;
    const vcMax = rpm0 * 1.5;
    // FZ: use fz_min0, fz_max0
    const fzMin = fz_min0;
    const fzMax = fz_max0;

    enhance(sVc, vcMin.toFixed(1), vcMax.toFixed(1), 1);
    enhance(sFz, fzMin.toFixed(3), fzMax.toFixed(3), 0.001);

    log('Slider limits set: VC in ±50% of rpm0, FZ within [min,max]');
  });

  // ===== RADAR INITIALIZATION =====
  let radar = window.radarChartInstance;
  const canvas = $('radarChart');
  if (canvas) {
    if (radar) radar.destroy();
    radar = new Chart(canvas.getContext('2d'), {
      type: 'radar', data: {
        labels: ['Vida útil','Terminación','Potencia'],
        datasets:[{ data:[50,50,50], backgroundColor:'rgba(33,150,243,0.3)', borderColor:'rgba(33,150,243,0.8)', borderWidth:2 }]
      }, options:{ scales:{r:{beginAtZero:true,suggestedMax:100,ticks:{stepSize:20}}}, plugins:{legend:{display:false}} }
    });
    window.radarChartInstance = radar;
    log('Radar initialized at neutral');
  } else warn('Radar canvas missing');

  // ===== RECALC LOGIC =====
  function computeFeed(vc,fz) {
    return group('computeFeed', () => {
      const rpm  = vc;
      const feedMm = rpm * fz * Z;
      log('Computed feedrate', feedMm.toFixed(0));
      return { rpm, feed: feedMm };
    });
  }

  const thickness = parseFloat(sP.dataset.thickness) || 0;
  function updatePasses() {
    const maxPass = Math.ceil(thickness / parseFloat(sAe.value));
    sP.min = 1; sP.max = maxPass;
    if (+sP.value > maxPass) sP.value = maxPass;
    infoP.textContent = `${sP.value} pasadas de ${(thickness/+sP.value).toFixed(2)} mm`;
  }

  let timer;
  function scheduleRecalc() {
    clearError(); clearTimeout(timer); timer = setTimeout(recalc, 300);
  }

  function onVCChange() {
    group('vcChange', () => {
      const vcVal = clamp(+sVc.value, +sVc.min, +sVc.max);
      sVc.value = vcVal;
      const { rpm, feed } = computeFeed(vcVal, +sFz.value);
      if (rpm < vcMin || rpm > vcMax) {
        showError(`VC fuera de rango: ${rpm.toFixed(0)}`);
        return;
      }
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
      const payload = { fz:+sFz.value, vc:+sVc.value, ae:+sAe.value, passes:+sP.value, thickness, D, Z, params:{fr_max,coef_seg,Kc11,mc,alpha,eta} };
      table(payload);
      try {
        const res = await fetch(`${BASE_URL}/ajax/step6_ajax_legacy_minimal.php`, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken}, body:JSON.stringify(payload), cache:'no-store' });
        if (!res.ok) throw new Error(res.status===403?'Sesión expirada':`HTTP ${res.status}`);
        const js  = await res.json(); if (!js.success) throw new Error(js.error||'Error servidor');
        const d   = js.data;
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
        if (radar && Array.isArray(d.radar) && d.radar.length===3) {
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
  updatePasses(); recalc();
  window.addEventListener('error', e => showError(e.message));
})();
