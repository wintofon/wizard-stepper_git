/* global feather, bootstrap */
(() => {
  'use strict';

  const DEBUG = true;
  const LS_KEY = 'wizard_progress';
  const LOAD_ENDPOINT = 'public/load-step.php';
  const HANDLE_ENDPOINT = 'public/handle-step.php';

  const $qs  = sel => document.querySelector(sel);
  const $qsa = sel => [...document.querySelectorAll(sel)];
  const log  = (...args) => DEBUG && console.log('[Stepper]', ...args);
  const group = (title, fn) => {
    if (!DEBUG) return fn();
    console.group(title); try { fn(); } finally { console.groupEnd(); }
  };
  const dbgBox = $qs('#debug');
  const dbgMsg = txt => {
    if (!dbgBox) return;
    const ts = new Date().toLocaleTimeString();
    dbgBox.textContent = `[${ts}] ${txt}\n` + dbgBox.textContent;
  };

  const stepsBar   = $qsa('.stepper li');
  const stepHolder = $qs('#step-content');
  if (!stepsBar.length || !stepHolder) {
    log('No es página de wizard – abortando script.');
    return;
  }
  const MAX_STEPS = stepsBar.length;

  const getProg = () => Number(localStorage.getItem(LS_KEY)) || 1;
  const setProg = s => localStorage.setItem(LS_KEY, s);

  const renderBar = current => {
    const prog = getProg();
    stepsBar.forEach(li => {
      const n = Number(li.dataset.step);
      li.classList.toggle('done',      n < prog);
      li.classList.toggle('active',    n === current);
      li.classList.toggle('clickable', n <= prog - 1);
      li.innerHTML =
        `<span>${n}. ${li.dataset.label}</span>` +
        (n < prog ? ' ✅' : n === current ? ' 🟢' : '');
    });
  };

  /** Ejecuta scripts <script> embebidos en el HTML del paso (necesario para los AJAX). */
  const runStepScripts = container => {
    [...container.querySelectorAll('script')].forEach(tag => {
      if (tag.src) {
        // Scripts externos (sólo si no están cargados aún)
        if (!document.querySelector(`head script[src="${tag.src}"]`)) {
          const s = document.createElement('script');
          s.src = tag.src;
          s.defer = true;
          s.onload = () => log(`[stepper.js] Cargado: ${tag.src}`);
          s.onerror = () => console.error(`[stepper.js] ⚠️ Falló carga: ${tag.src}`);
          document.head.appendChild(s);
        }
      } else {
        // Scripts inline (vital para cada paso)
        try {
          const inlineScript = document.createElement('script');
          inlineScript.textContent = tag.textContent;
          document.body.appendChild(inlineScript).remove();
          log('[stepper.js] Ejecutado inline script');
        } catch (err) {
          console.warn('[stepper.js] Error ejecutando inline script', err);
        }
      }
    });
  };

  /** Carga por AJAX el paso y lo inyecta, ejecutando inicializadores de JS y dependencias */
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
        log('fetch status', r.status);
        if (r.status === 403) throw new Error('FORBIDDEN');
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return r.text();
      })
      .then(html => {
        stepHolder.innerHTML = html;
        runStepScripts(stepHolder);

        // Inicializadores JS globales (Feather, Bootstrap tooltips)
        if (window.feather) feather.replace();
        if (window.bootstrap && bootstrap.Tooltip) {
          document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
          });
        }

        // Script extra: si el paso 6 necesita su propio JS
        if (step === 6) {
          if (!window.step6Loaded) {
            const script = document.createElement('script');
            script.src = '/wizard-stepper_git/assets/js/step6.js';
            script.defer = true;
            script.onload = () => { 
              window.step6Loaded = true;
              log('[stepper.js] 🔢 step6.js cargado OK');
              if (typeof window.initStep6 === 'function') window.initStep6();
            };
            script.onerror = () => console.error('[stepper.js] ⚠️ Error cargando step6.js');
            document.body.appendChild(script);
          } else {
            if (typeof window.initStep6 === 'function') window.initStep6();
          }
        }

        stepHolder.style.opacity = '1';
        renderBar(step);
        hookEvents();
        dbgMsg(`🧭 Paso ${step} cargado correctamente`);
      })
      .catch(err => {
        log('Error loadStep', err);
        stepHolder.innerHTML =
          `<div class="alert alert-danger">⚠️ Error cargando el paso ${step}: ${err.message}</div>`;
        dbgMsg(err.message);
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
        log('handle-step status', r.status);
        if (r.status === 403) throw new Error('FORBIDDEN');
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return r.json();
      })
      .then(js => {
        if (!js.success) {
          alert(js.error || 'Error al procesar');
          return;
        }
        const next = js.next ?? cur + 1;
        setProg(next);
        loadStep(next);
      })
      .catch(err => {
        log('Error sendForm', err);
        if (err.message === 'FORBIDDEN') {
          localStorage.removeItem(LS_KEY);
          alert('Sesión expirada. Reinicio.');
          renderBar(1);
          loadStep(1);
        } else {
          alert('Fallo de conexión');
          dbgMsg(err.message);
        }
      });
  });

  const hookEvents = () => {
    const form = stepHolder.querySelector('form');
    if (form) {
      form.addEventListener('submit', e => {
        e.preventDefault(); sendForm(form);
      });
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
      };
    }

    stepsBar.forEach(li => {
      if (!li.classList.contains('clickable')) return;
      li.onclick = () => {
        const n = Number(li.dataset.step);
        if (n <= getProg()) loadStep(n);
      };
    });
  };

  // INICIALIZACIÓN
  if (!localStorage.getItem(LS_KEY)) localStorage.setItem(LS_KEY, 1);
  renderBar(getProg());
  loadStep(getProg());

})();
