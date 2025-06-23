/*
 * File: step6.js
 * Epic CNC Wizard Step 6 – lógica de sliders con límites, recálculos AJAX y radar dinámico 🏹
 *
 * Main responsibility:
 *   • Sliders para fz y vc con límites bidireccionales (rpm_min, rpm_max)
 *   • Recálculos vía AJAX con CSRF
 *   • Radar Chart que refleja Vida Útil, Terminación y Potencia según fz
 *   • Console logs épicos con grupos, colores y emojis
 *
 * Behavior:
 *   - Al aumentar fz: ↑Potencia, ↑VidaÚtil, ↓Terminación
 *   - Al disminuir fz: ↓Potencia, ↓VidaÚtil, ↑Terminación
 *   - El slider de feedrate mide MMR y actualiza sólo Potencia en radar secundario
 *
 * Related:
 *   ajax/step6_ajax_legacy_minimal.php
 */
/* global Chart, window */
(() => {
  'use strict';

  // ===== CONFIG & LOGGING =====
  const BASE_URL = window.BASE_URL;
  const DEBUG    = window.DEBUG ?? true;
  const STYLE    = 'color:#009688;font-weight:bold';
  const log      = (...a) => DEBUG && console.log('%c[Step6🚀]', STYLE, ...a);
  const warn     = (...a) => DEBUG && console.warn('%c[Step6⚠️]', STYLE, ...a);
  const errorLog = (...a) => DEBUG && console.error('%c[Step6💥]', STYLE, ...a);
  const table    = d => DEBUG && console.table(d);
  const group    = (title, fn) => {
    if (!DEBUG) return fn();
    console.group(`%c[Step6] ${title}`, STYLE);
    try { return fn(); } finally { console.groupEnd(); }
  };

  // ===== PARAMETERS =====
  const {
    diameter: D = 0,
    flute_count: Z = 1,
    rpm_min = 0,
    rpm_max = 0,
    fr_max = Infinity,
    coef_seg = 0,
    Kc11 = 1,
    mc = 1,
    alpha = 0,
    eta = 1
  } = window.step6Params || {};
  const csrfToken = window.step6Csrf;
  log('Loaded params:', {D, Z, rpm_min, rpm_max, fr_max});

  // ===== DOM SELECTORS =====
  const $ = id => document.getElementById(id);
  const sFz   = $('sliderFz');
  const sVc   = $('sliderVc');
  const sAe   = $('sliderAe');
  const sP    = $('sliderPasadas');
  const infoP = $('textPasadasInfo');
  const err   = $('errorMsg');
  const out   = {
    vc: $('outVc'), fz: $('outFz'), hm: $('outHm'), n: $('outN'),
    vf: $('outVf'), hp: $('outHp'), mmr: $('valueMrr'), fc: $('valueFc'),
    w: $('valueW'), eta: $('valueEta'), ae: $('outAe'), ap: $('outAp')
  };
  if (![sFz, sVc, sAe, sP, infoP, err].every(Boolean) || !out.vc) {
    errorLog('Missing DOM elements – aborting Step6.');
    return;
  }

  // ===== UTILITIES =====
  function showError(msg) {
    err.textContent = msg;
    err.style.display = 'block';
    warn(msg);
  }
  function clearError() {
    err.style.display = 'none';
    err.textContent = '';
  }
  function clamp(val, min, max) {
    return Math.min(Math.max(val, min), max);
  }

  // ===== SLIDER ENHANCER =====
  function enhance(slider) {
    group(`Enhance ${slider.id}`, () => {
      const wrap = slider.closest('.slider-wrap');
      const bubble = wrap.querySelector('.slider-bubble');
      const min = parseFloat(slider.min);
      const max = parseFloat(slider.max);
      const step= parseFloat(slider.step);
      function update(raw) {
        const v = clamp(raw, min, max);
        slider.value = v;
        const pct = max>min ? ((v-min)/(max-min))*100 : 0;
        wrap.style.setProperty('--val', pct);
        if (bubble) bubble.textContent = v.toFixed(step<1?2:0);
      }
      slider.addEventListener('input', e => update(+e.target.value));
      update(+slider.value);
      log(`${slider.id} bounded [${min},${max}]`);
    });
  }

  // ===== SET SLIDER LIMITS =====
  group('SetLimits', () => {
    const minVc = (rpm_min*Math.PI*D)/1000;
    const maxVc = (rpm_max*Math.PI*D)/1000;
    sVc.min = minVc.toFixed(1);
    sVc.max = maxVc.toFixed(1);
    log('VC limits:', {minVc, maxVc});
    sVc.value = clamp(+sVc.value, minVc, maxVc).toFixed(1);
  });

  // ===== RADAR INIT =====
  let radar = window.radarChartInstance;
  const canvas = $('radarChart');
  if (canvas) {
    if (radar) radar.destroy();
    radar = new Chart(canvas.getContext('2d'), {
      type: 'radar', data: {
        labels:['Vida útil','Terminación','Potencia'],
        datasets:[{ data:[50,50,50], backgroundColor:'rgba(76,175,80,0.3)', borderColor:'rgba(76,175,80,0.8)', borderWidth:2 }]
      }, options:{scales:{r:{beginAtZero:true,suggestedMax:100,ticks:{stepSize:20}}},plugins:{legend:{display:false}}}
    });
    window.radarChartInstance = radar;
    log('Radar ready');
  } else warn('Radar canvas missing');

  // ===== RECALC =====
  function computeFeed(vc,fz){return group('computeFeed',()=>{const rpm=(vc*1000)/(Math.PI*D);const f=rpm*fz*Z;log('Feed',f);return f;});}
  const thick = parseFloat(sP.dataset.thickness)||0;
  function updatePasses(){const maxP=Math.max(1,Math.ceil(thick/parseFloat(sAe.value)));sP.min=1;sP.max=maxP;if(+sP.value>maxP)sP.value=maxP;infoP.textContent=`${sP.value} pasadas de ${(thick/+sP.value).toFixed(2)} mm`;}

  let timer;
  function schedule(){clearError();clearTimeout(timer);timer=setTimeout(recalc,300);}
  function onChange(){group('onParam',()=>{clearError();const vc=clamp(+sVc.value,+sVc.min,+sVc.max);const fz=clamp(+sFz.value,+sFz.min,+sFz.max);sVc.value=vc.toFixed(1);sFz.value=fz.toFixed(3);const feed=computeFeed(vc,fz);if(feed>fr_max)return showError(`Feed > ${fr_max}`);if(feed<=0)return showError('Feed ≤ 0');schedule();});}
  [sFz,sVc].forEach(el=>el.addEventListener('input',onChange));sAe.addEventListener('input',()=>{updatePasses();schedule();});sP.addEventListener('input',()=>{updatePasses();schedule();});

  async function recalc(){return group('recalc',async()=>{const payload={fz:+sFz.value,vc:+sVc.value,ae:+sAe.value,passes:+sP.value,thickness:thick,D,Z,params:{fr_max,coef_seg,Kc11,mc,alpha,eta}};table(payload);try{const res=await fetch(`${BASE_URL}/ajax/step6_ajax_legacy_minimal.php`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify(payload),cache:'no-store'});if(!res.ok)throw new Error(res.status===403?'Session expired':`HTTP ${res.status}`);const js=await res.json();if(!js.success)throw new Error(js.error||'Server error');const d=js.data;out.vc.textContent=`${d.vc} m/min`;out.fz.textContent=`${d.fz} mm/tooth`;out.hm.textContent=`${d.hm} mm`;out.n.textContent=d.n;out.vf.textContent=`${d.vf} mm/min`;out.hp.textContent=`${d.hp} HP`;out.mmr.textContent=d.mmr;out.fc.textContent=d.fc;out.w.textContent=d.watts;out.eta.textContent=`${d.etaPercent}%`;out.ae.textContent=d.ae.toFixed(2);out.ap.textContent=d.ap.toFixed(3);if(radar&&Array.isArray(d.radar)&&d.radar.length===3){radar.data.datasets[0].data=d.radar;radar.options.scales.r.suggestedMax=Math.max(...d.radar,100)*1.1;radar.update();log('Radar updated',d.radar);} }catch(e){errorLog('recalc error',e);showError(e.message);} });}

  // ===== INIT =====
  log('initStep6 started');[sFz,sVc,sAe,sP].forEach(enhance);updatePasses();recalc();window.addEventListener('error',e=>showError(e.message));
})();
