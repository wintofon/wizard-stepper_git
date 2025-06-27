/* =====================================================================
 * assets/js/step6.js ·  PASO 6 — Wizard CNC  (Versión Sin AJAX · Retry DOM)
 * ---------------------------------------------------------------------
 * • Calcula TODO en el cliente. 0 llamadas al servidor.
 * • Ahora incluye RETRY automático (10× cada 120 ms) si los sliders aún no
 *   existen cuando se invoca init() — útil cuando el paso se inyecta vía
 *   AJAX y el script carga antes que el fragmento HTML.
 * • No se auto-ejecuta: el loader de la vista llama window.step6.init()
 *   cuando ya insertó el HTML; si igual faltan nodos, el retry los espera.
 * ====================================================================*/

/* global Chart, CountUp, window */

/*************************** 1 · PARAMS *********************************/
const P = window.step6Params || {};
const {
  diameter      : D        = 1,
  flute_count   : Z        = 1,
  thickness     : THK      = 10,
  feed_max      : FR_MAX   = 6000,
  hp            : HP_AVAIL = 1.5,
  coef_seg      : K_SEG    = 1,
  Kc11          : KC       = 1500,
  mc                     = 0.2,
  alpha         : ALPHA    = 0.25,
  rpm_min               = 1000,
  rpm_max               = 24000,
} = P;

/*************************** 2 · STATE **********************************/
const state = {
  fz : +P.fz0  || 0.02,
  vc : +P.vc0  || 100,
  ae : (+P.diameter_mm || D) * 0.5,
  ap : +P.ap_slot || 1,
  chart: null, counters:{}
};

/*********************** 3 · HELPERS DOM & MATH *************************/
const $ = (sel, ctx=document)=>ctx.querySelector(sel);
const fmt=(n,d=1)=>Number.parseFloat(n).toFixed(d);
function setTxt(el,v,d=1){ if(el) el.textContent=fmt(v,d); }
function rpm(vc){ return (vc*1000)/(Math.PI*D); }
function feed(vc,fz){ return rpm(vc)*fz*Z; }
function hm(fz,ae){ return fz*Math.sqrt(ae/D); }
function mmr(vf,ae,ap){ return vf*ae*ap; }
function fc(mmr){ return KC*mmr*1e-3; }
function watts(fc,vf){ return (fc*vf)/60000; }
function hp(w){ return w/745.699872; }
function eta(hp){ return Math.min(100,(hp/HP_AVAIL)*100); }

/************************ 4 · RADAR CHART *******************************/
function makeRadar(arr){
  const ctx=$('#radarChart'); if(!ctx||!Chart) return;
  state.chart=new Chart(ctx,{type:'radar',data:{labels:['MMR','Fc','W','Hp','η'],datasets:[{data:arr,fill:true,borderWidth:2}]},options:{scales:{r:{min:0,max:1}},plugins:{legend:{display:false}}}});
}
function updRadar(arr){ if(!state.chart) makeRadar(arr); else{ state.chart.data.datasets[0].data=arr; state.chart.update(); } }

/************************ 5 · CORE RECALC ******************************/
function recalc(){
  const vf = feed(state.vc,state.fz);
  const n  = rpm(state.vc);
  const hmChip = hm(state.fz,state.ae);
  const ap = THK/Math.max(1,state.ap);
  const mmrVal = mmr(vf,state.ae,ap);
  const fcVal = fc(mmrVal);
  const wVal  = watts(fcVal,vf);
  const hpVal = hp(wVal);
  const etaVal= eta(hpVal);

  setTxt($('#outVc'),state.vc,1);
  setTxt($('#outFz'),state.fz,4);
  setTxt($('#outVf'),vf,0);
  setTxt($('#outN'), n,0);
  setTxt($('#outHm'),hmChip,4);
  setTxt($('#outAe'),state.ae,2);
  setTxt($('#outAp'),ap,2);
  setTxt($('#valueMrr'),mmrVal,0);
  setTxt($('#valueFc'), fcVal,0);
  setTxt($('#valueW'),  wVal,0);
  setTxt($('#outHp'),   hpVal,2);
  setTxt($('#valueEta'),etaVal,0);

  const norm=x=>Math.min(1,x);
  updRadar([norm(mmrVal/1e5), norm(fcVal/1e4), norm(wVal/3000), norm(hpVal/HP_AVAIL), norm(etaVal/100)]);
}

/********************** 6 · SLIDER BINDING *****************************/
function bindSlider(el,key,dec){
  if(!el) return;
  const bubble=el.nextElementSibling;
  const label = $('#val'+key.charAt(0).toUpperCase()+key.slice(1));
  const bubbleUpd=()=>{ if(bubble) bubble.textContent=fmt(el.value,dec); };
  bubbleUpd();
  el.addEventListener('input',()=>{ bubbleUpd(); });
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
  const needIds=['sliderVc','sliderFz','sliderAe','sliderPasadas'];
  const miss=needIds.filter(id=>!$('#'+id));
  if(miss.length){
    if(retry>0){ return setTimeout(()=>init(retry-1),120); }
    console.warn('[step6] sliders faltantes tras reintentos',miss);
    return;
  }

  // Config rangos Vc si rpm min/max
  const sVc=$('#sliderVc');
  if(sVc){ sVc.min=fmt((rpm_min*Math.PI*D)/1000,1); sVc.max=fmt((rpm_max*Math.PI*D)/1000,1); sVc.value=state.vc; }

  bindSlider($('#sliderVc'),'vc',1);
  bindSlider($('#sliderFz'),'fz',4);
  bindSlider($('#sliderAe'),'ae',1);
  setupPass($('#sliderPasadas'));

  makeRadar([0,0,0,0,0]); recalc();
  if(CountUp){ ['outVf','outN'].forEach(id=>{ const node=$('#'+id); const v=parseFloat(node.textContent)||0; state.counters[id]=new CountUp(node,v,{duration:.6,separator:' '}); if(!state.counters[id].error) state.counters[id].start(); }); }
  console.info('%c[step6] init listo (sin AJAX)','color:#29b6f6;font-weight:bold');
}

/********************** 8 · LEGACY HOOK *******************************/
if(typeof window!=='undefined'){ window.step6=window.step6||{}; window.step6.init=init; }
