/* =======================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC (radar + alertas dinámicas)
 *  ▸ Ajuste mutuo Vc ↔ RPM y fz ↔ Feedrate
 *  ▸ Alertas visuales cuando el sistema corrige Vc o fz
 *  ▸ Sin “Invalid left-hand side in assignment”  (NO usamos ?.prop = …)
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
  const diff  = (a={},b={}) => {
    const k = new Set([...Object.keys(a), ...Object.keys(b)]);
    return [...k].some(i => a[i] !== b[i]);
  };

  /* ───────────────────── PARÁMETROS DESDE PHP ───────────────────── */
  const P = window.step6Params;
  if (!P) return alert('⚠️ Parámetros técnicos faltantes.');

  const {
    diameter:D, flute_count:Z,
    rpm_min:RPM_MIN, rpm_max:RPM_MAX,
    fr_max:FR_MAX, coef_seg:K_SEG_transmission,
    Kc11:KC, mc:MC, eta:ETA,
    fz0:FZ0, vc0:VC0,
    thickness:THK, cut_length:CUT_LEN,
    hp_avail:HP_AVAIL,
    fz_min0:FZ_MIN, fz_max0:FZ_MAX,
    angle_ramp:ANGLE_RAMP
  } = P;

  const $   = s => document.querySelector(s);
  const fmt = (n,d=1)=>parseFloat(n).toFixed(d);

  /* ─────────────────── Elementos del DOM ─────────────────── */
  const SL = {
    fz:   $('#sliderFz'),
    vc:   $('#sliderVc'),
    ae:   $('#sliderAe'),
    pass: $('#sliderPasadas')
  };
  SL.fz.min = (FZ_MIN * K_SEG_transmission).toFixed(4);
  SL.fz.max = (FZ_MAX * K_SEG_transmission).toFixed(4);

  const OUT = {
    vc:$('#outVc'), fz:$('#outFz'), hm:$('#outHm'), n:$('#outN'),
    vf:$('#outVf'), hp:$('#outHp'), mmr:$('#valueMrr'), fc:$('#valueFc'),
    w:$('#valueW'), eta:$('#valueEta'), ae:$('#outAe'), ap:$('#outAp'),
    vf_ramp:$('#valueRampVf')
  };

  /* ─────────── Alertas existentes (rojas) ─────────── */
  const feedAlert=$('#feedAlert'), rpmAlert=$('#rpmAlert'),
        lenAlert=$('#lenAlert'),  hpAlert=$('#hpAlert');

  /* ─────────── Estado interno ─────────── */
  const state = {
    fz : +FZ0 * K_SEG_transmission,
    vc : +VC0,
    ae : D * 0.5,
    ap : 1,
    last:{}
  };

  /* ─────────── Alert helpers (rojas) ─────────── */
  const showAlert = (el,msg)=>{ el?.classList.remove('d-none'); el?.classList.add('alert','alert-danger'); if(el) el.textContent=msg; };
  const hideAlert = el       =>{ el?.classList.add('d-none');    if(el) el.textContent=''; };

  /* ─────────── NUEVAS alertas amarillas (ajustes automáticos) ─────────── */
  function showVcAlert(msg='⚠️ Vc ajustado para respetar límite de RPM'){
    const box=$('#vcAutoAlert'); if(!box) return;
    box.textContent=msg; box.classList.remove('d-none');
    clearTimeout(box._t); box._t=setTimeout(()=>box.classList.add('d-none'),3000);
  }
  function showFzAlert(msg='⚠️ fz ajustado para respetar límite de feedrate'){
    const box=$('#fzAutoAlert'); if(!box) return;
    box.textContent=msg; box.classList.remove('d-none');
    clearTimeout(box._t); box._t=setTimeout(()=>box.classList.add('d-none'),3000);
  }

  /* ─────────── Validaciones básicas ─────────── */
  const validateFeed   = v=> v<FR_MAX ? hideAlert(feedAlert):showAlert(feedAlert,`Feedrate > ${FR_MAX}`);
  const validateRpm    = n=> (RPM_MIN<=n&&n<=RPM_MAX)?hideAlert(rpmAlert):showAlert(rpmAlert,'RPM fuera de rango');
  const validateLength = ()=> THK<=CUT_LEN ? hideAlert(lenAlert):showAlert(lenAlert,'Espesor > largo de filo');
  const validateHP     = p=> p<80?hideAlert(hpAlert):showAlert(hpAlert,'⚠️ Potencia >80 %');

  /* ─────────── Fórmulas de corte ─────────── */
  const rpmCalc = vc=> (vc*1000)/(Math.PI*D);
  const feedCalc=(n,fz)=> n*fz*Z;
  const hmCalc  =(fz,ae)=>{const φ=2*Math.asin(Math.min(1,ae/D)); return φ?fz*(1-Math.cos(φ))/φ:fz;};
  const mmrCalc =(ap,vf,ae)=> (ap*ae*vf)/1000;
  const kc_h    = hm=> KC*Math.pow(hm,-MC);
  const FcT     =(kc,ap)=> kc*ap*Z*(1+K_SEG_transmission*Math.tan(0));
  const kWCalc  =(kc,ap,ae,vf)=> (ap*ae*vf*kc)/(60e6*ETA);
  const hpCalc  = w=> w*1.341;

  /* ─────────── Decoración sliders ─────────── */
  function beautify(slider,dec){
    if(!slider) return;
    const wrap = slider.closest('.slider-wrap');
    const bub  = wrap?.querySelector('.slider-bubble');
    const [min,max]=[+slider.min,+slider.max];
    const paint=v=>{
      wrap.style.setProperty('--val',((v-min)/(max-min))*100);
      if(bub) bub.textContent = fmt(v,dec);
      (v<=min||v>=max)?bub?.classList.add('limit'):bub?.classList.remove('limit');
    };
    paint(+slider.value);
    slider.addEventListener('input',e=>{paint(+e.target.value);onInput();});
  }

  /* ─────────── Sincronizar “Pasadas” ─────────── */
  function syncPass(){
    const minP=Math.ceil(THK/CUT_LEN)||1;
    const maxP=Math.max(1,Math.ceil(THK/state.ae));
    SL.pass.min=minP; SL.pass.max=maxP; SL.pass.step=1;
    let p=+SL.pass.value; if(p<minP)p=minP; if(p>maxP)p=maxP;
    SL.pass.value=p; state.ap=p;
    $('#textPasadasInfo').textContent=`${p} pasada${p>1?'s':''} de ${(THK/p).toFixed(2)} mm`;
  }

  /* ─────────── Render de resultados y radar ─────────── */
  let radar;
  const render = snap=>{
    if(!diff(state.last,snap)) return;
    for(const k in snap) if(OUT[k]) OUT[k].textContent = fmt(snap[k], snap[k]%1?2:0);
    if(radar){
      radar.data.datasets[0].data=[snap.life,snap.power,snap.finish];
      radar.data.datasets[0].backgroundColor = snap.power<50  ?'rgba(76,175,80,.2)'
                                            : snap.power<80  ?'rgba(255,152,0,.2)'
                                                             :'rgba(244,67,54,.2)';
      radar.update();
    }
    state.last = snap;
  };

  /* ─────────── Lógica principal de recálculo ─────────── */
  function recalc(){
    log('🧮 Recalc');
    /* 1) RPM */
    let N = rpmCalc(state.vc);

    /* 1-A) Vc se ajusta si RPM fuera de rango */
    if(N>RPM_MAX || N<RPM_MIN){
      const lim   = N>RPM_MAX ? RPM_MAX : RPM_MIN;
      const newVc = (lim * Math.PI * D) / 1000;
      state.vc    = newVc;
      SL.vc.value = fmt(newVc,1);

      // actualizo burbuja
      const wrap = SL.vc.closest('.slider-wrap');
      const bub  = wrap?.querySelector('.slider-bubble');
      if(bub) bub.textContent = fmt(newVc,1);

      // pinto la pista del slider para que el thumb se mueva al nuevo valor
      if(wrap){
        const minVc = +SL.vc.min, maxVc = +SL.vc.max;
        wrap.style.setProperty('--val', ((newVc - minVc) / (maxVc - minVc)) * 100);
      }

      showVcAlert(`⚠️ RPM ${fmt(N,0)} fuera de rango → Vc ${fmt(newVc,1)} m/min`);
      return recalc();  // rebote con Vc corregido
    }

    /* 2) Feedrate */
    const vfRaw = feedCalc(N,state.fz);
    let vf = Math.min(vfRaw, FR_MAX);

    /* 2-A) fz se ajusta si feedrate fuera de rango */
    if(vfRaw >= FR_MAX){
      const newFz = FR_MAX / (N * Z);
      state.fz = newFz;
      SL.fz.value = fmt(newFz,4);
      const bub = SL.fz.closest('.slider-wrap')?.querySelector('.slider-bubble');
      if(bub) bub.textContent = fmt(newFz,4);
      showFzAlert(`⚠️ Feedrate ${fmt(vfRaw,0)} > ${FR_MAX} → fz ${fmt(newFz,4)} mm/diente`);
      vf = FR_MAX;
    }

    /* 3) ap, validaciones y cálculos */
    const apVal = THK / state.ap;
    validateFeed(vf); validateRpm(N); validateLength();
    const hmVal = hmCalc(state.fz,state.ae);
    const kcVal = kc_h(hmVal);
    const wVal  = kWCalc(kcVal,apVal,state.ae,vf);
    const hpVal = hpCalc(wVal);
    validateHP( (hpVal/(HP_AVAIL||1))*100 );

    /* 4) Render */
    render({
      vc:state.vc, n:N|0, vf:vf|0,
      vf_ramp:Math.round(vf*Math.cos(ANGLE_RAMP*Math.PI/180)/Z),
      ae:state.ae, ap:apVal, hm:hmVal,
      mmr:Math.round(mmrCalc(apVal,vf,state.ae)),
      fc:FcT(kcVal,apVal)|0,
      hp:hpVal.toFixed(1), w:(wVal*1000)|0,
      eta:Math.min(100,(hpVal/(HP_AVAIL||1))*100)|0,
      life:Math.min(100,((state.fz-FZ_MIN)/(FZ_MAX-FZ_MIN))*100),
      power:(hpVal/(HP_AVAIL||1))*100,
      finish:Math.max(0,100-Math.min(100,((state.fz-FZ_MIN)/(FZ_MAX-FZ_MIN))*100)),
      fz:state.fz
    });
  }

  /* ─────────── Handler genérico de sliders ─────────── */
  const onInput = ()=>{
    state.fz = (+SL.fz.value)*K_SEG_transmission;
    state.vc = +SL.vc.value;
    const maxAe = Math.floor(D);
    state.ae = Math.min(+SL.ae.value,maxAe); SL.ae.value = state.ae;
    syncPass(); recalc();
  };

  /* ─────────── Radar init ─────────── */
  const makeRadar =()=>{
    const ctx=$('#radarChart')?.getContext('2d');
    if(!ctx||!window.Chart){ warn('Chart.js no cargó'); return; }
    radar=new Chart(ctx,{
      type:'radar',
      data:{ labels:['Vida Útil','Potencia','Terminación'],
             datasets:[{data:[0,0,0],fill:true,borderWidth:2,
               backgroundColor:'rgba(76,175,80,.2)',borderColor:'#4caf50'}] },
      options:{scales:{r:{min:0,max:100,ticks:{stepSize:20}}}, plugins:{legend:{display:false}}}
    });
  };

  /* ─────────────────────── INIT ─────────────────────── */
  try{
    /* Límites iniciales de sliders */
    SL.vc.min = fmt(VC0*0.5,1);
    SL.vc.max = fmt(VC0*1.5,1);
    SL.vc.value = fmt(state.vc,1);
    SL.ae.min = '0.1';
    SL.ae.max = fmt(Math.floor(D),1);
    SL.ae.value = fmt(state.ae,1);
    SL.pass.value = '1';

    /* Mostrar límites numéricos de los sliders */
    const vcWrap = SL.vc.closest('.mb-4');
    if(vcWrap){
      vcWrap.querySelector('span:nth-child(1)')?.textContent = fmt(VC0*0.5,1);
      vcWrap.querySelector('#valVc')?.textContent      = fmt(VC0,1);
      vcWrap.querySelector('span:last-child')?.textContent = fmt(VC0*1.5,1);
    }
    const fzWrap = SL.fz.closest('.mb-4');
    if(fzWrap){
      fzWrap.querySelector('span:nth-child(1)')?.textContent    = fmt(FZ_MIN*K_SEG_transmission,4);
      fzWrap.querySelector('#valFz')?.textContent               = fmt(FZ0*K_SEG_transmission,4);
      fzWrap.querySelector('span:last-child')?.textContent     = fmt(FZ_MAX*K_SEG_transmission,4);
    }

    /* Beautify y listeners */
    beautify(SL.fz,4);
    beautify(SL.vc,1);
    beautify(SL.ae,1);
    beautify(SL.pass,0);
    ['input','change'].forEach(evt=>{
      ['fz','vc','ae','pass'].forEach(k=>SL[k]?.addEventListener(evt,onInput));
    });

    makeRadar();
    syncPass();
    recalc();
    log('Init completo');
  }catch(e){
    error(e);
    alert('Error JS: '+e.message);
  }
})();
