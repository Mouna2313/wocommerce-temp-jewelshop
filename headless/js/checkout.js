/**
 * Real checkout against WooCommerce's Store API (POST /wc/store/v1/checkout)
 * — this places an actual order, no different from what WooCommerce's own
 * block-based checkout does. Payment is limited to gateways that support
 * the Blocks/Store API checkout with zero extra credentials: Cash on
 * Delivery ('cod') and Direct Bank Transfer ('bacs'), both built into
 * WooCommerce core. See headless/README.md for why (Stripe/PayPal need
 * their own separate API keys from an actual payment provider — that's
 * an inherent requirement, not something deferred).
 */
(function () {
  // required=false for the shipping fields, which start out hidden (only
  // shown if "ship to a different address" is checked) — a `required`
  // attribute on a hidden field blocks form submission with a silent
  // browser error ("not focusable") instead of a validation message, since
  // the browser has nowhere visible to point the "please fill this in"
  // bubble at. js/checkout.js toggles `required` on/off with visibility.
  function addressFieldsHTML(prefix, required) {
    var req = required ? ' required' : '';
    return (
      '<p class="form-row form-row-first"><label>First name <span class="required">*</span></label><input class="input-text"' + req + ' data-field="' + prefix + '_first_name"></p>' +
      '<p class="form-row form-row-last"><label>Last name <span class="required">*</span></label><input class="input-text"' + req + ' data-field="' + prefix + '_last_name"></p>' +
      '<p class="form-row form-row-wide"><label>Street address <span class="required">*</span></label><input class="input-text"' + req + ' data-field="' + prefix + '_address_1" placeholder="Street address"></p>' +
      '<p class="form-row form-row-wide"><input class="input-text" data-field="' + prefix + '_address_2" placeholder="Apartment, suite, etc. (optional)"></p>' +
      '<p class="form-row form-row-first"><label>City <span class="required">*</span></label><input class="input-text"' + req + ' data-field="' + prefix + '_city"></p>' +
      '<p class="form-row form-row-last"><label>State</label><input class="input-text" data-field="' + prefix + '_state"></p>' +
      '<p class="form-row form-row-first"><label>Postcode <span class="required">*</span></label><input class="input-text"' + req + ' data-field="' + prefix + '_postcode"></p>' +
      '<p class="form-row form-row-last"><label>Country <span class="required">*</span></label><input class="input-text"' + req + ' data-field="' + prefix + '_country" placeholder="US" maxlength="2"></p>'
    );
  }

  function reviewTableHTML(cart) {
    var rows = cart.items.map(function (item) {
      return '<tr><td class="product-name">' + OSRender.escapeHTML(item.name) + ' <strong>× ' + item.quantity + '</strong></td>' +
        '<td class="product-total">' + OSRender.formatMoney(item.totals.line_subtotal, item.totals) + '</td></tr>';
    }).join('');
    var t = cart.totals;
    return '<table class="shop_table woocommerce-checkout-review-order-table"><thead><tr><th>Product</th><th>Subtotal</th></tr></thead><tbody>' +
      rows +
      '<tr><th>Subtotal</th><td>' + OSRender.formatMoney(t.total_items, t) + '</td></tr>' +
      (parseInt(t.total_discount, 10) ? '<tr><th>Discount</th><td>-' + OSRender.formatMoney(t.total_discount, t) + '</td></tr>' : '') +
      '<tr class="order-total"><th>Total</th><td>' + OSRender.formatMoney(t.total_price, t) + '</td></tr>' +
      '</tbody></table>';
  }

  function render(cart) {
    var root = document.querySelector('[data-checkout-root]');
    if (!cart.items.length) {
      root.innerHTML = '<div class="wishlist-page__empty"><p>Your cart is empty.</p><a href="shop.html" class="btn btn--coral">Continue Shopping</a></div>';
      return;
    }

    root.innerHTML =
      '<form class="checkout woocommerce-checkout checkout-page" data-checkout-form>' +
        '<div class="checkout-layout">' +
          '<div class="checkout-layout__details">' +
            '<h3>Billing Details</h3>' +
            addressFieldsHTML('billing', true) +
            '<p class="form-row form-row-wide"><label>Email <span class="required">*</span></label><input class="input-text" type="email" required data-field="billing_email"></p>' +
            '<p class="form-row form-row-wide"><label>Phone</label><input class="input-text" data-field="billing_phone"></p>' +

            '<p class="form-row"><label><input type="checkbox" data-ship-different> Ship to a different address</label></p>' +
            '<div data-shipping-fields hidden>' +
              '<h3>Shipping Details</h3>' +
              addressFieldsHTML('shipping', false) +
            '</div>' +

            '<h3>Payment Method</h3>' +
            '<ul class="payment_methods">' +
              '<li><label><input type="radio" name="payment_method" value="cod" checked> Cash on Delivery</label></li>' +
              '<li><label><input type="radio" name="payment_method" value="bacs"> Direct Bank Transfer</label></li>' +
            '</ul>' +
            '<p style="color:var(--color-ink-faint);font-size:var(--text-small)">These two gateways need no setup beyond enabling them in WooCommerce → Settings → Payments. Card payments (Stripe, PayPal, etc.) need their own account with that provider first — see headless/README.md.</p>' +
          '</div>' +

          '<div class="checkout-layout__review">' +
            '<h3 id="order_review_heading">Your Order</h3>' +
            '<div id="order_review" class="woocommerce-checkout-review-order">' + reviewTableHTML(cart) + '</div>' +
            '<div id="payment">' +
              '<button type="submit" id="place_order" class="btn btn--coral" style="width:100%">Place Order</button>' +
              '<p data-checkout-feedback role="status" aria-live="polite"></p>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</form>';

    document.querySelector('[data-ship-different]').addEventListener('change', function (e) {
      var shippingFields = document.querySelector('[data-shipping-fields]');
      shippingFields.hidden = !e.target.checked;
      shippingFields.querySelectorAll('[data-field$="_first_name"], [data-field$="_last_name"], [data-field$="_address_1"], [data-field$="_city"], [data-field$="_postcode"], [data-field$="_country"]').forEach(function (input) {
        input.required = e.target.checked;
      });
    });

    document.querySelector('[data-checkout-form]').addEventListener('submit', function (e) {
      e.preventDefault();
      var form = e.target;
      var feedback = document.querySelector('[data-checkout-feedback]');
      var btn = document.getElementById('place_order');

      function field(name) { var el = form.querySelector('[data-field="' + name + '"]'); return el ? el.value : ''; }

      var billing = {
        first_name: field('billing_first_name'), last_name: field('billing_last_name'),
        address_1: field('billing_address_1'), address_2: field('billing_address_2'),
        city: field('billing_city'), state: field('billing_state'),
        postcode: field('billing_postcode'), country: field('billing_country'),
        email: field('billing_email'), phone: field('billing_phone'),
      };
      var shipDifferent = document.querySelector('[data-ship-different]').checked;
      var shipping = shipDifferent ? {
        first_name: field('shipping_first_name'), last_name: field('shipping_last_name'),
        address_1: field('shipping_address_1'), address_2: field('shipping_address_2'),
        city: field('shipping_city'), state: field('shipping_state'),
        postcode: field('shipping_postcode'), country: field('shipping_country'),
      } : billing;
      var paymentMethod = form.querySelector('input[name="payment_method"]:checked').value;

      btn.disabled = true;
      feedback.textContent = 'Placing your order…';

      StoreAPI.checkout(billing, shipping, paymentMethod).then(function (order) {
        showConfirmation(order);
      }).catch(function (err) {
        feedback.textContent = err.message;
        btn.disabled = false;
      });
    });
  }

  function showConfirmation(order) {
    var root = document.querySelector('[data-checkout-root]');
    var orderId = order.order_id || order.id;
    var isLoggedIn = window.OSAuth && OSAuth.isLoggedIn();
    root.innerHTML =
      '<div class="wishlist-page__empty">' +
        '<h2>Thank you — your order is placed.</h2>' +
        '<p>Order ' + (order.order_key ? '#' + OSRender.escapeHTML(order.order_key) : '#' + orderId) + ' has been received.</p>' +
        (isLoggedIn ? '<a class="btn btn--coral" href="order.html?id=' + orderId + '">View Order</a> ' : '') +
        '<a class="btn btn--outline" href="shop.html">Continue Shopping</a>' +
      '</div>';
    if (window.OSUpdateCartBadge) OSUpdateCartBadge({ items_count: 0 });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var yearEl = document.querySelector('[data-year]');
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    StoreAPI.getCart().then(render).catch(function (err) {
      document.querySelector('[data-checkout-root]').innerHTML = '<p>Could not load your cart: ' + OSRender.escapeHTML(err.message) + '</p>';
    });
  });
})();
