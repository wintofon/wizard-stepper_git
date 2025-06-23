/*
 * File: step6.js
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * TODO: Extend documentation.
 */
/** Ubicación: C:\xampp\htdocs\wizard-stepper_git\assets\js\step6.js */
/* global Chart */

// Mantiene la instancia del gráfico entre ejecuciones
window.radarChartInstance = window.radarChartInstance || null;

window.initStep6 = function () {
  const BASE_URL = window.BASE_URL;
  const DEBUG = window.DEBUG ?? false;
  const TAG = '[WizardStepper]';
  const logger = (lvl, ...a) => { if (!DEBUG) return; const ts = new Date().toISOString(); console[lvl](`${TAG} ${ts}`, ...a); };
  const log = (...a) => logger('log', ...a);
  const warn = (...a) => logger('warn', ...a);
  const error = (...a) => logger('error', ...a);
  const table = d => { if (DEBUG) console.table(d); };
  const group = (title, fn) => { if (!DEBUG) return fn(); console.group(`${TAG} ${new Date().toISOString()} ${title}`); try { return fn(); } finally { console.groupEnd(); } };
  // 1. Parámetros inyectados por PHP
  const {
    diameter: D,
    flute_count: Z,
    rpm_min: rpmMin,
    rpm_max: rpmMax,
    fr_max = Number.POSITIVE_INFINITY,
    coef_seg = 1,
    Kc11 = 1200,
    mc = 0.2,
    alpha = 0,
    eta = 1,
  } = window.step6Params || {};

  const csrfToken = window.step6Csrf;

  // 2. Referencias al DOM
  const sFz   = document.getElementById('sliderFz'),
        sVc   = document.getElementById('sliderVc'),
        sAe   = document.getElementById('sliderAe'),
        sP    = document.getElementById('sliderPasadas'),
        infoP = document.getElementById('textPasadasInfo'),
        err   = document.getElementById('errorMsg'),
        out   = {
          vc:  document.getElementById('outVc'),
          fz:  document.getElementById('outFz'),
          hm:  document.getElementById('outHm'),
          n:   document.getElementById('outN'),
          vf:  document.getElementById('outVf'),
          hp:  document.getElementById('outHp'),
          mmr: document.getElementById('valueMrr'),
          fc:  document.getElementById('valueFc'),
          w:   document.getElementById('valueW'),
          eta: document.getElementById('valueEta'),
          ae:  document.getElementById('outAe'),  // ← nuevo
        ap:  document.getElementById('outAp')   // ← nuevo
      };

  function enhanceSlider(slider) {
    return group('enhanceSlider', () => {
    log('slider', slider);
    const wrap = slider.closest('.slider-wrap');
    if (!wrap) return;
    const bubble = wrap.querySelector('.slider-bubble');
    const min = parseFloat(slider.min || 0);
    const max = parseFloat(slider.max || 1);
    const step = parseFloat(slider.step || 1);
    wrap.style.setProperty('--step-pct', (step / (max - min)) * 100);
    function update(val) {
      const pct = ((val - min) / (max - min)) * 100;
      wrap.style.setProperty('--val', pct);
      if (bubble) bubble.textContent = parseFloat(val).toFixed(step < 1 ? 2 : 0);
    }
    slider.addEventListener('input', e => update(parseFloat(e.target.value)));
    update(parseFloat(slider.value));
    log('return void');
    });
  }

  // 3. Límites de Vc desde rpmMin/rpmMax
  const vcMinAllowed = (rpmMin * Math.PI * D) / 1000;
  const vcMaxAllowed = (rpmMax * Math.PI * D) / 1000;
  sVc.min = vcMinAllowed.toFixed(1);
  sVc.max = vcMaxAllowed.toFixed(1);
  if (+sVc.value < vcMinAllowed) sVc.value = vcMinAllowed.toFixed(1);
  if (+sVc.value > vcMaxAllowed) sVc.value = vcMaxAllowed.toFixed(1);

  // 4. Radar Chart init
  const canvas = document.getElementById('radarChart');
  if (window.radarChartInstance) {
    window.radarChartInstance.destroy();
  }
  const ctx = canvas.getContext('2d');
  window.radarChartInstance = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: ['Vida útil','Terminación','Potencia'],
      datasets:[{
        data:[0,0,0],
        backgroundColor:'rgba(79,195,247,0.35)',
        borderColor:'rgba(79,195,247,0.8)',
        borderWidth:2
      }]
    },
    options:{scales:{r:{max:100,ticks:{stepSize:20}}},plugins:{legend:{display:false}}}
  });
  const radar = window.radarChartInstance;

  // 5. Mostrar/ocultar errores
  function showError(msg) {
    err.style.display = 'block';
    err.textContent = msg;
  }
  function clearError() {
    err.style.display = 'none';
    err.textContent = '';
  }

  // 6. Calcular feedrate Vf
  function computeFeed(vc, fz) {
    return group('computeFeed', () => {
      log('inputs', { vc, fz });
      const rpm = (vc * 1000) / (Math.PI * D);
      const result = rpm * fz * Z;
      log('return', result);
      return result;
    });
  }

  // 7. Bloqueo de slider
  function lockSlider(slider, msg) {
    if (!slider.dataset.limitValue) {
      slider.dataset.limitValue = slider.value;
    }
    slider.value = slider.dataset.limitValue;
    slider.disabled = true;
    showError(msg);
  }
  function unlockSlider(slider) {
    slider.disabled = false;
    delete slider.dataset.limitValue;
  }

  // 8. Pasadas slider / info
  const thickness = parseFloat(sP.dataset.thickness);
  function updatePasadasSlider() {
    const maxP = Math.ceil(thickness / parseFloat(sAe.value));
    sP.min = 1; sP.max = maxP; sP.step = 1;
    if (sP.value > maxP) sP.value = maxP;
  }
  function updatePasadasInfo() {
    const p = +sP.value;
    infoP.textContent = `${p} pasadas de ${(thickness/p).toFixed(2)} mm`;
  }

  // 9. Debounce
  let timer;
  function scheduleRecalc() {
    clearError();
    clearTimeout(timer);
    timer = setTimeout(recalc, 200);
  }

  // 10. Handler común para fz/vc
  function onParamChange() {
    group('onParamChange', () => {
      log('vc', sVc.value, 'fz', sFz.value);
    clearError();
    const vc = parseFloat(sVc.value),
          fz = parseFloat(sFz.value),
          feed = computeFeed(vc, fz);

    if (feed > fr_max) {
      this.dataset.limitValue = this.value;
      lockSlider(this, `Feedrate supera límite (${fr_max}). Ajusta el otro valor.`);
      return;
    }
    if (feed <= 0) {
      this.dataset.limitValue = this.value;
      lockSlider(this, `Feedrate demasiado bajo.`);
      return;
    }
    unlockSlider(sVc);
    unlockSlider(sFz);
    scheduleRecalc();
    log('return void');
    });
  }

  // 11. Conectar listeners
  sFz.addEventListener('input', onParamChange);
  sVc.addEventListener('input', onParamChange);
  sAe.addEventListener('input', () => {
    updatePasadasSlider();
    updatePasadasInfo();
    scheduleRecalc();
  });
  sP.addEventListener('input', () => {
    updatePasadasInfo();
    scheduleRecalc();
  });

  // 12. AJAX + recalc
  async function recalc() {
    return group('recalc', async () => {
    const payload = {
      fz:        parseFloat(sFz.value),
      vc:        parseFloat(sVc.value),
      ae:        parseFloat(sAe.value),
      passes:    parseInt(sP.value,10),
      thickness: thickness,
      D, Z,
      params:    { fr_max, coef_seg, Kc11, mc, alpha, eta }
    };

    table(payload);
    try {
      const res = await fetch(`${BASE_URL}/ajax/step6_ajax_legacy_minimal.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(payload),
        cache: 'no-store'
      });
      if (res.status === 403) {
        return showError('Sesión expirada. Recargá la página.');
      }
      if (!res.ok) {
        return showError(`AJAX error ${res.status}`);
      }
      const msg = await res.json();
      if (!msg.success) {
        return showError(`Servidor: ${msg.error}`);
      }
      const d = msg.data;

      // 13. Pinta resultados
      out.vc.textContent  = `${d.vc} m/min`;
      out.fz.textContent  = `${d.fz} mm/tooth`;
      out.hm.textContent  = `${d.hm} mm`;
      out.n.textContent   = d.n;
      out.vf.textContent  = `${d.vf} mm/min`;
      out.hp.textContent  = `${d.hp} HP`;
      out.mmr.textContent = d.mmr;
      out.fc.textContent  = d.fc;
      out.w.textContent   = d.watts;
      out.eta.textContent = d.etaPercent;
      // ← Aquí pintamos los nuevos
      out.ae.textContent  = d.ae.toFixed(2);
      out.ap.textContent  = d.ap.toFixed(3);

      // 14. Radar
      if (Array.isArray(d.radar) && d.radar.length === 3) {
        radar.data.datasets[0].data = d.radar;
        radar.update();
      }
      table(d);
    } catch (e) {
      error('recalc error', e);
      showError(`Conexión fallida: ${e.message}`);
    }
    log('return void');
    });
  }

  // 15. Kickoff
  [sFz, sVc, sAe, sP].forEach(enhanceSlider);
  updatePasadasSlider();
  updatePasadasInfo();
  recalc();
  if (!window.step6ErrorHandlerAdded) {
    window.addEventListener('error', ev => showError(`JS: ${ev.message}`));
    window.step6ErrorHandlerAdded = true;
  }
};
