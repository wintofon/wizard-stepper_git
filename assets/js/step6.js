/* File: assets/js/step6.js
 * Epic CNC Wizard Step 6 – completos logs en consola, sliders dinámicos y radar 🔥
 */
/* global Chart, window */

window.radarChartInstance = window.radarChartInstance || null;

window.initStep6 = function () {
  'use strict';

  const BASE_URL = window.BASE_URL;
  const DEBUG    = window.DEBUG ?? true;
  const STYLE    = 'color:#2196F3;font-weight:bold';
  const log      = (...a) => DEBUG && console.log('%c[Step6🚀]', STYLE, ...a);
  const warn     = (...a) => DEBUG && console.warn('%c[Step6⚠️]', STYLE, ...a);
  const errorLog = (...a) => DEBUG && console.error('%c[Step6💥]', STYLE, ...a);
  const group    = (title, fn) => {
    if (!DEBUG) return fn();
    console.group(`%c[Step6] ${title}`, STYLE);
    try { return fn(); }
    finally { console.groupEnd(); }
  };

  // 1. Params from PHP
  const {
    diameter: D = 0,
    flute_count: Z = 1,
    vc0 = 0,
    fz0 = 0,
    fz_min0 = 0,
    fz_max0 = 1,
    fr_max = Infinity,
    coef_seg = 0,
    Kc11 = 1,
    mc = 1,
    alpha = 0,
    eta = 1,
  } = window.step6Params || {};
  const csrfToken = window.step6Csrf;

  log('Loaded params:', { D, Z, vc0, fz0, fz_min0, fz_max0, fr_max });

  // 2. DOM refs
  const $ = id => document.getElementById(id);
  const sFz   = $('sliderFz');
  const sVc   = $('sliderVc');
  const sAe   = $('sliderAe');
  const sP    = $('sliderPasadas');
  const infoP = $('textPasadasInfo');
  const err   = $('errorMsg');
  const out   = {
    vc: $('outVc'),  fz: $('outFz'), hm: $('outHm'), n: $('outN'),
    vf: $('outVf'), hp: $('outHp'), mmr: $('valueMrr'), fc: $('valueFc'),
    w: $('valueW'), eta: $('valueEta'), ae: $('outAe'), ap: $('outAp')
  };
  if (![sFz,sVc,sAe,sP,infoP,err].every(Boolean) || !out.vc) {
    errorLog('Missing DOM elements – aborting initStep6.');
    return;
  }

  function showError(msg) {
    err.textContent = msg; err.style.display = 'block';
    warn(msg);
  }
  function clearError() {
    err.style.display = 'none'; err.textContent = '';
  }
  function clamp(v,mx,mn) { return Math.min(Math.max(v,mx),mn); }

  // 3. Enhance helper
  function enhance(slider, min, max, step) {
    group(`Enhance ${slider.id}`, () => {
      const wrap   = slider.closest('.slider-wrap');
      const bubble = wrap.querySelector('.slider-bubble');
      slider.min  = min;
      slider.max  = max;
      slider.step = step;
      function update(raw) {
        const v = clamp(raw, parseFloat(min), parseFloat(max));
        slider.value = v;
        const pct = parseFloat(max)>parseFloat(min)
          ? ((v-parseFloat(min))/(parseFloat(max)-parseFloat(min)))*100
          : 0;
        wrap.style.setProperty('--val', pct);
        if (bubble) bubble.textContent = v.toFixed(step < 1 ? 2 : 0);
      }
      slider.addEventListener('input', e => update(+e.target.value));
      update(+slider.value);
      log(`${slider.id} bounded [${min},${max}] step=${step}`);
    });
  }

  // 4. Set slider limits
  group('SetSliderLimits', () => {
    const minVc = vc0 * 0.5, maxVc = vc0 * 1.5;
    enhance(sVc, minVc.toFixed(1), maxVc.toFixed(1), 1);
    sVc.value = vc0.toFixed(1);
    enhance(sFz, fz_min0.toFixed(3), fz_max0.toFixed(3), 0.001);
    sFz.value = fz0.toFixed(3);
    log(`VC slider ±50% around vc0=${vc0}, FZ in [${fz_min0},${fz_max0}]`);
  });

  // 5. Radar init
  let radar = window.radarChartInstance;
  const canvas = $('radarChart');
  if (canvas) {
    if (radar) radar.destroy();
    radar = new Chart(canvas.getContext('2d'), {
      type: 'radar',
      data: {
        labels: ['Vida útil','Terminación','Potencia'],
        datasets:[{ data:[50,50,50],
          backgroundColor:'rgba(33,150,243,0.3)',
          borderColor:'rgba(33,150,243,0.8)', borderWidth:2 }]
      },
      options:{ scales:{r:{beginAtZero:true,suggestedMax:100,ticks:{stepSize:20}}},plugins:{legend:{display:false}} }
    });
    window.radarChartInstance = radar;
    log('Radar initialized at [50,50,50]');
  } else warn('Radar canvas missing');

  // 6. Compute feed/rpm
  function computeFeed(vc,fz) {
    return group('computeFeed', () => {
      const rpm  = vc;
      const feed = rpm * fz * Z;
      log('rpm/feed:', rpm, '/', feed.toFixed(0));
      return { rpm, feed };
    });
  }

  // 7. Passes slider
  const thickness = parseFloat(sP.dataset.thickness)||0;
  function updatePasses() {
    const maxP = Math.ceil(thickness/parseFloat(sAe.value));
    sP.min=1; sP.max=maxP;
    if(+sP.value>maxP) sP.value=maxP;
    infoP.textContent = `${sP.value} pasadas de ${(thickness/+sP.value).toFixed(2)} mm`;
  }

  // 8. Handlers + debounce
  let timer;
  function schedule() { clearError(); clearTimeout(timer); timer=setTimeout(recalc,300); }

  function onVCChange() {
    group('vcChange', () => {
      const vcVal = clamp(+sVc.value,+sVc.min,+sVc.max);
      sVc.value = vcVal;
      const { rpm, feed } = computeFeed(vcVal, +sFz.value);
      if(feed>fr_max) return showError(`Feed > ${fr_max}`);
      out.n.textContent = rpm;
      out.vf.textContent=`${feed.toFixed(0)} mm/min`;
      schedule();
    });
  }

  function onFZChange() {
    group('fzChange', () => {
      const fzVal = clamp(+sFz.value,+sFz.min,+sFz.max);
      sFz.value = fzVal;
      const { rpm, feed } = computeFeed(+sVc.value, fzVal);
      if(feed>fr_max) return showError(`Feed > ${fr_max}`);
      out.fz.textContent=`${fzVal.toFixed(3)} mm/tooth`;
      out.vf.textContent=`${feed.toFixed(0)} mm/min`;
      schedule();
    });
  }

  [sVc].forEach(el=>el.addEventListener('input', onVCChange));
  [sFz].forEach(el=>el.addEventListener('input', onFZChange));
  sAe.addEventListener('input',()=>{ updatePasses(); schedule(); });
  sP.addEventListener('input',()=>{ updatePasses(); schedule(); });

  // 9. AJAX + epic logs
  async function recalc() {
    return group('recalc', async () => {
      const payload = {
        fz:+sFz.value, vc:+sVc.value, ae:+sAe.value,
        passes:+sP.value, thickness, D, Z,
        params:{fr_max,coef_seg,Kc11,mc,alpha,eta}
      };
      console.group('%c[Step6 AJAX] Request', STYLE);
      console.log(payload);
      console.groupEnd();
      try {
        const res  = await fetch(`${BASE_URL}/ajax/step6_ajax_legacy_minimal.php`, {
          method:'POST',
          headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},
          body:JSON.stringify(payload), cache:'no-store'
        });
        const js   = await res.clone().json();
        console.group('%c[Step6 AJAX] Response', STYLE);
        console.log('Status:',res.status, js);
        console.groupEnd();
        if(!res.ok) throw new Error(res.status===403?'Sesión expirada':`HTTP ${res.status}`);
        if(!js.success) throw new Error(js.error);
        const d = js.data;
        // paint outputs...
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
        // radar update
        if(radar && Array.isArray(d.radar) && d.radar.length===3) {
          radar.data.datasets[0].data = d.radar;
          radar.options.scales.r.suggestedMax = Math.max(...d.radar,100)*1.1;
          radar.update();
          log('Radar updated:', d.radar);
        }
      } catch(e) {
        console.group('%c[Step6 AJAX] Error', STYLE);
        console.error(e);
        console.groupEnd();
        showError(e.message);
      }
    });
  }

  // 10. Kickoff
  log('initStep6 start');
  updatePasses();
  recalc();
  window.addEventListener('error', ev=>showError(ev.message));
};
