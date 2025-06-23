/*
 * File: select_mode.js
 * Epic CNC Wizard Mode Selector – versión estelar 🌟
 *
 * Responsabilidad principal:
 *   Resaltar la opción elegida, enviar el formulario con estilo épico
 *   y narrar cada paso en la consola con dramatismo.
 * Related files: wizard.php?state=mode
 * TODO: Añadir fanfarrias y confeti en el DOM.
 */

(() => {
  'use strict';

  const TAG_STYLE = 'color:#673AB7;font-weight:bold';
  const group = (title, fn) => {
    console.group(`%c[ModeSelector✨] ${title}`, TAG_STYLE);
    try { return fn(); } finally { console.groupEnd(); }
  };
  const log  = (...args) => console.log('%c[ModeSelector🚀]', TAG_STYLE, ...args);
  const warn = (...args) => console.warn('%c[ModeSelector⚠️]', TAG_STYLE, ...args);
  const error= (...args) => console.error('%c[ModeSelector💥]', TAG_STYLE, ...args);

  document.addEventListener('DOMContentLoaded', () => {
    group('DOMReady', () => {
      log('DOM cargado: inicializando modo épico');
      const form = document.querySelector('.wizard-welcome form');
      if (!form) {
        error('❌ Formulario de selección no encontrado.');
        return;
      }
      log('Formulario encontrado', form);

      const options = Array.from(form.querySelectorAll('.mode-option'));
      if (!options.length) {
        warn('⚠️ No se hallaron opciones de modo.');
        return;
      }
      log(`Se encontraron ${options.length} opciones`);

      options.forEach(option => {
        option.style.cursor = 'pointer';
        option.addEventListener('click', () => {
          group(`OptionClick - ${option.dataset.mode || option.textContent.trim()}`, () => {
            log('Opción seleccionada:', option);
            options.forEach(o => {
              const isActive = (o === option);
              o.classList.toggle('selected', isActive);
              if (isActive) log(`▷ Modo activo: ${o.dataset.mode || o.textContent.trim()}`);
            });
            const input = option.querySelector('input[type="radio"]');
            if (input) {
              input.checked = true;
              log('Radio checked:', input.value);
            } else {
              warn('⚠️ No se halló input[type="radio"] en la opción');
            }
            log('Enviando formulario en 150ms...');
            setTimeout(() => {
              log('🔥 Enviando formulario ahora.');
              table: console.table({ mode: input?.value || null });
              form.submit();
            }, 150);
          });
        });
      });
    });
  });
})();
