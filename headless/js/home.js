(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var yearEl = document.querySelector('[data-year]');
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    var grid = document.querySelector('[data-bestsellers]');
    if (!grid) return;

    StoreAPI.getProducts({ orderby: 'popularity', per_page: 4 })
      .then(function (products) {
        if (!products.length) {
          grid.innerHTML = '<p>No products yet — add some in WooCommerce.</p>';
          return;
        }
        grid.innerHTML = products.map(OSRender.productCardHTML).join('');
        OSRender.initLoupeOn(grid);
      })
      .catch(function (err) {
        grid.innerHTML = '<p>Could not load products: ' + OSRender.escapeHTML(err.message) + '</p>';
      });
  });
})();
