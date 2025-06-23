/*
 * File: wizard_stepper.js
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Improved to avoid conflicts with Step 6, unify script loading, and use async/await.
 */
/* global feather, bootstrap */
(() => {
  'use strict';

  // Configuración inicial
  const BASE_URL           = window.BASE_URL;
  const DEBUG              = window.DEBUG ?? false;
  const LS_KEY             = 'wizard_progress';
  const LOAD_ENDPOINT      = `${BASE_URL}/public/load-step.php`;
  const HANDLE_ENDPOINT    = `${BASE_URL}/public/handle-step.php`;
  const MAX_RETRY_INTERVAL = 5;

  // Utilidades de logging
  const TAG = '[WizardStepper]';
  const logger = (lvl, ...args) => {
    if (!DEBUG) return;
    console[lvl](`${TAG} ${new Date().toISOString()}:`, ...args);
  };
  const log = (...a) => logger('log', ...a);
  const warn = (...a) => logger('warn', ...a);
  const error = (...a) => logger('error', ...a);

  // Selectores
  const $qs  = s => document.querySelector(s);
  const $qsa = s => Array.from(document.querySelectorAll(s));

  // Paso y barra de progreso
  const stepsBar   = $qsa('.stepper li');
  const stepHolder = $qs('#step-content');
  if (!stepsBar.length || !stepHolder) {
    log('No es página de wizard – abortando.');
    return;
  }
  const MAX_STEPS = stepsBar.length;

  // Estado de progreso en localStorage
  const getProg = () => Number(localStorage.getItem(LS_KEY)) || 1;
  const setProg = v => localStorage.setItem(LS_KEY, v);

  // Carga de scripts externos (evita duplicados)
  const loadExternalScript = async src => {
    if (document.querySelector(`script[src="${src}"]`)) return;
    return new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = src;
      s.defer = true;
      s.onload = resolve;
      s.onerror = () => reject(new Error(`Failed to load ${src}`));
      document.head.appendChild(s);
    });
  };

  // Renderiza la barra de pasos con Feather Icons
  const renderBar = current => {
    const prog = getProg();
    stepsBar.forEach(li => {
      const n = parseInt(li.dataset.step, 10);
      li.classList.toggle('done',       n < prog);
      li.classList.toggle('active',     n === current);
      li.classList.toggle('clickable',  n <= prog - 1);
      const icon = n < prog ? 'check-circle' : (n === current ? 'circle' : '');
      li.innerHTML = `<span>${n}. ${li.dataset.label}</span>` +
                     (icon ? ` <i data-feather="${icon}"></i>` : '');
    });
    feather.replace();
  };

  // Ejecuta scripts inline retornados por AJAX (sin afectar Step 6.js)
  const runStepScripts = container => {
    log('runStepScripts', container);
    container.querySelectorAll('script').forEach(tag => {
      if (tag.src && !/step6\.js$/.test(tag.src)) {
        // Carga solo scripts que no sean step6.js
        loadExternalScript(tag.src).then(() => log('Loaded', tag.src)).catch(err => error(err));
      } else if (!tag.src) {
        // Inline script
        const inline = document.createElement('script');
        inline.textContent = tag.textContent;
        if (tag.type) inline.type = tag.type;
        if (tag.nonce) inline.nonce = tag.nonce;
        document.body.appendChild(inline).remove();
        log('Executed inline script');
      }
    });
  };

  // Carga de un paso vía AJAX, con manejo de Step 6 aislado
  const loadStep = async step => {
    log('loadStep', step);
    const prog = getProg();
    if (step < 1 || step > MAX_STEPS || step > prog + 1) {
      log('Salto bloqueado a', step);
      renderBar(prog);
      return;
    }

    stepHolder.style.opacity = '0.3';
    try {
      const res = await fetch(`${LOAD_ENDPOINT}?step=${step}${DEBUG ? '&debug=1' : ''}`, { cache: 'no-store' });
      if (res.status === 403) throw new Error('FORBIDDEN');
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const html = await res.text();

      // Inyecta contenido y scripts
      stepHolder.innerHTML = html;
      runStepScripts(stepHolder);
      if (window.feather) feather.replace();
      if (window.bootstrap?.Tooltip) {
        $qsa('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
      }

      // Aislamiento de Step 6: carga su JS solo aquí
      if (step === 6) {
        if (!window.step6Loaded) {
          await loadExternalScript(`${BASE_URL}/assets/js/step6.js`);
          window.step6Loaded = true;
        }
        window.initStep6?.();
      }

      stepHolder.style.opacity = '1';
      renderBar(step);
      hookEvents();
      window.initLazy?.();
      log('Paso cargado:', step);

    } catch (err) {
      error('Error loadStep', err);
      stepHolder.innerHTML = `<div class="alert alert-danger">⚠️ Error cargando paso ${step}: ${err.message}</div>`;
      if (err.message === 'FORBIDDEN') {
        localStorage.removeItem(LS_KEY);
        log('Sesión reiniciada.');
        renderBar(1);
        loadStep(1);
      }
    }
  };

  // Envía formulario del paso actual y avanza
  const sendForm = async form => {
    log('sendForm');
    const data = new FormData(form);
    try {
      const res = await fetch(`${HANDLE_ENDPOINT}${DEBUG ? '?debug=1' : ''}`, { method: 'POST', body: data });
      if (res.status === 403) throw new Error('FORBIDDEN');
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const json = await res.json();
      log('Response JSON', json);
      if (!json.success) {
        alert(json.error || 'Error al procesar');
        return;
      }
      const cur = parseInt(data.get('step'), 10);
      let next = typeof json.next === 'number' ? json.next : cur + 1;
      next = Math.min(next, MAX_STEPS);
      setProg(next);
      loadStep(next);
    } catch (err) {
      error('Error sendForm', err);
      if (err.message === 'FORBIDDEN') {
        localStorage.removeItem(LS_KEY);
        alert('Sesión expirada. Reinicio.');
        renderBar(1);
        loadStep(1);
      } else {
        alert('Fallo de conexión');
      }
    }
  };

  // Conexión de eventos de navegación y validación
  const hookEvents = () => {
    log('hookEvents');
    const form = stepHolder.querySelector('form');
    if (form) {
      form.addEventListener('submit', e => { e.preventDefault(); sendForm(form); });
      form.querySelectorAll('input,select,textarea').forEach(el =>
        el.addEventListener('input', () => {
          el.classList.toggle('is-valid', el.checkValidity());
          el.classList.toggle('is-invalid', !el.checkValidity());
        })
      );
      const prev = form.querySelector('.btn-prev');
      prev?.addEventListener('click', e => {
        e.preventDefault();
        const back = Math.max(1, getProg() - 1);
        setProg(back);
        loadStep(back);
      });
    }
    stepsBar.forEach(li => {
      if (li.classList.contains('clickable')) {
        li.addEventListener('click', () => {
          const n = parseInt(li.dataset.step, 10);
          if (n <= getProg()) loadStep(n);
        });
      }
    });
  };

  // Inicialización del wizard
  if (!localStorage.getItem(LS_KEY)) setProg(1);
  renderBar(getProg());
  loadStep(getProg());
})();
