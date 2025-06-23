/*
 * File: wizard_stepper.js
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * TODO: Extend documentation.
 */
/* global feather, bootstrap */
(() => {
  'use strict';

  const BASE_URL        = window.BASE_URL;
  const DEBUG           = window.DEBUG ?? false;
  const LS_KEY          = 'wizard_progress';
  const LOAD_ENDPOINT   = `${BASE_URL}/public/load-step.php`;
  const HANDLE_ENDPOINT = `${BASE_URL}/public/handle-step.php`;

  const TAG   = '[WizardStepper]';
  const log   = (...a) => { if (DEBUG) console.log(TAG, ...a); };
  const warn  = (...a) => { if (DEBUG) console.warn(TAG, ...a); };
  const error = (...a) => { if (DEBUG) console.error(TAG, ...a); };
  const table = data => { if (DEBUG) console.table(data); };

  const $qs  = sel => document.querySelector(sel);
  const $qsa = sel => Array.from(document.querySelectorAll(sel));

  const stepsBar   = $qsa('.stepper li');
  const stepHolder = $qs('#step-content');
  if (!stepsBar.length || !stepHolder) {
    log('No es página de wizard – abortando script.');
    return;
  }

  const MAX_STEPS = stepsBar.length;

  const getProg = () => Number(localStorage.getItem(LS_KEY)) || 1;
  const setProg = s => localStorage.setItem(LS_KEY, s);

  function renderBar(current) {
    const prog = getProg();
    stepsBar.forEach(li => {
      const n = Number(li.dataset.step);
      li.classList.toggle('done',      n < prog);
      li.classList.toggle('active',    n === current);
      li.classList.toggle('clickable', n <= prog - 1);
      const icon = n < prog ? 'check-circle'
                   : (n === current ? 'circle' : '');
      li.innerHTML = `<span>${n}. ${li.dataset.label}</span>` +
                     (icon ? ` <i data-feather="${icon}"></i>` : '');
    });
    feather.replace();
  }

  function runStepScripts(container) {
    log('runStepScripts');
    container.querySelectorAll('script').forEach(tag => {
      // No recargar el JS de Step 6 aquí
      if (tag.src && !tag.src.endsWith('step6.js')) {
        if (!document.querySelector(`head script[src="${tag.src}"]`)) {
          const s = document.createElement('script');
          s.src = tag.src;
          if (tag.type)  s.type = tag.type;
          if (tag.nonce) s.nonce = tag.nonce;
          s.defer = true;
          s.onload  = () => log('Script cargado:', tag.src);
          s.onerror = () => error('Error cargando:', tag.src);
          document.head.appendChild(s);
        }
      } else if (!tag.src) {
        // Inline script
        const inline = document.createElement('script');
        if (tag.type)  inline.type = tag.type;
        if (tag.nonce) inline.nonce = tag.nonce;
        inline.textContent = tag.textContent;
        document.body.appendChild(inline).remove();
        log('Inline script ejecutado');
      }
    });
  }

  function loadStep(step) {
    log('loadStep', step);
    const prog = getProg();
    if (step < 1 || step > MAX_STEPS || step > prog + 1) {
      log('Salto bloqueado a', step);
      renderBar(prog);
      return;
    }

    stepHolder.style.opacity = '0.3';
    fetch(`${LOAD_ENDPOINT}?step=${step}${DEBUG ? '&debug=1' : ''}`, { cache: 'no-store' })
      .then(r => {
        log('Fetch status', r.status);
        if (r.status === 403) throw new Error('FORBIDDEN');
        if (!r.ok)       throw new Error(`HTTP ${r.status}`);
        return r.text();
      })
      .then(html => {
        stepHolder.innerHTML = html;
        runStepScripts(stepHolder);

        // Inicializar Feather y tooltips
        feather.replace();
        if (window.bootstrap && bootstrap.Tooltip) {
          $qsa('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
          });
        }

        // Cargar JS exclusivo de Step 6
        if (step === 6) {
          if (!window.step6Loaded) {
            const s6 = document.createElement('script');
            s6.src   = `${BASE_URL}/assets/js/step6.js`;
            s6.defer = true;
            s6.onload = () => {
              window.step6Loaded = true;
              log('step6.js cargado');
              if (typeof window.initStep6 === 'function') window.initStep6();
            };
            s6.onerror = () => error('Error cargando step6.js');
            document.body.appendChild(s6);
          } else if (typeof window.initStep6 === 'function') {
            window.initStep6();
          }
        }

        stepHolder.style.opacity = '1';
        renderBar(step);
        hookEvents();
        if (typeof window.initLazy === 'function') window.initLazy();
        log('Paso cargado:', step);
      })
      .catch(err => {
        error('Error loadStep', err);
        stepHolder.innerHTML =
          `<div class="alert alert-danger">⚠️ Error cargando paso ${step}: ${err.message}</div>`;
        if (err.message === 'FORBIDDEN') {
          localStorage.removeItem(LS_KEY);
          log('Sesión reiniciada');
          renderBar(1);
          loadStep(1);
        }
      });
  }

  function sendForm(form) {
    log('sendForm');
    const data = new FormData(form);
    const cur  = Number(data.get('step'));

    fetch(`${HANDLE_ENDPOINT}${DEBUG ? '?debug=1' : ''}`, { method: 'POST', body: data })
      .then(r => {
        log('handle-step status', r.status);
        if (r.status === 403) throw new Error('FORBIDDEN');
        if (!r.ok)       throw new Error(`HTTP ${r.status}`);
        return r.json();
      })
      .then(js => {
        table(js);
        if (!js.success) {
          alert(js.error || 'Error al procesar');
          return;
        }
        let next = typeof js.next === 'number' ? js.next : cur + 1;
        next = Math.min(next, MAX_STEPS);
        setProg(next);
        loadStep(next);
      })
      .catch(err => {
        error('Error sendForm', err);
        if (err.message === 'FORBIDDEN') {
          localStorage.removeItem(LS_KEY);
          alert('Sesión expirada. Reinicio.');
          renderBar(1);
          loadStep(1);
        } else {
          alert('Fallo de conexión');
        }
      });
  }

  function hookEvents() {
    log('hookEvents');
    const form = stepHolder.querySelector('form');
    if (form) {
      form.addEventListener('submit', e => { e.preventDefault(); sendForm(form); });
      form.querySelectorAll('input,select,textarea').forEach(el =>
        el.addEventListener('input', () => {
          el.classList.toggle('is-valid',   el.checkValidity());
          el.classList.toggle('is-invalid', !el.checkValidity());
        })
      );
      const prevBtn = form.querySelector('.btn-prev');
      if (prevBtn) {
        prevBtn.addEventListener('click', e => {
          e.preventDefault();
          const back = Math.max(1, getProg() - 1);
          setProg(back);
          loadStep(back);
        });
      }
    }
    stepsBar.forEach(li => {
      if (li.classList.contains('clickable')) {
        li.addEventListener('click', () => {
          const n = Number(li.dataset.step);
          if (n <= getProg()) loadStep(n);
        });
      }
    });
  }

  // Inicialización
  if (!localStorage.getItem(LS_KEY)) setProg(1);
  renderBar(getProg());
  loadStep(getProg());
})();
