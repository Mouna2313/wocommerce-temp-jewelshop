(function () {
  function statusBadge(order) {
    return '<mark class="order-status status-' + order.status + '">' + OSRender.escapeHTML(order.status.replace(/^wc-/, '')) + '</mark>';
  }

  function orderRowHTML(order) {
    return '<a class="account-order-row" href="order.html?id=' + order.id + '">' +
      '<span class="account-order-row__number">#' + order.number + '</span>' +
      '<span class="account-order-row__date">' + new Date(order.date_created).toLocaleDateString() + '</span>' +
      statusBadge(order) +
      '<span class="account-order-row__total">' + OSRender.formatMoney(Math.round(parseFloat(order.total) * 100), { currency_minor_unit: 2, currency_prefix: '$', currency_suffix: '' }) + '</span>' +
      '</a>';
  }

  function loadOverview() {
    OSAuth.api('/orders').then(function (orders) {
      var recent = document.querySelector('[data-recent-orders]');
      recent.innerHTML = orders.length ? orders.slice(0, 3).map(orderRowHTML).join('') : '<p>No orders yet. <a href="shop.html">Start shopping →</a></p>';
    });
  }

  function loadOrders() {
    var list = document.querySelector('[data-orders-list]');
    OSAuth.api('/orders').then(function (orders) {
      list.innerHTML = orders.length ? orders.map(orderRowHTML).join('') : '<p>No orders yet. <a href="shop.html">Start shopping →</a></p>';
    }).catch(function (err) { list.innerHTML = '<p>Could not load orders: ' + OSRender.escapeHTML(err.message) + '</p>'; });
  }

  function fillAddressForm(form, address) {
    form.querySelectorAll('[data-field]').forEach(function (input) {
      input.value = (address && address[input.dataset.field]) || '';
    });
  }
  function readAddressForm(form) {
    var out = {};
    form.querySelectorAll('[data-field]').forEach(function (input) { out[input.dataset.field] = input.value; });
    return out;
  }

  function loadAddresses() {
    OSAuth.api('/account').then(function (account) {
      fillAddressForm(document.querySelector('[data-address-form="billing"]'), account.billing);
      fillAddressForm(document.querySelector('[data-address-form="shipping"]'), account.shipping);
    });
  }

  function loadDetails() {
    OSAuth.api('/account').then(function (account) {
      document.getElementById('details_first_name').value = account.first_name || '';
      document.getElementById('details_last_name').value = account.last_name || '';
      document.getElementById('details_email').value = account.email || '';
    });
  }

  var LOADERS = { overview: loadOverview, orders: loadOrders, addresses: loadAddresses, details: loadDetails };

  function showTab(tab) {
    document.querySelectorAll('[data-tab]').forEach(function (el) { el.hidden = el.dataset.tab !== tab; });
    document.querySelectorAll('[data-tab-link]').forEach(function (el) { el.classList.toggle('is-active', el.dataset.tabLink === tab); });
    if (LOADERS[tab]) LOADERS[tab]();
  }

  document.addEventListener('DOMContentLoaded', function () {
    var yearEl = document.querySelector('[data-year]');
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    if (!OSAuth.isLoggedIn()) {
      location.href = 'login.html?redirect=' + encodeURIComponent('account.html');
      return;
    }

    document.querySelector('[data-account-gate]').hidden = true;
    document.querySelector('[data-account-app]').hidden = false;

    var user = OSAuth.getUser();
    document.querySelector('[data-welcome-name]').textContent = (user && user.name) || 'there';

    document.querySelector('[data-lost-password]').href =
      (window.OS_CONFIG.API_BASE || '').replace(/\/+$/, '') + '/wp-login.php?action=lostpassword';

    document.querySelector('[data-logout]').addEventListener('click', function (e) {
      e.preventDefault();
      OSAuth.logout();
      location.href = 'index.html';
    });

    document.querySelectorAll('[data-address-form]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var group = form.dataset.addressForm;
        var feedback = document.querySelector('[data-address-feedback]');
        feedback.textContent = 'Saving…';
        OSAuth.api('/account', { method: 'POST', body: (function () { var o = {}; o[group] = readAddressForm(form); return o; })() })
          .then(function () { feedback.textContent = 'Saved.'; })
          .catch(function (err) { feedback.textContent = err.message; });
      });
    });

    document.querySelector('[data-details-form]').addEventListener('submit', function (e) {
      e.preventDefault();
      var feedback = document.querySelector('[data-details-feedback]');
      feedback.textContent = 'Saving…';
      OSAuth.api('/account', {
        method: 'POST',
        body: { first_name: document.getElementById('details_first_name').value, last_name: document.getElementById('details_last_name').value },
      }).then(function () { feedback.textContent = 'Saved.'; })
        .catch(function (err) { feedback.textContent = err.message; });
    });

    var tab = new URLSearchParams(location.search).get('tab') || 'overview';
    if (!LOADERS[tab]) tab = 'overview';
    showTab(tab);
  });
})();
