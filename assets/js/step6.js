/* =====================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC
 * ---------------------------------------------------------------------
 *  ▸ 100 % frontend-only: replica todas las fórmulas del backend PHP.
 *  ▸ Sin llamadas AJAX. Toma los datos precargados en window.step6Params.
 *  ▸ Incluye capa de autovalidación + consola interactiva para debug.
 * ---------------------------------------------------------------------
 *  © 2025 — MIT License
 * ====================================================================*/

// eslint-disable-next-line unicorn/prefer-module
const g = typeof window !== 'undefined' ? window : globalThis;

// ─────────────────────────── 1 · DEBUG UTIL ──────────────────────────
const $DBG = {
  enabled: false,
  log(...a) { if (this.enabled) console.log('%c[step6]', 'color:#29b6f6', ...a); },
  warn(...a) { if (this.enabled) console.warn('[step6]', ...a); },
  error(...a) { console.error('[step6]', ...a); },
};
(() => {  // auto-enable with ?debug or step6Params.debug
  try { const u = new URL(location.href); if (u.searchParams.has('debug')) $DBG.enabled = true; } catch {}
  if (g.step6Params?.debug) $DBG.enabled = true;
})();

// ─────────────────────── 2 · PARAMS & VALIDACIÓN ─────────────────────
const P = g.step6Params ?? {};
const REQUIRED = [
  'diameter', 'flute_count', 'thickness', 'fr_max', 'hp_avail',
  'coef_seg', 'Kc11', 'mc', 'rack_rad', 'eta',
  'rpm_min', 'rpm_max', 'fz0', 'vc0', 'ap_slot',
];
const missing = REQUIRED.filter(k => P[k] === undefined);
if (missing.length) {
  $DBG.error('Parámetros faltantes → abort', missing);
  throw new Error(`step6Params incompletos: ${missing.join(',')}`);
}

// ───────────── 3 · DESTRUCTURACIÓN (sin defaults mágicos) ────────────
const {
  diameter:        D,
  flute_count:     Z,
  thickness:       THK,
  fr_max:          FR_MAX,
  hp_avail:        HP_AVAIL,
  coef_seg:        K_SEG,
  Kc11:            KC,
  mc:              MC,
  rack_rad:        ALPHA,
  eta:             ETA,
  rpm_min:         RPM_MIN,
  rpm_max:         RPM_MAX,
  fz0:             FZ0,
  vc0:             VC0,
  ap_slot:         AP_SLOT,
} = P;

// ───────────────────────── 4 · STATE (reactivo) ──────────────────────
const S = {
  fz:  +FZ0,
  vc:  +VC0,
  ae:  D * 0.5,        // ancho inicial = 50 % Ø
  ap:  +AP_SLOT || 1,  // nº pasadas
  chart:    null,
  counters: {},
};

// ───────────────────────── 5 · HELPERS GENÉRICOS ─────────────────────
const $        = (sel, ctx = document) => ctx.querySelector(sel);
const fmt      = (n, d = 1) => Number.parseFloat(n).toFixed(d);
const setTxt   = (el, v, d = 1) => { if (el) el.textContent = fmt(v, d); };
const clamp    = (x, min, max) => Math.min(Math.max(x, min), max);

// ──────────────────────── 6 · FÓRMULAS CNC (1:1) ─────────────────────
const rpm  = vc           => (vc * 1_000) / (Math.PI * D);
const feed = (n, fz)      => n * fz * Z;
const phi  = ae           => 2 * Math.asin(Math.min(1, ae / D));
const hm   = (fz, ae)     => { const p = phi(ae); return p ? fz * (1 - Math.cos(p)) / p : fz; };
const mmr  = (ap, vf, ae) => (ap * vf * ae) / 1_000;      // → cm³/min
const Fct  = (hmv, ap, fz)=> KC * hmv ** -MC * ap * fz * Z * (1 + K_SEG * Math.tan(ALPHA));
const kW   = (F, Vc)      => (F * Vc) / (60_000 * ETA);
const hp   = kWv          => kWv * 1.341;

// ────────────────────────── 7 · RADAR CHART ─────────────────────────
function makeRadar(data = [0, 0, 0, 0, 0]) {
  const ctx = $('#radarChart');
  if (!ctx || !g.Chart) return;
  S.chart = new Chart(ctx, {
    type: 'radar',
    data: { labels: ['MMR', 'Fc', 'W', 'Hp', 'η'], datasets: [{ data, fill: true, borderWidth: 2 }] },
    options: {
      scales: { r: { min: 0, max: 1 } },
      animation: { duration: 150 },
      plugins: { legend: { display: false } },
    },
  });
}
const updRadar = arr => {
  if (!S.chart) makeRadar(arr);
  else { S.chart.data.datasets[0].data = arr; S.chart.update(); }
};

