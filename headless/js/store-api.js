/**
 * Ora & Stone — WooCommerce Store API client
 *
 * Talks to the built-in WooCommerce Store API (wc/store/v1) on a
 * separately-hosted WordPress backend. Uses the Cart-Token / Nonce
 * header pair instead of cookies, since a cross-origin frontend can't
 * rely on WooCommerce's session cookie (third-party cookie
 * restrictions in modern browsers would break it).
 *
 * Cart-Token: a stateless JWT identifying the guest cart. Returned on
 * every response, must be echoed back on every request.
 * Nonce: WooCommerce's write-protection nonce. Returned on every
 * response, must be echoed back on every state-changing (non-GET)
 * request, or the request is rejected with 401.
 */
(function (global) {
  var TOKEN_KEY = 'os_cart_token';
  var NONCE_KEY = 'os_cart_nonce';

  function apiRoot() {
    var base = (global.OS_CONFIG && global.OS_CONFIG.API_BASE) || '';
    return base.replace(/\/+$/, '') + '/wp-json/wc/store/v1';
  }

  function getToken() { return sessionStorage.getItem(TOKEN_KEY) || ''; }
  function getNonce() { return sessionStorage.getItem(NONCE_KEY) || ''; }

  function captureTokens(headers) {
    var token = headers.get('Cart-Token');
    var nonce = headers.get('Nonce');
    if (token) sessionStorage.setItem(TOKEN_KEY, token);
    if (nonce) sessionStorage.setItem(NONCE_KEY, nonce);
  }

  function doFetch(path, options) {
    options = options || {};
    var method = (options.method || 'GET').toUpperCase();
    // When logged in (see js/auth.js), attaching the JWT here — not just on
    // the account-api calls — is what makes an order placed at checkout
    // attach to the customer's account instead of coming through as a
    // guest order: the JWT plugin resolves wp_get_current_user() for any
    // REST request that carries it, Store API included.
    var authToken = localStorage.getItem('os_auth_token');
    var headers = Object.assign(
      { 'Content-Type': 'application/json', 'Cart-Token': getToken() },
      method !== 'GET' ? { 'Nonce': getNonce() } : {},
      authToken ? { 'Authorization': 'Bearer ' + authToken } : {},
      options.headers || {}
    );

    return fetch(apiRoot() + path, {
      method: method,
      headers: headers,
      credentials: 'omit',
      body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
    }).then(function (res) {
      captureTokens(res.headers);
      if (!res.ok) {
        return res.json().catch(function () { return {}; }).then(function (err) {
          var e = new Error(err.message || ('Store API error ' + res.status));
          e.status = res.status;
          e.code = err.code;
          throw e;
        });
      }
      var body = res.status === 204 ? null : res.json();
      return options.raw ? Promise.resolve(body).then(function (b) { return { body: b, headers: res.headers }; }) : body;
    });
  }

  // Write requests need a nonce, which only arrives on a prior response.
  // If we don't have one yet (e.g. first thing the user does is hit
  // "Add to cart"), seed it with a GET /cart first.
  function request(path, options) {
    options = options || {};
    var method = (options.method || 'GET').toUpperCase();
    if (method === 'GET' || getNonce()) {
      return doFetch(path, options);
    }
    return doFetch('/cart', { method: 'GET' }).then(function () {
      return doFetch(path, options);
    });
  }

  global.StoreAPI = {
    getProducts: function (params) {
      var qs = new URLSearchParams(params || {}).toString();
      return request('/products' + (qs ? '?' + qs : ''));
    },
    // Same as getProducts, but also returns total/totalPages from the
    // response headers, needed for the shop page's pagination controls.
    getProductsWithMeta: function (params) {
      var qs = new URLSearchParams(params || {}).toString();
      return request('/products' + (qs ? '?' + qs : ''), { raw: true }).then(function (res) {
        return {
          items: res.body,
          total: parseInt(res.headers.get('X-WP-Total'), 10) || 0,
          totalPages: parseInt(res.headers.get('X-WP-TotalPages'), 10) || 1,
        };
      });
    },
    getProduct: function (id) {
      return request('/products/' + encodeURIComponent(id));
    },
    getCategories: function () {
      return request('/products/categories?per_page=0&hide_empty=true');
    },
    getCart: function () {
      return request('/cart');
    },
    addItem: function (id, quantity, variation) {
      return request('/cart/add-item', {
        method: 'POST',
        body: { id: id, quantity: quantity || 1, variation: variation || [] },
      });
    },
    updateItem: function (key, quantity) {
      return request('/cart/update-item', { method: 'POST', body: { key: key, quantity: quantity } });
    },
    removeItem: function (key) {
      return request('/cart/remove-item', { method: 'POST', body: { key: key } });
    },
    applyCoupon: function (code) {
      return request('/cart/apply-coupon', { method: 'POST', body: { code: code } });
    },
    removeCoupon: function (code) {
      return request('/cart/remove-coupon', { method: 'POST', body: { code: code } });
    },
    // billing/shipping: the address shape documented in headless/README.md
    // (first_name, last_name, address_1, city, state, postcode, country,
    // email, phone). paymentMethod: a gateway id enabled in WooCommerce →
    // Settings → Payments that supports the Blocks/Store API checkout,
    // e.g. 'cod' or 'bacs' — see README for why those two specifically.
    checkout: function (billing, shipping, paymentMethod) {
      return request('/checkout', {
        method: 'POST',
        body: {
          billing_address: billing,
          shipping_address: shipping || billing,
          payment_method: paymentMethod,
          payment_data: [],
        },
      });
    },
  };
})(window);
