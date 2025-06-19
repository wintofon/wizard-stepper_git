// File: wizard/assets/js/main.js
// Controla el botón de inicio y limpia sesión/localStorage
const BASE_URL = window.BASE_URL || '/wizard-stepper_git';
document.addEventListener('DOMContentLoaded', () => {
  const btnStart = document.getElementById('btn-start');
  if (!btnStart) return;

  btnStart.addEventListener('click', () => {
    // Destruye sesión PHP y localStorage antes de ir a selección de modo
    fetch(`${BASE_URL}/public/reset.php`, { method: 'GET' })
      .finally(() => {
        localStorage.removeItem('wizard_progress');
        // Redirige a la selección de modo
        window.location.href = `${BASE_URL}/index.php?state=mode`;
      });
  });
});