// ───────────────────────── 8 · CÁLCULO CENTRAL ──────────────────────
function recalc() {
  const N        = rpm(S.vc);
  const feedRaw  = feed(N, S.fz);
  const feedVal  = Math.min(feedRaw, FR_MAX);
  const apVal    = THK / Math.max(1, S.ap);
  const hmVal    = hm(S.fz, S.ae);
  const mmrVal   = mmr(apVal, feedVal, S.ae);
  const FctVal   = Fct(hmVal, apVal, S.fz);
  const kWVal    = kW(FctVal, S.vc);
  const WVal     = kWVal * 1_000;
  const HPVal    = hp(kWVal);
  const etaVal   = clamp((HPVal / HP_AVAIL) * 100, 0, 100);

  // Actualizar UI
  setTxt($('#outVc'),  S.vc, 1);
  setTxt($('#outFz'),  S.fz, 4);
  setTxt($('#outVf'),  feedVal, 0);
  setTxt($('#outN'),   N, 0);
  setTxt($('#outHm'),  hmVal, 4);
  setTxt($('#outAe'),  S.ae, 2);
  setTxt($('#outAp'),  apVal, 3);
  setTxt($('#valueMrr'), mmrVal, 0);
  setTxt($('#valueFc'),  FctVal, 0);
  setTxt($('#valueW'),   WVal, 0);
  setTxt($('#outHp'),    HPVal, 2);
  setTxt($('#valueEta'), etaVal, 0);

  // Radar normalizado (0-1)
  const n = x => Math.min(1, x);
  updRadar([
    n(mmrVal / 1e5),
    n(FctVal / 1e4),
    n(WVal  / 3_000),
    n(HPVal / HP_AVAIL),
    n(etaVal / 100),
  ]);
}

// ───────────────────────── 9 · CONTROLES UI ─────────────────────────
function bindSlider(el, key, decimals) {
  if (!el) return;
  const bubble = el.nextElementSibling;
  const label  = $('#val' + key[0].toUpperCase() + key.slice(1));
  const show   = () => { if (bubble) bubble.textContent = fmt(el.value, decimals); };
  show();
  el.addEventListener('input', show);
  el.addEventListener('change', () => {
    S[key] = +el.value;
    if (label) label.textContent = fmt(el.value, decimals);
    recalc();
  });
}

function setupPassSlider(slider) {
  if (!slider) return;
  const info = $('#textPasadasInfo');
  const refresh = () => {
    S.ap = +slider.value;
    if (info) info.textContent =
      `${slider.value} pasada${slider.value > 1 ? 's' : ''} de ${(THK / slider.value).toFixed(2)} mm`;
    recalc();
  };
  slider.addEventListener('input', refresh);
  refresh();
}

// ────────────────────────── 10 · INIT PUBLIC ────────────────────────
export function init(retry = 10) {
  const NEED = ['sliderVc', 'sliderFz', 'sliderAe', 'sliderPasadas'];
  const MISS = NEED.filter(id => !$('#' + id));
  if (MISS.length) {
    if (retry) {
      return setTimeout(() => init(retry - 1), 120);
    }
    $DBG.error('Sliders no encontrados en el DOM:', MISS);
    return;
  }

  // Ajustar límites de Vc según RPM min/max
  const sVc = $('#sliderVc');
  if (sVc) {
    sVc.min   = fmt((RPM_MIN * Math.PI * D) / 1_000, 1);
    sVc.max   = fmt((RPM_MAX * Math.PI * D) / 1_000, 1);
    sVc.value = S.vc;
  }

  bindSlider($('#sliderVc'), 'vc', 1);
  bindSlider($('#sliderFz'), 'fz', 4);
  bindSlider($('#sliderAe'), 'ae', 1);
  setupPassSlider($('#sliderPasadas'));

  makeRadar();
  recalc();

  // CountUp para valores grandes
  if (typeof g.CountUp === 'function') {
    ['outVf', 'outN'].forEach(id => {
      const node = $('#' + id);
      const val  = parseFloat(node.textContent) || 0;
      const cu   = new CountUp(node, val, { duration: 0.6, separator: ' ' });
      if (!cu.error) { S.counters[id] = cu; cu.start(); }
    });
  }

  $DBG.log('init OK (frente a frente con backend)');
}

// ────────────────────────── 11 · AUTOBOOT ───────────────────────────
if (g) {
  g.step6 = g.step6 || {};
  g.step6.init = init;
}
