/* ======================================================================
 *  assets/js/step6.js · PASO 6 — Wizard CNC  (build 2025-06-28)
 * ----------------------------------------------------------------------
 *  ▸ TODO lo necesario se ejecuta 100 % en cliente (sin AJAX).
 *  ▸ Cubre las restricciones:
 *        – Vc limitado ±50 % del valor base,
 *          pero NUNCA fuera de rpm_min / rpm_max.
 *        – fz limitado al rango catálogo.
 *        – feedrate jamás supera fr_max.
 *  ▸ Cuando algún límite se supera, el slider “empuja” al otro
 *    (queda bloqueado sólo hacia el lado prohibido, libre al revés).
 *  ▸ Fórmulas revisadas:
 *        FcT [N] = Kc(h) · ap · Z · (1 + coef_seg·tan α)
 *        PkW    = Kc(h) · ap · ae · vf / (60 000 · η)
 *  ▸ Radar 3 ejes — Vida Útil · Potencia · Terminación:
 *        ↑ fz  ⇒ ↑ Vida Útil, ↑ Potencia y ↓ Terminación
 * ====================================================================*/

/* global Chart, window */
(() => {
  'use strict';

  /* ──────────────────── DEBUG helpers ──────────────────── */
  const DEBUG = window.DEBUG ?? false;
  const tag   = '[Step-6]';
  const log   = (...m) => DEBUG && console.log(tag, ...m);
  const warn  = (...m) => DEBUG && console.warn(tag, ...m);
  const err   = (...m) => console.error(tag, ...m);
  const $     = (s, c = document) => c.querySelector(s);
  const fmt   = (v, d = 1) => Number.parseFloat(v).toFixed(d);

  /* ───────────────── PARAMS inyectados PHP ─────────────── */
  const P = window.step6Params;
  if (!P) { err('step6Params vacío'); return; }

  /*   …………… destructuring con nombres cortos ……………        */
  const {
    diameter        : D,
    flute_count     : Z,
    rpm_min         : RPM_MIN,
    rpm_max         : RPM_MAX,
    fr_max          : FR_MAX,
    coef_seg        : K_SEG,
    Kc11            : KC11,
    mc,
    eta             : ETA,
    alpha           : ALPHA = 0,
    fz0             : FZ0,
    vc0             : VC0,
    thickness       : THK,
    fz_min0         : FZ_MIN,
    fz_max0         : FZ_MAX,
    hp_avail        : HP_AVAIL
  } = P;

  /* ───────────────────── DOM refs ──────────────────────── */
  const SL = {
    fz  : $('#sliderFz'),
    vc  : $('#sliderVc'),
    ae  : $('#sliderAe'),
    pas : $('#sliderPasadas')
  };
  const OUT = {
    vc : $('#outVc'),   fz : $('#outFz'), hm : $('#outHm'),
    n  : $('#outN'),    vf : $('#outVf'), hp : $('#outHp'),
    mmr: $('#valueMrr'),fc : $('#valueFc'), w : $('#valueW'),
    eta: $('#valueEta'),ae : $('#outAe'), ap : $('#outAp')
  };
  const infoPas = $('#textPasadasInfo');
  const msgBox  = $('#errorMsg');

  /* ────────────────── fórmula helpers ──────────────────── */
  const rpm  = vc       => vc * 1000 / (Math.PI * D);
  const feed = (n, fz)  => n * fz * Z;
  const phi  = ae       => 2 * Math.asin(Math.min(1, ae / D));
  const hm   = (fz, ae) => { const p = phi(ae); return p ? fz * (1 - Math.cos(p)) / p : fz; };
  const Kc_h = h        => KC11 * Math.pow(h, -mc);               // N/mm²
  const FcT  = (h, ap)  => Kc_h(h) * ap * Z * (1 + K_SEG * Math.tan(ALPHA));
  const PkW  = (h, ap, ae, vf) => Kc_h(h) * ap * ae * vf / (60_000 * ETA);
  const mmr  = (ap, ae, vf) => ap * ae * vf / 1000;               // cm³/min

  /* ──────────────────── estado ─────────────────────────── */
  const st = { fz:+FZ0, vc:+VC0, ae:D*0.5, pas:1 };

  /* ───────────────── radar init ────────────────────────── */
  let radar;
  const makeRadar = () => {
    const ctx = $('#radarChart')?.getContext('2d');
    if (!ctx || !Chart) return;
    radar = new Chart(ctx, {
      type:'radar',
      data:{ labels:['Vida Útil','Potencia','Terminación'],
             datasets:[{ data:[0,0,0], fill:true, borderWidth:2 }]},
      options:{ scales:{ r:{ min:0,max:100,ticks:{stepSize:20}} },
                plugins:{ legend:{ display:false } } }
    });
  };

  /* ───────────── slider prettifier (burbuja) ───────────── */
  const prettify = (sl, dec = 2) => {
    if (!sl) return;
    const wrap = sl.closest('.slider-wrap');
    const bub  = wrap?.querySelector('.slider-bubble');
    const min  = +sl.min, max = +sl.max;
    const upd  = v => {
      wrap?.style.setProperty('--val', ((v - min) * 100) / (max - min));
      bub && (bub.textContent = fmt(v, dec));
    };
    upd(+sl.value);
    sl.addEventListener('input', e => upd(+e.target.value));
  };

  /* ──────────── actualizar slider Pasadas ──────────────── */
  const syncPas = () => {
    const maxPas = Math.max(1, Math.ceil(THK / st.ae));
    SL.pas.max = maxPas;
    if (+SL.pas.value > maxPas) SL.pas.value = maxPas;
    st.pas = +SL.pas.value;
    infoPas.textContent =
      `${st.pas} pasada${st.pas > 1 ? 's' : ''} de ${(THK / st.pas).toFixed(2)} mm`;
  };

  /* ─────────────────── render OUT ─────────────────────── */
  const render = snap => {
    for (const k in snap) OUT[k] && (OUT[k].textContent = fmt(snap[k], snap[k] % 1 ? 2 : 0));
    radar && (radar.data.datasets[0].data = [snap.life, snap.power, snap.finish], radar.update());
  };

  /* ────────────── recálculo principal ─────────────────── */
  const recalc = () => {
    const N   = rpm(st.vc);
    const vf  = Math.min(feed(N, st.fz), FR_MAX);
    const ap  = THK / st.pas;
    const h   = hm(st.fz, st.ae);

    /* bloquear slider infractor sólo hacia el lado prohibido */
    if (feed(N, st.fz) > FR_MAX) { SL.fz.value = fmt(st.fz,4); SL.fz.max = fmt(st.fz,4); }
    else                         SL.fz.max = fmt(FZ_MAX,4);

    const fc   = FcT(h, ap);
    const kW   = PkW(h, ap, st.ae, vf);
    const hp   = kW * 1.341;

    /* radar % */
    const life   = Math.min(100, ((st.fz - FZ_MIN) / (FZ_MAX - FZ_MIN)) * 100);
    const power  = Math.min(100, (hp / HP_AVAIL) * 100);
    const finish = 100 - life;

    render({
      vc:st.vc, fz:st.fz, hm:h, n:Math.round(N), vf:Math.round(vf), hp:hp,
      mmr:mmr(ap, st.ae, vf), fc:fc, w:kW*1000, eta:Math.min(100, (hp/HP_AVAIL)*100),
      ae:st.ae, ap:ap, life, power, finish
    });

    if (DEBUG) {
      console.groupCollapsed(tag,'breakdown');
      console.log('hm =',h.toFixed(4),'mm — Kc(h) =',Kc_h(h).toFixed(0),'N/mm²');
      console.log('FcT=',fc.toFixed(0),'N ·  P=',kW.toFixed(2),'kW =',hp.toFixed(2),'HP');
      console.groupEnd();
    }
  };

  /* ─────────── on-change común (vc,fz,ae,pas) ──────────── */
  const onInput = () => {
    st.fz  = +SL.fz.value;
    st.vc  = +SL.vc.value;
    st.ae  = +SL.ae.value;
    syncPas();
    recalc();
  };

  /* ─────────────────────   INIT   ─────────────────────── */
  try {
    /* Vc slider ±50 % pero dentro de RPM min/max */
    const vcRPMmin = RPM_MIN * Math.PI * D / 1000;
    const vcRPMmax = RPM_MAX * Math.PI * D / 1000;
    SL.vc.min = fmt(Math.max(VC0 * 0.5, vcRPMmin), 1);
    SL.vc.max = fmt(Math.min(VC0 * 1.5, vcRPMmax), 1);
    SL.vc.value = fmt(st.vc, 1);

    /* Pasadas arranca en 1 */
    SL.pas.value = 1;

    /* decorar sliders */
    prettify(SL.fz,4); prettify(SL.vc,1); prettify(SL.ae,2); prettify(SL.pas,0);

    /* listeners */
    ['fz','vc','ae','pas'].forEach(k => SL[k]?.addEventListener('input', onInput));

    makeRadar();
    syncPas();
    recalc();
    log('init OK');
  } catch (e) {
    err(e); msgBox && (msgBox.textContent = e.message);
  }
})();
