/* =====================================================================
 * assets/js/step6.js ·  PASO 6 — Wizard CNC · Versión CLIENT (100 % fórmulas backend)
 * ---------------------------------------------------------------------
 *  – Calcula localmente lo mismo que hacía el antiguo endpoint PHP:
 *      hm   = fz·(1 – cos φ)/φ                       (Hon & De Vries)
 *      MMR  = ap·feed·ae / 1000                     (cm³/min)
 *      Fct  = Kc11·hm^(–mc)·ap·fz·Z·(1+coef_seg·tanα)
 *      kW   = Fct·Vc / (60 000·η)                   ➜ W, HP
 *
 *  – Cero AJAX.  Si los sliders aún no están en el DOM reintenta 10× / 120 ms.
 *  – Cuando falta algún dato en window.step6Params lo avisa por consola y usa
 *    un valor por defecto seguro.
 * ====================================================================*/

/* global Chart, CountUp, window */

/*-------------- 1 · PARAMS + helper de aviso -------------------------*/
const P = window.step6Params || {};

function need (key, def) {
  if (P[key] === undefined) {
    console.warn(`[step6] parámetro “${key}” ausente → default ${def}`);
    return def;
  }
  return P[key];
}

const D        = +need('diameter',          1);        // mm
const Z        = +need('flute_count',       1);
const THK      = +need('thickness',         10);       // mm
const FR_MAX   = +need('fr_max',            6000);     // mm/min
const HP_AVAIL = +need('hp_avail',          1.5);      // HP disponibles
const K_SEG    = +need('coef_seg',          1.0);      // coef. seguridad
const KC       = +need('Kc11',              1500);     // N/mm²
const mc       = +need('mc',                0.20);     // exponente
const ALPHA    = +need('rack_rad',          0.0);      // rad
const eta      = +need('eta',               1.0);      // eficiencia 0–1
const RPM_MIN  = +need('rpm_min',           1000);
const RPM_MAX  = +need('rpm_max',           24000);

/*-------------- 2 · STATE -------------------------------------------*/
const state = {
  fz : +need('fz0',      0.02),             // mm/diente
  vc : +need('vc0',      100),              // m/min
  ae : (P.diameter ?? D) * 0.5,             // mm (50 % de D)
  ap : +need('ap_slot',  1),                // nº pasadas (1 = slot)
  chart : null,
  counters : {}
};

/*-------------- 3 · HELPERS DOM & MATH ------------------------------*/
const $    = (sel, ctx = document) => ctx.querySelector(sel);
const fmt  = (n, d = 1) => Number.parseFloat(n).toFixed(d);
const setT = (el, v, d = 1) => { if (el) el.textContent = fmt(v, d); };

// Fórmulas idénticas al backend
const rpm   = vc       => (vc * 1000) / (Math.PI * D);
const feed  = (r, fz)  => r * fz * Z;
const phi   = ae       => 2 * Math.asin(Math.min(1, ae / D));
const hm    = (fz, ae) => { const p = phi(ae); return p ? fz * (1 - Math.cos(p)) / p : fz; };
const mmr   = (ap, vf, ae) => (ap * vf * ae) / 1000;                // cm³/min
const Fct   = (hmv, ap, fz) => KC * Math.pow(hmv, -mc) * ap * fz * Z *
                               (1 + K_SEG * Math.tan(ALPHA));
const kW    = (F, Vc) => (F * Vc) / (60_000 * eta);
const hp    = kWv => kWv * 1.341;

/*-------------- 4 · RADAR -------------------------------------------*/
function makeRadar (arr) {
  const ctx = $('#radarChart');
  if (!ctx || !Chart) return;
  state.chart = new Chart(ctx, {
    type   : 'radar',
    data   : { labels: ['MMR', 'Fc', 'W', 'Hp', 'η'],
               datasets: [{ data: arr, fill: true, borderWidth: 2 }] },
    options: { scales: { r: { min: 0, max: 1 } },
               plugins: { legend: { display: false } } }
  });
}
const updRadar = arr => {
  if (!state.chart) makeRadar(arr);
  else { state.chart.data.datasets[0].data = arr; state.chart.update(); }
};

