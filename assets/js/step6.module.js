/* STEP6-AJAX */
import { debounce } from './utils.js';

console.info('[step6] JS cargado (module)');

const w = window;
const d = document;
const DEBUG = w.DEBUG === true;
const ttl = 600000; // 10 min
const dbg = (...m) => { if (DEBUG) console.log(...m); };
const grp = (t, fn) => { if (!DEBUG) return fn(); console.group(t); try { return fn(); } finally { console.groupEnd(); } };
const q = (id) => d.getElementById(id);
const csrfToken = w.step6Csrf || d.querySelector('meta[name="csrf-token"]')?.content || '';

const step = d.querySelector('.step6');
const errBox = q('errorMsg');
const sliders = { fz: q('sliderFz'), vc: q('sliderVc'), ae: q('sliderAe'), p: q('sliderPasadas') };
const out = { rpm: q('outN'), feed: q('outVf'), vc: q('outVc'), fz: q('outFz'), hm: q('outHm'), hp: q('outHp'), mmr: q('valueMrr'), fc: q('valueFc'), w: q('valueW'), eta: q('valueEta'), ae: q('outAe'), ap: q('outAp') };
let radar = null;
try {
  const ctx = q('radarChart');
  if (w.Chart && ctx) {
    radar = new w.Chart(ctx.getContext('2d'), {
      type: 'radar',
      data: {
        labels: ['Vida útil', 'Terminación', 'Potencia'],
        datasets: [{ data: [0,0,0], backgroundColor: 'rgba(79,195,247,0.35)', borderColor: 'rgba(79,195,247,0.8)', borderWidth: 2 }]
      },
      options: { scales: { r: { max: 100, ticks: { stepSize: 20 } } }, plugins: { legend: { display: false } } }
    });
  }
} catch (e) { dbg('chart fail', e); }

w.addEventListener('unload', () => {
  try {
    Object.keys(sessionStorage).forEach(k => {
      if (k.indexOf('step6:') === 0) sessionStorage.removeItem(k);
    });
  } catch (e) {}
});

const hash = (s) => { let h = 0, i; for (i = 0; i < s.length; i++) { h = (h << 5) - h + s.charCodeAt(i); h |= 0; } return h.toString(36); };
const showErr = (m) => { if (errBox) errBox.textContent = m; };
const clearErr = () => { if (errBox) errBox.textContent = ''; };
function setLoading(on) {
  if (step) step.classList.toggle('loading', !!on);
  Object.keys(sliders).forEach(k => {
    const s = sliders[k];
    if (s) s.disabled = !!on;
  });
}
function paint(d) {
  try {
    if (out.vc) out.vc.textContent = d.vc + ' m/min';
    if (out.fz) out.fz.textContent = d.fz + ' mm/tooth';
    if (out.rpm) out.rpm.textContent = d.n;
    if (out.feed) out.feed.textContent = d.vf + ' mm/min';
    if (out.hm) out.hm.textContent = d.hm + ' mm';
    if (out.hp) out.hp.textContent = d.hp + ' HP';
    if (out.mmr) out.mmr.textContent = d.mmr;
    if (out.fc) out.fc.textContent = d.fc;
    if (out.w) out.w.textContent = d.watts;
    if (out.eta) out.eta.textContent = d.etaPercent;
    if (out.ae) out.ae.textContent = d.ae.toFixed(2);
    if (out.ap) out.ap.textContent = d.ap.toFixed(3);
    if (radar && Array.isArray(d.radar)) {
      radar.data.datasets[0].data = d.radar;
      radar.update();
    }
  } catch (e) { dbg('paint err', e); }
}
function getPayload() {
  const base = window.step6Params || {};
  return {
    /* sliders */
    fz: parseFloat(sliders.fz.value),
    vc: parseFloat(sliders.vc.value),
    ae: parseFloat(sliders.ae.value),
    passes: parseInt(sliders.p.value, 10),
    /* extras requeridos por el backend */
    thickness: base.thickness,
    D: base.D,
    Z: base.Z,
    params: {
      fr_max: base.fr_max,
      coef_seg: base.coef_seg,
      Kc11: base.Kc11,
      mc: base.mc,
      alpha: base.alpha,
      eta: base.eta
    }
  };
}
function fetchData(body, key, retry) {
  const ctrl = new AbortController();
  const to = setTimeout(() => { ctrl.abort(); }, 8000);
  const t0 = performance.now();
  const url = w.step6AjaxUrl || 'ajax/step6_ajax_legacy_minimal.php';
  return fetch(url, {
    method: 'POST',
    body,
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken, 'Accept': '*/*' },
    credentials: 'same-origin',
    cache: 'no-store',
    signal: ctrl.signal
  }).then(r => {
    clearTimeout(to);
    grp('response', () => { dbg('status', r.status); });
    if (!r.ok) throw new Error('HTTP ' + r.status);
    return r.json();
  }).then(j => {
    if (!j.success) throw new Error(j.error || 'Error');
    sessionStorage.setItem(key, JSON.stringify({ ts: Date.now(), data: j.data }));
    paint(j.data);
    console.info('[step6] AJAX cargado correctamente');
  }).catch(e => {
    if (retry && (e.name === 'AbortError' || e.message === 'Failed to fetch'))
      return fetchData(body, key, false);
    showErr(e.message);
    console.error('[step6] Error AJAX:', e);
  }).finally(() => {
    setLoading(false);
    dbg('ms', (performance.now() - t0).toFixed(1));
  });
}
function recalc() {
  try {
    clearErr();
    const p = getPayload();
    const body = JSON.stringify(p);
    const key = 'step6:' + hash(body);
    let item;
    try { item = JSON.parse(sessionStorage.getItem(key) || 'null'); } catch (e) { item = null; }
    if (item && Date.now() - item.ts < ttl) paint(item.data);
    setLoading(true);
    grp('request', () => { dbg(p); });
    fetchData(body, key, true);
  } catch (e) { showErr(e.message); setLoading(false); }
}
const recalcDebounced = debounce(recalc, 200);
export function init() {
  try {
    ['fz', 'vc', 'ae', 'p'].forEach(k => { if (sliders[k]) sliders[k].addEventListener('input', recalcDebounced); });
    recalc();
  } catch (e) { showErr(e.message); }
}
console.info('[step6] init() exportado');
