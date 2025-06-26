/*
 * File: wizard_stepper.js
 * Descripción: Controlador principal del Wizard CNC (modo Stepper).
 * Versión blindada: evita errores de DOM, controla carga de scripts y eventos JS.
 */

/* global feather, bootstrap */
(() => {
  'use strict';

  const BASE_URL     = window.BASE_URL;
  const DEBUG        = window.DEBUG ?? false;
  const LS_KEY       = 'wizard_progress';
  const LOAD_ENDPOINT   = `${BASE_URL}/public/load-step.php`;
  const HANDLE_ENDPOINT = `${BASE_URL}/public/handle-step.php`;

  const TAG = '[WizardStepper]';
  const $qs  = sel => document.querySelector(sel);
  const $qsa = sel => [...document.querySelectorAll(sel)];
  const logger = (lvl, ...a) => { if (DEBUG) console[lvl](`${TAG} ${new Date().toISOString()}`, ...a); };
  const log    = (...a) => logger('log', ...a);
  const warn   = (...a) => logger('warn', ...a);
  const error  = (...a) => logger('error', ...a);
  const table  = data => { if (DEBUG) console.table(data); };
  const group  = (title, fn) => { if (!DEBUG) return fn(); console.group(`${TAG} ${new Date().toISOString()} ${title}`); try { return fn(); } finally { console.groupEnd(); } };

  const stepsBar   = $qsa('.stepper li');
  const stepHolder = $qs('#step-content');
  const dbgBox     = $qs('#debug');
  const dbgMsg     = txt => {
    if (!dbgBox) return;
    const ts = new Date().toLocaleTimeString();
    dbgBox.textContent = `[${ts}] ${txt}\n` + dbgBox.textContent;
  };

  if (!stepsBar.length || !stepHolder) {
    log('No es página de wizard – abortando script.');
    return;
  }

  const MAX_STEPS = stepsBar.length;
  const getProg = () => Number(localStorage.getItem(LS_KEY)) || 1;
  const setProg = s => localStorage.setItem(LS_KEY, s);

const renderBar = current => {
  const prog = getProg();               // paso más alto alcanzado

  stepsBar.forEach(li => {
    const n = Number(li.dataset.step);

    // ­­­­­­­­­­­­­­­­­­­­­­­­­­­­­ clases de estado
    li.classList.toggle('done',      n < prog);
    li.classList.toggle('active',    n === current);
    li.classList.toggle('clickable', n <= prog - 1);

    // ­­­­­­­­­­­­­­­­­­­­­­­­­­­­­ icono según estado
    let icon = '';
    if (n < prog)         icon = '<i data-feather="check-circle"></i>';   // completado
    else if (n === current) icon = '<i data-feather="zap"></i>';          // activo
    else                  icon = '<i data-feather="circle"></i>';        // pendiente

    // ­­­­­­­­­­­­­­­­­­­­­­­­­­­­­ contenido final
    li.innerHTML = `<span>${n}. ${li.dataset.label}</span> ${icon}`;
  });

  /* IMPORTANTE: tras modificar el DOM, refrescamos Feather */
  feather.replace({ class: 'feather' });
};


  const runStepScripts = container => group('runStepScripts', () => {
    if (container.querySelector('html, head, body')) {
      error('❌ DOM inválido: se encontraron etiquetas <html>, <head> o <body> embebidas.');
      dbgMsg('❌ Error crítico: el paso contiene etiquetas duplicadas.');
      return;
    }
    [...container.querySelectorAll('script')].forEach(tag => {
      if (tag.src) {
        if (!document.querySelector(`head script[src="${tag.src}"]`)) {
          const s = document.createElement('script');
          s.src = tag.src;
          if (tag.type) s.type = tag.type;
          if (tag.nonce) s.nonce = tag.nonce;
          s.defer = true;
          s.onload = () => log(`[stepper.js] Cargado: ${tag.src}`);
          s.onerror = () => error(`⚠️ Falló carga: ${tag.src}`);
          document.head.appendChild(s);
        }
      } else {
        try {
          const inlineScript = document.createElement('script');
          if (tag.type) inlineScript.type = tag.type;
          if (tag.nonce) inlineScript.nonce = tag.nonce;
          inlineScript.textContent = tag.textContent;
          document.body.appendChild(inlineScript).remove();
          log('[stepper.js] Ejecutado inline script');
        } catch (err) {
          warn('Error ejecutando inline script', err);
        }
      }
    });
  });

  const loadStep = step => group(`loadStep(${step})`, () => {
    const prog = getProg();
    if (step < 1 || step > MAX_STEPS || step > prog + 1) {
      dbgMsg('🔒 Salto bloqueado');
      renderBar(prog);
      return;
    }

    stepHolder.style.opacity = '.3';
    fetch(`${LOAD_ENDPOINT}?step=${step}${DEBUG ? '&debug=1' : ''}`, { cache: 'no-store' })
      .then(r => {
        if (r.status === 403) throw new Error('FORBIDDEN');
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return r.text();
      })
      .then(html => {
        stepHolder.innerHTML = html;
        runStepScripts(stepHolder);

        requestAnimationFrame(() => {
          if (window.feather) feather.replace();
        });

        if (window.bootstrap && bootstrap.Tooltip) {
          document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
        }

        stepHolder.style.opacity = '1';
        renderBar(step);
        hookEvents();
        window.initLazy?.();
        dbgMsg(`🧭 Paso ${step} cargado correctamente`);
      })
      .catch(err => {
        error('Error loadStep', err);
        dbgMsg(err.message);
        stepHolder.innerHTML = `<div class="alert alert-danger">⚠️ Error cargando el paso ${step}: ${err.message}</div>`;
        if (err.message === 'FORBIDDEN') {
          localStorage.removeItem(LS_KEY);
          dbgMsg('⚠️ Sesión desfasada. Reinicio.');
          renderBar(1);
          loadStep(1);
        }
      });
  });

  const sendForm = form => group('sendForm', () => {
    const data = new FormData(form);
    const cur = Number(data.get('step'));
    fetch(`${HANDLE_ENDPOINT}${DEBUG ? '?debug=1' : ''}`, { method: 'POST', body: data })
      .then(r => {
        if (r.status === 403) throw new Error('FORBIDDEN');
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return r.json();
      })
      .then(js => {
        table(js);
        if (!js.success) return alert(js.error || 'Error al procesar');
        let next = typeof js.next === 'number' ? js.next : cur + 1;
        if (next > MAX_STEPS) next = MAX_STEPS;
        setProg(next);
        loadStep(next);
        dbgMsg(`✔ Paso ${cur} enviado. Siguiente: ${next}`);
      })
      .catch(err => {
        error('Error sendForm', err);
        dbgMsg(err.message);
        if (err.message === 'FORBIDDEN') {
          localStorage.removeItem(LS_KEY);
          alert('Sesión expirada. Reinicio.');
          renderBar(1);
          loadStep(1);
        } else {
          alert('Fallo de conexión');
        }
      });
  });

  const hookEvents = () => group('hookEvents', () => {
    const form = stepHolder.querySelector('form');
    if (form) {
      form.addEventListener('submit', e => { e.preventDefault(); sendForm(form); });
      form.querySelectorAll('input,select,textarea').forEach(el =>
        el.addEventListener('input', () => {
          el.classList.toggle('is-valid', el.checkValidity());
          el.classList.toggle('is-invalid', !el.checkValidity());
        })
      );
      const prevBtn = form.querySelector('.btn-prev');
      if (prevBtn) prevBtn.onclick = e => {
        e.preventDefault();
        const back = Math.max(1, getProg() - 1);
        setProg(back);
        loadStep(back);
        dbgMsg(`⬅️ Volver al paso ${back}`);
      };
    }
    stepsBar.forEach(li => {
      if (!li.classList.contains('clickable')) return;
      li.onclick = () => {
        const n = Number(li.dataset.step);
        if (n <= getProg()) {
          dbgMsg(`🔎 Salto al paso ${n}`);
          loadStep(n);
        }
      };
    });
  });

  if (!localStorage.getItem(LS_KEY)) localStorage.setItem(LS_KEY, 1);
  renderBar(getProg());
  loadStep(getProg());

})();
