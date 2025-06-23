/* ============================================================================
 * File: wizard_stepper.js  (fragmento clave)
 * ---------------------------------------------------------------------------
 * Función loadStep(step) – Cargador robusto, blindado y anti-errores
 * ---------------------------------------------------------------------------
 * • Carga asincrónica de cada vista del wizard vía fetch → load-step.php
 * • Diferencia respuestas HTML vs JSON (errores) sin romper el DOM
 * • Traza cada evento importante en consola con estilos ANSI-CSS
 * • Contiene helpers showStepError() e initialiseStepScripts()
 * • Compatible con Feather Icons, Chart.js, sliders y cualquier init per-paso
 * • No depende de jQuery – puro ES2021
 * ========================================================================= */

/* --------------------------- CONFIGURACIÓN -------------------------------- */
const STEP_CONTAINER_SELECTOR = '#wizard-step';   // wrapper donde inyectar la vista
const BASE_URL                = window.BASE_URL || '';   // definido por PHP
const DEBUG                   = window.DEBUG ?? true;    // activar/Desactivar logs

/* --------------------------- HELPERS DE LOG -------------------------------- */
const LOG_STYLE = 'color:#4caf50;font-weight:bold';
const ERR_STYLE = 'color:#f44336;font-weight:bold';

function log(...a)  { DEBUG && console.log('%c[Wizard]', LOG_STYLE, ...a); }
function error(...a){ console.error('%c[Wizard]', ERR_STYLE, ...a);        }

/* --------------------------- FUNCIÓN PRINCIPAL ----------------------------- */
/**
 * Carga dinámica de un paso del wizard y reemplaza el contenedor.
 * @param {number} step Valor entre 1 y 6 inclusive.
 * @returns {Promise<void>}
 */
export async function loadStep(step = 1) {
  // Sanity check
  if (step < 1 || step > 6) {
    error('Paso inválido:', step);
    showStepError('Paso solicitado fuera de rango (1-6).');
    return;
  }

  const stepContainer = document.querySelector(STEP_CONTAINER_SELECTOR);
  if (!stepContainer) {
    error('No existe el contenedor', STEP_CONTAINER_SELECTOR);
    return;
  }

  const url  = `${BASE_URL}/load-step.php?step=${step}`;
  const opts = {
    method      : 'GET',
    credentials : 'same-origin', // Cookies de sesión
    headers     : {
      'Accept'           : 'text/html, application/json',
      'X-Requested-With' : 'fetch'
    }
  };

  log(`→ solicitando Paso ${step}…`, url);

  try {
    const resp = await fetch(url, opts);

    /* 1) Errores HTTP crudos (404/500) */
    if (!resp.ok) {
      throw new Error(`HTTP ${resp.status} – ${resp.statusText}`);
    }

    /* 2) Diferenciar JSON (error) vs HTML (vista) */
    const ctype = resp.headers.get('content-type') || '';
    if (ctype.includes('application/json')) {
      const data = await resp.json();
      const msg  = data.error || 'Error desconocido en el servidor.';
      showStepError(msg);
      error('Respuesta JSON de error:', data);
      return;
    }

    /* 3) Contenido HTML : insertar seguro */
    const html = await resp.text();
    stepContainer.innerHTML = html;
    log(`✓ Paso ${step} cargado y renderizado`);

    /* 4) Reinicializar scripts/icons específicos del paso */
    initialiseStepScripts(step);

  } catch (err) {
    /* 5) Falla de red, excepción JS, CORS, etc. */
    error('loadStep() atrapó excepción:', err);
    showStepError('No se pudo cargar el paso. Verificá tu conexión o recargá.');
  }
}

/* --------------------- VISUALIZACIÓN DE ERRORES EN DOM -------------------- */
/**
 * Muestra un alerta Bootstrap dentro del contenedor del wizard.
 * @param {string} message Texto del error.
 */
function showStepError(message) {
  const stepContainer = document.querySelector(STEP_CONTAINER_SELECTOR);
  if (!stepContainer) return;
  stepContainer.innerHTML =
    `<div class="alert alert-danger my-4" role="alert">
       ${message}
     </div>`;
}

/* ----------------- INICIALIZADORES ESPECÍFICOS POR PASO ------------------- */
/**
 * Re-inicializa iconos, sliders, charts, etc. después de inyectar la vista.
 * @param {number} step Paso recién cargado.
 */
function initialiseStepScripts(step) {
  /* Feather Icons */
  if (window.feather) requestAnimationFrame(() => window.feather.replace());

  /* Chart.js o sliders: agregá lo que necesites */
  switch (step) {
    case 6:
      if (typeof window.initStep6 === 'function') {
        window.initStep6(); // tu JS específico del Paso 6
      }
      break;
    // Otros pasos…
  }

  log(`→ Scripts inicializados para Step ${step}`);
}

/* ---------------------- API GLOBAL OPCIONAL ------------------------------- */
// Si querés exponer en window:
window.loadStep = loadStep;

/* ---------------------- AUTO-LOAD INICIAL (opcional) ---------------------- */
// Descomentá si querés cargar automáticamente el paso guardado en sesión.
// document.addEventListener('DOMContentLoaded', () => {
//   const initialStep = parseInt(document.body.dataset.initialStep || '1', 10);
//   loadStep(initialStep);
// });
