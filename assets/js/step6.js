/* =======================================================================
 *  assets/js/step6.js · PASO 6 — Wizard CNC  ·  v4 · 2025-06-28
 * -----------------------------------------------------------------------
 *  ▸ 100 % cálculo local (sin llamadas AJAX).
 *  ▸ Sliders “fz” y “Vc” respetan **todas** las restricciones:
 *        – Vc ± 50 % sobre el valor base,
 *        – pero nunca por fuera de RPM min / RPM max ni Feedrate max.
 *  ▸ El slider “Pasadas” (ap) se reajusta cada vez que cambia el ancho ae:
 *        max = ceil( thickness / ae ).
 *  ▸ Consola “silenciosa”: sólo emite log cuando el *snapshot* cambió.
 *  ▸ Radar Chart (3 ejes)
 *        1️⃣ Vida útil ↑ con hₘ bajo
 *        2️⃣ Consumo de potencia proporcional a HP
 *        3️⃣ Terminación inversamente proporcional a Vida útil
 *  ▸ Implementa **modelo de Kienzle** corregido:
 *        FcT = Kc11 · hₘ^(–mc) · ap · Z · (1+coef·tan α)
 *        P   = ( Kc11·hₘ^(–mc) · ap · ae · Vf ) / 60 000   [kW]
 * =====================================================================*/

/* global Chart, window */

