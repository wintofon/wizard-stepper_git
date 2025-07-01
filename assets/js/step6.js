/* =======================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC
 * Versión con:
 *   • Validación cruzada Vc ↔ RPM ↔ Feedrate
 *   • Alertas visuales automáticas cuando el sistema reajusta Vc o fz
 *   • Comentarios paso a paso
 * =====================================================================*/

(() => {
  'use strict';

  /* ───────────────────────── DEBUG HELPERS ───────────────────────── */
  const DEBUG = window.DEBUG ?? false;
  const TAG   = '[Step6]';
  const say   = (lvl, ...m) => { if (DEBUG) console[lvl](`${TAG}`, ...m); };
  const log   = (...m) => say('log',   ...m);
  const warn  = (...m) => say('warn',  ...m);
  const error = (...m) => say('error', ...m);

  /** Devuelve true si dos snapshots difieren (para evitar renders extra) */
  const diff = (a={}, b={}) => {
    const keys = new Set([...Object.keys(a), ...Object.keys(b)]);
    return [...keys].some(k => a[k] !== b[k]);
  };

  /* ──────────────────────── PARÁMETROS GLOBALES ──────────────────── */
  const P = window.step6Params;
  if (!P) return alert('⚠️ Parámetros técnicos faltantes.');

  const {
    diameter:    D,
    flute_count: Z,
    rpm_min:     RPM_MIN,
    rpm_max:     RPM_MAX,
    fr_max:      FR_MAX,
    coef_seg:    K_SEG_transmission,
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

  const $   = s => document.querySelector(s);
  const fmt = (n,d=1)=>parseFloat(n).toFixed(d);

  /* ───────────────────── ELEMENTOS DEL DOM ──────────────────────── */
  const SL = { fz:$('#sliderFz'), vc:$('#sliderVc'), ae:$('#sliderAe'), pass:$('#sliderPasadas') };
  SL.fz.min = (FZ_MIN*K_SEG_transmission).toFixed(4);
  SL.fz.max = (FZ_MAX*K_SEG_transmission).toFixed(4);

  const OUT = {
    vc:$('#outVc'), fz:$('#outFz'), hm:$('#outHm'), n:$('#outN'),
    vf:$('#outVf'), hp:$('#outHp'), mmr:$('#valueMrr'), fc:$('#valueFc'),
    w:$('#valueW'), eta:$('#valueEta'), ae:$('#outAe'), ap:$('#outAp'),
    vf_ramp:$('#valueRampVf')
  };

  const feedAlert=$('#feedAlert'), rpmAlert=$('#rpmAlert'),
        lenAlert=$('#lenAlert'),  hpAlert=$('#hpAlert');

  /* ────────────────────── STATE INICIAL ─────────────────────────── */
  const state = { fz:+FZ0*K_SEG_transmission, vc:+VC0, ae:D*0.5, ap:1, last:{} };

  /* ──────────────────── ALERTAS BOOTSTRAP ───────────────────────── */
  const showAlert = (el,msg)=>{ el?.classList.remove('d-none'); el?.classList.add('alert','alert-danger'); if(el) el.textContent=msg; };
  const hideAlert = el       =>{ el?.classList.add('d-none');    if(el) el.textContent=''; };

  /* ⚠️ NUEVOS carteles de ajuste automático (uno por slider) */
  function showVcAlert(msg='⚠️ Vc ajustado para respetar límite de RPM') {
    const box=$('#vcAutoAlert'); if(!box) return;
    box.textContent=msg; box.classList.remove('d-none');
    clearTimeout(box._t); box._t=setTimeout(()=>box.classList.add('d-none'),3000);
  }
  function showFzAlert(msg='⚠️ fz ajustado para respetar límite de feedrate') {
    const box=$('#fzAutoAlert'); if(!box) return;
    box.textContent=msg; box.classList.remove('d-none');
    clearTimeout(box._t); box._t=setTimeout(()=>box.classList.add('d-none'),3000);
  }

  /* ───────────────────── VALIDACIONES BÁSICAS ───────────────────── */
  const validateFeed   = vf => vf<FR_MAX ? hideAlert(feedAlert) : showAlert(feedAlert,`Feedrate > ${FR_MAX}`);
  const validateRpm    = n  => (RPM_MIN<=n&&n<=RPM_MAX)?hideAlert(rpmAlert):showAlert(rpmAlert,'RPM fuera de rango');
  const validateLength = () => THK<=CUT_LEN ? hideAlert(lenAlert):showAlert(lenAlert,'Espesor > largo de filo');
  const validateHP     = p  => p<80? hideAlert(hpAlert):showAlert(hpAlert,'⚠️ Potencia >80 %');

  /* ────────────────────── FORMULAS DE CORTE ────────────────────── */
  const rpmCalc = vc=> (vc*1000)/(Math.PI*D);
  const feedCalc=(n,fz)=> n*fz*Z;
  const hmCalc  =(fz,ae)=>{const φ=2*Math.asin(Math.min(1,ae/D));return φ?fz*(1-Math.cos(φ))/φ:fz;};
  const mmrCalc =(ap,vf,ae)=> (ap*ae*vf)/1000;
  const kc_h    =hm=> KC*Math.pow(hm,-MC);
  const FcT     =(kc,ap)=> kc*ap*Z*(1+K_SEG_transmission*Math.tan(0));
  const kWCalc  =(kc,ap,ae,vf)=> (ap*ae*vf*kc)/(60e6*ETA);
  const hpCalc  =w=> w*1.341;

  /* ────────────── BEAUTIFY SLIDERS (burbuja + color) ───────────── */
  function beautify(slider,dec){
    if(!slider) return;
    const wrap=slider.closest('.slider-wrap');
    const bub =wrap?.querySelector('.slider-bubble');
    const [min,max]=[+slider.min,+slider.max];
    const paint=v=>{
      wrap.style.setProperty('--val',((v-min)/(max-min))*100);
      bub&&(bub.textContent=fmt(v,dec));
      v<=min||v>=max?bub?.classList.add('limit'):bub?.classList.remove('limit');
    };
    paint(+slider.value);
    slider.addEventListener('input',e=>{paint(+e.target.value);onInput();});
  }

  /* ─────────────── SINCRONIZAR SLIDER “Pasadas” ──────────────── */
  function syncPass(){
    const minP=Math.ceil(THK/CUT_LEN)||1;
    const maxP=Math.max(1,Math.ceil(THK/state.ae));
    SL.pass.min=minP; SL.pass.max=maxP; SL.pass.step=1;
    let p=+SL.pass.value; if(p<minP)p=minP; if(p>maxP)p=maxP;
    SL.pass.value=p; state.ap=p;
    $('#textPasadasInfo').textContent=`${p} pasada${p>1?'s':''} de ${(THK/p).toFixed(2)} mm`;
  }

  /* ───────────────────────── RECALC ─────────────────────────── */
  function recalc(){
    log('🧮 Recalc');

    /* 1) RPM a partir de Vc */
    let N=rpmCalc(state.vc);

    /* 1-A) Si RPM fuera de rango → ajustar Vc y salir */
    if(N>RPM_MAX||N<RPM_MIN){
      const newVc=((N>RPM_MAX?RPM_MAX:RPM_MIN)*Math.PI*D)/1000;
      log(`⚠️ RPM ${fmt(N,0)} fuera de rango ⇒ Vc→${fmt(newVc,1)}`);
      state.vc=newVc;
      SL.vc.value=fmt(newVc,1);
      SL.vc.closest('.slider-wrap')?.querySelector('.slider-bubble').textContent=fmt(newVc,1);
      showVcAlert(`⚠️ RPM ${fmt(N,0)} fuera de rango → Vc ajustado a ${fmt(newVc,1)} m/min`);
      return recalc();                 // rebote con el nuevo Vc
    }

    /* 2) Feedrate a partir de fz */
    const vfRaw=feedCalc(N,state.fz);
    let vf=Math.min(vfRaw,FR_MAX);

    /* 2-A) Si feedrate se pasa → ajustar fz */
    if(vfRaw>=FR_MAX){
      const newFz=FR_MAX/(N*Z);
      log(`⚠️ Feedrate ${fmt(vfRaw,0)} > ${FR_MAX} ⇒ fz→${fmt(newFz,4)}`);
      state.fz=newFz;
      SL.fz.value=fmt(newFz,4);
      SL.fz.closest('.slider-wrap')?.querySelector('.slider-bubble').textContent=fmt(newFz,4);
      showFzAlert(`⚠️ Feedrate ${fmt(vfRaw,0)} mm/min excedido → fz ${fmt(newFz,4)} mm/diente`);
      vf=FR_MAX;
    }

    /* 3) Profundidad de pasada (ap) */
    const apVal=THK/state.ap;

    /* 4) Validaciones visuales */
    validateFeed(vf); validateRpm(N); validateLength();
    const pctHp=hpCalc(kWCalc(kc_h(hmCalc(state.fz,state.ae)),apVal,state.ae,vf))/(HP_AVAIL||1)*100;
    validateHP(pctHp);

    /* 5) Cálculos finales */
    const hmVal=hmCalc(state.fz,state.ae);
    const kcVal=kc_h(hmVal);
    const wVal =kWCalc(kcVal,apVal,state.ae,vf);
    const hpVal=hpCalc(wVal);
    const mmrVal=mmrCalc(apVal,vf,state.ae);
    const fcVal =FcT(kcVal,apVal);

    /* 6) Actualizar UI + radar */
    render({
      vc:state.vc,n:N|0,vf:vf|0,
      vf_ramp:Math.round(vf*Math.cos(ANGLE_RAMP*Math.PI/180)/Z),
      ae:state.ae,ap:apVal,hm:hmVal,
      mmr:Math.round(mmrVal),fc:fcVal|0,hp:hpVal.toFixed(1),
      w:(wVal*1000)|0,eta:Math.min(100,pctHp)|0,
      life:Math.min(100,((state.fz-FZ_MIN)/(FZ_MAX-FZ_MIN))*100),
      power:pctHp,finish:Math.max(0,100-Math.min(100,((state.fz-FZ_MIN)/(FZ_MAX-FZ_MIN))*100)),
      fz:state.fz
    });
  }

  /* ─────────────── HANDLER GENERAL de sliders ─────────────── */
  function onInput(){
    state.fz=(+SL.fz.value)*K_SEG_transmission;
    state.vc=+SL.vc.value;
    const maxAe=Math.floor(D);
    state.ae=Math.min(+SL.ae.value,maxAe); SL.ae.value=state.ae;
    syncPass(); recalc();
  }

  /* ───────────────────────── INIT ─────────────────────────── */
  let radar;
  const makeRadar=()=>{
    const ctx=$('#radarChart')?.getContext('2d');
    if(!ctx||!window.Chart){warn('Chart.js no cargó');return;}
    radar=new Chart(ctx,{type:'radar',
      data:{labels:['Vida Útil','Potencia','Terminación'],
        datasets:[{data:[0,0,0],fill:true,borderWidth:2,
          backgroundColor:'rgba(76,175,80,.2)',borderColor:'#4caf50'}]},
      options:{scales:{r:{min:0,max:100,ticks:{stepSize:20}}},
               plugins:{legend:{display:false}}}});
  };

  try{
    /* Limites iniciales de Vc y ae */
    SL.vc.min=fmt(VC0*0.5,1); SL.vc.max=fmt(VC0*1.5,1); SL.vc.value=fmt(state.vc,1);
    SL.ae.min='0.1'; SL.ae.max=fmt(Math.floor(D),1); SL.ae.value=fmt(state.ae,1);
    SL.pass.value='1';

    /* Beautify sliders */
    beautify(SL.fz,4); beautify(SL.vc,1); beautify(SL.ae,1); beautify(SL.pass,0);

    /* Listeners */
    ['input','change'].forEach(evt=>{
      ['fz','vc','ae','pass'].forEach(k=>SL[k]?.addEventListener(evt,onInput));
    });

    /* Radar + primera pasada */
    makeRadar(); syncPass(); recalc(); log('Init completo');
  }catch(e){ error(e); alert('Error JS: '+e.message); }
})();
