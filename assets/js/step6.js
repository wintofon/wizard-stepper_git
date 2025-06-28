/* =======================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC
 * -----------------------------------------------------------------------
 *  ▸ Calcula/valida parámetros de corte 100 % en cliente.
 *  ▸ Slider Vc se mueve −50 %/ +50 % alrededor del valor base.
 *  ▸ Slider «Pasadas» ajusta su límite máximo según espesor del material
 *    y muestra info dinámica.
 *  ▸ Consola súper‑silenciosa – solo log cuando cambia el snapshot.
 *  ▸ Cualquier error fatal se refleja en la UI.
 *
 *  @version   4.1.0  2025‑06‑27
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
  const diff  = (a = {}, b = {}) => [...new Set([...Object.keys(a), ...Object.keys(b)])]
      .some(k => a[k] !== b[k]);

  const fatal = msg => {
    const box = $('#errorMsg');
    if (box) { box.style.display = 'block'; box.textContent = msg; }
    else window.alert?.(msg);
  };

  /* ────────────── PARAMS INYECTADOS DESDE PHP ────────────── */
  const P = window.step6Params;
  if (!P || Object.keys(P).length === 0) return fatal('step6Params vacío');

  const NEED = ['diameter','flute_count','rpm_min','rpm_max','fr_max','coef_seg','Kc11','mc','eta','fz0','vc0','thickness'];
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
    mc, eta,
    alpha:       ALPHA = 0,
    fz0:         FZ0,
    vc0:         VC0,
    thickness:   THK,
    hp_avail:    HP_AVAIL = 1
  } = P;

  /* ───────────────────────── STATE ───────────────────────── */
  const state = {
    fz:   +FZ0,
    vc:   +VC0,
    ae:   D * 0.5,
    ap:   1,
    last: {}
  };

  /* ───────────────────── DOM REFERENCES ─────────────────── */
  const SL = { fz:$('#sliderFz'), vc:$('#sliderVc'), ae:$('#sliderAe'), pass:$('#sliderPasadas') };
  const OUT = {
    vc:$('#outVc'), fz:$('#outFz'), hm:$('#outHm'), n:$('#outN'), vf:$('#outVf'),
    hp:$('#outHp'), mmr:$('#valueMrr'), fc:$('#valueFc'), w:$('#valueW'), eta:$('#valueEta'),
    ae:$('#outAe'), ap:$('#outAp')
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
    radar = new Chart(c,{type:'radar',data:{labels:['MMR','Fc','W','Hp','η'],datasets:[{data:[0,0,0,0,0],fill:true,borderWidth:2}]},options:{scales:{r:{min:0,max:1}},plugins:{legend:{display:false}}}});
  };

  /* ─────────────────────── RENDER ───────────────────────── */
  const render = data => {
    if (!diff(state.last, data)) return; // no cambió nada
    for (const k in data) {
      const el = OUT[k]; if (!el) continue;
      const v = data[k]; el.textContent = (typeof v === 'number') ? fmt(v, v%1?2:0) : v;
    }
    if (radar) {
      const [mmrV,fcV,wV,hpV,etaV] = [data.mmr,data.fc,data.w,data.hp,data.eta];
      radar.data.datasets[0].data = [Math.min(1,mmrV/1e5),Math.min(1,fcV/1e4),Math.min(1,wV/3000),Math.min(1,hpV/HP_AVAIL),Math.min(1,etaV/100)];
      radar.update();
    }
    state.last = data; log('render', data);
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
    render({vc:state.vc,fz:state.fz,hm:hmV,n:N|0,vf:vf|0,hp:HP(kWv),mmr:mmrV,fc:fcV|0,w:kWv*1000|0,eta:Math.min(100,(HP(kWv)/HP_AVAIL)*100)|0,ae:state.ae,ap});
  };

  /* ───────────── SLIDER UI HELPER (con burbuja) ─────────── */
  const prettify = (slider, dec=3) => {
    if (!slider) return;
    const wrap = slider.closest('.slider-wrap');
    const bub  = wrap?.querySelector('.slider-bubble');
    const min  = +slider.min; const max = +slider.max;
    const show = v => { wrap?.style.setProperty('--val', ((v-min)/(max-min))*100); if(bub) bub.textContent = fmt(v,dec); };
    show(+slider.value); slider.addEventListener('input', e=>{ show(+e.target.value); onInput(); });
  };

  /* ──────────────── ACTUALIZAR PASADAS ──────────────── */
  const syncPassSlider = () => {
    const maxP = Math.max(1, Math.ceil(THK)); // máx = 1 pasada/mm (simple)
    SL.pass.max = maxP; SL.pass.min = 1; SL.pass.step = 1;
    if (+SL.pass.value > maxP) SL.pass.value = maxP;
    state.ap = +SL.pass.value;
    if (infoPass) infoPass.textContent = `${state.ap} pasada${state.ap>1?'s':''} de ${(THK/state.ap).toFixed(2)} mm`;
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
    /* Vc slider ±50 % del valor base */
    const vcMin = VC0 * 0.5;
    const vcMax = VC0 * 1.5;
    SL.vc.min = vcMin.toFixed(1);
    SL.vc.max = vcMax.toFixed(1);
    SL.vc.value = fmt(state.vc,1);

    /* Pasadas slider base */
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
