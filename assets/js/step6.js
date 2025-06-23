/*
 * File: step6.js
 * Epic CNC Wizard Step 6 – límites estrictos y radar avanzado 🌟
 *
 * Main responsibility:
 *   Control de sliders con límites inflexibles, recálculos AJAX y radar chart mejorado,
 *   todo narrado en la consola con estilo épico.
 * Related: ajax/step6_ajax_legacy_minimal.php
 * TODO: Agregar notificaciones sonoras y accesibilidad ARIA.
 */
/* global Chart, window */

(() => {
  'use strict';

  // ================= CONFIG & LOGGING ==================
  const BASE_URL = window.BASE_URL;
  const DEBUG    = window.DEBUG ?? true;
  const STYLE    = 'color:#e91e63;font-weight:bold';
  const log      = (...a) => DEBUG && console.log('%c[Step6🚀]', STYLE, ...a);
  const warn     = (...a) => DEBUG && console.warn('%c[Step6⚠️]', STYLE, ...a);
  const errorLog = (...a) => DEBUG && console.error('%c[Step6💥]', STYLE, ...a);
  const table    = d => DEBUG && console.table(d);
  const group    = (t, fn) => {
    if (!DEBUG) return fn();
    console.group(`%c[Step6] ${t}`, STYLE);
    try { return fn(); } finally { console.groupEnd(); }
  };

  // ========== PARAMS INYECTADOS ===========
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
  log('Parámetros:', {D, Z, rpm_min, rpm_max, fr_max});

  // ========== DOM ELEMENTS ===========
  const getEl = id => document.getElementById(id);
  const sFz   = getEl('sliderFz');
  const sVc   = getEl('sliderVc');
  const sAe   = getEl('sliderAe');
  const sP    = getEl('sliderPasadas');
  const infoP = getEl('textPasadasInfo');
  const err   = getEl('errorMsg');
  const out   = {
    vc: getEl('outVc'), fz: getEl('outFz'), hm: getEl('outHm'), n: getEl('outN'),
    vf: getEl('outVf'), hp: getEl('outHp'), mmr: getEl('valueMrr'), fc: getEl('valueFc'),
    w: getEl('valueW'), eta: getEl('valueEta'), ae: getEl('outAe'), ap: getEl('outAp')
  };
  if (!sFz || !sVc || !sAe || !sP || !infoP || !err || !out.vc) {
    errorLog('DOM faltante – abortando Step6');
    return;
  }

  // ========== UTILITIES ===========
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

  // ========== SLIDER ENHANCER ===========
  function enhance(slider) {
    group(`Enhance ${slider.id}`, () => {
      const wrap = slider.closest('.slider-wrap');
      const bubble = wrap?.querySelector('.slider-bubble');
      const min = parseFloat(slider.min);
      const max = parseFloat(slider.max);
      const step = parseFloat(slider.step);
      function update(val) {
        const v = clamp(val, min, max);
        const pct = ((v - min) / (max - min)) * 100;
        wrap.style.setProperty('--val', pct);
        if (bubble) bubble.textContent = v.toFixed(step<1?2:0);
      }
      slider.addEventListener('input', e => update(parseFloat(e.target.value)));
      update(parseFloat(slider.value));
      log(`${slider.id} listo con límites [${min},${max}]`);
    });
  }

  // ========== VC LIMITS ===========
  group('vcLimits', () => {
    const minVc = (rpm_min * Math.PI * D) / 1000;
    const maxVc = (rpm_max * Math.PI * D) / 1000;
    sVc.min = minVc.toFixed(1);
    sVc.max = maxVc.toFixed(1);
    sVc.value = clamp(parseFloat(sVc.value), minVc, maxVc).toFixed(1);
    log('VC límites establecidos:', {minVc, maxVc});
  });

  // ========== RADAR SETUP ===========
  let radar = window.radarChartInstance;
  const canvas = getEl('radarChart');
  if (canvas) {
    if (radar) radar.destroy();
    radar = new Chart(canvas.getContext('2d'), {
      type: 'radar',
      data: { labels:['Vida útil','Terminación','Potencia'], datasets:[{ data:[0,0,0], backgroundColor:'rgba(233,30,99,0.3)', borderColor:'rgba(233,30,99,0.8)', borderWidth:2 }] },
      options: { scales:{r:{max:100,ticks:{stepSize:20}}}, plugins:{legend:{display:false}} }
    });
    window.radarChartInstance = radar;
    log('Radar inicializado con colores épicos');
  } else {
    warn('Canvas radar no encontrado');
  }

  // ========== CALCS & AJAX ===========
  function computeFeed(vc, fz) {
    return group('computeFeed', () => {
      const rpm = (vc*1000)/(Math.PI*D);
      const feed = rpm*fz*Z;
      log('feed:', feed);
      return feed;
    });
  }
  const thickness = parseFloat(sP.dataset.thickness) || 0;
  function updatePasses() {
    const maxPass = Math.max(1, Math.ceil(thickness/parseFloat(sAe.value)));
    sP.min = 1; sP.max = maxPass; sP.value = clamp(parseInt(sP.value,10),1,maxPass);
    infoP.textContent = `${sP.value} pasadas (${(thickness/sP.value).toFixed(2)} mm)`;
  }
  let debounce;
  function schedule() {
    clearError(); clearTimeout(debounce);
    debounce = setTimeout(recalc, 300);
  }
  function onChange(e) {
    group('onParamChange', () => {
      const vc = parseFloat(sVc.value);
      const fz = parseFloat(sFz.value);
      clearError();
      const feed = computeFeed(vc,fz);
      if (feed>fr_max) return showError(`Feed > ${fr_max}`);
      if (feed<=0) return showError('Feed ≤ 0');
      schedule();
    });
  }
  [sFz,sVc].forEach(el=>el.addEventListener('input',onChange));
  sAe.addEventListener('input',()=>{ updatePasses(); schedule(); });
  sP.addEventListener('input',()=>{ updatePasses(); schedule(); });

  async function recalc() {
    return group('recalc', async () => {
      const payload = {fz:+sFz.value,vc:+sVc.value,ae:+sAe.value,passes:+sP.value,thickness,D,Z,params:{fr_max,coef_seg,Kc11,mc,alpha,eta}};
      table(payload);
      try {
        const res = await fetch(`${BASE_URL}/ajax/step6_ajax_legacy_minimal.php`,{ method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify(payload),cache:'no-store'});
        if(!res.ok) throw new Error(res.status===403?'Sesión expirada':`HTTP ${res.status}`);
        const js = await res.json();
        if(!js.success) throw new Error(js.error||'Error servidor');
        const d = js.data;
        // pintar
        out.vc.textContent = `${d.vc} m/min`;
        out.fz.textContent = `${d.fz} mm/tooth`;
        out.hm.textContent = `${d.hm} mm`;
        out.n.textContent = d.n;
        out.vf.textContent = `${d.vf} mm/min`;
        out.hp.textContent = `${d.hp} HP`;
        out.mmr.textContent = d.mmr;
        out.fc.textContent = d.fc;
        out.w.textContent = d.watts;
        out.eta.textContent = `${d.etaPercent}%`;
        out.ae.textContent = d.ae.toFixed(2);
        out.ap.textContent = d.ap.toFixed(3);
        if(radar && Array.isArray(d.radar) && d.radar.length===3){
          radar.data.datasets[0].data = d.radar;
          radar.update();
          log('Radar data:',d.radar);
        }
      } catch(err) {
        errorLog('recalc:',err);
        showError(err.message);
      }
    });
  }

  // ========== INIT ===========
  log('🔧 initStep6 started');
  [sFz,sVc,sAe,sP].forEach(enhance);
  updatePasses(); recalc();
  window.addEventListener('error',e=>showError(e.message));
})();
