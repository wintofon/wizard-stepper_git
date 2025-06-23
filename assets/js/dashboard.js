/*
 * File: dashboard.js
 * Epic CNC Wizard Dashboard – versión gloriosa ⚡
 *
 * Main responsibility:
 *   Consultar el estado de sesión vía AJAX, procesar JSON 'casi-JSON'
 *   y mostrar snapshots en la consola con estilo épico.
 * Related files: session-api.php
 * TODO: Añadir efectos de sonido y fuegos artificiales.
 */
/* global BASE_URL, window */
(() => {
  'use strict';

  // ================= CONFIGURACIÓN ===================
  const DEBUG      = window.DEBUG ?? true;
  const BASE       = window.BASE_URL;
  const MAX_ERR    = 5;
  const TAG_STYLE  = 'color:#009688;font-weight:bold';
  let errorCount   = 0;
  let lastSnapshot = null;

  // =================== LOGGING =======================
  function log(...args)   { console.log('%c[Dashboard🌐]', TAG_STYLE, ...args); }
  function warn(...args)  { console.warn('%c[Dashboard⚠️]', TAG_STYLE, ...args); }
  function error(...args) { console.error('%c[Dashboard💥]', TAG_STYLE, ...args); }
  function table(data)    { console.table(data); }
  function group(title, fn) {
    console.group(`%c[Dashboard⏳] ${title}`, TAG_STYLE);
    try { return fn(); }
    finally { console.groupEnd(); }
  }

  // ================== FETCH SESSION ==================
  async function fetchSession() {
    return group('fetchSession', async () => {
      log('Iniciando fetch de sesión…');
      if (errorCount >= MAX_ERR) {
        warn(`Se alcanzó el máximo de ${MAX_ERR} errores. Pausando fetch.`);
        return;
      }

      try {
        const headers = window.csrfToken ? { 'X-CSRF-Token': window.csrfToken } : {};
        const response = await fetch(`${BASE}/public/session-api.php?debug=1`, {
          cache: 'no-store',
          headers
        });
        log(`Respuesta HTTP ${response.status}`);

        const contentType = response.headers.get('Content-Type') || '';
        if (!response.ok || !contentType.includes('application/json')) {
          throw new Error(`HTTP ${response.status} – Content-Type “${contentType}”`);
        }

        // Leer y limpiar texto
        let text = await response.text();
        text = text.trimStart();
        const braceIndex = text.search(/[{[]/);
        if (braceIndex > 0) {
          log('Eliminando basura antes del JSON…');
          text = text.slice(braceIndex);
        }
        if (!text.startsWith('{') && !text.startsWith('[')) {
          throw new Error('Payload no parece JSON');
        }

        const data = JSON.parse(text);
        log('Snapshot válido recibido');
        table(data);
        lastSnapshot = data;
        errorCount = 0;
      } catch (ex) {
        warn('Error al parsear session-api:', ex.message);
        errorCount++;
        const hint = `Fallo ${errorCount}/${MAX_ERR}: ${ex.message}`;
        if (lastSnapshot) {
          warn('▶️ Re-renderizando último snapshot válido');
          table(lastSnapshot);
          warn(hint);
        } else {
          warn(hint);
        }
      }
    });
  }

  // ================= INICIALIZACIÓN ==================
  document.addEventListener('DOMContentLoaded', () => {
    log('📅 DOM listo – arrancando bucle de sesión cada 2s');
    fetchSession();
    setInterval(fetchSession, 2000);
  });
})();
