/* =====================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC · 100 % client-side
 * ---------------------------------------------------------------------
 * - Fórmulas = backend 1:1 (hm, MMR, FcT, kW…)
 * - Sin AJAX en tiempo real: todo se recalcula en el navegador.
 * - Sliders con “pared móvil”: se bloquean sólo hacia el lado peligroso.
 * - Radar de 3 variables: Vida útil, Potencia, Terminación.
 * ====================================================================*/
/* global Chart, CountUp, window                                          */

(() => {
  /* ---------- 0 · DEBUG helpers ------------------------------------- */
  const DEBUG = window.DEBUG ?? false;
  const TAG   = '[Step-6]';
  const log   = (...a) => DEBUG && console.log (TAG, ...a);
  const warn  = (...a) => DEBUG && console.warn(TAG, ...a);
  const table = (d)   => DEBUG && console.table?.(d);

  /* ---------- 1 · Parámetros inyectados por PHP --------------------- */
  const P = window.step6Params || {};
  const REQUIRED = [
    'diameter', 'flute_count',
    'rpm_min', 'rpm_max', 'fr_max',
    'fz0', 'vc0',
    'Kc11', 'mc', 'coef_seg',
    'rack_rad', 'eta',
    'thickness'
  ];
  const miss = REQUIRED.filter(k => P[k] === undefined);
  if (miss.length) {
    console.error(TAG, 'Faltan parámetros críticos:', miss);
    return;
  }

  /* ––– Desestructuración segura ––––––––––––––––––––––––––––––––––– */
  const {
    diameter      : D,
    flute_count   : Z,
    rpm_min       : RPM_MIN,
    rpm_max       : RPM_MAX,
    fr_max        : FR_MAX,
    fz0, vc0,
    Kc11          : KC,
    mc,
    coef_seg      : K_SEG,
    rack_rad      : ALPHA,
    eta,
    thickness     : THK
  } = P;

  /* ---------- 2 · DOM refs ----------------------------------------- */
  const $ = sel => document.getElementById(sel);
  const sliders = {
    Vc : $('sliderVc'),
    Fz : $('sliderFz'),
    Ae : $('sliderAe'),
    P  : $('sliderPasadas')
  };
  const infoPasadas = $('textPasadasInfo');
  const errBox      = $('errorMsg');

  const out = {
    vc  : $('outVc'),  fz  : $('outFz'),   hm  : $('outHm'),
    n   : $('outN'),   vf  : $('outVf'),   hp  : $('outHp'),
    mmr : $('valueMrr'), fc : $('valueFc'), w   : $('valueW'),
    eta : $('valueEta'), ae : $('outAe'),   ap  : $('outAp')
  };

  /* ---------- 3 · Estado mutable ----------------------------------- */
  const state = {
    fz : +fz0,
    vc : +vc0,
    ae : (+P.diameter ?? D) * 0.5,
    passes : 1,
    radarChart : null
  };

  /* ---------- 4 · Math helpers (backend 1:1) ------------------------ */
  const rpm   = vc            => (vc * 1000) / (Math.PI * D);
  const feed  = (n, fz)       => n * fz * Z;
  const phi   = ae            => 2 * Math.asin(Math.min(1, ae / D));
  const hm    = (fz, ae)      => { const p = phi(ae); return p ? fz * (1 - Math.cos(p)) / p : fz; };
  const mmr   = (ap, vf, ae)  => (ap * vf * ae) / 1000;
  const FcT   = (hmv, ap, fz) => KC * Math.pow(hmv, -mc) * ap * fz * Z * (1 + K_SEG * Math.tan(ALPHA));
  const kW    = (F, Vc)       => (F * Vc) / (60_000 * eta);
  const hp    = kWv           => kWv * 1.341;

  /* ---------- 4½ · límites dinámicos ------------------------------- */
  function applyDynamicLimits() {
    /* —— Vc —— */
    const vcFromRpmMin = (RPM_MIN * Math.PI * D) / 1000;
    const vcFromRpmMax = (RPM_MAX * Math.PI * D) / 1000;
    const curFz        = parseFloat(sliders.Fz.value);
    const vcFromFeed   = (FR_MAX / (curFz * Z)) * (Math.PI * D) / 1000;

    sliders.Vc.min = vcFromRpmMin.toFixed(1);
    sliders.Vc.max = Math.min(vcFromRpmMax, vcFromFeed).toFixed(1);

    /* —— Fz —— */
    const curVc   = parseFloat(sliders.Vc.value);
    const curRpm  = rpm(curVc);
    const fzMax   = FR_MAX / (curRpm * Z);
    sliders.Fz.max = fzMax.toFixed(4);
  }

  /* ---------- 5 · util DOM ----------------------------------------- */
  const fmt = (n, d = 1) => Number.parseFloat(n).toFixed(d);
  const set = (el, v, d) => el && (el.textContent = fmt(v, d));

  function enhanceSlider(sl) {
    const wrap   = sl.closest('.slider-wrap');
    const bubble = wrap?.querySelector('.slider-bubble');
    const step   = parseFloat(sl.step || 1);
    const min    = () => parseFloat(sl.min || 0);
    const max    = () => parseFloat(sl.max || 1);
    const update = () => {
      const pct = ((sl.value - min()) / (max() - min())) * 100;
      wrap?.style.setProperty('--val', pct);
      bubble && (bubble.textContent = fmt(sl.value, step < 1 ? 2 : 0));
    };
    sl.addEventListener('input', update);
    update();
  }

  /* ---------- 6 · Radar (3 ejes) ----------------------------------- */
  function makeRadar() {
    const ctx = $('radarChart').getContext('2d');
    if (state.radarChart) state.radarChart.destroy();
    state.radarChart = new Chart(ctx, {
      type : 'radar',
      data : {
        labels   : ['Vida útil', 'Potencia', 'Terminación'],
        datasets : [{ data: [0, 0, 0], fill: true, borderWidth: 2 }]
      },
      options : { scales: { r: { max: 100, ticks: { stepSize: 20 } } },
                  plugins: { legend: { display: false } } }
    });
  }

  /* Potencia, vida útil, terminación (heurístico sencillo) */
  function radarValues(hpUsed, hpAvail, fz) {
    const potencia   = Math.min(100, (hpUsed / hpAvail) * 100);
    const vidaUtil   = Math.min(100, (1 / fz) * 100 * 0.2); // mayor fz ⇒ menor vida
    const terminacion= Math.max(0, 100 - vidaUtil);         // inverso (aprox.)
    return [vidaUtil, potencia, terminacion];
  }

  /* ---------- 7 · Re-cálculo principal ----------------------------- */
  function recalc() {
    state.fz     = parseFloat(sliders.Fz.value);
    state.vc     = parseFloat(sliders.Vc.value);
    state.ae     = parseFloat(sliders.Ae.value);
    state.passes = parseInt(sliders.P.value, 10);

    const N      = rpm(state.vc);
    const vfRaw  = feed(N, state.fz);
    const vf     = Math.min(vfRaw, FR_MAX);
    const ap     = THK / state.passes;
    const hmVal  = hm(state.fz, state.ae);
    const fct    = FcT(hmVal, ap, state.fz);
    const kWVal  = kW(fct, state.vc);
    const watts  = kWVal * 1000;
    const hpVal  = hp(kWVal);
    const mmrVal = mmr(ap, vf, state.ae);

    /* ---- pintar ---- */
    set(out.vc , state.vc, 1);
    set(out.fz , state.fz, 4);
    set(out.n  , N, 0);
    set(out.vf , vf, 0);
    set(out.hm , hmVal, 4);
    set(out.ae , state.ae, 2);
    set(out.ap , ap, 3);
    set(out.hp , hpVal, 2);
    set(out.mmr, mmrVal, 0);
    set(out.fc , fct, 0);
    set(out.w  , watts, 0);
    set(out.eta, (hpVal / hp(D ? kWVal : 1) ) .toFixed(0), 0);

    /* Radar */
    state.radarChart.data.datasets[0].data = radarValues(hpVal, P.hp_avail ?? hpVal, state.fz);
    state.radarChart.update();
  }

  /* ---------- 8 · edge hint opcional ------------------------------- */
  function edgeHint(sl, msg) {
    const atEdge = (+sl.value >= +sl.max - 1e-10) || (+sl.value <= +sl.min + 1e-10);
    errBox.style.display = atEdge ? 'block' : 'none';
    if (atEdge) errBox.textContent = msg;
  }

  /* ---------- 9 · Listeners ---------------------------------------- */
  sliders.Fz.addEventListener('input', () => {
    applyDynamicLimits();
    edgeHint(sliders.Fz, 'Límite de feedrate');
    recalc();
  });
  sliders.Vc.addEventListener('input', () => {
    applyDynamicLimits();
    edgeHint(sliders.Vc, 'Límite de rpm / feedrate');
    recalc();
  });
  sliders.Ae.addEventListener('input', () => {
    /* al cambiar ancho se recalcula nº pasadas válido */
    const maxP = Math.ceil(THK / sliders.Ae.value);
    sliders.P.max = maxP;
    if (+sliders.P.value > maxP) sliders.P.value = maxP;
    infoPasadas.textContent = `${sliders.P.value} pasadas de ${(THK / sliders.P.value).toFixed(2)} mm`;
    recalc();
  });
  sliders.P.addEventListener('input', () => {
    infoPasadas.textContent = `${sliders.P.value} pasadas de ${(THK / sliders.P.value).toFixed(2)} mm`;
    recalc();
  });

  /* ---------- 10 · Kick-off ---------------------------------------- */
  Object.values(sliders).forEach(enhanceSlider);
  applyDynamicLimits();
  makeRadar();
  infoPasadas.textContent = `1 pasadas de ${THK.toFixed(2)} mm`;
  recalc();

  log('Init completo');
})();
