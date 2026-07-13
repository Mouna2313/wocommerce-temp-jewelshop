/**
 * Ora & Stone — Jeweler's Loupe zoom
 * Applies a circular magnifier that follows the cursor over any
 * element with class "loupe" containing an <img>. Echoes how a
 * jeweler inspects a stone up close.
 */
(function () {
  function initLoupe(el) {
    var img = el.querySelector('img');
    if (!img) return;

    var glass = document.createElement('div');
    glass.className = 'loupe__glass';
    el.appendChild(glass);

    function setBackground() {
      glass.style.setProperty('--loupe-bg-image', 'url(' + img.currentSrc || img.src + ')');
    }
    if (img.complete) setBackground();
    else img.addEventListener('load', setBackground);

    el.addEventListener('mousemove', function (e) {
      var rect = el.getBoundingClientRect();
      var x = e.clientX - rect.left;
      var y = e.clientY - rect.top;
      var xPct = (x / rect.width) * 100;
      var yPct = (y / rect.height) * 100;

      el.style.setProperty('--loupe-x', x + 'px');
      el.style.setProperty('--loupe-y', y + 'px');
      glass.style.setProperty('--loupe-bg-pos', xPct + '% ' + yPct + '%');
      glass.style.setProperty('--loupe-bg-size', '220%');
      el.classList.add('is-active');
    });

    el.addEventListener('mouseleave', function () {
      el.classList.remove('is-active');
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (window.matchMedia('(hover: none)').matches) return; // skip touch devices
    document.querySelectorAll('.loupe').forEach(initLoupe);
  });

  // Exposed so render-utils.js can wire up the loupe on product cards
  // that get fetched and inserted after DOMContentLoaded has already fired.
  window.OSInitLoupeEl = initLoupe;
})();
