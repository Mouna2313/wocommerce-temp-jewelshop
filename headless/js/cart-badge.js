/**
 * Ora & Stone — header cart count badge, shared by every page.
 * Fetches the current cart once on load and updates .navbar__badge.
 */
(function () {
  function render(cart) {
    var badge = document.querySelector('[data-cart-badge]');
    if (!badge) return;
    var count = cart && cart.items_count ? cart.items_count : 0;
    badge.textContent = String(count);
    badge.hidden = count === 0;
  }

  document.addEventListener('DOMContentLoaded', function () {
    StoreAPI.getCart().then(render).catch(function (err) {
      console.warn('Could not load cart:', err.message);
    });
  });

  // Other page scripts call this after add/update/remove so the badge
  // stays in sync without a second network round trip.
  window.OSUpdateCartBadge = render;
})();
