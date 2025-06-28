/* =======================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC
 * -----------------------------------------------------------------------
 *  ▸ Cálculo y validación 100 % en cliente (sin AJAX).
 *  ▸ Slider Vc oscila entre −50 % y +50 % del valor base *pero* nunca
 *    sobrepasa las RPM mín / máx declaradas por la máquina.
 *  ▸ Slider «Pasadas» (ap) se recalcula cada vez que cambia el ancho ae;
 *    máx = ceil(thickness / ae).  Muestra info dinámica.
 *  ▸ Consola silenciosa: solo emite log si el *snapshot* cambia.
 *  ▸ Radar Chart a 3 ejes — Vida Útil · Potencia · Terminación.
 *      – ↑ fz ⇒ ↑ Vida Útil  &  ↑ Potencia  &  ↓ Terminación.
 *  ▸ Errores fatales ➜ se avisan en la UI.
 *
 *  @version   4.4.0  2025-06-29
 * =====================================================================*/

/* global Chart, window */

(() => {
  'use strict';

  /* ───────────────────────── 0 · DEBUG ───────────────────────── */
  const DEBUG = window.DEBUG ?? false;
  const TAG   = '[Step-6]';
  const now   = () => new Date().toISOString();
  const say   = (lvl, ...m) => { if (DEBUG) console[lvl](`${TAG} ${now()}`, ...m); };
  const log   = (...m) => say('log',   ...m);
  const warn  = (...m) => say('warn',  ...m);
  const error = (...m) => say('error', ...m);
  const table = d => { if (DEBUG) console.table(d); };

  /* ─────────────────────── 1 · HELPERS ─────────────────────── */
  const $   = (sel, ctx = document) => ctx.querySelector(sel);
  const fmt = (n, d = 1) => Number.parseFloat(n).toFixed(d);

  /** small diff util — retorna true si cambió algo */
  const changed = (a = {}, b = {}) =>
    [...new Set([...Object.keys(a), ...Object.keys(b)])].some(k => a[k] !== b[k]);

  /** fatal UI message */
  const fatal = msg => {
    const box = $('#errorMsg');
    box ? (box.style.display = 'block', box.textContent = msg) :
          window.alert?.(msg);
  };

  /* ───────────── 2 · PARAMS INYECTADOS DESDE PHP ───────────── */
  const P = window.step6Params;
  if (!P || Object.keys(P).length === 0) return fatal('step6Params vacío');

  const NEED = [
    'diameter','flute_count','rpm_min','rpm_max','fr_max',
    'coef_seg','Kc11','mc','eta','fz0','vc0','thickness',
    'fz_min0','fz_max0','hp_avail'
  ];
  const miss = NEED.filter(k => P[k] === undefined);
  if (miss.length) return fatal('Faltan claves: ' + miss.join(', '));

  const {
    diameter:    D,
    flute_count: Z,
    rpm_min:     RPM_MIN,
    rpm_max:     RPM_MAX,
    fr_max:      FR_MAX,
    coef_seg:    K_SEG,
    Kc11:        KC11,
    mc,
    eta,
    alpha:       ALPHA = 0,     // rad
    fz0:         FZ0,
    vc0:         VC0,
    thickness:   THK,
    hp_avail:    HP_AVAIL,
    fz_min0:     FZ_MIN,
    fz_max0:     FZ_MAX
  } = P;

  /* ───────────────────── 3 · STATE & DOM ───────────────────── */
  const state = {
    fz: +FZ0,
    vc: +VC0,
    ae: D * 0.5,
    ap: 1,
    last: {}
  };

  const SL = {
    fz:   $('#sliderFz'),
    vc:   $('#sliderVc'),
    ae:   $('#sliderAe'),
    pass: $('#sliderPasadas')
  };
  const OUT = {
    vc: $('#outVc'), fz: $('#outFz'), hm: $('#outHm'), n: $('#outN'),
    vf: $('#outVf'), hp: $('#outHp'), mmr: $('#valueMrr'),
    fc: $('#valueFc'), w: $('#valueW'), eta: $('#valueEta'),
    ae: $('#outAe'), ap: $('#outAp')
  };
  const infoPass = $('#textPasadasInfo');

  /* ─────────────────── 4 · FÓRMULAS BASE ──────────────────── */
  // 4.1 Geometría / cinemática
  const rpm  = vc            => (vc * 1000) / (Math.PI * D);   // rev/min
  const feed = (n,fz)        => n * fz * Z;                    // mm/min
  const phi  = ae            => 2 * Math.asin(Math.min(1, ae / D));
  const hm   = (fz,ae)       => { const p = phi(ae); return p ? fz*(1-Math.cos(p))/p : fz; };

  // 4.2 Esfuerzo específico corregido (Kienzle)
  const Kc_h = h => KC11 * Math.pow(h, -mc);                   // N/mm²

  // 4.3 Fuerza tangencial total (SLOT 100 % · incluye corrección geom.)
  const FcT = (h, ap) => {
    const base   = Kc_h(h);
    const factor = 1 + K_SEG * Math.tan(ALPHA);
    return base * ap * Z * factor;                             // N
  };

  // 4.4 MRR y Potencia de corte
  const mmr  = (ap, vf, ae)   => (ap * ae * vf) / 1000;        // cm³/min
  const Pcut = (h, ap, ae, vf) =>
      (Kc_h(h) * ap * ae * vf) / (60_000 * eta);               // kW

  /* ──────────────── 5 · RADAR CHART (3 ejes) ─────────────── */
  let radar = null;
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

  /* ──────────────────── 6 · RENDER UI ────────────────────── */
  const render = snap => {
    if (!changed(state.last, snap)) return; // evita spam
    for (const k in snap) {
      const el = OUT[k]; if (!el) continue;
      const v = snap[k]; el.textContent = (typeof v === 'number') ? fmt(v, v%1?2:0) : v;
    }
    radar && (radar.data.datasets[0].data = [snap.life,snap.power,snap.finish],
              radar.update());
    state.last = snap;
  };

  /* ─────────────────── 7 · CÁLCULO CENTRAL ───────────────── */
  const recalc = () => {
    const N      = rpm(state.vc);
    const vf     = Math.min(feed(N, state.fz), FR_MAX);
    const ap_mm  = THK / state.ap;
    const hm_mm  = hm(state.fz, state.ae);

    const fcN    = FcT(hm_mm, ap_mm);                 // N
    const kW     = Pcut(hm_mm, ap_mm, state.ae, vf);  // kW
    const hp     = kW * 1.341;

    /* Radar % */
    const lifePct   = Math.min(100, ((state.fz - FZ_MIN) / (FZ_MAX - FZ_MIN)) * 100);
    const powerPct  = Math.min(100, (hp / HP_AVAIL) * 100);
    const finishPct = Math.max(0, 100 - lifePct);

    /* DEBUG BREAKDOWN (solo si cambió algo visible) */
    if (DEBUG && changed(state.last, { hp })) {
      console.groupCollapsed(`${TAG} Cálculo potencia / fuerza`);
      console.log('h_m        =', hm_mm.toFixed(4), 'mm');
      console.log('Kc(h)      =', Kc_h(hm_mm).toFixed(0), 'N/mm²');
      console.log('ap (mm)    =', ap_mm.toFixed(3));
      console.log('ae (mm)    =', state.ae.toFixed(2));
      console.log('vf (mm/min)=', vf.toFixed(0));
      console.log('FcT (N)    =', fcN.toFixed(0));
      console.log('P (kW)     =', kW.toFixed(2));
      console.log('P (HP)     =', hp.toFixed(2));
      console.groupEnd();
    }

    render({
      vc:state.vc, fz:state.fz, hm:hm_mm, n:N|0, vf:vf|0,
      hp:hp, mmr:mmr(ap_mm,vf,state.ae), fc:fcN|0, w:kW*1000|0,
      eta:Math.min(100,(hp/HP_AVAIL)*100)|0, ae:state.ae, ap:ap_mm,
      life:lifePct, power:powerPct, finish:finishPct
    });
  };

  /* ─────────── 8 · SLIDERS · Presentación bubble ────────── */
  const pretty = (sl, dec) => {
    if (!sl) return;
    const wrap = sl.closest('.slider-wrap');
    const bub  = wrap?.querySelector('.slider-bubble');
    const min  = +sl.min, max = +sl.max;
    const upd  = v => {
      wrap?.style.setProperty('--val', ((v - min)/(max - min))*100);
      bub  && (bub.textContent = fmt(v,dec));
    };
    upd(+sl.value);
    sl.addEventListener('input', e => { upd(+e.target.value); onInput(e.target); });
  };

  /* ─────── 9 · BLOQUEO INTELIGENTE DE CADA SLIDER ──────── */
  const lock = (sl, dir) => {                // dir: 'low'|'high'
    sl.dataset.locked = dir;
    sl.classList.add('is-locked');
  };
  const unlock = sl => {
    sl.dataset.locked && delete sl.dataset.locked;
    sl.classList.remove('is-locked');
  };

  /* ───────────── 10 · SYNC & VALIDACIÓN INPUT ──────────── */
  const syncPassSlider = () => {
    const maxP = Math.max(1, Math.ceil(THK / state.ae));
    SL.pass.max = maxP; SL.pass.min = 1; SL.pass.step = 1;
    if (+SL.pass.value > maxP) SL.pass.value = maxP;
    state.ap = +SL.pass.value;
    infoPass && (infoPass.textContent =
       `${state.ap} pasada${state.ap>1?'s':''} de ${(THK/state.ap).toFixed(2)} mm`);
  };

  const onInput = sliderChanged => {
    /* valores provisionales según UI */
    const tmp = {
      fz : +SL.fz.value,
      vc : +SL.vc.value,
      ae : +SL.ae.value
    };
    const N   = rpm(tmp.vc);
    const vf  = feed(N, tmp.fz);

    /* Reglas de bloqueo — se aplica solo si hay violación       */
    /* 1) RPM fuera de rango? → bloquea Vc hacia ese extremo.    */
    if (N < RPM_MIN)      lock(SL.vc,'low');  else if (SL.vc.dataset.locked==='low')  unlock(SL.vc);
    if (N > RPM_MAX)      lock(SL.vc,'high'); else if (SL.vc.dataset.locked==='high') unlock(SL.vc);

    /* 2) Feedrate supera el máximo? → decide culpable           */
    if (vf > FR_MAX) {
      // ¿quién se movió?— si cambió Fz, bloquea Fz; si cambió Vc, bloquea Vc (high)
      if (sliderChanged === SL.fz) lock(SL.fz,'high'); else lock(SL.vc,'high');
    } else {
      unlock(SL.fz); unlock(SL.vc);
    }

    /* Propaga al state sólo si slider NO está bloqueado hacia ese lado */
    if (!SL.fz.dataset.locked) state.fz = tmp.fz;
    if (!SL.vc.dataset.locked) state.vc = tmp.vc;
    state.ae = tmp.ae;

    syncPassSlider();
    recalc();
  };

  /* ───────────────────── 11 · INIT ─────────────────────── */
  try {
    /* Vc slider: ±50 % del VC0, pero dentro de RPM min/max */
    const vcFromRPMmin = (RPM_MIN * Math.PI * D) / 1000;
    const vcFromRPMmax = (RPM_MAX * Math.PI * D) / 1000;
    const vcMin        = Math.max(VC0 * 0.5, vcFromRPMmin);
    const vcMax        = Math.min(VC0 * 1.5, vcFromRPMmax);
    SL.vc.min   = fmt(vcMin,1);
    SL.vc.max   = fmt(vcMax,1);
    SL.vc.value = fmt(state.vc,1);

    /* Pasadas arranca en 1 */
    SL.pass.value = 1;

    /* Pretty-sliders + listeners */
    pretty(SL.fz,4);  pretty(SL.vc,1);
    pretty(SL.ae,2);  pretty(SL.pass,0);
    ['fz','vc','ae','pass'].forEach(k => SL[k]?.addEventListener('change', () => onInput(SL[k])));

    /* Radar + render inicial */
    makeRadar();
    syncPassSlider();
    recalc();

    log('init OK');
  } catch (e) {
    error('init', e); fatal('JS: ' + e.message);
  }
})();
