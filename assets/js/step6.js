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
 *  • Potencia **corrige** divisor:   60 × 10⁶   (mm³/min → kW)
 *  • Consola silenciosa; solo registra cuando el *snapshot* cambia.
 * ====================================================================*/

(() => {
  'use strict';

  /* ────────────────────── DEBUG helpers ───────────────────── */
  const DEBUG = window.DEBUG ?? false;
  const TAG   = '[Step6]';
  const ts    = () => new Date().toISOString();
  const say   = (lvl, ...m) => { if (DEBUG) console[lvl](`${TAG} ${ts()}`, ...m); };
  const log   = (...m) => say('log',   ...m);
  const warn  = (...m) => say('warn',  ...m);
  const error = (...m) => say('error', ...m);
  const table = d => { if (DEBUG) console.table(d); };
  const diff  = (a={},b={}) => [...new Set([...Object.keys(a),...Object.keys(b)])]
                               .some(k => a[k]!==b[k]);

  /* ─────────────────── PARAMS INYECTADOS ─────────────────── */
  const P = window.step6Params;
  if (!P) return alert('⚠️  step6Params vacío – verifica la sesión.');

  const REQ = [
    'diameter','flute_count','rpm_min','rpm_max','fr_max',
    'coef_seg','Kc11','mc','eta','fz0','vc0','thickness',
    'fz_min0','fz_max0','hp_avail','angle_ramp'
  ];
  const missing = REQ.filter(k => P[k] === undefined);
  if (missing.length) return alert(`⚠️  Faltan claves: ${missing.join(', ')}`);

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
    hp_avail:    HP_AVAIL,
    fz_min0:     FZ_MIN,
    fz_max0:     FZ_MAX,
    angle_ramp:  ANGLE_RAMP = 15
  } = P;

  /* ──────────────── STATE & CONSTANTS ─────────────── */
  const state = {
    fz : +FZ0,
    vc : +VC0,
    ae : D*0.5,
    ap : 1,
    last:{}
  };

  /* ─────────────────── DOM references ────────────────── */
  const $     = (sel,ctx=document)=>ctx.querySelector(sel);
  const fmt   = (n,d=1)=>Number.parseFloat(n).toFixed(d);
  const SL    = {
    fz  : $('#sliderFz'),
    vc  : $('#sliderVc'),
    ae  : $('#sliderAe'),
    pass: $('#sliderPasadas')
  };
  const OUT   = {
    vc:$('#outVc'), fz:$('#outFz'), hm:$('#outHm'), n:$('#outN'), vf:$('#outVf'),
    hp:$('#outHp'), mmr:$('#valueMrr'), fc:$('#valueFc'), w:$('#valueW'), eta:$('#valueEta'),
    ae:$('#outAe'), ap:$('#outAp'), vf_ramp:$('#valueRampVf')
  };
  const infoPass = $('#textPasadasInfo');
  const errBox   = $('#errorMsg');

  const fatal = msg => { errBox? (errBox.textContent=msg,errBox.style.display='block') : alert(msg); };

  /* ──────────────────── FORMULAS ──────────────────── */
  const rpm    = vc          => (vc*1000)/(Math.PI*D);
  const feed   = (n,fz)      => n*fz*Z;
  const phi    = ae          => 2*Math.asin(Math.min(1, ae/D));
  const hm     = (fz,ae)     => { const p=phi(ae); return p? fz*(1-Math.cos(p))/p : fz; };
  const mmr    = (ap,vf,ae)  => (ap*ae*vf)/1000;
  const kc_h   = hmV         => KC*Math.pow(hmV,-MC);   // módulo Kc(hm)
  const FcT    = (kc,ap)     => kc*ap*Z*(1+K_SEG*Math.tan(ALPHA)); // slot completo
  const kW     = (kc,ap,ae,vf)=> (ap*ae*vf*kc)/(60*1e6*ETA);       // 60·10^6 divisor
  const HP     = kWv         => kWv*1.341;

  /* ───────────────── Radar Chart ───────────────────── */
  let radar;
  const makeRadar = () => {
    const ctx=$('#radarChart')?.getContext('2d');
    radar = ctx && new Chart(ctx,{
      type:'radar',
      data:{labels:['Vida Útil','Potencia','Terminación'],
            datasets:[{data:[0,0,0],fill:true,borderWidth:2}]},
      options:{scales:{r:{min:0,max:100,ticks:{stepSize:20}}},
               plugins:{legend:{display:false}}}
    });
  };

  /* ─────────────────── render() ─────────────────────── */
  const render = snap => {
    if (!diff(state.last,snap)) return;
    for (const k in snap) if (OUT[k]) OUT[k].textContent = fmt(snap[k], snap[k]%1?2:0);
    radar && (radar.data.datasets[0].data=[snap.life,snap.power,snap.finish],radar.update());
    state.last=snap; log('render',snap);
  };

  /* ─────────────────── recalc() ─────────────────────── */
  const recalc = () => {
    const N      = rpm(state.vc);
    const vfRaw  = feed(N,state.fz);
    const vf     = Math.min(vfRaw, FR_MAX);
    const vfDisp = Math.round(vf);   // mismo valor que se muestra en resultados
    const vfRamp = vfDisp / Z;

    /* Si feedrate topa, corregir fz visualmente para reflejar límite */
    if (vfRaw > FR_MAX) state.fz = FR_MAX/(N*Z);

    const apVal  = THK/state.ap;
    const hmVal  = hm(state.fz,state.ae);
    const kcVal  = kc_h(hmVal);
    const mmrVal = mmr(apVal,vf,state.ae);
    const fcVal  = FcT(kcVal,apVal);
    const kWval  = kW(kcVal,apVal,state.ae,vf);
    const hpVal  = HP(kWval);

    /* Radar 0–100 % */
    const lifePct   = Math.min(100,((state.fz-FZ_MIN)/(FZ_MAX-FZ_MIN))*100);
    const powerPct  = Math.min(100,(hpVal/HP_AVAIL)*100);
    const finishPct = Math.max(0,100-lifePct);

    render({
      vc:state.vc, fz:state.fz, hm:hmVal, n:N|0, vf:vf|0, vf_ramp:vfRamp,
      hp:hpVal, mmr:mmrVal, fc:fcVal|0, w:kWval*1000|0,
      eta:Math.min(100,(hpVal/HP_AVAIL)*100)|0,
      ae:state.ae, ap:apVal,
      life:lifePct, power:powerPct, finish:finishPct
    });
  };

  /* ────────────── Slider UI helper ─────────────── */
  const beautify = (slider,dec=3) => {
    if (!slider) return;
    const wrap = slider.closest('.slider-wrap');
    const bub  = wrap?.querySelector('.slider-bubble');
    const min=+slider.min,max=+slider.max;
    const paint=v=>{wrap?.style.setProperty('--val',((v-min)/(max-min))*100);
                    bub&& (bub.textContent=fmt(v,dec));};
    paint(+slider.value); slider.addEventListener('input',e=>{paint(+e.target.value); onInput();});
  };

  /* ─────────────── pasadas sync ─────────────── */
  const syncPass = () => {
    const maxP=Math.max(1,Math.ceil(THK/state.ae));
    SL.pass.max=maxP; SL.pass.min=1; SL.pass.step=1;
    if (+SL.pass.value>maxP) SL.pass.value=maxP;
    state.ap=+SL.pass.value;
    infoPass&&(infoPass.textContent=`${state.ap} pasada${state.ap>1?'s':''} de ${(THK/state.ap).toFixed(2)} mm`);
  };

  /* ─────────────── onInput global ────────────── */
  const onInput = () => {
    state.fz = +SL.fz.value;
    state.vc = +SL.vc.value;
    state.ae = +SL.ae.value;
    syncPass();
    recalc();
  };

  /* ───────────────────── INIT ─────────────────── */
  try {
    /* Limitar VC: ±50 % pero dentro de RPM */
    const vcMin = Math.max(VC0*0.5,(RPM_MIN*Math.PI*D)/1000);
    const vcMax = Math.min(VC0*1.5,(RPM_MAX*Math.PI*D)/1000);
    SL.vc.min=fmt(vcMin,1); SL.vc.max=fmt(vcMax,1); SL.vc.value=fmt(state.vc,1);

    SL.pass.value=1;                // por defecto 1 pasada
    /* Embellece sliders y listeners */
    beautify(SL.fz,4); beautify(SL.vc,1); beautify(SL.ae,2); beautify(SL.pass,0);
    ['change'].forEach(evt=>['fz','vc','ae','pass']
      .forEach(k=>SL[k]&&SL[k].addEventListener(evt,onInput)));

    makeRadar(); syncPass(); recalc();
    log('init OK');
  } catch(e) { error(e); fatal('JS: '+e.message);}
})();
