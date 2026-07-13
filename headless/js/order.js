(function () {
  var MONEY_META = { currency_minor_unit: 2, currency_prefix: '$', currency_suffix: '' };
  function money(dollarString) { return OSRender.formatMoney(Math.round(parseFloat(dollarString || 0) * 100), MONEY_META); }

  function timelineHTML(order) {
    if (order.is_stopped) {
      return '<div class="order-timeline order-timeline--stopped"><p>' + OSRender.escapeHTML(order.stopped_message) + '</p></div>';
    }
    var steps = order.timeline.map(function (step) {
      return '<li class="order-timeline__step' + (step.reached ? ' is-complete' : '') + (step.is_current ? ' is-current' : '') + '">' +
        '<span class="order-timeline__dot" aria-hidden="true"></span>' +
        '<span class="order-timeline__label">' + OSRender.escapeHTML(step.label) + '</span></li>';
    }).join('');
    var tracking = order.tracking_number
      ? '<p class="order-timeline__tracking">Tracking: ' + (order.tracking_carrier ? OSRender.escapeHTML(order.tracking_carrier) + ' ' : '') + OSRender.escapeHTML(order.tracking_number) + '</p>'
      : '';
    return '<ol class="order-timeline">' + steps + '</ol>' + tracking;
  }

  function itemsTableHTML(order) {
    var rows = order.items.map(function (item) {
      return '<tr><td style="display:flex;align-items:center;gap:var(--space-sm)">' +
        (item.image ? '<img src="' + item.image + '" alt="" style="width:3rem;height:3rem;object-fit:cover;border-radius:var(--radius-sm)">' : '') +
        '<span>' + OSRender.escapeHTML(item.name) + ' × ' + item.quantity + '</span></td>' +
        '<td style="text-align:right">' + money(item.total) + '</td></tr>';
    }).join('');
    return '<table class="shop_table"><tbody>' + rows +
      '<tr><th>Subtotal</th><td style="text-align:right">' + money(order.subtotal) + '</td></tr>' +
      (parseFloat(order.shipping_total) ? '<tr><th>Shipping</th><td style="text-align:right">' + money(order.shipping_total) + '</td></tr>' : '') +
      (parseFloat(order.total_tax) ? '<tr><th>Tax</th><td style="text-align:right">' + money(order.total_tax) + '</td></tr>' : '') +
      '<tr class="order-total"><th>Total</th><td style="text-align:right">' + money(order.total) + '</td></tr>' +
      '</tbody></table>';
  }

  document.addEventListener('DOMContentLoaded', function () {
    var yearEl = document.querySelector('[data-year]');
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    if (!OSAuth.isLoggedIn()) {
      location.href = 'login.html?redirect=' + encodeURIComponent(location.pathname + location.search);
      return;
    }

    var id = new URLSearchParams(location.search).get('id');
    var root = document.querySelector('[data-order-root]');
    if (!id) { root.innerHTML = '<p>No order specified.</p>'; return; }

    OSAuth.api('/orders/' + id).then(function (order) {
      root.innerHTML =
        '<h1 class="page-title">Order #' + order.number + '</h1>' +
        '<p>Placed on ' + new Date(order.date_created).toLocaleDateString() + ' — <mark class="order-status status-' + order.status + '">' + OSRender.escapeHTML(order.status_label) + '</mark></p>' +
        timelineHTML(order) +
        '<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-lg);margin:var(--space-lg) 0">' +
          '<div><h3>Billing address</h3><address style="font-style:normal;color:var(--color-ink-soft)">' + order.billing_address + '</address></div>' +
          '<div><h3>Shipping address</h3><address style="font-style:normal;color:var(--color-ink-soft)">' + (order.shipping_address || order.billing_address) + '</address></div>' +
        '</div>' +
        '<p style="color:var(--color-ink-faint)">Payment method: ' + OSRender.escapeHTML(order.payment_method_title || '—') + '</p>' +
        itemsTableHTML(order);
    }).catch(function (err) {
      root.innerHTML = '<p>Could not load this order: ' + OSRender.escapeHTML(err.message) + '</p>';
    });
  });
})();
