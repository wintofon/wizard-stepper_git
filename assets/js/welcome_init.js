/*
 * File: welcome_init.js
 * Epic CNC Wizard Welcome Init – versión legendaria 🚀
 *
 * Main responsibility:
 *   Control del botón de inicio, limpieza de sesión y localStorage,
 *   y narración épica en la consola para guiar al usuario.
 * Related files: reset.php, wizard.php
 * TODO: Extender con mensajes de audio dramático.
 */
/* global BASE_URL */

// Espera a que el DOM esté listo para iniciar la odisea
console.group("%c[WelcomeInit🛡️] Iniciando bienvenida épica","color:#2196f3;font-weight:bold");
console.log("🔍 Buscando botón de inicio…");

document.addEventListener('DOMContentLoaded', () => {
  console.log("📦 DOM cargado: preparándose para la aventura...");

  const btnStart = document.getElementById('btn-start');
  if (!btnStart) {
    console.error("❌ Botón '#btn-start' no encontrado. Fin de la saga.");
    console.groupEnd();
    return;
  }

  console.log("✅ Botón encontrado: listo para el gran comienzo.");

  btnStart.addEventListener('click', () => {
    console.group("%c[WelcomeInit🛡️] Click en INICIAR","color:#4caf50;font-weight:bold");
    console.log("🗡️ Desatando reset épico de sesión... ", `${BASE_URL}/public/reset.php`);

    // Llamada al reset en backend
    fetch(`${BASE_URL}/public/reset.php`, { method: 'GET' })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        console.log("🔄 Sesión PHP destruida con éxito.");
      })
      .catch(err => {
        console.warn("⚠️ Error destruyendo sesión:", err);
      })
      .finally(() => {
        console.log("🧹 Limpiando progreso en localStorage...");
        localStorage.removeItem('wizard_progress');
        console.log("✅ localStorage limpio.");

        const nextUrl = `${BASE_URL}/wizard.php?state=mode`;
        console.log("🚀 Redirigiendo a selección de modo:", nextUrl);
        console.groupEnd();
        window.location.href = nextUrl;
      });
  });
});
