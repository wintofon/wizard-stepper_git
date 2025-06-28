/* ======================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC  · build 2025-06-28 (bug-fix)
 * ----------------------------------------------------------------------
 *  · Ver README al inicio del archivo anterior para el resto de detalles
 * ====================================================================*/

/* global Chart, window */
(() => {
  'use strict';

  /* 1 ─── DEBUG helpers ─────────────────────────────────────────────── */
  const DEBUG = window.DEBUG ?? false;
  const TAG   = '[Step-6]';
  const log   = (...m) => DEBUG && console.log(TAG, ...m);
  const warn  = (...m) => DEBUG && console.warn(TAG, ...m);
  const fail  = (...m) => console.error(TAG, ...m);

  const $   = (s,c=document)=>c.querySelector(s);
  const fmt = (v,d=1)=>Number.parseFloat(v).toFixed(d);

  /* 2 ─── Params inyectados por PHP ─────────────────────────────────── */
  const P = window.step6Params;
  if (!P){ fail('step6Params vacío'); return; }

  const {
    diameter:D, flute_count:Z, rpm_min:RPM_MIN, rpm_max:RPM_MAX,
    fr_max:FR_MAX, coef_seg:K_SEG, Kc11:KC11, mc, eta:ETA,
    alpha:ALPHA=0, fz0:FZ0, vc0:VC0, thickness:THK,
    fz_min0:FZ_MIN, fz_max0:FZ_MAX, hp_avail:HP_AVAIL
  } = P;

  /* 3 ─── DOM refs ──────────────────────────────────────────────────── */
  const SL={fz:$('#sliderFz'),vc:$('#sliderVc'),ae:$('#sliderAe'),pas:$('#sliderPasadas')};
  const OUT={
    vc:$('#outVc'),fz:$('#outFz'),hm:$('#outHm'),n:$('#outN'),vf:$('#outVf'),
    hp:$('#outHp'),mmr:$('#valueMrr'),fc:$('#valueFc'),w:$('#valueW'),eta:$('#valueEta'),
    ae:$('#outAe'),ap:$('#outAp')
  };
  const infoPas=$('#textPasadasInfo'), errBox=$('#errorMsg');

  /* 4 ─── Modelos de cálculo ───────────────────────────────────────── */
  const rpm  = vc       => vc*1000/(Math.PI*D);
  const feed = (n,fz)   => n*fz*Z;
  const phi  = ae       => 2*Math.asin(Math.min(1,ae/D));
  const hm   = (fz,ae)  => {const p=phi(ae);return p?fz*(1-Math.cos(p))/p:fz;};
  const Kc_h = h        => KC11*Math.pow(h,-mc);          // N/mm²
  const FcT  = (h,ap)   => Kc_h(h)*ap*Z*(1+K_SEG*Math.tan(ALPHA));   // N
  /* -----------  Potencia CORREGIDA  --------------------
     W = Kc(h) · ap · ae · vf / (60 000 · η)               */
  const Pw   = (h,ap,ae,vf)=>Kc_h(h)*ap*ae*vf/(60_000*ETA);// W
  const mmr  = (ap,ae,vf)=>ap*ae*vf/1000;                 // cm³/min

  /* 5 ─── Estado ------------------------------------------------------------------------------------------------ */
  const st={fz:+FZ0,vc:+VC0,ae:D*0.5,pas:1};

  /* 6 ─── Radar ------------------------------------------------------------------------------------------------- */
  let radar;
  const makeRadar=()=>{
    const ctx=$('#radarChart')?.getContext('2d');
    if(!ctx||!Chart) return;
    radar=new Chart(ctx,{type:'radar',
      data:{labels:['Vida Útil','Potencia','Terminación'],
            datasets:[{data:[0,0,0],fill:true,borderWidth:2}]},
      options:{scales:{r:{min:0,max:100,ticks:{stepSize:20}}},
               plugins:{legend:{display:false}}}});
  };

  /* 7 ─── Slider embellish (burbuja) --------------------------------------------------------------------------- */
  const prettify=(sl,d=2)=>{
    if(!sl) return;
    const wrap=sl.closest('.slider-wrap'), bub=wrap?.querySelector('.slider-bubble');
    const min=+sl.min,max=+sl.max;
    const upd=v=>{wrap?.style.setProperty('--val',((v-min)*100)/(max-min));
                  bub&&(bub.textContent=fmt(v,d));};
    upd(+sl.value); sl.addEventListener('input',e=>upd(+e.target.value));
  };

  /* 8 ─── Pasadas slider sync ---------------------------------------------------------------------------------- */
  const syncPas=()=>{
    const maxP=Math.max(1,Math.ceil(THK/st.ae));
    SL.pas.max=maxP; if(+SL.pas.value>maxP) SL.pas.value=maxP;
    st.pas=+SL.pas.value;
    infoPas.textContent=`${st.pas} pasada${st.pas>1?'s':''} de ${(THK/st.pas).toFixed(2)} mm`;
  };

  /* 9 ─── Render OUT & radar ----------------------------------------------------------------------------------- */
  const render=s=>{
    for(const k in s) OUT[k]&&(OUT[k].textContent=fmt(s[k],s[k]%1?2:0));
    radar&&(radar.data.datasets[0].data=[s.life,s.power,s.finish], radar.update());
  };

  /* 10 ─── Recalc principal ------------------------------------------------------------------------------------ */
  const recalc=()=>{
    const N=rpm(st.vc);
    const vf=Math.min(feed(N,st.fz),FR_MAX);
    const ap=THK/st.pas;
    const h=hm(st.fz,st.ae);
    /*  bloqueos dinámicos: si feed > FR_MAX reduce fz */
    if(feed(N,st.fz)>FR_MAX){ SL.fz.max=fmt(st.fz,4); } else SL.fz.max=fmt(FZ_MAX,4);

    const Fc = FcT(h,ap);
    const W  = Pw(h,ap,st.ae,vf);      // vatios corregido
    const kW = W/1000;
    const HP = kW*1.341;

    const life   = Math.min(100,((st.fz-FZ_MIN)/(FZ_MAX-FZ_MIN))*100);
    const power  = Math.min(100,(HP/HP_AVAIL)*100);
    const finish = 100-life;

    render({vc:st.vc,fz:st.fz,hm:h,n:Math.round(N),vf:Math.round(vf),hp:HP,
            mmr:mmr(ap,st.ae,vf),fc:Fc,w:W,eta:Math.min(100,(HP/HP_AVAIL)*100),
            ae:st.ae,ap,life,power,finish});

    DEBUG&&console.groupCollapsed(TAG,'debug'),console.log(
      {hm:h,Kc:Kc_h(h),Fc,Fx_kW:kW,W,HP}),console.groupEnd();
  };

  /* 11 ─── common input handler ------------------------------------------------------------------------------- */
  const onInput=()=>{
    st.fz=+SL.fz.value; st.vc=+SL.vc.value; st.ae=+SL.ae.value; syncPas(); recalc();
  };

  /* 12 ─── INIT ------------------------------------------------------------------------------------------------ */
  try{
    /* Vc slider bounds */
    const VcMin=Math.max(VC0*0.5, RPM_MIN*Math.PI*D/1000);
    const VcMax=Math.min(VC0*1.5, RPM_MAX*Math.PI*D/1000);
    SL.vc.min=fmt(VcMin,1); SL.vc.max=fmt(VcMax,1); SL.vc.value=fmt(st.vc,1);

    SL.pas.value=1;

    [ [SL.fz,4],[SL.vc,1],[SL.ae,2],[SL.pas,0] ].forEach(([s,d])=>prettify(s,d));
    ['fz','vc','ae','pas'].forEach(k=>SL[k]?.addEventListener('input',onInput));

    makeRadar(); syncPas(); recalc(); log('init OK');
  }catch(e){fail(e);errBox&&(errBox.textContent=e.message);}
})();
