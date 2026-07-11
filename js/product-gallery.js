/**
 * Ora & Stone — single product gallery
 * Swaps the main gallery image when a thumbnail is activated. The .loupe
 * zoom (js/loupe.js) stays attached to the same <img> element and picks up
 * the new source automatically via its own "load" listener.
 */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var gallery = document.querySelector('.product-gallery');
    if (!gallery) return;

    var mainImg = gallery.querySelector('.product-gallery__main img');
    var thumbs = gallery.querySelectorAll('.product-gallery__thumb');
    if (!mainImg || !thumbs.length) return;

    thumbs.forEach(function (thumb) {
      thumb.addEventListener('click', function () {
        var full = thumb.dataset.full;
        if (!full) return;

        mainImg.src = full;
        mainImg.alt = thumb.dataset.alt || '';

        thumbs.forEach(function (t) {
          t.classList.remove('is-active');
          t.setAttribute('aria-pressed', 'false');
        });
        thumb.classList.add('is-active');
        thumb.setAttribute('aria-pressed', 'true');
      });
    });
  });
})();
