/* ========================================================================
 *  assets/js/step6.js · PASO 6 — Wizard CNC  ·  v4.4.0  2025-06-28
 * ------------------------------------------------------------------------
 *  ◉ 100 % cálculo en cliente, sin AJAX.
 *  ◉ Slider Vc oscila ±50 % de Vc₀ **pero** nunca sobrepasa RPM min/max.
 *  ◉ Slider «Pasadas» se recalcula al mover el ancho ae.
 *  ◉ Consola solo loguea cuando el *snapshot* cambia.
 *  ◉ Radar Chart (3 ejes) = Vida Útil · Potencia · Terminación.
 *      – ↑ fz ⇒ ↑ Vida Útil  &  ↑ Potencia  &  ↓ Terminación.
 *  ◉ Debug opcional súper detallado en el cálculo de FcT.
 * ======================================================================*/

/* global Chart, window */

(() => {
  'use strict';

/* ────────────────────────────── DEBUG ────────────────────────────── */
  const DEBUG = window.DEBUG ?? false;
  const TAG   = '[Step-6]';
  const ts    = () => new Date().toISOString();
  const say   = (lvl, ...m) => { if (DEBUG) console[lvl](`${TAG} ${ts()}`, ...m); };
  const log   = (...m) => say('log',   ...m);
  const warn  = (...m) => say('warn',  ...m);
  const error = (...m) => say('error', ...m);
  const table = data  => { if (DEBUG) console.table(data); };

/* ───────────────────────────── HELPERS ───────────────────────────── */
  const $   = (sel, ctx = document) => ctx.querySelector(sel);
  const fx  = (n, d = 1) => Number.parseFloat(n).toFixed(d);
  const diff = (a = {}, b = {}) =>
    [...new Set([...Object.keys(a), ...Object.keys(b)])].some(k => a[k] !== b[k]);

  const fatal = msg => {
    const box = $('#errorMsg');
    if (box) { box.style.display = 'block'; box.textContent = msg; }
    else     { window.alert?.(msg); }
  };

/* ────────────────── PARÁMETROS INYECTADOS POR PHP ───────────────── */
  const P = window.step6Params;
  if (!P || Object.keys(P).length === 0) { fatal('step6Params vacío'); return; }

  const REQ = [
    'diameter','flute_count','rpm_min','rpm_max','fr_max',
    'coef_seg','Kc11','mc','eta','fz0','vc0','thickness',
    'fz_min0','fz_max0','hp_avail'
  ];
  const miss = REQ.filter(k => P[k] === undefined);
  if (miss.length) { fatal('Faltan claves: ' + miss.join(', ')); return; }

/* ──────────────────────── DESTRUCTURACIÓN ───────────────────────── */
  const {
    diameter    : D,
    flute_count : Z,
    rpm_min     : RPM_MIN,
    rpm_max     : RPM_MAX,
    fr_max      : FR_MAX,
    coef_seg    : K_SEG,
    Kc11        : KC,
    mc,
    eta,
    alpha       : ALPHA = 0,
    fz0         : FZ0,
    vc0         : VC0,
    thickness   : THK,
    hp_avail    : HP_AVAIL,
    fz_min0     : FZ_MIN,
    fz_max0     : FZ_MAX
  } = P;

/* ─────────────────────────── STATE ──────────────────────────────── */
  const state = {
    fz   : +FZ0,
    vc   : +VC0,
    ae   : D * 0.5,
    ap   : 1,
    last : {}       // último snapshot renderizado
  };

/* ───────────────────── REFERENCIAS DOM ──────────────────────────── */
  const SL = {
    fz   : $('#sliderFz'),
    vc   : $('#sliderVc'),
    ae   : $('#sliderAe'),
    pass : $('#sliderPasadas')
  };
  const OUT = {
    vc : $('#outVc'),    fz : $('#outFz'),    hm : $('#outHm'),
    n  : $('#outN'),     vf : $('#outVf'),    hp : $('#outHp'),
    mmr: $('#valueMrr'), fc : $('#valueFc'),  w  : $('#valueW'),
    eta: $('#valueEta'), ae : $('#outAe'),    ap : $('#outAp')
  };
  const infoPass = $('#textPasadasInfo');

/* ─────────────────────── FORMULAS CNC ───────────────────────────── */
  const rpm   = vc           => (vc * 1000) / (Math.PI * D);
  const feed  = (n, fz)      => n * fz * Z;
  const phi   = ae           => 2 * Math.asin(Math.min(1, ae / D));
  const hm    = (fz, ae)     => { const p = phi(ae); return p ? fz * (1 - Math.cos(p)) / p : fz; };
  const mmr   = (ap, vf, ae) => (ap * vf * ae) / 1000;
  const kW    = (F, Vc)      => (F * Vc) / (60_000 * eta);
  const HP    = kWval        => kWval * 1.341;

  /* ——— Fuerza de Corte Total (Kienzle corregida) ——— */
  const FcT = (hmv, ap, fz) => {
    const Ac = ap * fz;                       // sección viruta por filo (mm²)
    const Kc = KC * Math.pow(hmv, -mc);       // coef. específico instantáneo
    const Fc_per_tooth = Kc * Ac;             // N
    const geoFactor    = 1 + K_SEG * Math.tan(ALPHA);
    const Fc_total     = Fc_per_tooth * Z * geoFactor;

    if (DEBUG) {
      console.groupCollapsed(`${TAG} FcT (fuerza total)`);
      console.log('Kc11           =', KC, 'N/mm²');
      console.log('h_m            =', fx(hmv,4), 'mm');
      console.log('mc             =', mc);
      console.log('Kc(h_m)        =', fx(Kc,2), 'N/mm²');
      console.log('ap             =', fx(ap,3), 'mm');
      console.log('fz             =', fx(fz,4), 'mm/diente');
      console.log('Ac (ap·fz)     =', fx(Ac,4), 'mm²');
      console.log('Z activos      =', Z);
      console.log('coef_seg       =', K_SEG);
      console.log('alpha (rad)    =', ALPHA);
      console.log('factor geom    =', fx(geoFactor,3));
      console.log('Fc / filo      =', fx(Fc_per_tooth,1), 'N');
      console.log('FcT total      =', fx(Fc_total,1),     'N');
      console.groupEnd();
    }
    return Fc_total;
  };

/* ─────────────────────── RADAR CHART ────────────────────────────── */
  let radar;
  const initRadar = () => {
    const ctx = $('#radarChart')?.getContext('2d');
    if (!ctx || !window.Chart) return;
    radar = new Chart(ctx, {
      type   : 'radar',
      data   : {
        labels   : ['Vida Útil','Potencia','Terminación'],
        datasets : [{ data:[0,0,0], fill:true, borderWidth:2 }]
      },
      options: {
        scales : { r:{ min:0, max:100, ticks:{ stepSize:20 } } },
        plugins: { legend:{ display:false } }
      }
    });
  };

/* ───────────────────────── RENDER ───────────────────────────────── */
  const render = snap => {
    if (!diff(state.last, snap)) return;        // no hay cambios
    for (const k in snap) {
      const el = OUT[k]; if (!el) continue;
      const v  = snap[k];
      el.textContent = (typeof v === 'number') ? fx(v, v % 1 ? 2 : 0) : v;
    }
    if (radar) { radar.data.datasets[0].data = [snap.life, snap.power, snap.finish]; radar.update(); }
    state.last = snap;
  };

/* ─────────────────────── RECÁLCULO ─────────────────────────────── */
  const recalc = () => {
    const N    = rpm(state.vc);
    const vf   = Math.min(feed(N, state.fz), FR_MAX);
    const ap   = THK / state.ap;
    const hmV  = hm(state.fz, state.ae);
    const mmrV = mmr(ap, vf, state.ae);
    const fcV  = FcT(hmV, ap, state.fz);
    const kWv  = kW(fcV, state.vc);
    const hpV  = HP(kWv);

    /* Ejes radar (0-100 %) */
    const lifePct   = Math.min(100, ((state.fz - FZ_MIN) / (FZ_MAX - FZ_MIN)) * 100);
    const powerPct  = Math.min(100, (hpV / HP_AVAIL) * 100);
    const finishPct = Math.max(0, 100 - lifePct);

    render({
      vc:state.vc, fz:state.fz, hm:hmV, n:N|0, vf:vf|0, hp:hpV,
      mmr:mmrV, fc:fcV|0, w:kWv*1000|0,
      eta:Math.min(100, (hpV/HP_AVAIL)*100)|0,
      ae:state.ae, ap,
      life:lifePct, power:powerPct, finish:finishPct
    });
  };

/* ──────────────────── SLIDER VISUAL/BURBUJA ─────────────────────── */
  const beautifySlider = (slider, dec = 3) => {
    if (!slider) return;
    const wrap = slider.closest('.slider-wrap');
    const bub  = wrap?.querySelector('.slider-bubble');
    const min  = +slider.min; const max = +slider.max;
    const disp = v => {
      wrap?.style.setProperty('--val', ((v - min) / (max - min)) * 100);
      if (bub) bub.textContent = fx(v, dec);
    };
    disp(+slider.value);
    slider.addEventListener('input', e => { disp(+e.target.value); onInput(); });
  };

/* ─────────────── SINCRONIZAR SLIDER PASADAS ─────────────────────── */
  const syncPass = () => {
    const maxP = Math.max(1, Math.ceil(THK / state.ae));
    SL.pass.max = maxP; SL.pass.min = 1; SL.pass.step = 1;
    if (+SL.pass.value > maxP) SL.pass.value = maxP;
    state.ap = +SL.pass.value;
    if (infoPass)
      infoPass.textContent = `${state.ap} pasada${state.ap > 1 ? 's' : ''} · ${(THK / state.ap).toFixed(2)} mm`;
  };

/* ────────────────────── INPUT HANDLER ───────────────────────────── */
  const onInput = () => {
    state.fz = +SL.fz.value;
    state.vc = +SL.vc.value;
    state.ae = +SL.ae.value;
    syncPass();
    recalc();
  };

/* ─────────────────────────── INIT ───────────────────────────────── */
  try {
    /* ——— Slider Vc limitado por ±50 % y RPM min/max ——— */
    const vcFromRPMmin = (RPM_MIN * Math.PI * D) / 1000;
    const vcFromRPMmax = (RPM_MAX * Math.PI * D) / 1000;
    const vcMin = Math.max(VC0 * 0.5, vcFromRPMmin);
    const vcMax = Math.min(VC0 * 1.5, vcFromRPMmax);
    SL.vc.min = fx(vcMin, 1);
    SL.vc.max = fx(vcMax, 1);
    SL.vc.value = fx(state.vc, 1);

    /* Slider Pasadas arranca en 1 */
    SL.pass.value = 1;

    /* Embellecer sliders + listeners */
    beautifySlider(SL.fz, 4);
    beautifySlider(SL.vc, 1);
    beautifySlider(SL.ae, 2);
    beautifySlider(SL.pass, 0);
    ['fz','vc','ae','pass'].forEach(k => SL[k]?.addEventListener('change', onInput));

    /* Radar + primera pasada */
    initRadar();
    syncPass();
    recalc();

    log('init listo');
  } catch (e) {
    error('init error:', e); fatal('JS: ' + e.message);
  }
})();