/*-------------- 5 · CORE recalc -------------------------------------*/
function recalc () {
  const rpmVal  = rpm(state.vc);
  const feedRaw = feed(rpmVal, state.fz);
  const feedVal = Math.min(feedRaw, FR_MAX);
  const apVal   = THK / Math.max(1, state.ap);
  const hmVal   = hm(state.fz, state.ae);
  const mmrVal  = mmr(apVal, feedVal, state.ae);
  const FctVal  = Fct(hmVal, apVal, state.fz);
  const kWVal   = kW(FctVal, state.vc);
  const WVal    = kWVal * 1000;
  const HPVal   = hp(kWVal);
  const etaVal  = Math.min(100, (HPVal / HP_AVAIL) * 100);

  setT($('#outVc'),  state.vc, 1);
  setT($('#outFz'),  state.fz, 4);
  setT($('#outVf'),  feedVal, 0);
  setT($('#outN'),   rpmVal,  0);
  setT($('#outHm'),  hmVal,   4);
  setT($('#outAe'),  state.ae,2);
  setT($('#outAp'),  apVal,   3);
  setT($('#valueMrr'), mmrVal, 0);
  setT($('#valueFc'),  FctVal, 0);
  setT($('#valueW'),   WVal,   0);
  setT($('#outHp'),    HPVal,  2);
  setT($('#valueEta'), etaVal, 0);

  const n = x => Math.min(1, x);
  updRadar([
    n(mmrVal / 1e5),
    n(FctVal / 1e4),
    n(WVal  / 3000),
    n(HPVal / HP_AVAIL),
    n(etaVal / 100)
  ]);
}

/*-------------- 6 · SLIDERS -----------------------------------------*/
function bindSlider (el, key, dec) {
  if (!el) return;
  const bubble = el.nextElementSibling;
  const label  = $('#val' + key[0].toUpperCase() + key.slice(1));
  const show   = () => { if (bubble) bubble.textContent = fmt(el.value, dec); };
  show();
  el.addEventListener('input', show);
  el.addEventListener('change', () => {
    state[key] = +el.value;
    if (label) label.textContent = fmt(el.value, dec);
    recalc();
  });
}
function setupPass (slider) {
  if (!slider) return;
  const info = $('#textPasadasInfo');
  const refresh = () => {
    state.ap = +slider.value;
    if (info) info.textContent =
      `${slider.value} pasada${slider.value > 1 ? 's' : ''} de ${(THK / slider.value).toFixed(2)} mm`;
    recalc();
  };
  slider.addEventListener('input', refresh);
  refresh();
}

/*-------------- 7 · INIT con retry DOM ------------------------------*/
export function init (retry = 10) {
  const need = ['sliderVc', 'sliderFz', 'sliderAe', 'sliderPasadas'];
  const miss = need.filter(id => !$('#' + id));
  if (miss.length) {
    if (retry) return setTimeout(() => init(retry - 1), 120);
    console.warn('[step6] sliders faltantes:', miss);
    return;
  }
  const sVc = $('#sliderVc');
  if (sVc) {
    sVc.min   = fmt((RPM_MIN * Math.PI * D) / 1000, 1);
    sVc.max   = fmt((RPM_MAX * Math.PI * D) / 1000, 1);
    sVc.value = state.vc;
  }
  bindSlider($('#sliderVc'), 'vc', 1);
  bindSlider($('#sliderFz'), 'fz', 4);
  bindSlider($('#sliderAe'), 'ae', 1);
  setupPass($('#sliderPasadas'));

  makeRadar([0, 0, 0, 0, 0]);
  recalc();

  if (typeof CountUp === 'function') {
    ['outVf', 'outN'].forEach(id => {
      const n = $('#' + id);
      const v = parseFloat(n.textContent) || 0;
      const cu = new CountUp(n, v, { duration: .6, separator: ' ' });
      if (!cu.error) cu.start();
    });
  }
  console.info('%c[step6] init OK – sin AJAX, fórmulas 1:1 con backend',
               'color:#29b6f6;font-weight:bold');
}

/*-------------- 8 · LEGACY HOOK -------------------------------------*/
if (typeof window !== 'undefined') {
  window.step6 = window.step6 || {};
  window.step6.init = init;
}
