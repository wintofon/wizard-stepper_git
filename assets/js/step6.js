/* =====================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC · 100 % client‑side
 * ---------------------------------------------------------------------
 *  ▶ Calcula en el navegador los parámetros técnicos de corte (hm, MMR,
 *    FcT, kW, hp…) exactamente igual que en el backend.
 *  ▶ Sin AJAX en tiempo real: todo es inmediato y reactivo.
 *  ▶ Sliders con “pared móvil”: se bloquean sólo hacia el lado peligroso.
 *  ▶ Radar de 3 variables (Vida útil, Potencia, Terminación) siempre vivo.
 * ---------------------------------------------------------------------
 * 2025‑06‑28 — Versión comentada paso a paso.
 * Incluye LOG extendido en consola para:
 *   • FcT   → Fuerza de Corte Total (modelo Kienzle)
 *   • kW / hp y MMR → Potencia y vol. de viruta
 *   • Limites dinámicos de sliders
 * ====================================================================*/
/* globals Chart, CountUp, window */

(() => {
  /* ------------------------------------------------------------------
   * 0 · DEBUG helpers
   * ———————————————————————————————————————————— */
  const DEBUG = window.DEBUG ?? false;            // activar con window.DEBUG=true antes de cargar el script
  const TAG   = '[Step‑6]';
  const log   = (...a) => DEBUG && console.log (TAG, ...a);
  const warn  = (...a) => DEBUG && console.warn(TAG, ...a);
  const table =  d   => DEBUG && console.table?.(d);

  /* ------------------------------------------------------------------
   * 1 · Parámetros inyectados por PHP
   *    Si falta algo crítico, aborta para no romper el DOM.
   * ———————————————————————————————————————————— */
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
    return; // sin parámetros no podemos calcular nada
  }

  /* ------------------------------------------------------------------
   * 1.1 · Desestructuramos solo lo necesario para evitar typos.
   * ———————————————————————————————————————————— */
  const {
    diameter      : D,           // Ø fresa (mm)
    flute_count   : Z,           // nº de filos
    rpm_min       : RPM_MIN,     // rpm mínimas reales del spindle
    rpm_max       : RPM_MAX,     // rpm máximas reales del spindle
    fr_max        : FR_MAX,      // feedrate máx admisible por máquina (mm/min)
    fz0, vc0,                     // punto base sugerido por el backend
    Kc11          : KC,          // coeficiente específico de corte (N/mm²)
    mc,                           // exponente de sensibilidad al espesor
    coef_seg      : K_SEG,       // corrección de filo / filo gastado
    rack_rad      : ALPHA,       // ángulo de ataque en radianes
    eta,                          // eficiencia mecánica del spindle (0‑1)
    thickness     : THK          // espesor total a mecanizar (mm)
  } = P;

  /* ------------------------------------------------------------------
   * 2 · Referencias DOM (sliders, salidas, etc.)
   * ———————————————————————————————————————————— */
  const $ = id => document.getElementById(id);

  const sliders = {
    Vc : $('sliderVc'),  // velocidad de corte (m/min)
    Fz : $('sliderFz'),  // avance por diente (mm)
    Ae : $('sliderAe'),  // ancho de pasada (mm)
    P  : $('sliderPasadas') // nº de pasadas
  };

  const infoPasadas = $('textPasadasInfo');      // texto "X pasadas de Y mm"
  const errBox      = $('errorMsg');             // caja de errores / hints

  const out = {                                  // salidas numéricas en la UI
    vc  : $('outVc'),   fz  : $('outFz'),   hm  : $('outHm'),
    n   : $('outN'),    vf  : $('outVf'),   hp  : $('outHp'),
    mmr : $('valueMrr'),fc  : $('valueFc'), w   : $('valueW'),
    eta : $('valueEta'),ae  : $('outAe'),   ap  : $('outAp')
  };

  /* ------------------------------------------------------------------
   * 3 · Estado reactivo (mutable)
   * ———————————————————————————————————————————— */
  const state = {
    fz : +fz0,
    vc : +vc0,
    ae : (+P.diameter ?? D) * 0.5, // default = ½ diámetro (perfilado)
    passes : 1,                    // nº de pasadas
    radarChart : null              // instancia Chart.js
  };

  /* ------------------------------------------------------------------
   * 4 · Helpers matemáticos (idénticos al backend PHP)
   * ———————————————————————————————————————————— */
  const rpm   = vc            => (vc * 1000) / (Math.PI * D);          // rpm a partir de Vc
  const feed  = (n, fz)       => n * fz * Z;                           // feedrate (mm/min)
  const phi   = ae            => 2 * Math.asin(Math.min(1, ae / D));   // ángulo barrido
  const hm    = (fz, ae)      => {
    const p = phi(ae);
    return p ? fz * (1 - Math.cos(p)) / p : fz;                        // espesor medio viruta
  };
  const mmr   = (ap, vf, ae)  => (ap * vf * ae) / 1000;                // vol. de viruta (cm³/min)

  /**
   * Calcula Fuerza de Corte Total (modelo Kienzle)
   * Log extendido cuando DEBUG=true para auditar cada término
   */
  const FcT = (hmv, ap, fz) => {
    const base   = KC * Math.pow(hmv, -mc);           // N/mm²
    const area   = ap * fz * Z;                       // mm²
    const factor = 1 + K_SEG * Math.tan(ALPHA);       // sin‑dim
    const result = base * area * factor;              // N

    if (DEBUG) {
      console.groupCollapsed(`${TAG} FcT – Fuerza de Corte Total`);
      console.log('Kc11          =', KC, 'N/mm²');
      console.log('h_m           =', hmv.toFixed(4), 'mm');
      console.log('mc            =', mc);
      console.log('ap            =', ap.toFixed(3), 'mm');
      console.log('fz            =', fz.toFixed(4), 'mm/diente');
      console.log('Z             =', Z);
      console.log('coef_seg      =', K_SEG);
      console.log('alpha (rad)   =', ALPHA);
      console.log('→ base        =', base.toFixed(2), 'N/mm²');
      console.log('→ area viruta =', area.toFixed(3), 'mm²');
      console.log('→ factor geo  =', factor.toFixed(3));
      console.log('= FcT total   =', result.toFixed(1), 'N');
      console.groupEnd();
    }
    return result;
  };

  // Potencia instantánea (kW) a partir de fuerza y velocidad de corte
  const kW = (F, Vc) => (F * Vc) / (60_000 * eta);
  const hp = kWv     => kWv * 1.341; // convierte kW → HP británicos

  /* ------------------------------------------------------------------
   * 4.5 · Límites dinámicos de sliders (pared móvil)
   *       Mantiene coherencia física (rpm, feed, etc.)
   * ———————————————————————————————————————————— */
  function applyDynamicLimits() {
    // —— Vc según rpm min/max ——
    const vcFromRpmMin = (RPM_MIN * Math.PI * D) / 1000;
    const vcFromRpmMax = (RPM_MAX * Math.PI * D) / 1000;
    // —— Vc según feedrate máximo actual ——
    const curFz      = parseFloat(sliders.Fz.value);
    const vcFromFeed = (FR_MAX / (curFz * Z)) * (Math.PI * D) / 1000;

    sliders.Vc.min = vcFromRpmMin.toFixed(1);
    sliders.Vc.max = Math.min(vcFromRpmMax, vcFromFeed).toFixed(1);

    // —— Fz según rpm actual ——
    const curVc  = parseFloat(sliders.Vc.value);
    const curRpm = rpm(curVc);
    const fzMax  = FR_MAX / (curRpm * Z);
    sliders.Fz.max = fzMax.toFixed(4);

    if (DEBUG) {
      log('applyDynamicLimits →', {
        vc_min: sliders.Vc.min, vc_max: sliders.Vc.max,
        fz_max: sliders.Fz.max
      });
    }
  }

  /* ------------------------------------------------------------------
   * 5 · Utilidades de UI (formato y sliders bonitos)
   * ———————————————————————————————————————————— */
  const fmt = (n, d=1) => Number.parseFloat(n).toFixed(d);
  const set = (el,v,d) => el && (el.textContent = fmt(v,d));

  function enhanceSlider(sl) {
    const wrap   = sl.closest('.slider-wrap');            // div envoltorio con CSS custom
    const bubble = wrap?.querySelector('.slider-bubble'); // valor flotante
    const step   = parseFloat(sl.step || 1);
    const min    = () => parseFloat(sl.min || 0);
    const max    = () => parseFloat(sl.max || 1);

    const update = () => {
      const pct = ((sl.value - min()) / (max() - min())) * 100;
      wrap?.style.setProperty('--val', pct);              // gradiente
      bubble && (bubble.textContent = fmt(sl.value, step < 1 ? 2 : 0));
    };

    sl.addEventListener('input', update);
    update(); // primera vez
  }

  /* ------------------------------------------------------------------
   * 6 · Radar (Chart.js)
   * ———————————————————————————————————————————— */
  function makeRadar() {
    const ctx = $('radarChart').getContext('2d');
    state.radarChart?.destroy();

    state.radarChart = new Chart(ctx, {
      type : 'radar',
      data : {
        labels   : ['Vida útil','Potencia','Terminación'],
        datasets : [{ data:[0,0,0], fill:true, borderWidth:2 }]
      },
      options : {
        scales : { r:{ max:100, ticks:{ stepSize:20 } } },
        plugins: { legend:{ display:false } }
      }
    });
  }

  // Heurística simple para radar (0‑100)
  function radarValues(hpUsed, hpAvail, fz) {
    const potencia    = Math.min(100,(hpUsed/hpAvail)*100);
    const vidaUtil    = Math.min(100,(1/fz)*20);  // 1↘fz ⇒ menos vida
    const terminacion = Math.max(0,100-vidaUtil);
    return [vidaUtil,potencia,terminacion];
  }

  /* ------------------------------------------------------------------
   * 7 · Re‑cálculo principal (on‑change)
   * ———————————————————————————————————————————— */
  function recalc() {
    // 7.1 · Sincronizar estado con sliders
    state.fz     = +sliders.Fz.value;
    state.vc     = +sliders.Vc.value;
    state.ae     = +sliders.Ae.value;
    state.passes = +sliders.P.value;

    // 7.2 · Cálculos paso a paso
    const N      = rpm(state.vc);
    const vfRaw  = feed(N, state.fz);
    const vf     = Math.min(vfRaw, FR_MAX);
    const ap     = THK / state.passes;
    const hmVal  = hm(state.fz,state.ae);
    const fct    = FcT(hmVal, ap, state.fz);
    const kWVal  = kW(fct, state.vc);
    const hpVal  = hp(kWVal);
    const mmrVal = mmr(ap, vf, state.ae);
    const watts  = kWVal*1000;

    // 7.3 · Render de salidas
    set(out.vc , state.vc ,1);
    set(out.fz , state.fz ,4);
    set(out.n  , N        ,0);
    set(out.vf , vf       ,0);
    set(out.hm , hmVal    ,4);
    set(out.ae , state.ae ,2);
    set(out.ap , ap       ,3);
    set(out.hp , hpVal    ,2);
    set(out.mmr, mmrVal   ,0);
    set(out.fc , fct      ,0);
    set(out.w  , watts    ,0);
    set(out.eta, (hpVal/hp(kWVal)).toFixed(0),0);

    // 7.4 · Radar
    state.radarChart.data.datasets[0].data = radarValues(hpVal, P.hp_avail ?? hpVal, state.fz);
    state.radarChart.update();

    // 7.5 · Logs de potencia y MMR
    if (DEBUG) {
      console.groupCollapsed(`${TAG} Potencia & MMR`);
      console.log('rpm (N)        =', N.toFixed(0));
      console.log('feedrate (vf)  =', vf.toFixed(0),'mm/min');
      console.log('ap             =', ap.toFixed(3),'mm');
      console.log('ae             =', state.ae.toFixed(2),'mm');
      console.log('hm             =', hmVal.toFixed(4),'mm');
      console.log('FcT            =', fct.toFixed(1),'N');
      console.log('kW             =', kWVal.toFixed
