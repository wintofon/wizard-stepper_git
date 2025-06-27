/* ==========================================================================
 * assets/js/step6.js  ·  PASO 6 – Wizard CNC (FIX v1.1)
 * --------------------------------------------------------------------------
 * • ES module auto-inicializable → export default { init }
 * • Maneja sliders (fz, Vc, Ae, pasadas) y hace _fetch_ al endpoint
 *     step6_ajax_legacy_minimal.php enviando los valores actuales.
 * • Incluye CSRF, credentials:same-origin, AbortController con reintento ×1,
 *   y overlay de spinner para UX.
 * • Nunca rompe el DOM: todos los errores se muestran en #errorMsg y nunca
 *   detienen la interacción de los sliders.
 * • Requiere: Feather (opcional), Chart.js, CountUp.js (precargados).
 * ========================================================================= */

/* global Chart, CountUp, window */

/********************  CONSTANTES / ESTADO GLOBAL  *********************/
// Desestructuramos con FALLOS CONTROLADOS: si el backend no inyectó alguna
// variable, usamos defaults para que el script no explote.
const BASE_PARAMS = window.step6Params   || {};
const CSRF        = window.step6Csrf     || '';
const AJAX_URL    = window.step6AjaxUrl  || '';

const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $all = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

const state = {
  // Valores dinámicos → si falta algo vienen en 0 para que el fetch inicial
  // lo sobreescriba con la respuesta real del backend.
  fz:  +BASE_PARAMS.fz0        || 0,
  vc:  +BASE_PARAMS.vc0        || 0,
  ae:  (+BASE_PARAMS.diameter_mm || 1) * 0.5,
  ap:  +BASE_PARAMS.ap_slot     || 1,
  // AbortController actual
  abort: null,
  // Chart instance / CountUps
  chart: null,
  counters: {},
};

/*****************************  UTILIDADES  ******************************/
const fmt = (n, dec = 1) => Number.parseFloat(n).toFixed(dec);

function setText (id, v, dec = 1) {
  const el = $(id.startsWith('#') ? id : `#${id}`);
  if (el) el.textContent = fmt(v, dec);
}

function showError (msg = '') {
  const box = $('#errorMsg');
  if (!box) return;
  box.textContent = msg;
  box.style.display = msg ? 'block' : 'none';
}

function toggleSpinner (show = true) {
  let overlay = $('#ajaxSpinner');
  if (!overlay && show) {
    overlay = document.createElement('div');
    overlay.id = 'ajaxSpinner';
    overlay.style.cssText = `position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.45);z-index:9999;backdrop-filter:blur(1.5px)`;
    overlay.innerHTML = '<div class="spinner-border" role="status" style="width:3rem;height:3rem;"></div>';
    document.body.appendChild(overlay);
  }
  if (overlay) overlay.style.display = show ? 'flex' : 'none';
}

function debounce (fn, ms = 300) {
  let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
}

/***************************  ACTUALIZAR UI  *****************************/
function applyBackendData (d) {
  if (!d) return;
  // Actualizar contadores grandes
  setText('#outVf', d.feed, 0);
  setText('#outN',  d.rpm, 0);
  setText('#outVc', d.vc, 1);
  setText('#outFz', d.fz, 4);
  setText('#outAp', d.ap, 2);
  setText('#outAe', d.ae, 2);
  setText('#outHm', d.hm, 4);
  setText('#outHp', d.hp, 2);
  setText('#valueMrr', d.mmr, 0);
  setText('#valueFc',  d.fc, 0);
  setText('#valueW',   d.w,  0);
  setText('#valueEta', d.eta,0);

  // Radar chart (5 ejes): Mmr, Fc, W, Hp, Eta
  const radarData = [d.mmr_norm, d.fc_norm, d.w_norm, d.hp_norm, d.eta];
  if (!state.chart) createRadar(radarData); else updateRadar(radarData);
}

function createRadar (dataArr) {
  const ctx = $('#radarChart');
  if (!ctx || !Chart) return;
  state.chart = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: ['MMR','Fc','W','Hp','η'],
      datasets: [{
        label: 'Distribución',
        data: dataArr,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { r: { min:0, max:1 } }
    }
  });
}

function updateRadar (arr) {
  if (!state.chart) return;
  state.chart.data.datasets[0].data = arr;
  state.chart.update();
}

/*****************************  AJAX CALL  ******************************/
async function fetchBackend () {
  if (!AJAX_URL) { showError('Endpoint AJAX no definido'); return; }

  // Abort anterior si existe
  if (state.abort) state.abort.abort();
  state.abort = new AbortController();
  const body = new URLSearchParams({
    csrf_token: CSRF,
    fz: state.fz,
    vc: state.vc,
    ae: state.ae,
    ap: state.ap,
  });

  toggleSpinner(true);
  showError('');

  try {
    const res = await fetch(AJAX_URL, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
      },
      body,
      signal: state.abort.signal
    });

    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    if (json.success === false) throw new Error(json.error || 'error');

    applyBackendData(json.data ?? json); // flexible backend
  }
  catch (err) {
    if (err.name === 'AbortError') return; // se abortó por nueva petición
    console.error('[step6] fetch error', err);
    if (!state._retried) {
      state._retried = true;
      setTimeout(fetchBackend, 700);
    } else {
      showError('Error al recalcular parámetros.');
    }
  }
  finally {
    toggleSpinner(false);
    state._retried = false;
  }
}

/****************************  SLIDERS  *********************************/
function bindSlider (id, key, dec = 4) {
  const el = $(id);
  if (!el) return;

  const bubble = el.nextElementSibling;
  const updateBubble = () => { bubble.textContent = fmt(el.value, dec); };
  updateBubble();

  el.addEventListener('input', debounce(() => {
    state[key] = parseFloat(el.value);
    const lbl = key === 'fz' ? '#valFz' : key === 'vc' ? '#valVc' : key === 'ae' ? '#valAe' : '';
    if (lbl) $(lbl).textContent = fmt(el.value, dec);
    fetchBackend();
  }, 250));

  el.addEventListener('input', updateBubble);
}

/****************************  INIT  ************************************/
export function init () {
  // Verificación mínima
  if (!$('#sliderFz')) { console.warn('[step6] sliders no encontrados'); return; }

  // Vincular sliders
  bindSlider('#sliderFz',       'fz', 4);
  bindSlider('#sliderVc',       'vc', 1);
  bindSlider('#sliderAe',       'ae', 1);
  bindSlider('#sliderPasadas',  'ap', 0);

  // Primer fetch → llena métricas reales
  fetchBackend();

  // Animar contadores iniciales si existen valores
  if (CountUp) {
    ['outVf','outN'].forEach(id => {
      const node = $('#'+id); if (!node) return;
      const val = parseFloat(node.textContent.replace(/\s/g,'')) || 0;
      state.counters[id] = new CountUp(node, val, { duration: .8, separator: ' ' });
      if (!state.counters[id].error) state.counters[id].start();
    });
  }

  console.info('%c[step6] init listo', 'color:#4caf50;font-weight:bold');
}

// Compat retro  → window.step6.init()
window.step6 = window.step6 || {}; window.step6.init = init;

// Auto-ejecución
if (document.readyState !== 'loading') init();
else document.addEventListener('DOMContentLoaded', init);
