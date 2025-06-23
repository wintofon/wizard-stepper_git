/*
 * File: select_mode.js
 * Adds interactivity to the mode selection view:
 * - Highlights the selected option
 * - Automatically submits the form when a mode is chosen
 */
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.wizard-welcome form');
  if (!form) return;

  const options = Array.from(form.querySelectorAll('.mode-option'));
  options.forEach(option => {
    option.addEventListener('click', () => {
      options.forEach(o => o.classList.toggle('selected', o === option));
      const input = option.querySelector('input[type="radio"]');
      if (input) {
        input.checked = true;
      }
      setTimeout(() => form.submit(), 150);
    });
  });
});
