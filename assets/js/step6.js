/*
 * File: step6.js
 * Epic CNC Wizard Step 6 – legendario recalculo y visualización 🛠️
 *
 * Main responsibility:
 *   Cargar parámetros, sliders, gráficos y recálculos en Step 6.
 *   Cada evento y cada resultado se celebran en la consola con pompa.
 * Related files:
 *   - ajax/step6_ajax_legacy_minimal.php
 *   - assets/js/step6.js (este)
 * TODO: Añadir modulaciones sonoras ultra dramáticas.
 */
/* global Chart, window */
(function() {
  'use strict';

  // =================== CONFIG & LOGGING ====================
  const BASE_URL  = window.BASE_URL;
  const DEBUG     = window.DEBUG ?? true;
  const TAG_STYLE = 'color:#2196F3;font-weight:bold';
  function log(...args)   { console.log('%c[Step6🚀]', TAG_STYLE, ...args); }
  function warn(...args)  { console.warn('%c[Step6⚠️]', TAG_STYLE, ...args); }
  function error(...args) { console.error('%c[Step6💥]', TAG_STYLE, ...args); }
  function table(d)       { console.table(d); }
  function group(title, fn) {
    console.group(`%c[Step6🔄] ${title}`, TAG_STYLE);
    try { return fn(); }
    finally { console.groupEnd(); }
  }

  // ================ PARAMETROS INYECTADOS ================
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
  log('Parámetros recibidos:', {D, Z, rpm_min, rpm_max, fr_max, coef_seg, Kc11, mc, alpha, eta});

  // ==================== DOM ELEMENTS =======================
  const sFz   = document.getElementById('sliderFz');
  const sVc   = document.getElementById('sliderVc');
  const sAe   = document.getElementById('sliderAe');
  const sP    = document.getElementById('sliderPasadas');
  const infoP = document.getElementById('textPasadasInfo');
  const err   = document.getElementById('errorMsg');

  const out = {
    vc:  document.getElementById('outVc'),
    fz:  document.getElementById('outFz'),
    hm:  document.getElementById('outHm'),
    n:   document.getElementById('outN'),
    vf:  document.getElementById('outVf'),
    hp:  document.getElementById('outHp'),
    mmr: document.getElementById('valueMrr'),
    fc:  document.getElementById('valueFc'),
    w:   document.getElementById('valueW'),
    eta: document.getElementById('valueEta'),
    ae:  document.getElementById('outAe'),
    ap:  document.getElementById('outAp')
  };

  // =================== SLIDER ENHANCER =====================
  function enhanceSlider(slider) {
    group('enhanceSlider', () => {
      const wrap = slider.closest('.slider-wrap');
      if (!wrap) return;
      const bubble = wrap.querySelector('.slider-bubble');
      const min = parseFloat(slider.min);
      const max = parseFloat(slider.max);
      const step = parseFloat(slider.step);
      wrap.style.setProperty('--step-pct', (step/(max-min))*100);
      function update(val) {
        const pct = ((val-min)/(max-min))*100;
        wrap.style.setProperty('--val', pct);
        if (bubble) bubble.textContent = val.toFixed(step<1?4:0);
      }
      slider.addEventListener('input', e => update(parseFloat(e.target.value)));
      update(parseFloat(slider.value));
      log('Slider listo:', slider.id);
    });
  }

  // =========== AJUSTE DE LIMITES PARA Vc ================
  group('vcLimits', () => {
    const vcMin = (rpm_min*Math.PI*D)/1000;
    const vcMax = (rpm_max*Math.PI*D)/1000;
    sVc.min = vcMin.toFixed(1);
    sVc.max = vcMax.toFixed(1);
    log('Vc permitido:', vcMin, vcMax);
  });

  // =================== RADAR CHART =========================
  let radar = window.radarChartInstance;
  const canvas = document.getElementById('radarChart');
  if (radar) radar.destroy();
  radar = new Chart(canvas.getContext('2d'), {
    type: 'radar',
    data: {
      labels: ['Vida útil','Terminación','Potencia'],
      datasets: [{
        data: [0,0,0],
        backgroundColor: 'rgba(79,195,247,0.3)',
        borderColor:     'rgba(79,195,247,0.8)',
        borderWidth: 2
      }]
    },
    options: { scales:{r:{max:100,ticks:{stepSize:20}}}, plugins:{legend:{display:false}} }
  });
  window.radarChartInstance = radar;
  log('Radar inicializado');

  // ================ ERROR HANDLING =======================
  function showError(msg) {
    err.style.display = 'block';
    err.textContent = msg;
    warn('Error mostrado:', msg);
  }
  function clearError() {
    err.style.display = 'none';
    err.textContent = '';
  }

  // ================ CALCULOS BASE =========================
  function computeFeed(vc, fz) {
    return group('computeFeed', () => {
      const rpm = (vc*1000)/(Math.PI*D);
      const feed = rpm*fz*Z;
      log('feed:', feed);
      return feed;
    });
  }

  function lockSlider(sl, msg) { sl.disabled=true; showError(msg); }
  function unlockSlider(sl) { sl.disabled=false; clearError(); }

  // ====== GESTIÓN DE PASADAS ========
  const thickness = parseFloat(sP.dataset.thickness);
  function updatePasadas() {
    const maxP = Math.ceil(thickness/parseFloat(sAe.value));
    sP.min=1; sP.max=maxP; sP.value=Math.min(sP.value,maxP);
    infoP.textContent = `${sP.value} pasadas (${(thickness/sP.value).toFixed(2)} mm)`;
  }

  // =================== RECALC AJAX ========================
  async function recalc() {
    return group('recalc', async () => {
      clearError();
      const payload = {
        fz: parseFloat(sFz.value),
        vc: parseFloat(sVc.value),
        ae: parseFloat(sAe.value),
        passes: +sP.value,
        thickness, D, Z,
        params: { fr_max, coef_seg, Kc11, mc, alpha, eta }
      };
      table(payload);
      try {
        const res = await fetch(`${BASE_URL}/ajax/step6_ajax_legacy_minimal.php`, {
          method: 'POST',
          headers: { 'Content-Type':'application/json', 'X-CSRF-Token':csrfToken },
          body: JSON.stringify(payload), cache:'no-store'
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const msg = await res.json();
        if (!msg.success) throw new Error(msg.error);
        const d = msg.data;
        log('Datos recibidos:', d);
        // PINTAR RESULTADOS
        out.vc.textContent  = d.vc + ' m/min';
        out.fz.textContent  = d.fz + ' mm/tooth';
        out.hm.textContent  = d.hm + ' mm';
        out.n.textContent   = d.n;
        out.vf.textContent  = d.vf + ' mm/min';
        out.hp.textContent  = d.hp + ' HP';
        out.mmr.textContent = d.mmr;
        out.fc.textContent  = d.fc;
        out.w.textContent   = d.watts;
        out.eta.textContent = d.etaPercent + ' %';
        out.ae.textContent  = d.ae.toFixed(2);
        out.ap.textContent  = d.ap.toFixed(3);
        // ACTUALIZAR RADAR
        if (Array.isArray(d.radar) && d.radar.length===3) {
          radar.data.datasets[0].data = d.radar;
          radar.update();
        }
        table(d);
      } catch(e) {
        error('recalc error', e);
        showError(e.message);
      }
    });
  }

  // =============== EVENTOS ================
  [sFz,sVc].forEach(sl => sl.addEventListener('input', () => {
    clearError();
    const feed = computeFeed(+sVc.value, +sFz.value);
    if (feed>fr_max) return lockSlider(sFz,'Feed > límite');
    if (feed<=0) return lockSlider(sFz,'Feed <= 0');
    unlockSlider(sFz); unlockSlider(sVc);
    recalc();
  }));
  sAe.addEventListener('input', () => { updatePasadas(); recalc(); });
  sP.addEventListener('input', () => { updatePasadas(); recalc(); });

  // ================= INICIO ====================
  log('🏁 Inicializando Step 6 épico…');
  enhanceSlider(sFz);
  enhanceSlider(sVc);
  enhanceSlider(sAe);
  enhanceSlider(sP);
  updatePasadas();
  recalc();
})();
