/* =====================================================================
 * assets/js/step6.js ·  PASO 6 — Wizard CNC  (Versión CLIENT · Fórmulas 1 : 1)
 * ---------------------------------------------------------------------
 *  ➤ Réplica exacta de los cálculos del antiguo endpoint PHP:
 *      · hm  (Hon & De Vries)          hm = fz·(1–cosφ)/φ
 *      · MMR = ap·feed·ae / 1000       (→ cm³/min, igual que backend)
 *      · Fct = Kc11·hm^(–mc)·ap·fz·Z·(1+coef_seg·tanα)
 *      · kW  = Fct·Vc / (60 000·η)     →   W, HP
 *  ➤ Cero llamadas AJAX.  Retry DOM (10×/120 ms) si sliders tardan.
 * ====================================================================*/

/* global Chart, CountUp, window */

/*************************** 1 · PARAMS *********************************/
const P = window.step6Params || {};
const {
  diameter      : D           = 1,      // mm
  flute_count   : Z           = 1,
  thickness     : THK         = 10,     // mm
  feed_max      : FR_MAX      = 6000,   // mm/min
  hp_avail      : HP_AVAIL    = 1.5,    // HP disponibles
  coef_seg      : K_SEG       = 1.0,    // coef. seguridad
  Kc11          : KC          = 1500,   // N/mm²
  mc                        = 0.2,    // exponente
  rack_rad      : ALPHA       = 0.0,    // rad
  eta                        = 1.0,    // eficiencia (1 = 100 %)
  rpm_min                    = 1000,
  rpm_max                    = 24000,
} = P;

/*************************** 2 · STATE **********************************/
const state = {
  fz : +P.fz0      || 0.02,                 // mm/diente
  vc : +P.vc0      || 100,                  // m/min
  ae : (+P.diameter_mm || D) * 0.5,         // mm (50 % por defecto)
  ap : +P.ap_slot  || 1,                    // nº pasadas (1 = slot)
  chart: null, counters:{}
};

/*********************** 3 · HELPERS DOM & MATH *************************/
const $ = (sel, ctx=document)=>ctx.querySelector(sel);
const fmt = (n,d=1)=>Number.parseFloat(n).toFixed(d);
const setTxt=(el,v,d=1)=>{ if(el) el.textContent=fmt(v,d); };

// Fórmulas backend 1:1
const rpm   = (vc)=> (vc*1000)/(Math.PI*D);                          // rev/min
const feed  = (rpm,fz)=> rpm*fz*Z;                                   // mm/min
const phi   = (ae)=> 2*Math.asin(Math.min(1, ae/D));                 // rad
const hm    = (fz,ae)=>{ const p=phi(ae); return p!==0? fz*(1-Math.cos(p))/p : fz; };
const mmr   = (ap,vf,ae)=> (ap*vf*ae)/1000;                          // cm³/min (÷1000)
const Fct   = (hm,ap,fz)=> KC*Math.pow(hm,-mc)*ap*fz*Z*(1+K_SEG*Math.tan(ALPHA));
const kW    = (F,Vc)=> (F*Vc)/(60000*eta);
const hp    = (kW)=> kW*1.341;

/************************ 4 · RADAR CHART *******************************/
function makeRadar(arr){
  const ctx=$('#radarChart'); if(!ctx||!Chart) return;
  state.chart=new Chart(ctx,{type:'radar',data:{labels:['MMR','Fc','W','Hp','η'],datasets:[{data:arr,fill:true,borderWidth:2}]},options:{scales:{r:{min:0,max:1}},plugins:{legend:{display:false}}}});
}
function updRadar(arr){ if(!state.chart) makeRadar(arr); else{ state.chart.data.datasets[0].data=arr; state.chart.update(); } }