(() => {
  'use strict';

  /* ───────────────────────── DEBUG ───────────────────────── */
  const DEBUG = window.DEBUG ?? false;
  const TAG   = '[Step-6]';
  const stamp = () => new Date().toISOString();
  const say   = (lvl, ...m) => { if (DEBUG) console[lvl](`${TAG} ${stamp()}`, ...m); };
  const log   = (...m) => say('log',   ...m);
  const warn  = (...m) => say('warn',  ...m);
  const error = (...m) => say('error', ...m);
  const table = d => { if (DEBUG) console.table(d); };

  /* ───────────────────────── HELPERS ─────────────────────── */
  const $   = (sel, ctx = document) => ctx.querySelector(sel);
  const fmt = (n, d = 1) => Number.parseFloat(n).toFixed(d);
  const diff = (a = {}, b = {}) =>
    [...new Set([...Object.keys(a), ...Object.keys(b)])].some(k => a[k] !== b[k]);

  const fatal = msg => {
    const box = $('#errorMsg');
    if (box) { box.style.display = 'block'; box.textContent = msg; }
    else window.alert?.(msg);
  };

  /* ─────────────── PARAMS INYECTADOS DESDE PHP ───────────── */
  const P = window.step6Params;
  if (!P || Object.keys(P).length === 0) return fatal('step6Params vacío');

  const NEED = [
    'diameter','flute_count','rpm_min','rpm_max','fr_max',
    'coef_seg','Kc11','mc','eta','fz0','vc0','thickness',
    'fz_min0','fz_max0','hp_avail'
  ];
  const miss = NEED.filter(k => P[k] === undefined);
  if (miss.length) return fatal('Faltan claves: ' + miss.join(', '));

  /* ────────────── DESTRUCTURACIÓN CON DEFAULTS ───────────── */
  const {
    diameter: D,                  // mm
    flute_count: Z,               // filos
    rpm_min: RPM_MIN,
    rpm_max: RPM_MAX,
    fr_max: FR_MAX,               // mm/min
    coef_seg: K_SEG,
    Kc11: KC,                     // N/mm²
    mc,                           // exponente
    eta,                          // eficiencia (1→100 %)
    alpha: ALPHA = 0,             // rad
    fz0:  FZ0,
    vc0:  VC0,
    thickness: THK,               // mm
    hp_avail: HP_AVAIL,           // HP máquina
    fz_min0: FZ_MIN,
    fz_max0: FZ_MAX
  } = P;

  /* ─────────────────────── STATE ────────────────────────── */
  const state = {
    fz: +FZ0,
    vc: +VC0,
    ae: D * 0.5,   // 50 % diámetro
    ap: 1,         // nº de pasadas
    last: {}       // último snapshot renderizado
  };

  /* ───────────────────── DOM REFERENCES ─────────────────── */
  const SL = {
    fz:   $('#sliderFz'),
    vc:   $('#sliderVc'),
    ae:   $('#sliderAe'),
    pass: $('#sliderPasadas')
  };
  const OUT = {
    vc: $('#outVc'), fz: $('#outFz'), hm: $('#outHm'), n: $('#outN'),
    vf: $('#outVf'), hp: $('#outHp'), mmr: $('#valueMrr'), fc: $('#valueFc'),
    w: $('#valueW'), eta: $('#valueEta'), ae: $('#outAe'), ap: $('#outAp')
  };
  const infoPass = $('#textPasadasInfo');

  /* ───────────────────── FORMULAS BASE ──────────────────── */
  const rpm      = vc          => (vc * 1000) / (Math.PI * D);                  // rev/min
  const feed     = (n, fz)     => n * fz * Z;                                   // mm/min
  const phi      = ae          => 2 * Math.asin(Math.min(1, ae / D));           // rad
  const hm       = (fz, ae)    => { const p = phi(ae); return p ? fz * (1-Math.cos(p)) / p : fz; };

  /* Modelo Kienzle corregido */
  const forceTangential = (hmv, apmm) => {
    const Kc = KC * Math.pow(hmv, -mc);                 // N/mm²
    const geom = 1 + K_SEG * Math.tan(ALPHA);           // factor geométrico
    return Kc * apmm * Z * geom;                        // N
  };

  /* Potencia a partir de MRR */
  const cuttingPowerKW = (ap, ae, vf, hmv) => {
    const Kc = KC * Math.pow(hmv, -mc);
    return (Kc * ap * ae * vf) / 60000;                 // kW
  };

  /* ────────────────────── RADAR CHART ───────────────────── */
  let radar;
  const makeRadar = () => {
    const ctx = $('#radarChart')?.getContext('2d');
    if (!ctx || !window.Chart) return;
    radar = new Chart(ctx,{
      type:'radar',
      data:{labels:['Vida Útil','Potencia','Terminación'],
            datasets:[{data:[0,0,0],fill:true,borderWidth:2}]},
      options:{scales:{r:{min:0,max:100,ticks:{stepSize:20}}},
               plugins:{legend:{display:false}}}
    });
  };

  /* ─────────────────────── RENDER ───────────────────────── */
  const render = snap => {
    if (!diff(state.last, snap)) return;           // sin cambios → no log
    for (const k in snap) {
      const el = OUT[k]; if (!el) continue;
      const v = snap[k];
      el.textContent = (typeof v === 'number') ? fmt(v, v % 1 ? 2 : 0) : v;
    }
    if (radar) {
      radar.data.datasets[0].data = [snap.life, snap.power, snap.finish];
      radar.update();
    }
    state.last = snap;
    log('render', snap);
  };

  /* ──────────────────── CÁLCULO PRINCIPAL ───────────────── */
  const recalc = () => {
    const N    = rpm(state.vc);
    const vf   = Math.min(feed(N, state.fz), FR_MAX);
    const apmm = THK / state.ap;
    const hmV  = hm(state.fz, state.ae);
    const mmrV = (apmm * vf * state.ae) / 1000;          // cm³/min
    const FcT  = forceTangential(hmV, apmm);             // N
    const kW   = cuttingPowerKW(apmm, state.ae, vf, hmV);
    const hpV  = kW * 1.341;

    /* ─── Radar: 0-100 % normalizado ─── */
    const lifePct   = Math.min(100, (FZ_MAX - state.fz)  / (FZ_MAX - FZ_MIN) * 100);
    const powerPct  = Math.min(100, (hpV    / HP_AVAIL)  * 100);
    const finishPct = 100 - lifePct;                     // inverso a vida

    render({
      vc: state.vc, fz: state.fz, hm: hmV, n: Math.round(N),
      vf: Math.round(vf), hp: hpV, mmr: mmrV, fc: Math.round(FcT),
      w:  Math.round(kW * 1000), eta: Math.round((hpV/HP_AVAIL)*100),
      ae: state.ae, ap: apmm,
      life: lifePct, power: powerPct, finish: finishPct
    });
  };

  /* ─────── Embellece slider (burbuja y CSS var) ─────────── */
  const prettify = (slider, decimals=3) => {
    if (!slider) return;
    const wrap = slider.closest('.slider-wrap');
    const bub  = wrap?.querySelector('.slider-bubble');
    const min  = +slider.min; const max = +slider.max;
    const draw = v=>{
      wrap?.style.setProperty('--val', ((v-min)/(max-min))*100);
      if (bub) bub.textContent = fmt(v,decimals);
    };
    draw(+slider.value);
    slider.addEventListener('input', e=>{ draw(+e.target.value); onInput(); });
  };

  /* ──────── Slider Pasadas depende de ae ─────────── */
  const syncPassSlider = () => {
    const maxP = Math.max(1, Math.ceil(THK / state.ae));
    SL.pass.max = maxP; SL.pass.min = 1; SL.pass.step = 1;
    if (+SL.pass.value > maxP) SL.pass.value = maxP;
    state.ap = +SL.pass.value;
    if (infoPass) infoPass.textContent =
      `${state.ap} pasada${state.ap>1?'s':''} de ${(THK/state.ap).toFixed(2)} mm`;
  };

  /* ──────────── Handler común de sliders ─────────── */
  const onInput = () => {
    state.fz = +SL.fz.value;
    state.vc = +SL.vc.value;
    state.ae = +SL.ae.value;
    syncPassSlider();
    recalc();
  };

  /* ───────────────────────── INIT ──────────────────────── */
  try {
    /* ── Límites Vc: ±50 % VC0 & dentro de RPM permitidas ── */
    const vcFromRPMmin = (RPM_MIN * Math.PI * D) / 1000;
    const vcFromRPMmax = (RPM_MAX * Math.PI * D) / 1000;
    const vcMin = Math.max(VC0 * 0.5, vcFromRPMmin);
    const vcMax = Math.min(VC0 * 1.5, vcFromRPMmax);
    SL.vc.min = fmt(vcMin,1);
    SL.vc.max = fmt(vcMax,1);
    SL.vc.value = fmt(state.vc,1);

    /* Slider Pasadas arranca en 1 */
    SL.pass.value = 1;

    /* Embellecer sliders + listeners */
    prettify(SL.fz,4); prettify(SL.vc,1); prettify(SL.ae,2); prettify(SL.pass,0);
    ['fz','vc','ae','pass'].forEach(k => SL[k]?.addEventListener('change', onInput));

    /* Radar + primer render */
    makeRadar();
    syncPassSlider();
    recalc();

    log('init OK');
  } catch (e) {
    error('init', e);
    fatal('JS: ' + e.message);
  }
})();
