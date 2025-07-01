/* =======================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC (con radar coloreado y alertas)
 * ====================================================================*/
(() => {
  'use strict';

  /* ────────────────────── DEBUG HELPERS ────────────────────── */
  const DEBUG = window.DEBUG ?? false;
  const TAG   = '[Step6]';
  const say   = (lvl, ...m) => { if (DEBUG) console[lvl](`${TAG}`, ...m); };
  const log   = (...m) => say('log',   ...m);
  const warn  = (...m) => say('warn',  ...m);
  const error = (...m) => say('error', ...m);
  const diff  = (a={}, b={}) => {
    const keys = new Set([...Object.keys(a), ...Object.keys(b)]);
    return [...keys].some(k => a[k] !== b[k]);
  };

  /* ─────────────────── PARAMS & DOM ─────────────────── */
  const P = window.step6Params;
  if (!P) return alert('⚠️  Parámetros técnicos faltantes.');

  const {
    diameter:    D,
    flute_count: Z,
    rpm_min:     RPM_MIN,
    rpm_max:     RPM_MAX,
    fr_max:      FR_MAX,
    coef_seg:    K_SEG_transmission = 1.2,
    Kc11:        KC,
    mc:          MC,
    eta:         ETA,
    fz0:         FZ0,
    vc0:         VC0,
    thickness:   THK,
    cut_length:  CUT_LEN,
    hp_avail:    HP_AVAIL,
    fz_min0:     FZ_MIN,
    fz_max0:     FZ_MAX,
    angle_ramp:  ANGLE_RAMP
  } = P;

  const $   = sel => document.querySelector(sel);
  const fmt = (n,d=1) => parseFloat(n).toFixed(d);

  const SL = {
    fz:   $('#sliderFz'),
    vc:   $('#sliderVc'),
    ae:   $('#sliderAe'),
    pass: $('#sliderPasadas')
  };
  // Multiplicar límites de fz por coef. seguridad
  SL.fz.min = (FZ_MIN * K_SEG_transmission).toFixed(4);
  SL.fz.max = (FZ_MAX * K_SEG_transmission).toFixed(4);

  const OUT = {
    vc:$('#outVc'), fz:$('#outFz'), hm:$('#outHm'), n:$('#outN'),
    vf:$('#outVf'), hp:$('#outHp'), mmr:$('#valueMrr'),
    fc:$('#valueFc'), w:$('#valueW'), eta:$('#valueEta'),
    ae:$('#outAe'), ap:$('#outAp'), vf_ramp:$('#valueRampVf')
  };
  const feedAlert = $('#feedAlert');
  const rpmAlert  = $('#rpmAlert');
  const lenAlert  = $('#lenAlert');
  const hpAlert   = $('#hpAlert');

  const state = { fz:+FZ0 * K_SEG_transmission, vc:+VC0, ae:D*0.5, ap:1, last:{} };

  /* ────────────────── ALERT HELPERS ────────────────── */
  const showAlert = (el,msg) => {
    if (!el) return;
    el.textContent = msg;
    el.classList.remove('d-none');
    el.classList.add('alert','alert-danger');
  };
  const hideAlert = el => {
    if (!el) return;
    el.textContent = '';
    el.classList.add('d-none');
  };

  /* ─────────────────── VALIDATIONS ─────────────────── */
  const validateFeed = vf => {
    if (vf >= FR_MAX) { showAlert(feedAlert, `Feedrate supera ${FR_MAX} mm/min`); return false; }
    hideAlert(feedAlert); return true;
  };
  const validateRpm = n => {
    if (n < RPM_MIN || n > RPM_MAX) { showAlert(rpmAlert, `RPM fuera de rango (${Math.round(n)})`); return false; }
    hideAlert(rpmAlert); return true;
  };
  const validateLength = () => {
    if (THK > CUT_LEN) { showAlert(lenAlert, `Espesor ${THK} mm > corte útil ${CUT_LEN} mm`); return false; }
    hideAlert(lenAlert); return true;
  };
  const validateHP = pct => {
    if (pct >= 80) showAlert(hpAlert, '⚠️ Potencia > 80% disponible');
    else          hideAlert(hpAlert);
  };

  /* ──────────────────── FORMULAS ──────────────────── */
  const rpmCalc  = vc        => (vc*1000)/(Math.PI*D);
  const feedCalc = (n,fz)    => n*fz*Z;
  const hmCalc   = (fz,ae)   => { const p = 2*Math.asin(Math.min(1, ae/D)); return p ? fz*(1-Math.cos(p))/p : fz; };
  const mmrCalc  = (ap,vf,ae)=> (ap*ae*vf)/1000;
  const kc_h     = hm       => KC*Math.pow(hm,-MC);
  const FcT      = (kc,ap)  => kc*ap*Z*(1 + K_SEG_transmission * Math.tan(0));
  const kWCalc   = (kc,ap,ae,vf)=> (ap*ae*vf*kc)/(60e6*ETA);
  const hpCalc   = w        => w*1.341;

  /* ────────────── SLIDER UI HELPERS ────────────── */
  function beautify(slider,dec) {
    if (!slider) return;
    const wrap = slider.closest('.slider-wrap');
    const bub  = wrap?.querySelector('.slider-bubble');
    const [min,max] = [+slider.min, +slider.max];
    const paint = v => {
      wrap.style.setProperty('--val', ((v-min)/(max-min))*100);
      if (bub) bub.textContent = fmt(v,dec);
    };
    paint(+slider.value);
    slider.addEventListener('input',e => { paint(+e.target.value); onInput(); });
  }

  /**
   * ─────────────────── PASADAS SYNC ───────────────────
   * Ajusta el rango del slider de 'pasadas' de modo que
   * la profundidad de pasada (THK/passes) nunca exceda CUT_LEN.
   */
  function syncPass() {
    // Mínimo de pasadas para que ap = THK/passes ≤ CUT_LEN
    const minPasses = Math.ceil(THK / CUT_LEN);
    // Máximo de pasadas para razonable (usa el ancho de pasada como límite alto)
    const maxPasses = Math.max(1, Math.ceil(THK / state.ae));

    SL.pass.min  = minPasses;
    SL.pass.max  = maxPasses;
    SL.pass.step = 1;

    // Si el valor actual está fuera de rango, ajústalo
    let p = +SL.pass.value;
    if (p < minPasses) p = minPasses;
    if (p > maxPasses) p = maxPasses;
    SL.pass.value = p;

    state.ap = p;
    $('#textPasadasInfo').textContent =
      `${state.ap} pasada${state.ap>1?'s':''} de ${(THK/state.ap).toFixed(2)} mm`;
  }

  /* ─────────────────── RECALC & RENDER ─────────────────── */
  let render = snap => {
    if (!diff(state.last, snap)) return;
    Object.entries(snap).forEach(([k,v]) => {
      if (OUT[k]) OUT[k].textContent = fmt(v, v%1?2:0);
    });
    if (radar) {
      radar.data.datasets[0].data = [snap.life, snap.power, snap.finish];
      radar.data.datasets[0].backgroundColor =
        snap.power < 50 ? 'rgba(76,175,80,0.2)' :
        snap.power < 80 ? 'rgba(255,152,0,0.2)' :
                         'rgba(244,67,54,0.2)';
      radar.update();
    }
    state.last = snap;
  };

  function recalc() {
    // Cálculo de RPM y feed
    const N     = rpmCalc(state.vc);
    const vfRaw = feedCalc(N, state.fz);
    const vf    = Math.min(vfRaw, FR_MAX);

    // Si feed topa, ajustar fz
    if (vfRaw >= FR_MAX) {
      state.fz = FR_MAX/(N*Z);
      SL.fz.value = fmt(state.fz,4);
      SL.fz.closest('.slider-wrap')
           .querySelector('.slider-bubble').textContent = fmt(state.fz,4);
    }

    // Profundidad de pasada
    const apVal = THK / state.ap;

    // Validaciones
    validateFeed(vf);
    validateRpm(N);
    validateLength();
    // (pasadas redondeadas abajo ya garantizan apVal ≤ CUT_LEN)

    // Demás cálculos
    const hmVal  = hmCalc(state.fz,state.ae);
    const kcVal  = kc_h(hmVal);
    const mmrVal = mmrCalc(apVal,vf,state.ae);
    const fcVal  = FcT(kcVal,apVal);
    const wVal   = kWCalc(kcVal,apVal,state.ae,vf);
    const hpVal  = hpCalc(wVal);

    // Porcentajes radar
    const lifePct   = Math.min(100,((state.fz-FZ_MIN)/(FZ_MAX-FZ_MIN))*100);
    const powerPct  = (hpVal/(HP_AVAIL||1))*100;
    const finishPct = Math.max(0,100-lifePct);
    validateHP(powerPct);

    // Render final
    render({
      vc:       state.vc,
      n:        N|0,
      vf:       vf|0,
      vf_ramp:  Math.round(vf * Math.cos(ANGLE_RAMP * Math.PI/180) / Z),
      ae:       state.ae,
      ap:       apVal,
      hm:       hmVal,
      mmr:      Math.round(mmrVal),
      fc:       fcVal|0,
      hp:       hpVal.toFixed(1),
      w:        (wVal*1000)|0,
      eta:      Math.min(100,powerPct)|0,
      life:     lifePct,
      power:    powerPct,
      finish:   finishPct,
      fz:       state.fz
    });
  }

  /* ─────────────────── INPUT HANDLER ─────────────────── */
  function onInput() {
    // fz multiplicado por coef. seguridad
    const rawFz = +SL.fz.value;
    state.fz = rawFz * K_SEG_transmission;

    state.vc = +SL.vc.value;
    // ae ≤ diámetro (redondeado abajo)
    const maxAe = Math.floor(D);
    state.ae = Math.min(+SL.ae.value, maxAe);
    SL.ae.value = state.ae;

    syncPass();
    recalc();
  }

  /* ───────────────────── INIT ─────────────────── */
  let radar;
  function makeRadar() {
    const ctx = $('#radarChart')?.getContext('2d');
    if (!ctx || !window.Chart) return warn('Chart.js no cargó');
    radar = new Chart(ctx, {
      type: 'radar',
      data: {
        labels: ['Vida Útil','Potencia','Terminación'],
        datasets: [{
          data: [0,0,0], fill:true, borderWidth:2,
          backgroundColor:'rgba(76,175,80,0.2)', borderColor:'#4caf50'
        }]
      },
      options: {
        scales: { r:{ min:0,max:100,ticks:{ stepSize:20 }}},
        plugins:{ legend:{ display:false }}
      }
    });
  }

  try {
    // Ajustes iniciales de sliders
    SL.vc.min   = fmt(VC0*0.5,1);
    SL.vc.max   = fmt(VC0*1.5,1);
    SL.vc.value = fmt(state.vc,1);

    SL.ae.min   = fmt(0.1,1);
    SL.ae.max   = fmt(Math.floor(D),1);
    SL.ae.value = fmt(state.ae,1);

    SL.pass.value = 1;

    beautify(SL.fz,4);
    beautify(SL.vc,1);
    beautify(SL.ae,1);
    beautify(SL.pass,0);

    ['input','change'].forEach(evt=>{
      ['fz','vc','ae','pass'].forEach(k=>{
        if (SL[k]) SL[k].addEventListener(evt,onInput);
      });
    });

    makeRadar();
    syncPass();
    recalc();
    log('Init completo');
  } catch(e) {
    error(e);
    alert('Error JS: '+e.message);
  }
})();
