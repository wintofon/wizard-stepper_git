/* =====================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC · Versión CLIENT (fórmulas 1 : 1)
 * ---------------------------------------------------------------------
 *  – Calcula localmente lo mismo que el antiguo endpoint PHP.
 *  – Cero AJAX; reintenta si los sliders aún no están en el DOM.
 *  – Si falta cualquier dato 👉 se detiene y lo muestra en consola.
 * ====================================================================*/

/* global Chart, CountUp, window */

/* ---------- 1 · Validar que nos llegó TODO -------------------------- */
const P = window.step6Params;
if (!P || Object.keys(P).length === 0) {
  console.error('[step6] window.step6Params no definido o vacío → abortando');
  throw new Error('step6Params missing');
}

const REQUIRED = [
  'diameter', 'flute_count', 'thickness',
  'fr_max', 'hp_avail', 'coef_seg',
  'Kc11', 'mc', 'rack_rad', 'eta',
  'rpm_min', 'rpm_max',
  'fz0', 'vc0', 'ap_slot'
];

const missing = REQUIRED.filter(k => P[k] === undefined);
if (missing.length) {
  console.error('[step6] faltan parámetros críticos:', missing);
  throw new Error('step6Params incompletos');
}

/* ---------- 2 · PARAMS seguros (sin defaults ficticios) -------------- */
const {
  diameter   : D,
  flute_count: Z,
  thickness  : THK,
  fr_max     : FR_MAX,
  hp_avail   : HP_AVAIL,
  coef_seg   : K_SEG,
  Kc11       : KC,
  mc,
  rack_rad   : ALPHA,
  eta,
  rpm_min    : RPM_MIN,
  rpm_max    : RPM_MAX,
  fz0,
  vc0,
  ap_slot
} = P;

/* ---------- 3 · STATE ---------------------------------------------- */
const state = {
  fz : +fz0,
  vc : +vc0,
  ae : D * 0.5,           // 50 % del diámetro como valor inicial
  ap : +ap_slot || 1,
  chart   : null,
  counters: {}
};

/* ---------- 4 · HELPERS -------------------------------------------- */
const $    = (sel, ctx = document) => ctx.querySelector(sel);
const fmt  = (n, d = 1) => Number.parseFloat(n).toFixed(d);
const setT = (el, v, d = 1) => { if (el) el.textContent = fmt(v, d); };

// Fórmulas backend 1 : 1
const rpm   = vc        => (vc * 1000) / (Math.PI * D);
const feed  = (n, fz)   => n * fz * Z;
const phi   = ae        => 2 * Math.asin(Math.min(1, ae / D));
const hm    = (fz, ae)  => { const p = phi(ae); return p ? fz * (1 - Math.cos(p)) / p : fz; };
const mmr   = (ap, vf, ae) => (ap * vf * ae) / 1000;
const Fct   = (hmv, ap, fz) => KC * Math.pow(hmv, -mc) * ap * fz * Z *
                               (1 + K_SEG * Math.tan(ALPHA));
const kW    = (F, Vc)   => (F * Vc) / (60_000 * eta);
const hp    = kWv       => kWv * 1.341;

/* ---------- 5 · RADAR ---------------------------------------------- */
function makeRadar(arr) {
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

/* ---------- 6 · CORE RECALC ---------------------------------------- */
function recalc () {
  const N       = rpm(state.vc);
  const feedRaw = feed(N, state.fz);
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
  setT($('#outN'),   N,        0);
  setT($('#outHm'),  hmVal,    4);
  setT($('#outAe'),  state.ae, 2);
  setT($('#outAp'),  apVal,    3);
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

/* ---------- 7 · SLIDERS -------------------------------------------- */
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
  const info   = $('#textPasadasInfo');
  const update = () => {
    state.ap = +slider.value;
    if (info) {
      info.textContent =
        `${slider.value} pasada${slider.value > 1 ? 's' : ''} de ` +
        `${(THK / slider.value).toFixed(2)} mm`;
    }
    recalc();
  };
  slider.addEventListener('input', update);
  update();
}

/* ---------- 8 · INIT (retry hasta que aparezcan los sliders) -------- */
export function init (retry = 10) {
  const need = ['sliderVc', 'sliderFz', 'sliderAe', 'sliderPasadas'];
  const miss = need.filter(id => !$('#' + id));
  if (miss.length) {
    if (retry)   return setTimeout(() => init(retry - 1), 120);
    console.error('[step6] sliders no encontrados:', miss);
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
      const n  = $('#' + id);
      const v  = parseFloat(n.textContent) || 0;
      const cu = new CountUp(n, v, { duration: .6, separator: ' ' });
      if (!cu.error) cu.start();
    });
  }
  console.info('%c[step6] init OK – datos completos y sin AJAX',
               'color:#29b6f6;font-weight:bold');
}

/* ---------- 9 · LEGACY HOOK ---------------------------------------- */
if (typeof window !== 'undefined') {
  window.step6 = window.step6 || {};
  window.step6.init = init;
}
