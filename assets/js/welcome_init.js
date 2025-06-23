/*
 * File: welcome_init.js
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * TODO: Extend documentation.
 */
// File: wizard/assets/js/welcome_init.js
// Controla el botón de inicio y limpia sesión/localStorage
const BASE_URL = window.BASE_URL;
document.addEventListener('DOMContentLoaded', () => {
  const btnStart = document.getElementById('btn-start');
  if (!btnStart) return;

  btnStart.addEventListener('click', () => {
    // Destruye sesión PHP y localStorage antes de ir a selección de modo
    fetch(`${BASE_URL}/public/reset.php`, { method: 'GET' })
      .finally(() => {
        localStorage.removeItem('wizard_progress');
        // Redirige a la selección de modo
        window.location.href = `${BASE_URL}/wizard.php?state=mode`;
      });
  });
});
