(function () {
  var PER_PAGE = 12;

  function getState() {
    var qs = new URLSearchParams(location.search);
    return {
      category: qs.get('category') || '',
      min_price: qs.get('min_price') || '',
      max_price: qs.get('max_price') || '',
      orderby: qs.get('orderby') || 'menu_order',
      page: parseInt(qs.get('page'), 10) || 1,
    };
  }

  function pushState(next) {
    var qs = new URLSearchParams();
    Object.keys(next).forEach(function (k) {
      if (next[k]) qs.set(k, next[k]);
    });
    history.pushState(null, '', 'shop.html' + (qs.toString() ? '?' + qs.toString() : ''));
    load();
  }

  function loadCategories(activeSlug) {
    var list = document.querySelector('[data-category-list]');
    StoreAPI.getCategories().then(function (cats) {
      var html = '<li><a href="shop.html"' + (activeSlug ? '' : ' class="is-active"') + '>All</a></li>';
      html += cats.map(function (cat) {
        var isActive = cat.slug === activeSlug;
        return '<li><a href="shop.html?category=' + encodeURIComponent(cat.slug) + '"' +
          (isActive ? ' class="is-active"' : '') + '>' + OSRender.escapeHTML(cat.name) + ' (' + cat.count + ')</a></li>';
      }).join('');
      list.innerHTML = html;
    }).catch(function () {
      list.innerHTML = '<li>Could not load categories.</li>';
    });
  }

  function buildParams(state) {
    var params = {
      per_page: PER_PAGE,
      page: state.page,
      category: state.category || undefined,
      min_price: state.min_price || undefined,
      max_price: state.max_price || undefined,
    };
    if (state.orderby === 'price-desc') {
      params.orderby = 'price';
      params.order = 'desc';
    } else if (state.orderby === 'price') {
      params.orderby = 'price';
      params.order = 'asc';
    } else {
      params.orderby = state.orderby;
    }
    Object.keys(params).forEach(function (k) { if (params[k] === undefined) delete params[k]; });
    return params;
  }

  function renderPagination(state, totalPages) {
    var nav = document.querySelector('[data-pagination]');
    if (totalPages <= 1) { nav.innerHTML = ''; return; }
    var html = '';
    for (var p = 1; p <= totalPages; p++) {
      html += '<button type="button" class="btn ' + (p === state.page ? 'btn--coral' : 'btn--outline') +
        '" data-page="' + p + '">' + p + '</button>';
    }
    nav.innerHTML = html;
    nav.querySelectorAll('[data-page]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        pushState(Object.assign({}, state, { page: parseInt(btn.dataset.page, 10) }));
      });
    });
  }

  function load() {
    var state = getState();

    document.querySelector('[data-orderby]').value = state.orderby;
    document.getElementById('min-price').value = state.min_price;
    document.getElementById('max-price').value = state.max_price;
    document.querySelector('[data-clear-filters]').hidden = !(state.category || state.min_price || state.max_price);

    loadCategories(state.category);

    var grid = document.querySelector('[data-product-grid]');
    grid.innerHTML = '<li>Loading products…</li>';

    StoreAPI.getProductsWithMeta(buildParams(state)).then(function (res) {
      document.querySelector('[data-result-count]').textContent = res.total + (res.total === 1 ? ' product' : ' products');
      if (!res.items.length) {
        grid.innerHTML = '<li>No products match these filters.</li>';
      } else {
        grid.innerHTML = res.items.map(OSRender.productCardHTML).join('');
        OSRender.initLoupeOn(grid);
      }
      renderPagination(state, res.totalPages);
    }).catch(function (err) {
      grid.innerHTML = '<li>Could not load products: ' + OSRender.escapeHTML(err.message) + '</li>';
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var yearEl = document.querySelector('[data-year]');
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    document.querySelector('[data-orderby]').addEventListener('change', function (e) {
      pushState(Object.assign({}, getState(), { orderby: e.target.value, page: 1 }));
    });

    document.querySelector('[data-price-form]').addEventListener('submit', function (e) {
      e.preventDefault();
      pushState(Object.assign({}, getState(), {
        min_price: document.getElementById('min-price').value,
        max_price: document.getElementById('max-price').value,
        page: 1,
      }));
    });

    window.addEventListener('popstate', load);
    load();
  });
})();
