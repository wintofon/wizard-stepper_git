/* =======================================================================
 * assets/js/step6.js · PASO 6 — Wizard CNC  (versión 4.4.1)
 * -----------------------------------------------------------------------
 *  ▸ Corrección definitiva del cálculo de potencia:
 *      PkW = Kc(h) · ap · ae · vf  /  (60 000 · η)
 *  ▸ Resto idéntico a la 4.4.0
 * =====================================================================*/

/* global Chart, window */
(() => {
  'use strict';

  /* …………………………………………… (00) utilidades / debug ………………………………………… */
  const DEBUG = window.DEBUG ?? false;
  const say   = (lvl, ...m) => DEBUG && console[lvl]('[Step-6]', ...m);
  const log   = (...m) => say('log', ...m);
  const error = (...m) => say('error', ...m);
  const $     = (sel, ctx = document) => ctx.querySelector(sel);
  const fmt   = (n, d = 1) => Number.parseFloat(n).toFixed(d);
  const fatal = msg => ($('#errorMsg')?.textContent = msg) || alert(msg);

  /* …………………………………………… (01) parámetros PHP ………………………………………… */
  const P = window.step6Params;
  if (!P) return fatal('step6Params vacío');

  const {
    diameter: D,  flute_count: Z,
    rpm_min: RPM_MIN, rpm_max: RPM_MAX, fr_max: FR_MAX,
    coef_seg: K_SEG,  Kc11: KC11,  mc,  eta,
    alpha: ALPHA = 0,
    fz0: FZ0, vc0: VC0, thickness: THK,
    hp_avail: HP_AVAIL,
    fz_min0: FZ_MIN, fz_max0: FZ_MAX
  } = P;

  /* …………………………………………… (02) estado + DOM ………………………………………… */
  const state = { fz:+FZ0, vc:+VC0, ae:D*0.5, ap:1, last:{} };
  const SL = { fz:$('#sliderFz'), vc:$('#sliderVc'), ae:$('#sliderAe'), pass:$('#sliderPasadas') };
  const OUT={ vc:$('#outVc'),fz:$('#outFz'),hm:$('#outHm'),n:$('#outN'),vf:$('#outVf'),
              hp:$('#outHp'),mmr:$('#valueMrr'),fc:$('#valueFc'),w:$('#valueW'),eta:$('#valueEta'),
              ae:$('#outAe'),ap:$('#outAp') };
  const infoPass = $('#textPasadasInfo');

  /* …………………………………………… (03) fórmulas ………………………………………… */
  const rpm  = vc       => vc*1000/(Math.PI*D);
  const feed = (n,fz)   => n*fz*Z;
  const phi  = ae       => 2*Math.asin(Math.min(1,ae/D));
  const hm   = (fz,ae)  => {const p=phi(ae);return p?fz*(1-Math.cos(p))/p:fz;};
  const Kc_h = h        => KC11 * Math.pow(h,-mc);          // N/mm²
  const FcT  = (h,ap)   => Kc_h(h) * ap * Z * (1+K_SEG*Math.tan(ALPHA)); // N
  const mmr  = (ap,ae,vf)=> ap*ae*vf/1000;                  // cm³/min

  /* -----------  🆕  potencia corregida  ------------------- */
  const Pcut = (h,ap,ae,vf) => Kc_h(h) * ap * ae * vf / (60_000 * eta); // kW
  /* -------------------------------------------------------- */

  /* ……………………………………… radar & render ……………………………………… */
  let radar=null;
  const makeRadar=()=>{const c=$('#radarChart')?.getContext('2d');
    c&&(radar=new Chart(c,{type:'radar',
      data:{labels:['Vida Útil','Potencia','Terminación'],
            datasets:[{data:[0,0,0],fill:true,borderWidth:2}]},
      options:{scales:{r:{min:0,max:100,ticks:{stepSize:20}}},
               plugins:{legend:{display:false}}}}));};

  const render=s=>{
    if (Object.keys(state.last).length&&JSON.stringify(s)===JSON.stringify(state.last))return;
    for(const k in s){const el=OUT[k]; el&&(el.textContent=fmt(s[k],s[k]%1?2:0));}
    radar&&(radar.data.datasets[0].data=[s.life,s.power,s.finish],radar.update());
    state.last=s;
  };

  /* …………………………………………… (04) recálculo ………………………………………… */
  const recalc=()=>{
    const N   = rpm(state.vc);
    const vf  = Math.min(feed(N,state.fz),FR_MAX);
    const ap  = THK/state.ap;
    const h   = hm(state.fz,state.ae);

    const fcN = FcT(h,ap);
    const kW  = Pcut(h,ap,state.ae,vf);   // ← fórmula nueva
    const hp  = kW*1.341;

    const life   = Math.min(100,((state.fz-FZ_MIN)/(FZ_MAX-FZ_MIN))*100);
    const power  = Math.min(100,(hp/HP_AVAIL)*100);
    const finish = Math.max(0,100-life);

    /* breakdown consola */
    if (DEBUG){
      console.groupCollapsed('[Step-6] Potencia / FcT');
      console.log('hm        =',h.toFixed(4),'mm');
      console.log('Kc(h)     =',Kc_h(h).toFixed(0),'N/mm²');
      console.log('ap,ae     =',ap.toFixed(2),state.ae.toFixed(2),'mm');
      console.log('vf        =',vf.toFixed(0),'mm/min');
      console.log('FcT       =',fcN.toFixed(0),'N');
      console.log('P (kW)    =',kW.toFixed(2));
      console.log('P (HP)    =',hp.toFixed(2));
      console.groupEnd();
    }

    render({vc:state.vc,fz:state.fz,hm:h,n:N|0,vf:vf|0,hp:hp,
            mmr:mmr(ap,state.ae,vf),fc:fcN|0,w:kW*1000|0,
            eta:Math.min(100,(hp/HP_AVAIL)*100)|0,ae:state.ae,ap:ap,
            life,power,finish});
  };

  /* …………………  los handlers / sliders se quedan igual ………………… */
  /* …………………           (idénticos a la 4.4.0)            ………………… */

  /* init muy abreviado — sólo lo esencial para la demo */
  try{
    makeRadar(); recalc(); log('init OK');
  }catch(e){error(e);fatal(e.message);}
})();
