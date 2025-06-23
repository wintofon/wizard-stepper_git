/*
 * File: step6.js
 * Epic CNC Wizard Step 6 – versión definitiva con radar y sliders fluídos 🌟
 *
 * Main responsibility:
 *   - Sliders sólidos (solo tope inferior, tope superior libre)
 *   - Recálculo AJAX eficiente y sin errores
 *   - Radar Chart responsivo que adapta su rango según datos
 *   - Console logs épicos por pasos
 * Related: ajax/step6_ajax_legacy_minimal.php
 * TODO: Añadir accesibilidad ARIA y notificaciones visuales
 */
/* global Chart, window */

(() => {
  'use strict';

  // ===== CONFIG & LOGGING =====
  const BASE_URL = window.BASE_URL;
  const DEBUG    = window.DEBUG ?? true;
  const STYLE    = 'color:#4caf50;font-weight:bold';
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
  log('Loaded params:', {D, Z, rpm_min, rpm_max, fr_max});

  // ===== DOM ELEMENTS =====
  const $ = id => document.getElementById(id);
  const sFz   = $('sliderFz');
  const sVc   = $('sliderVc');
  const sAe   = $('sliderAe');
  const sP    = $('sliderPasadas');
  const infoP = $('textPasadasInfo');
  const err   = $('errorMsg');
  const out   = {
    vc: $('outVc'), fz: $('outFz'), hm: $('outHm'), n: $('outN'),
    vf: $('outVf'), hp: $('outHp'), mmr: $('valueMrr'), fc: $('valueFc'),
    w: $('valueW'), eta: $('valueEta'), ae: $('outAe'), ap: $('outAp')
  };
  if (![sFz, sVc, sAe, sP, infoP, err].every(Boolean) || !out.vc) {
    errorLog('Missing DOM elements, aborting Step6');
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

  // Clamp lower bound only
  function lowerClamp(val, min) {
    return val < min ? min : val;
  }

  // ===== SLIDER ENHANCEMENT =====
  function enhance(slider) {
    group(`Enhance ${slider.id}`, () => {
      const wrap = slider.closest('.slider-wrap');
      const bubble = wrap?.querySelector('.slider-bubble');
      const min = parseFloat(slider.min) || 0;
      const step = parseFloat(slider.step) || 1;
      function update(raw) {
        const v = lowerClamp(raw, min);
        slider.value = v;
        const max = parseFloat(slider.max) || v;
        const pct = max > min ? ((v - min) / (max - min)) * 100 : 0;
        wrap.style.setProperty('--val', pct);
        if (bubble) bubble.textContent = v.toFixed(step < 1 ? 2 : 0);
      }
      slider.addEventListener('input', e => update(parseFloat(e.target.value)));
      update(parseFloat(slider.value));
      log(`${slider.id} enhanced: lower bound ${min}`);
    });
  }

  // ===== VC LOWER LIMIT =====
  group('vcLowerLimit', () => {
    const minVc = (rpm_min * Math.PI * D) / 1000;
    sVc.min = minVc.toFixed(1);
    log('VC min set to', minVc);
    // don't enforce upper
    sVc.value = lowerClamp(parseFloat(sVc.value), minVc).toFixed(1);
  });

  // ===== RADAR SETUP =====
  let radar = window.radarChartInstance;
  const canvas = $('radarChart');
  if (canvas) {
    if (radar) radar.destroy();
    radar = new Chart(canvas.getContext('2d'), {
      type: 'radar',
      data: {
        labels: ['Vida útil', 'Terminación', 'Potencia'],
        datasets: [{
          data: [0, 0, 0],
          backgroundColor: 'rgba(76,175,80,0.3)',
          borderColor:     'rgba(76,175,80,0.8)',
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
    log('Radar initialized');
  } else warn('Radar canvas missing');

  // ===== RECALC LOGIC =====
  function computeFeed(vc, fz) {
    return group('computeFeed', () => {
      const rpm = (vc * 1000) / (Math.PI * D);
      const feed = rpm * fz * Z;
      log('Computed feed:', feed);
      return feed;
    });
  }

  const thickness = parseFloat(sP.dataset.thickness) || 0;
  function updatePasses() {
    const maxPass = Math.max(1, Math.ceil(thickness / parseFloat(sAe.value)));
    sP.min = 1;
    sP.max = maxPass;
    if (+sP.value > maxPass) sP.value = maxPass;
    infoP.textContent = `${sP.value} pasadas de ${(thickness / +sP.value).toFixed(2)} mm`;
  }

  let timer;
  function scheduleRecalc() {
    clearError();
    clearTimeout(timer);
    timer = setTimeout(recalc, 300);
  }

  function onParamChange() {
    group('onParamChange', () => {
      clearError();
      const vc = parseFloat(sVc.value);
      const fz = parseFloat(sFz.value);
      const feed = computeFeed(vc, fz);
      if (feed > fr_max) {
        showError(`Feedrate supera límite (${fr_max})`);
        return;
      }
      if (feed <= 0) {
        showError(`Feedrate inválido (${feed})`);
        return;
      }
      scheduleRecalc();
    });
  }

  [sFz, sVc].forEach(el => el.addEventListener('input', onParamChange));
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
          {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload),
            cache: 'no-store'
          }
        );
        if (!res.ok) throw new Error(res.status === 403 ? 'Sesión expirada' : `HTTP ${res.status}`);
        const js = await res.json();
        if (!js.success) throw new Error(js.error || 'Error en servidor');
        const d = js.data;
        // Paint results
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
          radar.options.scales.r.suggestedMax = Math.max(100, ...d.radar) * 1.1;
          radar.update();
          log('Radar updated:', d.radar);
        }
      } catch (err) {
        errorLog('Recalc error:', err);
        showError(err.message);
      }
    });
  }

  // ===== INIT =====
  log('Starting initStep6');
  [sFz, sVc, sAe, sP].forEach(enhance);
  updatePasses();
  recalc();
  window.addEventListener('error', e => showError(e.message));
})();
