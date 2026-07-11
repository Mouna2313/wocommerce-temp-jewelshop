/**
 * Ora & Stone — wishlist add/remove
 * Delegated click handler for any ".wishlist-btn" (product cards, single
 * product page). Toggles state via AJAX and keeps the navbar badge in sync.
 */
(function () {
  if (typeof oraandstoneWishlist === 'undefined') return;

  function setButtonState(btn, active) {
    btn.classList.toggle('is-active', active);
    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    btn.setAttribute('aria-label', active ? 'Remove from wishlist' : 'Add to wishlist');
    var path = btn.querySelector('svg');
    if (path) path.setAttribute('fill', active ? 'currentColor' : 'none');
  }

  function updateCount(count) {
    var badge = document.getElementById('oraandstone-wishlist-count');
    if (!badge) return;
    badge.textContent = count;
    badge.hidden = count < 1;
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.wishlist-btn');
    if (!btn) return;
    e.preventDefault();

    if (btn.disabled) return;
    btn.disabled = true;

    var body = new URLSearchParams({
      action: 'oraandstone_toggle_wishlist',
      product_id: btn.dataset.productId,
      nonce: oraandstoneWishlist.nonce,
    });

    fetch(oraandstoneWishlist.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
    })
      .then(function (res) { return res.json(); })
      .then(function (json) {
        if (!json.success) return;
        setButtonState(btn, json.data.in_wishlist);
        updateCount(json.data.count);
        document.querySelectorAll('.wishlist-btn[data-product-id="' + btn.dataset.productId + '"]').forEach(function (other) {
          if (other !== btn) setButtonState(other, json.data.in_wishlist);
        });
        document.dispatchEvent(new CustomEvent('oraandstone:wishlist-change', {
          detail: { productId: btn.dataset.productId, inWishlist: json.data.in_wishlist },
        }));
      })
      .finally(function () {
        btn.disabled = false;
      });
  });
})();
