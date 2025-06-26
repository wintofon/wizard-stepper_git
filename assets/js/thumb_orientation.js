(() => {
  window.checkThumbOrientation = function (img) {
    if (!img) return;
    const apply = () => {
      if (img.naturalHeight > img.naturalWidth) {
        img.classList.add('portrait');
      }
    };
    if (img.complete) {
      apply();
    } else {
      img.addEventListener('load', apply, { once: true });
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('img.thumb').forEach(window.checkThumbOrientation);
  });
})();
