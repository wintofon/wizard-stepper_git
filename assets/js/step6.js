/* =======================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC  (client‑side only)
 * -----------------------------------------------------------------------
 *  ▸ Calcula todos los parámetros técnicos (hm, MMR, FcT, kW, hp, etc.)
 *    SIN llamadas AJAX: todo se recalcula instantáneamente en el navegador.
 *  ▸ Sliders con “pared móvil”: no permiten valores que violen rpm o feed máx.
 *  ▸ Radar Chart (Vida Útil · Potencia · Terminación) siempre reactivo.
 *  ▸ Consola silenciosa — solo loguea cuando DEBUG = true.
 *
 *  2025‑06‑28 · Versión 4.3.1 — Fórmula FcT reescrita y comentada paso a paso.
 * =====================================================================*/
/* global Chart, window */

(() => {
  'use strict';

  /* ───────────────────────── DEBUG ───────────────────────── */
  const DEBUG  = window.DEBUG ?? false;
  const TAG    = '[Step6]';
  const stamp  = () => new Date().toISOString();
  const say    = (lvl, ...m) => { if (DEBUG) console[lvl](`${TAG} ${stamp()}`, ...m); };
  const log    = (...m) => say('log',   ...m);
  const warn   = (...m) => say('warn',  ...m);
  const error  = (...m) => say('error', ...m);
  const table  = d   => { if (DEBUG) console.table(d); };

  /* ───────────────────────── HELPERS ─────────────────────── */
  const $     = (sel, ctx = document) => ctx.querySelector(sel);
  const fmt   = (n, d = 1) => Number.parseFloat(n).toFixed(d);
  const diff  = (a = {}, b = {}) =>
    [...new Set([...Object.keys(a), ...Object.keys(b)])].some(k => a[k] !== b[k]);

  const fatal = msg => {
    const box = $('#errorMsg');
    if (box) { box.style.display = 'block'; box.textContent = msg; }
    else window.alert?.(msg);
  };

  /* ────────────── PARAMS INYECTADOS DESDE PHP ────────────── */
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
    diameter:    D,
    flute_count: Z,
    rpm_min:     RPM_MIN,
    rpm_max:     RPM_MAX,
    fr_max:      FR_MAX,
    coef_seg:    K_SEG,
    Kc11:        KC,
    mc,
    eta,
    alpha:       ALPHA = 0, // radianes
    fz0:         FZ0,
    vc0:         VC0,
    thickness:   THK,
    hp_avail:    HP_AVAIL,
    fz_min0:     FZ_MIN,
    fz_max0:     FZ_MAX
  } = P;

  /* ───────────────────────── STATE ───────────────────────── */
  const state = {
    fz: +FZ0,
    vc: +VC0,
    ae: D * 0.5,  // ancho default = ½ Ø (perfilado)
    ap: 1,
    last: {}
  };

  /* ───────────────────── DOM REFERENCES ─────────────────── */
  const SL = {
    fz:   $('#sliderFz'),
    vc:   $('#sliderVc'),
    ae:   $('#sliderAe'),
    pass: $('#sliderPasadas')
  };
  const OUT = {
    vc: $('#outVc'), fz: $('#outFz'), hm: $('#outHm'), n: $('#outN'), vf: $('#outVf'),
    hp: $('#outHp'), mmr: $('#valueMrr'), fc: $('#valueFc'), w: $('#valueW'), eta: $('#valueEta'),
    ae: $('#outAe'), ap: $('#outAp')
  };
  const infoPass = $('#textPasadasInfo');

  /* ────────────────────── FORMULAS ──────────────────────── */
  const rpm  = vc            => (vc * 1000) / (Math.PI * D);               // convierte Vc → rpm
  const feed = (n, fz)       => n * fz * Z;                                // feedrate (mm/min)
  const phi  = ae            => 2 * Math.asin(Math.min(1, ae / D));        // ángulo barrido
  const hm   = (fz, ae)      => { const p = phi(ae); return p ? fz * (1 - Math.cos(p)) / p : fz; };
  const mmr  = (ap, vf, ae)  => (ap * vf * ae) / 1000;                      // cm³/min

  /**
   * ► Fuerza de Corte Total (FcT)
   *   Implementa el modelo Kienzle:
   *   Fc = Kc1.1 · h_m^{-mc} · ap · fz · Z · (1 + k_seg·tanα)
   *   – Kc1.1 y mc dependen del material (inyectados desde PHP).
   *   – Factor geométrico añade sobrecarga por filo gastado / ataque.
   */
  const FcT = (hmV, ap, fz) => {
    const base   = KC * Math.pow(hmV, -mc);           // N/mm²
    const area   = ap * fz * Z;                       // mm² (ap · fz · Z)
    const factor = 1 + K_SEG * Math.tan(ALPHA);       // sin dimensión
    const F      = base * area * factor;              // N (fuerza total)

    if (DEBUG) {
      console.groupCollapsed(`${TAG} FcT detail`);
      console.log({KC, hmV, mc, ap, fz, Z, K_SEG, ALPHA, base, area, factor, F});
      console.groupEnd();
    }
    return F;
  };

  const kW = (F, Vc) => (F * Vc) / (60_000 * eta);    // potencia mecánica (kW)
  const HP = kWv     => kWv * 1.341;                  // kW → HP

  /* ──────────────────── RADAR CHART ─────────────────────── */
  let radar;
  const makeRadar = () => {
    const ctx = $('#radarChart')?.getContext('2d');
    if (!ctx || !window.Chart) return;
    radar?.destroy();
    radar = new Chart(ctx, {
      type:'radar',
      data:{labels:['Vida Útil','Potencia','Terminación'],datasets:[{data:[0,0,0],fill:true,borderWidth:2}]},
      options:{scales:{r:{min:0,max:100,ticks:{stepSize:20}}},plugins:{legend:{display:false}}}
    });
  };

  /* ─────────────────────── RENDER ───────────────────────── */
  const render = snap => {
    if (!diff(state.last, snap)) return; // sin cambios = sin reflow
    for (const k in snap) {
      const el = OUT[k]; if (!el) continue;
      const v = snap[k]; el.textContent = (typeof v === 'number') ? fmt(v, v % 1 ? 2 : 0) : v;
    }
    radar && (radar.data.datasets[0].data = [snap.life, snap.power, snap.finish], radar.update());
    state.last = snap;
  };

  /* ────────────────────── CALCULO LOCAL ─────────────────── */
  const recalc = () => {
    const N     = rpm(state.vc);
    const vfRaw = feed(N, state.fz);
    const vf    = Math.min(vfRaw, FR_MAX);
    const ap    = THK / state.ap;
    const hmV   = hm(state.fz, state.ae);
    const fcV   = FcT(hmV, ap, state.fz);
    const kWv   = kW(fcV, state.vc);
    const hpV   = HP(kWv);
    const mmrV  = mmr(ap, vf, state.ae);

    /* ► Ejes para Radar (0‑100 %) */
    const lifePct   = Math.min(100, Math.max(0, ((state.fz - FZ_MIN) / (FZ_MAX - FZ_MIN)) * 100));
    const powerPct  = Math.min(100, (hpV / HP_AVAIL) * 100);
    const finishPct = Math.max(0, 100 - lifePct);

    render({
      vc:state.vc, fz:state.fz, hm:hmV, n:Math.round(N), vf:Math.round(vf),
      hp:hpV, mmr:mmrV, fc:Math.round(fcV), w:Math.round(kWv*1000),
      eta:Math.round((hpV / HP_AVAIL) * 100), ae:state.ae, ap,
      life:lifePct, power:powerPct, finish:finishPct
    });
  };

  /* ───────────── SLIDER UI HELPER (burbuja) ────────────── */
  const prettify = (slider, dec = 2) => {
    if (!slider) return;
    const wrap = slider.closest('.slider-wrap');
    const bubble = wrap?.querySelector('.
