(function () {
  var root = document.querySelector('[data-cart-root]');

  function itemRowHTML(item) {
    var img = (item.images && item.images[0]) || {};
    var max = item.quantity_limits && item.quantity_limits.maximum < 1000 ? item.quantity_limits.maximum : '';
    var min = (item.quantity_limits && item.quantity_limits.minimum) || 0;
    return (
      '<tr class="woocommerce-cart-form__cart-item cart_item" data-item-key="' + item.key + '">' +
        '<td class="product-remove">' +
          '<a href="#" class="remove" aria-label="Remove this item" data-remove-item>&times;</a>' +
        '</td>' +
        '<td class="product-thumbnail">' +
          '<a href="product.html?id=' + item.id + '"><img src="' + (img.src || '') + '" alt="' + OSRender.escapeHTML(img.alt || item.name) + '"></a>' +
        '</td>' +
        '<td class="product-name" data-title="Product">' +
          '<a href="product.html?id=' + item.id + '">' + OSRender.escapeHTML(item.name) + '</a>' +
        '</td>' +
        '<td class="product-price" data-title="Price">' + OSRender.formatMoney(item.prices.price, item.prices) + '</td>' +
        '<td class="product-quantity" data-title="Quantity">' +
          '<div class="quantity">' +
            '<label class="visually-hidden" for="qty-' + item.key + '">Quantity</label>' +
            '<input type="number" id="qty-' + item.key + '" class="qty" min="' + min + '"' +
              (max ? ' max="' + max + '"' : '') + ' value="' + item.quantity + '" data-qty-input>' +
          '</div>' +
        '</td>' +
        '<td class="product-subtotal" data-title="Subtotal">' + OSRender.formatMoney(item.totals.line_subtotal, item.totals) + '</td>' +
      '</tr>'
    );
  }

  function totalsHTML(cart) {
    var t = cart.totals;
    return (
      '<div class="cart_totals">' +
        '<h2>Cart totals</h2>' +
        '<table class="shop_table">' +
          '<tr><th>Subtotal</th><td>' + OSRender.formatMoney(t.total_items, t) + '</td></tr>' +
          (parseInt(t.total_shipping, 10) ? '<tr><th>Shipping</th><td>' + OSRender.formatMoney(t.total_shipping, t) + '</td></tr>' : '') +
          (parseInt(t.total_tax, 10) ? '<tr><th>Tax</th><td>' + OSRender.formatMoney(t.total_tax, t) + '</td></tr>' : '') +
          '<tr class="order-total"><th>Total</th><td>' + OSRender.formatMoney(t.total_price, t) + '</td></tr>' +
        '</table>' +
        '<div class="wc-proceed-to-checkout">' +
          '<a href="checkout.html" class="btn btn--coral checkout-button" style="width:100%;text-align:center">Proceed to Checkout</a>' +
        '</div>' +
      '</div>'
    );
  }

  function render(cart) {
    window.OSUpdateCartBadge(cart);

    if (!cart.items.length) {
      root.innerHTML =
        '<div class="wishlist-page__empty">' +
          '<p>Your cart is empty.</p>' +
          '<a href="shop.html" class="btn btn--coral">Continue Shopping</a>' +
        '</div>';
      return;
    }

    root.innerHTML =
      '<div class="cart-layout">' +
        '<form class="woocommerce-cart-form cart-page" data-cart-form>' +
          '<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">' +
            '<thead><tr>' +
              '<th class="product-remove"><span class="visually-hidden">Remove item</span></th>' +
              '<th class="product-thumbnail"><span class="visually-hidden">Thumbnail</span></th>' +
              '<th class="product-name">Product</th>' +
              '<th class="product-price">Price</th>' +
              '<th class="product-quantity">Quantity</th>' +
              '<th class="product-subtotal">Subtotal</th>' +
            '</tr></thead>' +
            '<tbody>' +
              cart.items.map(itemRowHTML).join('') +
              '<tr><td colspan="6" class="actions">' +
                '<div class="coupon">' +
                  '<label for="coupon_code" class="visually-hidden">Coupon:</label>' +
                  '<input type="text" id="coupon_code" class="input-text" placeholder="Coupon code" data-coupon-input>' +
                  '<button type="button" class="btn btn--outline" data-apply-coupon>Apply coupon</button>' +
                '</div>' +
              '</td></tr>' +
            '</tbody>' +
          '</table>' +
          '<p data-cart-feedback role="status" aria-live="polite"></p>' +
        '</form>' +
        '<div class="cart-collaterals">' + totalsHTML(cart) + '</div>' +
      '</div>';

    wireRows();
    wireCoupon();
  }

  function reload() {
    StoreAPI.getCart().then(render).catch(function (err) {
      root.innerHTML = '<p>Could not load cart: ' + OSRender.escapeHTML(err.message) + '</p>';
    });
  }

  function wireRows() {
    root.querySelectorAll('[data-remove-item]').forEach(function (link) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        var key = link.closest('[data-item-key]').dataset.itemKey;
        StoreAPI.removeItem(key).then(render).catch(showFeedback);
      });
    });

    root.querySelectorAll('[data-qty-input]').forEach(function (input) {
      input.addEventListener('change', function () {
        var key = input.closest('[data-item-key]').dataset.itemKey;
        var qty = parseInt(input.value, 10);
        if (qty < 0 || isNaN(qty)) return;
        StoreAPI.updateItem(key, qty).then(render).catch(showFeedback);
      });
    });
  }

  function wireCoupon() {
    var btn = root.querySelector('[data-apply-coupon]');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var code = root.querySelector('[data-coupon-input]').value.trim();
      if (!code) return;
      StoreAPI.applyCoupon(code).then(render).catch(showFeedback);
    });
  }

  function showFeedback(err) {
    var feedback = root.querySelector('[data-cart-feedback]');
    if (feedback) feedback.textContent = err.message;
  }

  document.addEventListener('DOMContentLoaded', function () {
    var yearEl = document.querySelector('[data-year]');
    if (yearEl) yearEl.textContent = new Date().getFullYear();
    reload();
  });
})();
