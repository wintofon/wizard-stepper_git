/* =====================================================================
 * assets/js/step6.js ·  PASO 6 — Wizard CNC  (Versión Sin AJAX)
 * ---------------------------------------------------------------------
 * 👉  Calcula **todo** del lado cliente.  No llama al backend.
 * ---------------------------------------------------------------------
 * ·  ES module auto-inicializable → export { init }
 * ·  Reacciona a los sliders (Vc, fz, ae, pasadas) y recalcula en vivo:
 *        n  (RPM), vf  (Feedrate), hm  (h chip),
 *        MMR, Fc, Potencia (W & HP) y η (%)
 * ·  Usa params inyectados por PHP (`window.step6Params`). Si faltan, pone
 *    defaults seguros para que nunca crashee.
 * ·  Sin dependencias de servidor, sin fetch, sin AbortController.
 * ·  Radar Chart opcional (Chart.js) para 5 métricas normalizadas.
 * ====================================================================*/

/* global Chart, CountUp, window */

/************************ 1. CONST & STATE *****************************/
const P = window.step6Params || {};
const {
  // Geometría y máquina
  diameter      : D          = 1,    // mm
  flute_count   : Z          = 1,
  thickness     : THK        = 10,   // mm
  feed_max      : FR_MAX     = 6000, // mm/min
  hp            : HP_AVAIL   = 1.5,  // HP disponibles
  // Coef. de corte (opcionales)
  coef_seg      : K_SEG      = 1,    // coef de seguridad
  Kc11          : KC         = 1500, // N/mm^2
  mc                        = 0.2,  // exponente empírico
  alpha         : ALPHA      = 0.25, // factor potencia
} = P;

const $ = (sel, ctx=document)=>ctx.querySelector(sel);
const fmt=(n,d=1)=>Number.parseFloat(n).toFixed(d);

const state = {
  fz : +P.fz0  || 0.02,                       // mm/diente
  vc : +P.vc0  || 100,                        // m/min
  ae : (+P.diameter_mm||D)*0.5,               // mm
  ap : +P.ap_slot || 1,                       // mm (por pasada)
  // Chart / CountUps
  chart:null, counters:{},
};

/************************ 2. DOM refs **********************************/
const SLIDERS = {
  vc :  $('#sliderVc'),
  fz :  $('#sliderFz'),
  ae :  $('#sliderAe'),
  p  :  $('#sliderPasadas'),
};
const BUBBLES = {
  vc : SLIDERS.vc?.nextElementSibling,
  fz : SLIDERS.fz?.nextElementSibling,
  ae : SLIDERS.ae?.nextElementSibling,
};
const LABELS = {
  vc : $('#valVc'), fz : $('#valFz'), ae : $('#valAe'),
};
const OUT = {
  vf : $('#outVf'), n : $('#outN'),   vc : $('#outVc'), fz : $('#outFz'),
  hm : $('#outHm'), hp: $('#outHp'),  mmr: $('#valueMrr'),
  fc : $('#valueFc'), w : $('#valueW'), eta: $('#valueEta'),
  ae : $('#outAe'), ap : $('#outAp'),
};
const INFO_P   = $('#textPasadasInfo');
const ERR_BOX  = $('#errorMsg');

/************************ 3. UTILIDADES DOM ****************************/
function showErr(msg=''){ ERR_BOX.textContent=msg; ERR_BOX.style.display=msg?'block':'none'; }
function updBubble(k){ if(BUBBLES[k]) BUBBLES[k].textContent=fmt(state[k],k==='fz'?4:1); }
function updLabel (k){ if(LABELS[k])  LABELS[k].textContent = fmt(state[k],k==='fz'?4:1); }
function setOut(id,v,d=1){ if(OUT[id]) OUT[id].textContent = fmt(v,d); }

/************************ 4. CÁLCULOS LOCALES **************************/
function rpmFromVc(vc){ return (vc*1000)/(Math.PI*D); }               // rev/min
function feedFrom(vc,fz){ return rpmFromVc(vc)*fz*Z; }                // mm/min
function hmFrom(fz,ae,D){ return fz*Math.sqrt(ae/D); }                // chip medio
function mmrFrom(vf,ae,ap){ return vf*ae*ap; }                        // mm^3/min
function fcFrom(K,mmr){ return K*mmr*1e-3; }                          // N (aprox)
function wattsFrom(fc,vf){ return (fc*vf)/60000; }                    // W
function hpFrom(w){ return w/745.699872; }
function etaFrom(hp){ return Math.min(100,(hp/HP_AVAIL)*100); }

