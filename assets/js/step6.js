/*
 * File: step6.js
 * Epic CNC Wizard Step 6 – límites unidireccionales y radar legendario 🏹
 *
 * Main responsibility:
 *   Controlar sliders solo por límite inferior, recálculos AJAX, y actualizar radar.
 *   Consola épica en cada paso.
 * Related files: ajax/step6_ajax_legacy_minimal.php
 */
/* global Chart, window */

(() => {
  'use strict';

  // ========= CONFIG & LOGGING =========
  const BASE_URL = window.BASE_URL;
  const DEBUG    = window.DEBUG ?? true;
  const STYLE    = 'color:#ff5722;font-weight:bold';
  const log      = (...a) => DEBUG && console.log('%c[Step6🚀]', STYLE, ...a);
  const warn     = (...a) => DEBUG && console.warn('%c[Step6⚠️]', STYLE, ...a);
  const errorLog = (...a) => DEBUG && console.error('%c[Step6💥]', STYLE, ...a);
  const table    = data => DEBUG && console.table(data);
  const group    = (t, fn) => {
    if (!DEBUG) return fn();
    console.group(`%c[Step6] ${t}`, STYLE);
    try { return fn(); } finally { console.groupEnd(); }
  };

  // ========= PARAMETROS =========
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
  log('Params:', {D, Z, rpm_min, rpm_max, fr_max});

  // ========= DOM Helpers =========
  const $ = id => document.getElementById(id);
  const sFz   = $('sliderFz'),
        sVc   = $('sliderVc'),
        sAe   = $('sliderAe'),
        sP    = $('sliderPasadas'),
        infoP = $('textPasadasInfo'),
        err   = $('errorMsg');
  const out = {
    vc: $('outVc'), fz: $('outFz'), hm: $('outHm'), n: $('outN'),
    vf: $('outVf'), hp: $('outHp'), mmr: $('valueMrr'), fc: $('valueFc'),
    w: $('valueW'), eta: $('valueEta'), ae: $('outAe'), ap: $('outAp')
  };
  if (![sFz,sVc,sAe,sP,infoP,err].every(el => el) || !out.vc) {
    errorLog('DOM missing – aborting');
    return;
  }

  // ========= UTILITIES =========
  function showError(msg) {
    err.textContent = msg;
    err.style.display = 'block';
    warn(msg);
  }
  function clearError() {
    err.style.display = 'none'; err.textContent = '';
  }

  // ========= SLIDER ENHANCER =========
  function enhance(slider) {
    group(`Enhance ${slider.id}`, () => {
      const wrap = slider.closest('.slider-wrap');
      const bubble = wrap.querySelector('.slider-bubble');
      const min = parseFloat(slider.min);
      const step = parseFloat(slider.step);
      // Only enforce lower bound
      function update(val) {
        const v = val < min ? min : val;
        slider.value = v;
        const pct = ((v - min) / (parseFloat(slider.max) - min)) * 100;
        wrap.style.setProperty('--val', pct);
        if (bubble) bubble.textContent = v.toFixed(step<1?2:0);
      }
      slider.addEventListener('input', e => update(parseFloat(e.target.value)));
      update(parseFloat(slider.value));
      log(`${slider.id} enhanced with lower bound ${min}`);
    });
  }

  // ========= VC LIMITS (lower only) =========
  group('vcLimits', () => {
    const minVc = (rpm_min * Math.PI * D) / 1000;
    sVc.min = minVc.toFixed(1);
    log('VC lower limit:', minVc);
    sVc.value = Math.max(parseFloat(sVc.value), minVc).toFixed(1);
  });

  // ========= RADAR SETUP =========
  let radar = window.radarChartInstance;
  const canvas = $('radarChart');
  if (canvas) {
    if (radar) radar.destroy();
    radar = new Chart(canvas.getContext('2d'), {
      type: 'radar', data: {
        labels: ['Vida útil','Terminación','Potencia'],
        datasets:[{ data:[0,0,0], backgroundColor:'rgba(255,87,34,0.3)', borderColor:'rgba(255,87,34,0.8)', borderWidth:2 }]
      }, options: { scales:{r:{max:100,beginAtZero:true,ticks:{stepSize:20}}},plugins:{legend:{display:false}} }
    });
    window.radarChartInstance = radar;
    log('Radar ready');
  } else warn('Radar canvas missing');

  // ========= RECALCULATION =========
  function computeFeed(vc,fz) {
    return group('computeFeed',()=>{
      const rpm = (vc*1000)/(Math.PI*D);
      const f = rpm*fz*Z;
      log('feed:',f);
      return f;
    });
  }
  const thickness = parseFloat(sP.dataset.thickness)||0;
  function updatePasses() {
    const maxP = Math.max(1,Math.ceil(thickness/parseFloat(sAe.value)));
    sP.min = 1; sP.max = maxP;
    infoP.textContent = `${sP.value} pasadas de ${(thickness/sP.value).toFixed(2)} mm`;
  }
  let timer;
  function schedule() { clearError(); clearTimeout(timer); timer = setTimeout(recalc,300); }
  function onParam() {
    group('onParamChange',()=>{
      const vc=parseFloat(sVc.value),fz=parseFloat(sFz.value);
      clearError();
      const feed=computeFeed(vc,fz);
      if(feed>fr_max) return showError(`Feed>${fr_max}`);
      if(feed<=0) return showError('Feed≤0');
      schedule();
    });
  }
  [sFz,sVc].forEach(el=>el.addEventListener('input',onParam));
  sAe.addEventListener('input',()=>{updatePasses();schedule();});
  sP.addEventListener('input',()=>{updatePasses();schedule();});

  async function recalc(){
    return group('recalc',async()=>{
      const payload={fz:+sFz.value,vc:+sVc.value,ae:+sAe.value,passes:+sP.value,thickness,D,Z,params:{fr_max,coef_seg,Kc11,mc,alpha,eta}};
      table(payload);
      try{
        const res=await fetch(`${BASE_URL}/ajax/step6_ajax_legacy_minimal.php`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify(payload),cache:'no-store'});
        if(!res.ok) throw new Error(res.status===403?'Sesión expirada':`HTTP ${res.status}`);
        const js=await res.json(); if(!js.success) throw new Error(js.error||'Error servidor');
        const d=js.data;
        // paint
        out.vc.textContent=`${d.vc} m/min`;
        out.fz.textContent=`${d.fz} mm/tooth`;
        out.hm.textContent=`${d.hm} mm`;
        out.n.textContent=d.n;
        out.vf.textContent=`${d.vf} mm/min`;
        out.hp.textContent=`${d.hp} HP`;
        out.mmr.textContent=d.mmr;
        out.fc.textContent=d.fc;
        out.w.textContent=d.watts;
        out.eta.textContent=`${d.etaPercent}%`;
        out.ae.textContent=d.ae.toFixed(2);
        out.ap.textContent=d.ap.toFixed(3);
        if(radar&&Array.isArray(d.radar)&&d.radar.length===3){radar.data.datasets[0].data=d.radar;radar.update();log('Radar updated:',d.radar);}      
      }catch(e){errorLog('recalc error:',e);showError(e.message);}    });
  }

  // ===== INIT =====
  log('initStep6 start');
  [sFz,sVc,sAe,sP].forEach(enhance);
  updatePasses(); recalc();
  window.addEventListener('error',e=>showError(e.message));
})();
