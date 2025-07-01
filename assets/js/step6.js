/* =======================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC
 * -----------------------------------------------------------------------
 *  • 100 % cálculo en cliente, sin AJAX.
 *  • Deslizadores:
 *      – Vc  = ±50 % del valor base, pero nunca sobrepasa RPM min / max.
 *      – fz  limitado por tabla (fz_min0..fz_max0) y por feedrate max.
 *      – ae  actualiza automáticamente el nº de pasadas (ap).
 *  • Radar Chart de 3 ejes: Vida Útil · Potencia · Terminación.
 *      – ↑ fz  ⇒ ↑ Vida Útil + ↑ Potencia + ↓ Terminación.
 *      – Potencia mapeada con γ suave (0.5) para menos agresividad.
 *  • Potencia **corrige** divisor:   60 × 10⁶   (mm³/min → kW)
 *  • Consola silenciosa; solo registra cuando el *snapshot* cambia.
 * ====================================================================*/

(() => {
  'use strict';

  /* ────────────────────── DEBUG HELPERS ────────────────────── */
  const DEBUG = window.DEBUG ?? false;
  const TAG   = '[Step6]';
  const ts    = () => new Date().toISOString();
  const say   = (lvl, ...m) => { if (DEBUG) console[lvl](`${TAG} ${ts()}`, ...m); };
  const log   = (...m) => say('log',   ...m);
  const warn  = (...m) => say('warn',  ...m);
  const error = (...m) => say('error', ...m);
  const diff  = (a={}, b={}) => {
    const keys = new Set([...Object.keys(a), ...Object.keys(b)]);
    return [...keys].some(k => a[k] !== b[k]);
  };

  /* ─────────────────── PARAMS INYECTADOS ─────────────────── */
  const P = window.step6Params;
  if (!P) {
    alert('⚠️  step6Params vacío – verifica la sesión.');
    return;
  }

  const REQUIRED_KEYS = [
    'diameter','flute_count','rpm_min','rpm_max','fr_max',
    'coef_seg','Kc11','mc','eta','fz0','vc0','thickness',
    'fz_min0','fz_max0','hp_avail','angle_ramp','cut_length'
  ];
  const missing = REQUIRED_KEYS.filter(k => P[k] === undefined);
  if (missing.length) {
    alert(`⚠️  Faltan claves: ${missing.join(', ')}`);
    return;
  }

  /* ─────────—— DESTRUCTURACIÓN CON ALIAS ─────────—— */
  const {
    diameter:    D,
    flute_count: Z,
    rpm_min:     RPM_MIN,
    rpm_max:     RPM_MAX,
    fr_max:      FR_MAX,
    coef_seg:    K_SEG,
    Kc11:        KC,
    mc:          MC,
    eta:         ETA,
    alpha:       ALPHA = 0,
    fz0:         FZ0,
    vc0:         VC0,
    thickness:   THK,
    cut_length:  CUT_LEN,
    hp_avail:    HP_AVAIL,
    fz_min0:     FZ_MIN,
    fz_max0:     FZ_MAX,
    angle_ramp:  ANGLE_RAMP
  } = P;

  /* ──────────────── STATE & CONSTANTS ─────────────── */
  const state = {
    fz: +FZ0,
    vc: +VC0,
    ae: D * 0.5,
    ap: 1,
    last: {}
  };

  /* ─────────────────── DOM REFERENCES ─────────────────── */
  const $   = (sel, ctx=document) => ctx.querySelector(sel);
  const fmt = (n, d=1) => parseFloat(n).toFixed(d);

  const SL = {
    fz: $('#sliderFz'),
    vc: $('#sliderVc'),
    ae: $('#sliderAe'),
    pass: $('#sliderPasadas')
  };

  const OUT = {
    vc: $('#outVc'),
    fz: $('#outFz'),
    hm: $('#outHm'),
    n:  $('#outN'),
    vf: $('#outVf'),
    hp: $('#outHp'),
    mmr: $('#valueMrr'),
    fc: $('#valueFc'),
    w:  $('#valueW'),
    eta:$('#valueEta'),
    ae: $('#outAe'),
    ap: $('#outAp'),
    vf_ramp: $('#valueRampVf')
  };

  const infoPass  = $('#textPasadasInfo');
  const errBox    = $('#errorMsg');
  const feedAlert = $('#feedAlert');
  const rpmAlert  = $('#rpmAlert');
  const lenAlert  = $('#lenAlert');

  const fatal = msg => {
    if (errBox) {
      errBox.textContent = msg;
      errBox.style.display = 'block';
    } else {
      alert(msg);
    }
  };

  /* ─────────────────── ALERT HELPERS ─────────────────── */
  const showAlert = (el, msg) => {
    if (!el) return;
    el.textContent = msg;
    el.classList.remove('d-none');
    el.classList.add('alert', 'alert-danger');
  };
  const hideAlert = el => {
    if (!el) return;
    el.textContent = '';
    el.classList.add('d-none');
  };

  /* ───────────────── VALIDATIONS ───────────────── */
  const validateFeed = vf => {
    if (vf >= FR_MAX) {
      showAlert(feedAlert, `Feedrate supera el máximo (${FR_MAX} mm/min)`);
      return false;
    }
    hideAlert(feedAlert);
    return true;
  };

  const validateRpm = n => {
    if (n < RPM_MIN || n > RPM_MAX) {
      showAlert(rpmAlert, `RPM fuera de rango (${Math.round(n)})`);
      return false;
    }
    hideAlert(rpmAlert);
    return true;
  };

  const validateLength = () => {
    if (THK > CUT_LEN) {
      showAlert(lenAlert, `Material (${THK} mm) > longitud útil fresa (${CUT_LEN} mm)`);
      return false;
    }
    hideAlert(lenAlert);
    return true;
  };

  /* ──────────────────── FORMULAS ──────────────────── */
  const rpmCalc   = vc             => (vc * 1000) / (Math.PI * D);
  const feedCalc  = (n, fz)        => n * fz * Z;
  const phi       = ae             => 2 * Math.asin(Math.min(1, ae / D));
  const hmCalc    = (fz, ae)       => {
    const p = phi(ae);
    return p ? fz * (1 - Math.cos(p)) / p : fz;
  };
  const mmrCalc   = (ap, vf, ae)   => (ap * ae * vf) / 1000;
  const kc_h      = hmV           => KC * Math.pow(hmV, -MC);
  const FcT      = (kc, ap)       => kc * ap * Z * (1 + K_SEG * Math.tan(ALPHA));
  const kWCalc   = (kc, ap, ae, vf)=> (ap * ae * vf * kc) / (60e6 * ETA);
  const hpCalc   = kWv            => kWv * 1.341;

  /* ────────────── helper: waitFor(lib) ────────────── */
  const ready = (test, cb, retries = 20) => {
    if (test()) return cb();
    if (retries > 0) {
      setTimeout(() => ready(test, cb, retries - 1), 120);
    } else {
      warn('Dependencia no cargó: se omite función dependiente');
    }
  };

  /* ───────────────── RADAR CHART SETUP ───────────────── */
  let radar;

  function makeRadar() {
    const ctx = $('#radarChart')?.getContext('2d');
    if (!ctx || !window.Chart) {
      warn('Chart.js ausente: sin radar');
      return;
    }

    // Función de mapeo suave para potencia (γ = 0.5)
    const mapPower = r => {
      const γ  = 0.5;
      const rγ = Math.pow(r, γ);
      return Math.min(100, (100 * rγ) / (1 + rγ));
    };

    // Crear instancia del radar
    radar = new Chart(ctx, {
      type: 'radar',
      data: {
        labels: ['Vida Útil', 'Potencia', 'Terminación'],
        datasets: [{
          data: [0, 0, 0],
          fill: true,
          borderWidth: 2,
          backgroundColor: 'rgba(76, 175, 80, 0.2)',   // verde suave
          borderColor: '#4caf50'
        }]
      },
      options: {
        scales: {
          r: {
            min: 0,
            max: 100,
            ticks: { stepSize: 20 }
          }
        },
        plugins: {
          legend: { display: false }
        }
      }
    });

    // Sobrescribir render para aplicar mapPower a snap.power
    const originalRender = render;
    render = snap => {
      // Aplicar curva suave a porcentaje de potencia
      snap.power = mapPower(snap.hp / (HP_AVAIL || 1) * 100);
      originalRender(snap);
    };
  }

  /* ─────────────────── RENDER FUNCTION ───────────────────── */
  let render = snap => {
    if (!diff(state.last, snap)) return;

    // Actualizar valores en DOM
    for (const key in snap) {
      if (OUT[key]) {
        OUT[key].textContent = fmt(snap[key], snap[key] % 1 ? 2 : 0);
      }
    }

    // Actualizar radar
    if (radar) {
      radar.data.datasets[0].data = [snap.life, snap.power, snap.finish];
      radar.update();
    }

    state.last = snap;
    log('render', snap);
  };

  /* ─────────────────── RECALC FUNCTION ───────────────────── */
  function recalc() {
    // Cálculos básicos
    const N      = rpmCalc(state.vc);
    const vfRaw  = feedCalc(N, state.fz);
    const vf     = Math.min(vfRaw, FR_MAX);
    const vfRamp = vf * Math.cos(ANGLE_RAMP * Math.PI / 180) / Z;

    // Ajuste de fz si topa con fr_max
    if (vfRaw >= FR_MAX) {
      state.fz = FR_MAX / (N * Z);
      SL.fz.value = fmt(state.fz, 4);
      const bubble = SL.fz.closest('.slider-wrap')?.querySelector('.slider-bubble');
      if (bubble) bubble.textContent = fmt(state.fz, 4);
    }

    const apVal = THK / state.ap;

    // Validaciones
    validateFeed(vf);
    validateRpm(N);
    validateLength();

    // Cálculos secundarios
    const hmVal  = hmCalc(state.fz,   state.ae);
    const kcVal  = kc_h(hmVal);
    const mmrVal = mmrCalc(apVal, vf, state.ae);
    const fcVal  = FcT(kcVal, apVal);
    const kWval  = kWCalc(kcVal, apVal, state.ae, vf);
    const hpVal  = hpCalc(kWval);

    // Porcentajes para radar
    const lifePct   = Math.min(100, ((state.fz - FZ_MIN) / (FZ_MAX - FZ_MIN)) * 100);
    const powerPct  = (hpVal / (HP_AVAIL || 1)) * 100;
    const finishPct = Math.max(0, 100 - lifePct);

    // Llamada a render con snapshot completo
    render({
      vc:      state.vc,
      fz:      state.fz,
      hm:      hmVal,
      n:       N | 0,
      vf:      vf | 0,
      vf_ramp: Math.round(vfRamp),
      hp:      hpVal,
      mmr:     Math.round(mmrVal),
      fc:      fcVal | 0,
      w:       (kWval * 1000) | 0,
      eta:     Math.min(100, powerPct) | 0,
      ae:      state.ae,
      ap:      apVal,
      life:    lifePct,
      power:   powerPct,
      finish:  finishPct
    });
  }

  /* ────────────── SLIDER UI HELPERS ─────────────── */
  function beautify(slider, decimals = 3) {
    if (!slider) return;
    const wrap = slider.closest('.slider-wrap');
    const bub  = wrap?.querySelector('.slider-bubble');
    const min  = +slider.min;
    const max  = +slider.max;

    const paint = v => {
      wrap.style.setProperty('--val', ((v - min) / (max - min)) * 100);
      if (bub) bub.textContent = fmt(v, decimals);
    };

    paint(+slider.value);
    slider.addEventListener('input', e => {
      paint(+e.target.value);
      onInput();
    });
  }

  function syncPass() {
    const maxPass = Math.max(1, Math.ceil(THK / state.ae));
    SL.pass.max  = maxPass;
    SL.pass.min  = 1;
    SL.pass.step = 1;

    if (+SL.pass.value > maxPass) {
      SL.pass.value = maxPass;
    }

    state.ap = +SL.pass.value;
    if (infoPass) {
      infoPass.textContent = `${state.ap} pasada${state.ap > 1 ? 's' : ''} de ${(THK / state.ap).toFixed(2)} mm`;
    }
  }

  function onInput() {
    state.fz = +SL.fz.value;
    state.vc = +SL.vc.value;
    state.ae = +SL.ae.value;
    syncPass();
    recalc();
  }

  /* ───────────────────── INIT ─────────────────── */
  try {
    // Limitar rango de Vc
    const vcMin = VC0 * 0.5;
    const vcMax = VC0 * 1.5;
    SL.vc.min   = fmt(vcMin, 1);
    SL.vc.max   = fmt(vcMax, 1);
    SL.vc.value = fmt(state.vc, 1);

    // Inicializar pasadas
    SL.pass.value = 1;

    // Embellecer sliders
    beautify(SL.fz, 4);
    beautify(SL.vc, 1);
    beautify(SL.ae, 2);
    beautify(SL.pass, 0);

    // Eventos de cambio
    ['change', 'input'].forEach(evt => {
      ['fz', 'vc', 'ae', 'pass'].forEach(key => {
        if (SL[key]) SL[key].addEventListener(evt, onInput);
      });
    });

    // Esperar Chart.js antes de crear radar y calcular
    ready(() => window.Chart, () => {
      makeRadar();
      syncPass();
      recalc();
    });

    log('INIT OK — esperando Chart.js');
  } catch (e) {
    error(e);
    fatal('JS Error: ' + e.message);
  }
})();