/************************ 5. RADAR CHART *******************************/
function makeRadar(arr){
  const ctx=$('#radarChart'); if(!ctx||!Chart) return;
  state.chart=new Chart(ctx,{type:'radar',data:{labels:['MMR','Fc','W','Hp','η'],datasets:[{data:arr,fill:true,borderWidth:2}]},options:{scales:{r:{min:0,max:1}},plugins:{legend:{display:false}}}});
}
function updRadar(arr){ if(!state.chart) makeRadar(arr); else{ state.chart.data.datasets[0].data=arr; state.chart.update(); } }

/************************ 6. RECÁLCULO GENERAL *************************/
function recalc(){
  showErr('');
  // 1) Validaciones simples
  const vf = feedFrom(state.vc,state.fz);
  if (vf>FR_MAX){ showErr(`Límite feedrate ${FR_MAX}`); return; }

  // 2) Cálculos principales
  const n   = rpmFromVc(state.vc);
  const hm  = hmFrom(state.fz,state.ae,D);
  const ap  = THK/Math.max(1,state.ap);
  const mmr = mmrFrom(vf,state.ae,ap);
  const fc  = fcFrom(KC,mmr);
  const w   = wattsFrom(fc,vf);
  const hp  = hpFrom(w);
  const eta = etaFrom(hp);

  // 3) Salida UI
  setOut('vc', state.vc,1); setOut('fz',state.fz,4);
  setOut('vf',vf,0);        setOut('n',n,0);
  setOut('hm',hm,4);        setOut('ae',state.ae,2);
  setOut('ap',ap,2);        setOut('mmr',mmr,0);
  setOut('fc',fc,0);        setOut('w',w,0);
  setOut('hp',hp,2);        setOut('eta',eta,0);

  // 4) Radar normalizado (simple: dividir por máximos admisibles)
  const norm = x=>Math.min(1,x);
  updRadar([ norm(mmr/1e5), norm(fc/1e4), norm(w/3000), norm(hp/HP_AVAIL), norm(eta/100) ]);
}

/************************ 7. SLIDERS & EVENTOS *************************/
function bindSlider(name,dec=1){
  const el=SLIDERS[name]; if(!el) return;
  const setVal=()=>{ state[name]=parseFloat(el.value); updBubble(name); updLabel(name); recalc(); };
  updBubble(name); updLabel(name);
  el.addEventListener('input',()=>{ state[name]=parseFloat(el.value); updBubble(name); });
  el.addEventListener('change',setVal);
}
function initPassSlider(){
  const maxP=Math.ceil(THK/state.ae); SLIDERS.p.min=1; SLIDERS.p.max=maxP; SLIDERS.p.step=1; if(+SLIDERS.p.value>maxP) SLIDERS.p.value=maxP; state.ap=+SLIDERS.p.value; updPassInfo();
  SLIDERS.p.addEventListener('input',()=>{ state.ap=+SLIDERS.p.value; updPassInfo(); recalc(); });
}
function updPassInfo(){ const p=state.ap; INFO_P.textContent=`${p} pasada${p>1?'s':''} de ${(THK/p).toFixed(2)} mm`; }

/************************ 8. INIT PÚBLICO ******************************/
export function init(){
  if(!SLIDERS.fz){ console.warn('[step6] sliders faltantes'); return; }
  // Configurar rangos Vc según rpm_min/max si existen
  if(P.rpm_min&&P.rpm_max){ SLIDERS.vc.min=fmt((P.rpm_min*Math.PI*D)/1000,1); SLIDERS.vc.max=fmt((P.rpm_max*Math.PI*D)/1000,1); }
  // Valores iniciales
  SLIDERS.vc.value=state.vc; SLIDERS.fz.value=state.fz; SLIDERS.ae.value=state.ae; SLIDERS.p.value=state.ap;

  // Enlazar sliders
  bindSlider('vc',1); bindSlider('fz',4); bindSlider('ae',1); initPassSlider();

  // Radar inicial & primera pasada de cálculo
  makeRadar([0,0,0,0,0]); recalc();

  // Contadores animados para Vf y RPM
  if(CountUp){ ['outVf','outN'].forEach(id=>{ const node=$('#'+id); const v=parseFloat(node.textContent)||0; state.counters[id]=new CountUp(node,v,{duration:.6,separator:' '}); if(!state.counters[id].error) state.counters[id].start(); }); }

  console.info('%c[step6] init sin AJAX listo','color:#29b6f6;font-weight:bold');
}

/********************* 9. AUTO-EJECUCIÓN / LEGACY **********************/
if(typeof window!=='undefined'){ window.step6=window.step6||{}; window.step6.init=init; }
if(document.readyState!=='loading') init();
else document.addEventListener('DOMContentLoaded',init);
