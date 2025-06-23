/* --------------------------------------------------------------------------
 * File: assets/js/step6.js
 * Epic CNC Wizard – Paso 6
 * --------------------------------------------------------------------------
 *  • Sliders inteligentes (Vc ±50 % en torno a vc0, acotado por rpm_min/max)
 *  • Validación “sin trabas” → muestra errores pero nunca congela el control
 *  • Radar reactivo (⇑ fz ⇒ +Potencia +Vida –Terminación)
 *  • Todo narrado en consola con colores y grupos plegables
 * ------------------------------------------------------------------------ */
/* globals Chart, window -------------------------------------------------- */

window.radarChartInstance = window.radarChartInstance || null;

window.initStep6 = function () {
  'use strict';

  /* ========== CONFIG / LOGGING ========================================= */
  const BASE_URL = window.BASE_URL;
  const DEBUG    = window.DEBUG ?? true;
  const STYLE    = 'color:#2196F3;font-weight:bold';
  const log      = (...a) => DEBUG && console.log('%c[Step6🚀]', STYLE, ...a);
  const warn     = (...a) => DEBUG && console.warn('%c[Step6⚠️]', STYLE, ...a);
  const errLog   = (...a) => DEBUG && console.error('%c[Step6💥]', STYLE, ...a);
  const group    = (t,fn)=>DEBUG?console.groupCollapsed(`%c[Step6] ${t}`,STYLE)&&fn()&&console.groupEnd():fn();

  /* ========== PARAMS INYECTADOS POR PHP ================================ */
  const {
    diameter : D          = 0,
    flute_count : Z       = 1,
    /* rangos base desde la BD */
    vc0, vc_min0, vc_max0,
    fz0, fz_min0, fz_max0,
    /* límites de máquina */
    rpm_min = 0,
    rpm_max = 0,
    fr_max  = Infinity,
    /* coeficientes varios (para PHP) */
    coef_seg = 0, Kc11 = 1, mc = 1, alpha = 0, eta = 1
  } = window.step6Params || {};

  const csrfToken = window.step6Csrf || '';

  /* ========== REFERENCIAS AL DOM ======================================= */
  const $ = id => document.getElementById(id);
  const sFz = $('sliderFz'),  sVc = $('sliderVc'),
        sAe = $('sliderAe'),  sP  = $('sliderPasadas');
  const infoP = $('textPasadasInfo'), msgErr = $('errorMsg');
  const out = {
    vc  : $('outVc'),  fz  : $('outFz'), hm : $('outHm'),
    n   : $('outN'),   vf  : $('outVf'), hp : $('outHp'),
    mmr : $('valueMrr'),  fc : $('valueFc'), w : $('valueW'),
    eta : $('valueEta'),  ae : $('outAe'),  ap : $('outAp')
  };

  if (![sVc,sFz,sAe,sP,infoP,msgErr].every(Boolean)) {
    errLog('Faltan elementos en el DOM – abortando initStep6.');
    return;
  }

  /* ========== HELPERS =================================================== */
  const clamp = (v, min, max) => Math.min(Math.max(v, min), max);

  function showError (txt) {
    msgErr.textContent = txt; msgErr.style.display = 'block';
  }
  function clearError () { msgErr.style.display = 'none'; }

  /* ========== BEAUTIFY SLIDERS ========================================= */
  function beautify (slider) {
    group(`Beautify ${slider.id}`, () => {
      const wrap   = slider.closest('.slider-wrap');
      const bubble = wrap?.querySelector('.slider-bubble');
      const step   = parseFloat(slider.step || 1);
      function update (v) {
        const pct = ( (v - slider.min) / (slider.max - slider.min) ) * 100;
        wrap.style.setProperty('--val', pct);
        bubble && (bubble.textContent = (+v).toFixed(step < 1 ? 3 : 1));
      }
      slider.addEventListener('input', e => update(e.target.value));
      update(slider.value);
    });
  }

  /* ========== LIMITES DINÁMICOS DE VC & FZ ============================= */
  group('Slider limits', () => {
    /* 1. Vc: ±50 % alrededor de vc0, pero dentro de rpm_min/rpm_max */
    const vcMinByRpm = (rpm_min * Math.PI * D) / 1000;
    const vcMaxByRpm = (rpm_max * Math.PI * D) / 1000;
    const vcMin = Math.max(vc0 * 0.5, vcMinByRpm, vc_min0);
    const vcMax = Math.min(vc0 * 1.5, vcMaxByRpm, vc_max0);
    Object.assign(sVc, { min: vcMin.toFixed(1), max: vcMax.toFixed(1), step: 1 });
    sVc.value = clamp(vc0, vcMin, vcMax).toFixed(1);

    /* 2. Fz desde la base de datos */
    Object.assign(sFz, { min: fz_min0.toFixed(3), max: fz_max0.toFixed(3), step: 0.001 });
    sFz.value = clamp(fz0, fz_min0, fz_max0).toFixed(3);
  });

  beautify(sVc); beautify(sFz); beautify(sAe); beautify(sP);

  /* ========== PASADAS =================================================== */
  const thickness = parseFloat(sP.dataset.thickness || '0');
  function updatePasses () {
    const maxP = Math.ceil(thickness / parseFloat(sAe.value));
    sP.min = 1; sP.max = maxP; sP.step = 1;
    if (+sP.value > maxP) sP.value = maxP;
    infoP.textContent =
      `${sP.value} pasadas de ${(thickness / +sP.value).toFixed(2)} mm`;
  }
  updatePasses();

  /* ========== RADAR ===================================================== */
  let radar;
  (function initRadar () {
    const canvas = $('radarChart');
    if (!canvas) return;
    if (window.radarChartInstance) window.radarChartInstance.destroy();
    radar = new Chart(canvas.getContext('2d'), {
      type : 'radar',
      data : {
        labels   : ['Vida útil','Terminación','Potencia'],
        datasets : [{
          data            : [50,50,50],
          backgroundColor : 'rgba(33,150,243,.3)',
          borderColor     : 'rgba(33,150,243,.8)',
          borderWidth     : 2
        }]
      },
      options : {
        scales  : { r : { beginAtZero:true, suggestedMax:100, ticks:{ stepSize:20 } } },
        plugins : { legend:{ display:false } }
      }
    });
    window.radarChartInstance = radar;
  })();

  /* ========== CÁLCULOS RÁPIDOS ========================================= */
  function calcRpmFeed (vc, fz) {
    const rpm  = (vc * 1000) / (Math.PI * D);
    const feed = rpm * fz * Z;
    return { rpm: Math.round(rpm), feed };
  }

  function limitMsg (vc, fz) {
    const { rpm, feed } = calcRpmFeed(vc, fz);
    if (rpm < rpm_min) return `RPM ${rpm} por debajo de ${rpm_min}`;
    if (rpm > rpm_max) return `RPM ${rpm} por encima de ${rpm_max}`;
    if (feed > fr_max) return `Feed ${feed.toFixed(0)} > ${fr_max}`;
    return '';
  }

  /* ========== DEBOUNCE ================================================== */
  let t;
  const debounce = fn => { clearTimeout(t); t = setTimeout(fn, 250); };

  /* ========== RE-CÁLCULO AJAX ========================================== */
  async function recalc () {
    const payload = {
      fz        : +sFz.value,  vc      : +sVc.value,
      ae        : +sAe.value,  passes  : +sP.value,
      thickness, D, Z,
      params    : { fr_max, coef_seg, Kc11, mc, alpha, eta }
    };

    group('AJAX recalc', () => console.table(payload));

    try {
      const res = await fetch(`${BASE_URL}/ajax/step6_ajax_legacy_minimal.php`, {
        method  : 'POST',
        headers : { 'Content-Type':'application/json','X-CSRF-Token':csrfToken },
        body    : JSON.stringify(payload), cache:'no-store'
      });
      const js  = await res.json();

      console.dir({ status: res.status, response: js });

      if (!res.ok)              throw new Error(`HTTP ${res.status}`);
      if (!js.success)          throw new Error(js.error || 'Error servidor');

      const d = js.data;
      /* pintar resultados ------------------------------------------------ */
      out.vc.textContent  = `${d.vc} m/min`;
      out.fz.textContent  = `${d.fz} mm/tooth`;
      out.hm.textContent  = `${d.hm} mm`;
      out.n .textContent  = d.n;
      out.vf.textContent  = `${d.vf} mm/min`;
      out.hp.textContent  = `${d.hp}`;
      out.mmr.textContent = d.mmr;
      out.fc .textContent = d.fc;
      out.w  .textContent = d.watts;
      out.eta.textContent = `${d.etaPercent}%`;
      out.ae .textContent = d.ae.toFixed(2);
      out.ap .textContent = d.ap.toFixed(3);

      /* radar ------------------------------------------------------------ */
      if (radar && Array.isArray(d.radar) && d.radar.length === 3) {
        radar.data.datasets[0].data = d.radar;
        radar.update();
      }
    } catch (e) {
      errLog(e);
      showError(`⛔ ${e.message}`);
    }
  }

  /* ========== EVENTOS SLIDERS ========================================== */
  function onAnyInput () {
    const msg = limitMsg(+sVc.value, +sFz.value);
    if (msg) { showError(msg); return; }
    clearError(); debounce(recalc);
  }

  /* fz ⇅ → Potencia & Vida ↑ / Terminación ↓ (y vice-versa) */
  sFz.addEventListener('input', onAnyInput);
  sVc.addEventListener('input', onAnyInput);

  sAe.addEventListener('input', () => { updatePasses(); onAnyInput(); });
  sP .addEventListener('input', () => { updatePasses(); onAnyInput(); });

  /* ========== DISPARO INICIAL ========================================== */
  log('initStep6 listo ✅');
  recalc();
  window.addEventListener('error', ev => showError(`JS: ${ev.message}`));
};
