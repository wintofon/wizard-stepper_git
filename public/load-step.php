/* ============================================================================
 * File: wizard_stepper.js
 * ---------------------------------------------------------------------------
 * Función loadStep(step) – Cargador ROBUSTO, blindado y anti-errores
 * ---------------------------------------------------------------------------
 * • Carga asincrónica de cada vista del wizard (fetch → load-step.php)
 * • Diferencia respuestas HTML (vista válida) vs JSON (error) sin romper DOM
 * • Trazas de consola con estilo (+DEBUG toggle desde PHP → window.DEBUG)
 * • Helpers: showStepError() e initialiseStepScripts()
 * • Compatible con Feather Icons, Chart.js, sliders… 100 % ES2021 – sin jQuery
 * ========================================================================= */

/* --------------------------- CONFIGURACIÓN -------------------------------- */
const STEP_CONTAINER_SELECTOR = '#wizard-step';        // wrapper principal
const BASE_URL                = window.BASE_URL || ''; // inyectado por PHP
const DEBUG                   = window.DEBUG ?? true;  // toggle desde PHP

/* --------------------------- HELPERS DE LOG -------------------------------- */
const LOG_STYLE = 'color:#4caf50;font-weight:bold';
const ERR_STYLE = 'color:#f44336;font-weight:bold';

function log(...a)   { DEBUG && console.log('%c[Wizard]', LOG_STYLE, ...a); }
function error(...a) { console.error('%c[Wizard]', ERR_STYLE, ...a); }

/* --------------------------- FUNCIÓN PRINCIPAL ----------------------------- */
/**
 * Carga dinámica de un paso del wizard y reemplaza el contenedor.
 * @param  {number} step  Valor entre 1 y 6 inclusive.
 * @return {Promise<void>}
 */
export async function loadStep(step = 1) {            // ← Si NO usás ES modules,
                                                      //   quitá la palabra export
  /* ---------- 0. Sanity check ------------------------------------------------ */
  if (step < 1 || step > 6) {
    error('Paso inválido:', step);
    showStepError('Paso solicitado fuera de rango (1-6).');
    return;
  }

  const stepContainer = document.querySelector(STEP_CONTAINER_SELECTOR);
  if (!stepContainer) {                     // Sin contenedor ⇢ abortar elegante
    error('No existe el contenedor', STEP_CONTAINER_SELECTOR);
    return;
  }

  /* ---------- 1. Fetch ------------------------------------------------------- */
  const url  = `${BASE_URL}/load-step.php?step=${step}`;
  const opts = {
    method      : 'GET',
    credentials : 'same-origin',            // envía cookies de sesión
    headers     : {
      'Accept'           : 'text/html, application/json',
      'X-Requested-With' : 'fetch'
    }
  };

  log(`→ solicitando Paso ${step}…`, url);

  try {
    const resp = await fetch(url, opts);

    /* 1.1 Errores HTTP crudos (404, 500…) */
    if (!resp.ok) {
      throw new Error(`HTTP ${resp.status} – ${resp.statusText}`);
    }

    /* 1.2 Diferenciar JSON (error) vs HTML (vista) */
    const ctype = resp.headers.get('content-type') || '';
    if (ctype.includes('application/json')) {
      const data = await resp.json();
      const msg  = data.error || 'Error desconocido en el servidor.';
      showStepError(msg);
      error('Respuesta JSON de error:', data);
      return;
    }

    /* 1.3 Contenido HTML → insertar seguro */
    const html = await resp.text();
    stepContainer.innerHTML = html;         // nunca genera nodos <html> extra
    log(`✓ Paso ${step} cargado y renderizado`);

    /* 1.4 Reinicializar scripts/icons específicos del paso */
    initialiseStepScripts(step);

  } catch (err) {
    /* 1.5 Falla de red, excepción JS, CORS, etc. */
    error('loadStep() atrapó excepción:', err);
    showStepError('No se pudo cargar el paso. Verificá tu conexión o recargá.');
  }
}

/* --------------------- VISUALIZACIÓN DE ERRORES EN DOM -------------------- */
/**
 * Muestra un alerta Bootstrap dentro del contenedor del wizard.
 * @param {string} message  Texto del error.
 */
function showStepError(message) {
  const stepContainer = document.querySelector(STEP_CONTAINER_SELECTOR);
  if (!stepContainer) return;

  stepContainer.innerHTML = `
    <div class="alert alert-danger my-4" role="alert">
      ${message}
    </div>`;
}

/* ----------------- INICIALIZADORES ESPECÍFICOS POR PASO ------------------- */
/**
 * Re-inicializa iconos, sliders, charts… después de inyectar la vista.
 * @param {number} step  Paso recién cargado.
 */
function initialiseStepScripts(step) {
  /* Feather Icons */
  if (window.feather) {
    requestAnimationFrame(() => window.feather.replace());
  }

  /* Chart.js, sliders u otros: agregá lo que necesites */
  switch (step) {
    case 6:
      if (typeof window.initStep6 === 'function') {
        window.initStep6();                 // tu JS específico del Paso 6
      }
      break;
    // case 5: … etc.
  }

  log(`→ Scripts inicializados para Step ${step}`);
}

/* ---------------------- API GLOBAL OPCIONAL ------------------------------- */
window.loadStep = loadStep;                 // disponible en consola

/* ---------------------- AUTO-LOAD INICIAL (activo) ------------------------ */
/* ▸ Lee data-initial-step del <body> (lo setea wizard.php), default = 1.
 * ▸ Permite que al refrescar la página el usuario retome donde quedó.
 */
document.addEventListener('DOMContentLoaded', () => {
  const initialStepAttr = document.body.dataset.initialStep || '1';
  const initialStep     = parseInt(initialStepAttr, 10);
  log(`🌐 Auto-load inicial: paso ${initialStep}`);
  loadStep(initialStep);
});
