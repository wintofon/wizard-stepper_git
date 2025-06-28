/* =======================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC
 * -----------------------------------------------------------------------
 *  ▸ Cálculo y validación 100 % en cliente (sin AJAX).
 *  ▸ Slider Vc oscila entre −50 % y +50 % del valor base *pero* nunca
 *    sobrepasa las RPM mín / máx declaradas por la máquina.
 *  ▸ Slider «Pasadas» (ap) se recalcula cada vez que cambia el ancho ae;
 *    máx = ceil(thickness / ae).  Muestra info dinámica.
 *  ▸ Consola silenciosa: solo emite log si el *snapshot* cambia.
 *  ▸ Radar Chart a 3 ejes — Vida Útil · Potencia · Terminación.
 *      – ↑ fz ⇒ ↑ Vida Útil  &  ↑ Potencia  &  ↓ Terminación.
 *  ▸ Errores fatales ➜ se avisan en la UI.
 *
 *  @version   4.3.0  2025‑06‑27
 * =====================================================================*/

/* global Chart, window */

(() => {
  'use strict';

  /* ───────────────────────── DEBUG ───────────────────────── */
  const DEBUG  = window.DEBUG ?? false;
  const TAG    = '[Step6]';
  const stamp  = () => new Date().toISOString();
  const say    = (lvl, ...m) => { if (DEBUG) console[lvl](${TAG} ${stamp()}, ...m); };
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
    alpha:       ALPHA = 0,
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
    ae: D * 0.5,
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
  const rpm  = vc            => (vc*1000)/(Math.PI*D);
  const feed = (n,fz)        => n*fz*Z;
  const phi  = ae            => 2*Math.asin(Math.min(1, ae/D));
  const hm   = (fz,ae)       => { const p = phi(ae); return p ? fz*(1-Math.cos(p))/p : fz; };
  const mmr  = (ap,vf,ae)    => (ap*vf*ae)/1000;
  const Fct  = (h,ap,fz)     => KC*Math.pow(h,-mc)*ap*fz*Z*(1+K_SEG*Math.tan(ALPHA));
  const kW   = (F,Vc)        => (F*Vc)/(60_000*eta);
  const HP   = kWv           => kWv*1.341;

  /* ──────────────────── RADAR CHART ─────────────────────── */
  let radar;
  const makeRadar = () => {
    const c = $('#radarChart')?.getContext('2d');
    if (!c || !window.Chart) return;
    radar = new Chart(c,{type:'radar',data:{labels:['Vida Útil','Potencia','Terminación'],datasets:[{data:[0,0,0],fill:true,borderWidth:2}]},options:{scales:{r:{min:0,max:100,ticks:{stepSize:20}}},plugins:{legend:{display:false}}}});
  };

  /* ─────────────────────── RENDER ───────────────────────── */
  const render = snap => {
    if (!diff(state.last, snap)) return; // no hubo cambios
    for (const k in snap) {
      const el = OUT[k]; if (!el) continue;
      const v = snap[k]; el.textContent = (typeof v === 'number') ? fmt(v, v%1?2:0) : v;
    }
    if (radar) { radar.data.datasets[0].data = [snap.life,snap.power,snap.finish]; radar.update(); }
    state.last = snap; log('render', snap);
  };

  /* ────────────────────── CALCULO LOCAL ─────────────────── */
  const recalc = () => {
    const N    = rpm(state.vc);
    const vf   = Math.min(feed(N,state.fz), FR_MAX);
    const ap   = THK / state.ap;
    const hmV  = hm(state.fz,state.ae);
    const mmrV = mmr(ap,vf,state.ae);
    const fcV  = Fct(hmV,ap,state.fz);
    const kWv  = kW(fcV,state.vc);
    const hpV  = HP(kWv);

    /* Radar axes (0‑100 %) */
    const lifePct   = Math.min(100, Math.max(0, ((state.fz - FZ_MIN) / (FZ_MAX - FZ_MIN)) * 100));
    const powerPct  = Math.min(100, (hpV / HP_AVAIL) * 100);
    const finishPct = Math.max(0, 100 - lifePct);

    render({
      vc:state.vc, fz:state.fz, hm:hmV, n:N|0, vf:vf|0, hp:hpV, mmr:mmrV, fc:fcV|0,
      w:kWv*1000|0, eta:Math.min(100,(hpV/HP_AVAIL)*100)|0, ae:state.ae, ap,
      life:lifePct, power:powerPct, finish:finishPct
    });
  };

  /* ───────────── SLIDER UI HELPER (burbuja) ────────────── */
  const prettify = (slider, dec=3) => {
    if (!slider) return;
    const wrap = slider.closest('.slider-wrap');
    const bub  = wrap?.querySelector('.slider-bubble');
    const min  = +slider.min; const max = +slider.max;
    const show = v => { wrap?.style.setProperty('--val', ((v-min)/(max-min))*100); if(bub) bub.textContent = fmt(v,dec); };
    show(+slider.value);
    slider.addEventListener('input', e=>{ show(+e.target.value); onInput(); });
  };

  /* ──────────────── ACTUALIZAR PASADAS ──────────────── */
  const syncPassSlider = () => {
    const maxP = Math.max(1, Math.ceil(THK / state.ae));
    SL.pass.max = maxP; SL.pass.min = 1; SL.pass.step = 1;
    if (+SL.pass.value > maxP) SL.pass.value = maxP;
    state.ap = +SL.pass.value;
    if (infoPass) infoPass.textContent = ${state.ap} pasada${state.ap>1?'s':''} de ${(THK/state.ap).toFixed(2)} mm;
  };

  /* ───────────── INPUT HANDLER (todos sliders) ─────────── */
  const onInput = () => {
    state.fz = +SL.fz.value;
    state.vc = +SL.vc.value;
    state.ae = +SL.ae.value;
    syncPassSlider();
    recalc();
  };

  /* ─────────────────────────── INIT ────────────────────── */
  try {
    /* Vc slider límites: ±50 % del VC0 + respeta RPM min/max */
    const vcFromRPMmin = (RPM_MIN * Math.PI * D)/1000;
    const vcFromRPMmax = (RPM_MAX * Math.PI * D)/1000;
    const vcMin = Math.max(VC0*0.5, vcFromRPMmin);
    const vcMax = Math.min(VC0*1.5, vcFromRPMmax);
    SL.vc.min = fmt(vcMin,1);
    SL.vc.max = fmt(vcMax,1);
    SL.vc.value = fmt(state.vc,1);

    /* Pasadas slider arranca en 1 */
    SL.pass.value = 1;

    /* Embellecer sliders & listeners */
    prettify(SL.fz,4); prettify(SL.vc,1); prettify(SL.ae,2); prettify(SL.pass,0);
    ['fz','vc','ae','pass'].forEach(k=>SL[k]?.addEventListener('change', onInput));

    /* Radar + render inicial */
    makeRadar();
    syncPassSlider();
    recalc();

    log('init OK');
  } catch (e) {
    error('init', e); fatal('JS: ' + e.message);
  }
})();