/************************ 5 · CORE RECALC ******************************/
function recalc(){
  const rpmVal = rpm(state.vc);
  const feedRaw = feed(rpmVal,state.fz);
  const feedVal = Math.min(feedRaw, FR_MAX);
  const apVal   = THK/Math.max(1,state.ap);
  const hmVal   = hm(state.fz,state.ae);
  const mmrVal  = mmr(apVal,feedVal,state.ae);
  const FctVal  = Fct(hmVal,apVal,state.fz);
  const kWVal   = kW(FctVal,state.vc);
  const WVal    = kWVal*1000;
  const HPVal   = hp(kWVal);
  const etaVal  = Math.min(100,(HPVal/HP_AVAIL)*100);

  setTxt($('#outVc'),state.vc,1);
  setTxt($('#outFz'),state.fz,4);
  setTxt($('#outVf'),feedVal,0);
  setTxt($('#outN'), rpmVal,0);
  setTxt($('#outHm'),hmVal,4);
  setTxt($('#outAe'),state.ae,2);
  setTxt($('#outAp'),apVal,3);
  setTxt($('#valueMrr'),mmrVal,0);
  setTxt($('#valueFc'), FctVal,0);
  setTxt($('#valueW'),  WVal,0);
  setTxt($('#outHp'),   HPVal,2);
  setTxt($('#valueEta'),etaVal,0);

  const norm=x=>Math.min(1,x);
  updRadar([
    norm(mmrVal/1e5),
    norm(FctVal/1e4),
    norm(WVal/3000),
    norm(HPVal/HP_AVAIL),
    norm(etaVal/100)
  ]);
}

/********************** 6 · SLIDER BINDING *****************************/
function bindSlider(el,key,dec){
  if(!el) return;
  const bubble=el.nextElementSibling;
  const label=$('#val'+key.charAt(0).toUpperCase()+key.slice(1));
  const bubbleUpd=()=>{ if(bubble) bubble.textContent=fmt(el.value,dec); };
  bubbleUpd();
  el.addEventListener('input',bubbleUpd);
  el.addEventListener('change',()=>{ state[key]=+el.value; if(label) label.textContent=fmt(el.value,dec); recalc(); });
}
function setupPass(slider){
  if(!slider) return;
  const info=$('#textPasadasInfo');
  const refresh=()=>{ state.ap=+slider.value; if(info) info.textContent=`${slider.value} pasada${slider.value>1?'s':''} de ${(THK/slider.value).toFixed(2)} mm`; recalc(); };
  slider.addEventListener('input',refresh); refresh();
}

/************************ 7 · INIT con RETRY ***************************/
export function init(retry=10){
  const need=['sliderVc','sliderFz','sliderAe','sliderPasadas'];
  const miss=need.filter(id=>!$('#'+id));
  if(miss.length){ if(retry>0) return setTimeout(()=>init(retry-1),120); console.warn('[step6] sliders faltantes',miss); return; }

  const sVc=$('#sliderVc');
  if(sVc){ sVc.min=fmt((rpm_min*Math.PI*D)/1000,1); sVc.max=fmt((rpm_max*Math.PI*D)/1000,1); sVc.value=state.vc; }

  bindSlider($('#sliderVc'),'vc',1);
  bindSlider($('#sliderFz'),'fz',4);
  bindSlider($('#sliderAe'),'ae',1);
  setupPass($('#sliderPasadas'));

  makeRadar([0,0,0,0,0]); recalc();
  if(CountUp){ ['outVf','outN'].forEach(id=>{ const n=$('#'+id); const v=parseFloat(n.textContent)||0; state.counters[id]=new CountUp(n,v,{duration:.6,separator:' '}); if(!state.counters[id].error) state.counters[id].start(); }); }
  console.info('%c[step6] init listo (fórmulas backend)','color:#29b6f6;font-weight:bold');
}

/********************** 8 · LEGACY HOOK *******************************/
if(typeof window!=='undefined'){ window.step6=window.step6||{}; window.step6.init=init; }
