/* =======================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC (con radar coloreado y alertas)
 * ====================================================================*/

(() => {
  'use strict'; 

  /* ────────────────────── DEBUG HELPERS ────────────────────── */
  const DEBUG = window.DEBUG ?? false;
  const TAG   = '[Step6]';
  const say   = (lvl, ...m) => { if (DEBUG) console[lvl](${TAG}, ...m); };
  const log   = (...m) => say('log',   ...m);
  const warn  = (...m) => say('warn',  ...m);
  const error = (...m) => say('error', ...m);
  /** Compara dos snapshots y devuelve true si hay cambio */
  const diff  = (a={}, b={}) => {
    const keys = new Set([...Object.keys(a), ...Object.keys(b)]);
    return [...keys].some(k => a[k] !== b[k]);
  };

  /* ─────────────────── PARAMS & DOM ─────────────────── */
  const P = window.step6Params;
  if (!P) return alert('⚠️  Parámetros técnicos faltantes.');

  const {
    diameter:    D,                // diámetro (mm)
    flute_count: Z,                // número de filos
    rpm_min:     RPM_MIN,          // rpm mínimo
    rpm_max:     RPM_MAX,          // rpm máximo
    fr_max:      FR_MAX,           // feedrate máximo (mm/min)
    coef_seg:    K_SEG_transmission, // coef seguridad (transmisión)
    Kc11:        KC,               // coef corte específico
    mc:          MC,               // exponente mc
    eta:         ETA,              // eficiencia
    fz0:         FZ0,              // fz base
    vc0:         VC0,              // vc base
    thickness:   THK,              // espesor material
    cut_length:  CUT_LEN,          // largo de filo (mm)
    hp_avail:    HP_AVAIL,         // HP disponible
    fz_min0:     FZ_MIN,           // fz mínimo base
    fz_max0:     FZ_MAX,           // fz máximo base
    angle_ramp:  ANGLE_RAMP        // ángulo de rampa (grados)
  } = P;

  const $   = sel => document.querySelector(sel);
  const fmt = (n,d=1) => parseFloat(n).toFixed(d);

  /* ───────────── SLIDERS & OUTPUT ELEMENTS ───────────── */
  const SL = {
    fz:   $('#sliderFz'),
    vc:   $('#sliderVc'),
    ae:   $('#sliderAe'),
    pass: $('#sliderPasadas')
  };
  // Al arrancar, ajustar los límites del slider fz por el coeficiente de seguridad
  SL.fz.min = (FZ_MIN * K_SEG_transmission).toFixed(4);
  SL.fz.max = (FZ_MAX * K_SEG_transmission).toFixed(4);

  const OUT = {
    vc:      $('#outVc'),
    fz:      $('#outFz'),
    hm:      $('#outHm'),
    n:       $('#outN'),
    vf:      $('#outVf'),
    hp:      $('#outHp'),
    mmr:     $('#valueMrr'),
    fc:      $('#valueFc'),
    w:       $('#valueW'),
    eta:     $('#valueEta'),
    ae:      $('#outAe'),
    ap:      $('#outAp'),
    vf_ramp: $('#valueRampVf')
  };

  /* ────────────────── ALERT ELEMENTS ────────────────── */
  const feedAlert = $('#feedAlert');
  const rpmAlert  = $('#rpmAlert');
  const lenAlert  = $('#lenAlert');
  const hpAlert   = $('#hpAlert');

  /* ───────────────────── STATE ────────────────────── */
  const state = {
    // fz inicial escalado por coef. seguridad
    fz: +FZ0 * K_SEG_transmission,
    vc: +VC0,
    ae: D * 0.5,
    ap: 1,
    last: {}
  };

  /* ────────────────── ALERT HELPERS ────────────────── */
  const showAlert = (el,msg) => {
    el?.classList.remove('d-none');
    el?.classList.add('alert','alert-danger');
    if (el) el.textContent = msg;
  };
  const hideAlert = el => {
    el?.classList.add('d-none');
    if (el) el.textContent = '';
  };

  /* ─────────────────── VALIDATIONS ─────────────────── */
  const validateFeed   = vf  => (vf<FR_MAX ? hideAlert(feedAlert) : showAlert(feedAlert, Feedrate > ${FR_MAX}));
  const validateRpm    = n   => (RPM_MIN<=n&&n<=RPM_MAX ? hideAlert(rpmAlert) : showAlert(rpmAlert, RPM fuera de rango));
  const validateLength = ()  => (THK<=CUT_LEN ? hideAlert(lenAlert) : showAlert(lenAlert, Espesor > largo de filo));
  const validateHP     = pct => (pct<80   ? hideAlert(hpAlert)   : showAlert(hpAlert, '⚠️ Potencia >80%'));

  /* ──────────────────── FORMULAS ──────────────────── */
  const rpmCalc  = vc          => (vc*1000)/(Math.PI*D);
  const feedCalc = (n,fz)      => n*fz*Z;
  const hmCalc   = (fz,ae)     => {
    const phi = 2*Math.asin(Math.min(1,ae/D));
    return phi>0 ? fz*(1-Math.cos(phi))/phi : fz;
  };
  const mmrCalc  = (ap,vf,ae)  => (ap*ae*vf)/1000;
  const kc_h     = hm          => KC*Math.pow(hm,-MC);
  const FcT      = (kc,ap)     => kc*ap*Z*(1 + K_SEG_transmission*Math.tan(0));
  const kWCalc   = (kc,ap,ae,vf)=> (ap*ae*vf*kc)/(60e6*ETA);
  const hpCalc   = w           => w*1.341;

  /* ────────────── SLIDER UI HELPERS ────────────── */
  function beautify(slider,dec) {
    if (!slider) return;
    const wrap = slider.closest('.slider-wrap');
    const bub  = wrap?.querySelector('.slider-bubble');
    const [min,max] = [+slider.min, +slider.max];
    const paint = v => {
      // colorear slider-bubble en rojo si está en límite
      wrap.style.setProperty('--val',((v-min)/(max-min))*100);
      bub && (bub.textContent = fmt(v,dec));
      if (v<=min || v>=max) bub?.classList.add('limit');
      else                 bub?.classList.remove('limit');
    };
    paint(+slider.value);
    slider.addEventListener('input', e => { paint(+e.target.value); onInput(); });
  }

  /**
   * ─────────────────── PASADAS SYNC ───────────────────
   * Ajusta el slider de pasadas para que la profundidad (THK/p)s
   * nunca exceda el largo de filo CUT_LEN.
   */
  function syncPass() {
    // mínimo de pasadas: THK/p ≤ CUT_LEN ⇒ p ≥ THK/CUT_LEN
    const minP = Math.ceil(THK/CUT_LEN) || 1;
    // máximo de pasadas: p ≤ THK/ae (para que ap≥1)
    const maxP = Math.max(1, Math.ceil(THK/state.ae));
    SL.pass.min  = minP;
    SL.pass.max  = maxP;
    SL.pass.step = 1;
    let p = +SL.pass.value;
    if (p<minP) p=minP;
    if (p>maxP) p=maxP;
    SL.pass.value = p;
    state.ap = p;
    $('#textPasadasInfo').textContent =
      ${p} pasada${p>1?'s':''} de ${(THK/p).toFixed(2)} mm;
  }

  /* ─────────────────── RECALC & RENDER ─────────────────── */
  let render = snap => {
    if (!diff(state.last,snap)) return;
    // actualizar DOM
    for (let k in snap) {
      if (OUT[k]) OUT[k].textContent = fmt(snap[k], snap[k]%1?2:0);
    }
    // actualizar radar
    if (radar) {
      radar.data.datasets[0].data = [snap.life, snap.power, snap.finish];
      radar.data.datasets[0].backgroundColor =
        snap.power<50 ? 'rgba(76,175,80,0.2)' :
        snap.power<80 ? 'rgba(255,152,0,0.2)' :
                       'rgba(244,67,54,0.2)';
      radar.update();
    }
    state.last = snap;
  };

  function recalc() {
    // 1) RPM y feed
    const N     = rpmCalc(state.vc);
    const vfRaw = feedCalc(N, state.fz);
    const vf    = Math.min(vfRaw,FR_MAX);

    // 2) Ajuste auto de fz si topa feedrate
    if (vfRaw>=FR_MAX) {
      state.fz = FR_MAX/(N*Z);
      SL.fz.value = fmt(state.fz,4);
      SL.fz.closest('.slider-wrap')
            .querySelector('.slider-bubble').textContent = fmt(state.fz,4);
    }

    // 3) Profundidad de pasada
    const apVal = THK/state.ap;

    // 4) Validaciones
    validateFeed(vf);
    validateRpm(N);
    validateLength();
    validateHP((hpCalc(kWCalc(kc_h(hmCalc(state.fz,state.ae)),apVal,state.ae,vf))/(HP_AVAIL||1))*100);

    // 5) Cálculos intermedios
    const hmVal  = hmCalc(state.fz,state.ae);
    const kcVal  = kc_h(hmVal);
    const mmrVal = mmrCalc(apVal,vf,state.ae);
    const fcVal  = FcT(kcVal,apVal);
    const wVal   = kWCalc(kcVal,apVal,state.ae,vf);
    const hpVal  = hpCalc(wVal);

    // 6) Radar %
    const lifePct   = Math.min(100,((state.fz-FZ_MIN)/(FZ_MAX-FZ_MIN))*100);
    const powerPct  = (hpVal/(HP_AVAIL||1))*100;
    const finishPct = Math.max(0,100-lifePct);

    // 7) Render final
    render({
      vc:      state.vc,
      n:       N|0,
      vf:      vf|0,
      vf_ramp: Math.round(vf*Math.cos(ANGLE_RAMP*Math.PI/180)/Z),
      ae:      state.ae,
      ap:      apVal,
      hm:      hmVal,
      mmr:     Math.round(mmrVal),
      fc:      fcVal|0,
      hp:      hpVal.toFixed(1),
      w:       (wVal*1000)|0,
      eta:     Math.min(100,powerPct)|0,
      life:    lifePct,
      power:   powerPct,
      finish:  finishPct,
      fz:      state.fz
    });
  }

  /* ─────────────────── INPUT HANDLER ─────────────────── */
  function onInput() {
    // 1) Avance por diente multiplicado
    const rawFz = +SL.fz.value;
    state.fz = rawFz * K_SEG_transmission;
    // 2) Velocidad de corte
    state.vc = +SL.vc.value;
    // 3) Ancho de pasada ≤ diámetro (redondeo hacia abajo)
    const maxAe = Math.floor(D);
    state.ae = Math.min(+SL.ae.value, maxAe);
    SL.ae.value = state.ae;
    // 4) Sincronizar pasadas y recálculo
    syncPass();
    recalc();
  }

  /* ───────────────────── INIT ─────────────────── */
  let radar;
  function makeRadar() {
    const ctx = $('#radarChart')?.getContext('2d');
    if (!ctx || !window.Chart) { warn('Chart.js no cargó'); return; }
    radar = new Chart(ctx, {
      type: 'radar',
      data: {
        labels: ['Vida Útil','Potencia','Terminación'],
        datasets: [{ data:[0,0,0], fill:true, borderWidth:2,
          backgroundColor:'rgba(76,175,80,0.2)', borderColor:'#4caf50'
        }]
      },
      options: {
        scales: { r:{ min:0,max:100,ticks:{ stepSize:20 } } },
        plugins: { legend:{ display:false } }
      }
    });
  }

  try {
    // ─ Ajustes iniciales de sliders ─
    SL.vc.min   = fmt(VC0*0.5,1);
    SL.vc.max   = fmt(VC0*1.5,1);
    SL.vc.value = fmt(state.vc,1);

    SL.ae.min   = fmt(0.1,1);
    SL.ae.max   = fmt(Math.floor(D),1);
    SL.ae.value = fmt(state.ae,1);

    SL.pass.value = 1;

    // ─ Embellecer y colorear ─
    beautify(SL.fz,4);
    beautify(SL.vc,1);
    beautify(SL.ae,1);
    beautify(SL.pass,0);

    // ─ Eventos de entrada ─
    ['input','change'].forEach(evt => {
      ['fz','vc','ae','pass'].forEach(k => {
        SL[k]?.addEventListener(evt,onInput);
      });
    });

    // ─ Inicializar radar + recálculo ─
    makeRadar();
    syncPass();
    recalc();
    log('Init completo');
  } catch(e) {
    error(e);
    alert('Error JS: '+e.message);
  }
})();
