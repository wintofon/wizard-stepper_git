/*
 * File: wizard_stepper.js
 * Epic CNC Wizard Stepper – version 3.0
 *
 * ¡Este script es una oda a la narración épica! Cada acción, cada paso
 * y cada evento se reflejan orgullosamente en la consola para tu deleite.
 * TODO: Extender documentación con diagramas UML y poesía inspiracional.
 */
/* global feather, bootstrap */
(() => {
  'use strict';

  // ===================== CONFIGURACIÓN =====================
  const BASE_URL        = window.BASE_URL;
  const DEBUG           = window.DEBUG ?? true; // Modo épico: siempre enciende logs
  const LS_KEY          = 'wizard_progress';
  const LOAD_ENDPOINT   = `${BASE_URL}/public/load-step.php`;
  const HANDLE_ENDPOINT = `${BASE_URL}/public/handle-step.php`;

  // ===================== UTILIDADES =====================
  const TAG = '%c[WizardStepper⚙️]%c';
  const log   = (...args) => console.log(TAG, 'color:#4caf50;font-weight:bold', '', ...args);
  const warn  = (...args) => console.warn(TAG, 'color:#ff9800;font-weight:bold', '', ...args);
  const error = (...args) => console.error(TAG, 'color:#f44336;font-weight:bold', '', ...args);
  const table = data => console.table(data);
  const group = (title, fn) => {
    console.group(`${TAG[0]} ${title}`);
    try { return fn(); }
    finally { console.groupEnd(); }
  };

  // =================== SELECTORES ======================
  const $qs  = sel => document.querySelector(sel);
  const $qsa = sel => Array.from(document.querySelectorAll(sel));

  const stepsBar   = $qsa('.stepper li');
  const stepHolder = $qs('#step-content');
  if (!stepsBar.length || !stepHolder) {
    warn('⛔ No es una página de wizard – abortando épicamente.');
    return;
  }
  const MAX_STEPS = stepsBar.length;

  // ================ PROGRESO LOCALSTORAGE =============
  const getProg = () => Number(localStorage.getItem(LS_KEY)) || 1;
  const setProg = s => {
    localStorage.setItem(LS_KEY, s);
    log(`📥 Progreso guardado: paso ${s}`);
  };

  // ================= BAR RENDER =======================
  function renderBar(current) {
    group('renderBar', () => {
      const prog = getProg();
      log(`🔢 Renderizando barra (actual: ${current}, guardado: ${prog})`);
      stepsBar.forEach(li => {
        const n = +li.dataset.step;
        const done     = n < prog;
        const active   = n === current;
        const clickable= n <= prog - 1;
        li.classList.toggle('done', done);
        li.classList.toggle('active', active);
        li.classList.toggle('clickable', clickable);
        const icon = done ? 'check-circle' : (active ? 'circle' : 'minus-circle');
        li.innerHTML = `<span>${n}. ${li.dataset.label}</span> <i data-feather="${icon}"></i>`;
        log(`  · Paso ${n}: done=${done}, active=${active}, clickable=${clickable}`);
      });
      feather.replace();
    });
  }

  // ================= SCRIPT LOADER ====================
  function runStepScripts(container) {
    group('runStepScripts', () => {
      log('Buscando <script> internos y externos…');
      container.querySelectorAll('script').forEach(tag => {
        if (tag.src) {
          const src = tag.src;
          if (src.endsWith('step6.js')) {
            warn('🔒 Evitando recarga de step6.js');
            return;
          }
          if (!document.querySelector(`script[src="${src}"]`)) {
            log(`🔗 Cargando script: ${src}`);
            const s = document.createElement('script');
            s.src = src; s.defer = true;
            document.head.appendChild(s);
          } else {
            log(`✔️ Script ya cargado: ${src}`);
          }
        } else {
          log('✍️ Ejecutando script inline');
          const inline = document.createElement('script');
          inline.textContent = tag.textContent;
          document.body.appendChild(inline).remove();
        }
      });
    });
  }

  // ================ CARGAR PASO =======================
  function loadStep(step) {
    group(`loadStep(${step})`, () => {
      const prog = getProg();
      log(`Intentando cargar paso ${step} (prog: ${prog})`);
      if (step < 1 || step > MAX_STEPS || step > prog + 1) {
        warn('🚧 Salto de paso bloqueado.');
        renderBar(prog);
        return;
      }
      stepHolder.style.opacity = '0.3';

      fetch(`${LOAD_ENDPOINT}?step=${step}${DEBUG? '&debug=1':''}`, { cache: 'no-store' })
        .then(r => {
          log(`HTTP ${r.status} recibido`);
          if (!r.ok) throw new Error(r.status === 403 ? 'FORBIDDEN' : `HTTP ${r.status}`);
          return r.text();
        })
        .then(html => {
          log('🎨 Inyectando contenido HTML…');
          stepHolder.innerHTML = html;
          runStepScripts(stepHolder);
          feather.replace();
          if (window.bootstrap?.Tooltip) {
            $qsa('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
          }
          if (step === 6) {
            log('🔢 Paso 6 detectado: cargando sus artificios…');
            if (!window.step6Loaded) {
              const s6 = document.createElement('script');
              s6.src = `${BASE_URL}/assets/js/step6.js`; s6.defer = true;
              s6.onload = () => { window.step6Loaded = true; log('✅ step6.js cargado'); window.initStep6?.(); };
              document.body.appendChild(s6);
            } else {
              log('♻️ Re-inicializando Step6'); window.initStep6?.();
            }
          }
          stepHolder.style.opacity = '1';
          renderBar(step);
          hookEvents();
          window.initLazy?.();
          log(`🏁 Paso ${step} cargado con gloria.`);
        })
        .catch(err => {
          error('💥 Error loadStep', err);
          stepHolder.innerHTML = `<div class="alert alert-danger">⚠️ ${err.message}</div>`;
          if (err.message === 'FORBIDDEN') {
            localStorage.removeItem(LS_KEY);
            warn('🔄 Sesión expirada: reiniciando wizard.');
            setProg(1); loadStep(1);
          }
        });
    });
  }

  // =============== ENVIAR FORMULARIO ==================
  function sendForm(form) {
    group('sendForm', () => {
      const data = new FormData(form);
      const cur = +data.get('step');
      log(`✉️ Enviando datos de paso ${cur}…`);
      fetch(`${HANDLE_ENDPOINT}${DEBUG? '?debug=1':''}`, { method: 'POST', body: data })
        .then(r => { if (!r.ok) throw new Error(r.status===403?'FORBIDDEN':`HTTP ${r.status}`); return r.json(); })
        .then(js => {
          table(js);
          if (!js.success) { alert(js.error||'Error'); return; }
          const next = Math.min(js.next||cur+1, MAX_STEPS);
          setProg(next); loadStep(next);
          log(`➡️ Avanzando al paso ${next}`);
        })
        .catch(err => {
          error('💥 Error sendForm', err);
          if (err.message==='FORBIDDEN') { localStorage.removeItem(LS_KEY); alert('Expirado'); setProg(1); loadStep(1); }
          else alert('Conexión fallida');
        });
    });
  }

  // =============== EVENTOS ============================
  function hookEvents() {
    group('hookEvents', () => {
      log('🔗 Conectando eventos…');
      const form = stepHolder.querySelector('form');
      if (form) {
        form.addEventListener('submit', e => { e.preventDefault(); sendForm(form); });
        $qsa('input,select,textarea', form).forEach(el =>
          el.addEventListener('input', () => {
            el.classList.toggle('is-valid', el.checkValidity());
            el.classList.toggle('is-invalid', !el.checkValidity());
          })
        );
        form.querySelector('.btn-prev')?.addEventListener('click', e => {
          e.preventDefault(); const back = Math.max(1,getProg()-1); setProg(back); loadStep(back);
        });
      }
      stepsBar.forEach(li => {
        if (li.classList.contains('clickable')) li.addEventListener('click', () => loadStep(+li.dataset.step));
      });
    });
  }

  // ================= INICIALIZACIÓN ====================
  log('🚀 Iniciando CNC Wizard Epico…');
  if (!localStorage.getItem(LS_KEY)) setProg(1);
  renderBar(getProg());
  loadStep(getProg());
})();
