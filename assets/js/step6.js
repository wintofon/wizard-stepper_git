/* =====================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC · 100 % client-side
 * ---------------------------------------------------------------------
 *  ▶ Calcula en el navegador los parámetros técnicos de corte (hm, MMR,
 *    FcT, kW, hp…) exactamente igual que en el backend.
 *  ▶ Sin AJAX en tiempo real: todo es inmediato y reactivo.
 *  ▶ Sliders con “pared móvil”: se bloquean sólo hacia el lado peligroso.
 *  ▶ Radar de 3 variables (Vida útil, Potencia, Terminación) siempre vivo.
 * ---------------------------------------------------------------------
 * 2025-06-28 — Versión comentada y depurada.
 * ====================================================================*/
/* globals Chart, CountUp, window */

(() => {
  /* ------------------------------------------------------------------
   * 0 · DEBUG helpers
   * ------------------------------------------------------------------ */
  const DEBUG = window.DEBUG ?? false;            // Activar con window.DEBUG=true antes de cargar el script
  const TAG   = '[Step-6]';
  const log   = (...a) => DEBUG && console.log (TAG, ...a);
  const warn  = (...a) => DEBUG && console.warn(TAG, ...a);
  const table =  d   => DEBUG && console.table?.(d);

  /* ------------------------------------------------------------------
   * 1 · Parámetros inyectados por PHP
   * ------------------------------------------------------------------ */
  const P = window.step6Params || {};
  const REQUIRED = [
    'diameter','flute_count',
    'rpm_min','rpm_max','fr_max',
    'fz0','vc0',
    'Kc11','mc','coef_seg',
    'rack_rad','eta',
    'thickness'
  ];
  const miss = REQUIRED.filter(k => P[k] === undefined);
  if (miss.length) {
    console.error(TAG,'Faltan parámetros críticos:',miss);
    return; // Abortamos: sin parámetros no hay cálculos
  }

  /* ------------------------------------------------------------------
   * 2 · Desestructuración de parámetros
   * ------------------------------------------------------------------ */
  const {
    diameter      : D,           // Ø fresa (mm)
    flute_count   : Z,           // nº de filos
    rpm_min       : RPM_MIN,     // rpm mínimas reales del spindle
    rpm_max       : RPM_MAX,     // rpm máximas reales del spindle
    fr_max        : FR_MAX,      // feedrate máx (mm/min)
    fz0, vc0,                     // punto base sugerido
    Kc11          : KC,          // coeficiente específico de corte (N/mm²)
    mc,                           // exponente de sensibilidad al espesor
    coef_seg      : K_SEG,       // corrección de filo gastado
    rack_rad      : ALPHA,       // ángulo de ataque (rad)
    eta,                          // eficiencia del spindle
    thickness     : THK          // espesor total (mm)
  } = P;

  /* ------------------------------------------------------------------
   * 3 · DOM refs
   * ------------------------------------------------------------------ */
  const $ = id => document.getElementById(id);

  const sliders = {
    Vc : $('sliderVc'),  // velocidad de corte (m/min)
    Fz : $('sliderFz'),  // avance por diente (mm)
    Ae : $('sliderAe'),  // ancho de pasada (mm)
    P  : $('sliderPasadas') // nº de pasadas
  };

  const infoPasadas = $('textPasadasInfo');
  const errBox      = $('errorMsg');

  const out = {
    vc  : $('outVc'),   fz  : $('outFz'),   hm  : $('outHm'),
    n   : $('outN'),    vf  : $('outVf'),   hp  : $('outHp'),
    mmr : $('valueMrr'),fc  : $('valueFc'), w   : $('valueW'),
    eta : $('valueEta'),ae  : $('outAe'),   ap  : $('outAp')
  };

  /* ------------------------------------------------------------------
   * 4 · Estado mutable
   * ------------------------------------------------------------------ */
  const state = {
    fz : +fz0,
    vc : +vc0,
    ae : (+D) * 0.5, // por defecto medio diámetro
    passes : 1,
    radarChart : null
  };

  /* ------------------------------------------------------------------
   * 5 · Helpers matemáticos
   * ------------------------------------------------------------------ */
  const rpm   = vc            => (vc * 1000) / (Math.PI * D);
  const feed  = (n, fz)       => n * fz * Z;
  const phi   = ae            => 2 * Math.asin(Math.min(1, ae / D));
  const hm    = (fz, ae)      => {
    const p = phi(ae);
    return p ? fz * (1 - Math.cos(p)) / p : fz;
  };
  const mmr   = (ap, vf, ae)  => (ap * vf * ae) / 1000; // cm³/min

  // —— Fuerza de corte total (modelo Kienzle extendido) ——
  const FcT = (hmv, ap, fz) => {
    const base   = KC * Math.pow(hmv, -mc);      // N/mm²
    const area   = ap * fz * Z;                  // mm²
    const factor = 1 + K_SEG * Math.tan(ALPHA);  // dim.
    const F      = base * area * factor;         // N

    if (DEBUG) {
      console.groupCollapsed(`${TAG} FcT detail`);
      console.log({KC, hmv, mc, ap, fz, Z, K_SEG, ALPHA, base, area, factor, F});
      console.groupEnd();
    }
    return F;
  };

  const kW = (F,Vc) => (F * Vc) / (60_000 * eta);
  const hp = kWv    => kWv * 1.341;

  /* ------------------------------------------------------------------
   * 6 · Límites dinámicos (pared móvil)
   * ------------------------------------------------------------------ */
  function applyDynamicLimits() {
    const vcMin = (RPM_MIN * Math.PI * D) / 1000;
    const vcMaxRPM = (RPM_MAX * Math.PI * D) / 1000;
    const curFz = +sliders.Fz.value || state.fz;
    const vcMaxFeed = (FR_MAX / (curFz * Z)) * (Math.PI * D) / 1000;

    sliders.Vc.min = vcMin.toFixed(1);
    sliders.Vc.max = Math.min(vcMaxRPM, vcMaxFeed).toFixed(1);

    const curVc = +sliders.Vc.value || state.vc;
    const curRPM = rpm(curVc);
    const fzMax = FR_MAX / (curRPM * Z);
    sliders.Fz.max = fzMax.toFixed(4);

    DEBUG && log('Limits', {vcMin:sliders.Vc.min, vcMax:sliders.Vc.max, fzMax:sliders.Fz.max});
  }

  /* ------------------------------------------------------------------
   * 7 · UI helpers
   * ------------------------------------------------------------------ */
  const fmt = (n,d=1)=>Number.parseFloat(n).toFixed(d);
  const set = (el,v,d)=>el&&(el.textContent=fmt(v,d));

  function enhanceSlider(sl) {
    const wrap   = sl.closest('.slider-wrap');
    const bubble = wrap?.querySelector('.slider-bubble');
    const step   = parseFloat(sl.step||1);

    const update = () => {
      const min = +sl.min||0, max = +sl.max||1;
      const pct = ((+sl.value-min)/(max-min))*100;
      wrap?.style.setProperty('--val', pct);
      bubble && (bubble.textContent = fmt(sl.value, step<1?2:0));
    };

    sl.addEventListener('input', update);
    update();
  }

  /* ------------------------------------------------------------------
   * 8 · Radar Chart
   * ------------------------------------------------------------------ */
  function makeRadar() {
    const ctx = $('radarChart').getContext('2d');
    state.radarChart?.destroy();
    state.radarChart = new Chart(ctx, {
      type:'radar',
      data:{labels:['Vida útil','Potencia','Terminación'],datasets:[{data:[0,0,0],fill:true,borderWidth:2}]},
      options:{scales:{r:{max:100,ticks:{stepSize:20}}},plugins:{legend:{display:false}}}
    });
  }

  const radarValues = (hpUsed,hpAvail,fz) => {
    const potencia = Math.min(100,(hpUsed/hpAvail)*100);
    const vida     = Math.min(100,(1/fz)*20);
    const finish   = Math.max(0,100-vida);
    return [vida,potencia,finish];
  };

  /* ------------------------------------------------------------------
   * 9 · Re-cálculo principal
   * ------------------------------------------------------------------ */
  function recalc() {
    // sincronizar estado
    state.fz     = +sliders.Fz.value;
    state.vc     = +sliders.Vc.value;
    state.ae     = +sliders.Ae.value;
    state.passes = +sliders.P.value;

    // cálculos
    const N     = rpm(state.vc);
    const vfRaw = feed(N,state.f
