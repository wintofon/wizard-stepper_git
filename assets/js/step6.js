/* =======================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC
 * -----------------------------------------------------------------------
 *  ▸ Calcula/valida parámetros de corte en el cliente.
 *  ▸ Sigue pudiendo consultar al endpoint PHP (legado) cuando es preciso.
 *  ▸ Consola súper‐silenciosa: SOLO escribe cuando algo CAMBIA.
 *  ▸ Cualquier error fatal se muestra también en la UI.
 *
 *  @version   4.0.0  2025‑06‑27
 *  @author    @your‑nick
 * =====================================================================*/

/* global Chart, window, fetch */

(() => {
  'use strict';

  /* ──────────────────────────── SET‑UP DEBUG ───────────────────────── */
  const DEBUG  = window.DEBUG ?? false;          // definir antes en <script>
  const TAG    = '[Step6]';
  const stamp  = () => new Date().toISOString();
  const say    = (lvl, ...msg) => { if (DEBUG) console[lvl](`${TAG} ${stamp()}`, ...msg); };
  const log    = (...m) => say('log',   ...m);
  const warn   = (...m) => say('warn',  ...m);
  const error  = (...m) => say('error', ...m);
  const table  = data  => { if (DEBUG) console.table(data); };

  /* ─────────────────── HELPERS DOM / CALC / VALIDATION ─────────────── */
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const fmt = (n, d = 1) => Number.parseFloat(n).toFixed(d);

  /** Compara shallow‑object y devuelve true si cambió algo */
  const changed = (prev = {}, next = {}) => {
    const k = new Set(Object.keys(prev).concat(Object.keys(next)));
    for (const key of k) if (prev[key] !== next[key]) return true;
    return false;
  };

  /** Muestra un error crítico en el <div id="errorMsg"> o alert() */
  const showFatal = msg => {
    const box = $('#errorMsg');
    if (box) {
      box.style.display = 'block';
      box.textContent   = msg;
    } else {
      window.alert?.(msg);
    }
  };

  /* ───────────────────────── PARAMS DESDE PHP ─────────────────────── */
  if (!window.step6Params || Object.keys(window.step6Params).length === 0) {
    return showFatal('step6Params vacío – no se puede continuar');
  }
  const P = window.step6Params;

  const REQ = [
    'diameter', 'flute_count', 'rpm_min', 'rpm_max', 'fr_max',
    'coef_seg', 'Kc11', 'mc', 'eta', 'fz0', 'vc0'
  ];
  const miss = REQ.filter(k => P[k] === undefined);
  if (miss.length) {
    return showFatal('Faltan claves en step6Params: ' + miss.join(', '));
  }

  /* ──────────────── DESTRUCTURAMOS CON DEFAULTS SEGUROS ───────────── */
  const {
    diameter:      D,
    flute_count:   Z,
    rpm_min:       RPM_MIN,
    rpm_max:       RPM_MAX,
    fr_max:        FR_MAX,
    coef_seg:      K_SEG,
    Kc11:          KC,
    mc, eta,
    alpha:         ALPHA = 0,
    fz0:           FZ0,
    vc0:           VC0,
    ap_slot:       AP_SLOT = 1,
    hp_avail:      HP_AVAIL = 1
  } = P;

  /* ───────────────────────────── STATE ────────────────────────────── */
  const state = {
    fz:   +FZ0,
    vc:   +VC0,
    ae:   D * 0.5,
    ap:   +AP_SLOT,
    lastRendered: {}
  };

  /* ──────────────────────── REFERENCIAS DOM ───────────────────────── */
  const SL = {
    fz:   $('#sliderFz'),
    vc:   $('#sliderVc'),
    ae:   $('#sliderAe'),
    pass: $('#sliderPasadas')
  };
  const OUT = {
    vc:  $('#outVc'),  fz:  $('#outFz'),    hm:  $('#outHm'),
    n:   $('#outN'),   vf:  $('#outVf'),    hp:  $('#outHp'),
    mmr: $('#valueMrr'),     fc:  $('#valueFc'),   w:   $('#valueW'),
    eta: $('#valueEta'),
    ae:  $('#outAe'),   ap:  $('#outAp')
  };
  const infoPass = $('#textPasadasInfo');
  const errBox   = $('#errorMsg');

  /* ──────────────── FORMULAS FÍSICAS (backend 1:1) ──────────────── */
  const rpm  = vc       => (vc * 1000) / (Math.PI * D);
  const feed = (n, fz)  => n * fz * Z;
  const phi  = ae       => 2 * Math.asin(Math.min(1, ae / D));
  const hm   = (fz, ae) => { const p = phi(ae); return p ? fz * (1 - Math.cos(p)) / p : fz; };
  const mmr  = (ap, vf, ae) => (ap * vf * ae) / 1000;
  const Fct  = (hmv, ap, fz) => KC * Math.pow(hmv, -mc) * ap * fz * Z * (1 + K_SEG * Math.tan(ALPHA));
  const kW   = (F, Vc) => (F * Vc) / (60_000 * eta);
  const HP   = kWv => kWv * 1.341;

  /* ────────────────────────── RADAR CHART ─────────────────────────── */
  let radar;
  const makeRadar = () => {
    const ctx = $('#radarChart')?.getContext('2d');
    if (!ctx || !window.Chart) return;
    radar = new Chart(ctx, {
      type   : 'radar',
      data   : { labels: ['MMR', 'Fc', 'W', 'Hp', 'η'], datasets: [{ data:[0,0,0,0,0], fill:true, borderWidth:2 }] },
      options: { scales:{ r:{ min:0, max:1 } }, plugins:{ legend:{ display:false } } }
    });
  };

  /* ─────────────────────── RENDER ↔ DIFF OUTPUTS ──────────────────── */
  const render = data => {
    if (!changed(state.lastRendered, data)) return; // 👈 NADA CAMBIÓ

    for (const k in data) {
      const el = OUT[k];
      if (!el) continue;
      const v = data[k];
      if (typeof v === 'number') {
        el.textContent = v % 1 ? v.toFixed(2) : v;
      } else {
        el.textContent = v;
      }
    }

    /* radar normalizado (sincrónico) */
    if (radar) {
      radar.data.datasets[0].data = [
        Math.min(1, data.mmr / 1e5),
        Math.min(1, data.fc  / 1e4),
        Math.min(1, data.w   / 3000),
        Math.min(1, data.hp  / HP_AVAIL),
        Math.min(1, data.eta / 100)
      ];
      radar.update();
    }

    state.lastRendered = data;
    log('render()', data);
  };

  /* ─────────────────────────── RECALC LOCAL ───────────────────────── */
  const recalcLocal = () => {
    const N       = rpm(state.vc);
    const vfRaw   = feed(N, state.fz);
    const vf      = Math.min(vfRaw, FR_MAX);
    const ap      = (SL.pass ? +SL.pass.value : 1) ? (P.thickness / +SL.pass.value) : 0;
    const hmV     = hm(state.fz, state.ae);
    const mmrV    = mmr(ap, vf, state.ae);
    const fcV     = Fct(hmV, ap, state.fz);
    const kWv     = kW(fcV, state.vc);
    const watts   = kWv * 1000;
    const hpV     = HP(kWv);
    const etaPct  = Math.min(100, (hpV / HP_AVAIL) * 100);

    render({ vc:state.vc, fz:state.fz, hm:hmV, n:N|0, vf:vf|0, hp:hpV, mmr:mmrV, fc:fcV|0, w:watts|0, eta:etaPct|0, ae:state.ae, ap });
  };

  /* ─────────────────────── SLIDER ENHANCEMENT ─────────────────────── */
  const enhance = (slider, decimals = 3) => {
    if (!slider) return;
    const wrap   = slider.closest('.slider-wrap');
    const bubble = wrap?.querySelector('.slider-bubble');
    const min    = +slider.min || 0;
    const max    = +slider.max || 1;
    const upd    = v => {
      const pct = ((v - min) / (max - min)) * 100;
      wrap?.style.setProperty('--val', pct);
      if (bubble) bubble.textContent = fmt(v, decimals);
    };
    slider.addEventListener('input', e => { upd(+e.target.value); onInput(); });
    upd(+slider.value);
  };

  /* ─────────── ACTUALIZA state Y TRIGGEREA CÁLCULO ────────────── */
  const onInput = () => {
    state.fz = +SL.fz.value;
    state.vc = +SL.vc.value;
    state.ae = +SL.ae.value;
    recalcLocal();
  };

  /* ──────────────────────────── INIT FLOW ─────────────────────────── */
  try {
    // 0) límites Vc ⇢ slider
    const vcMin = (RPM_MIN * Math.PI * D) / 1000;
    const vcMax = (RPM_MAX * Math.PI * D) / 1000;
    SL.vc.min = vcMin.toFixed(1);
    SL.vc.max = vcMax.toFixed(1);

    // 1) sliders bonitos + listeners
    enhance(SL.fz, 4);
    enhance(SL.vc, 1);
    enhance(SL.ae, 2);
    enhance(SL.pass, 0);
    ['fz','vc','ae','pass'].forEach(k => SL[k]?.addEventListener('change', onInput));

    // 2) pasadas info
    if (infoPass && SL.pass) {
      const upd = () => infoPass.textContent = `${SL.pass.value} pasadas de ${(P.thickness / SL.pass.value).toFixed(2)} mm`;
      SL.pass.addEventListener('input', upd); upd();
    }

    // 3) chart y primer render
    makeRadar();
    onInput();

    log('init OK');
  } catch (e) {
    error('init error', e);
    showFatal('JS init: ' + e.message);
  }
})();
