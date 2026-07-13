/**
 * Ora & Stone — shared render helpers for the headless frontend.
 * Store API responses carry currency formatting metadata per-object
 * (prices are integers in minor units, e.g. cents) — these helpers
 * turn that into the same markup content-product.php used to produce.
 */
(function (global) {
  function formatMoney(minorAmount, meta) {
    if (minorAmount === undefined || minorAmount === null || minorAmount === '') return '';
    var minorUnit = parseInt(meta.currency_minor_unit, 10) || 0;
    var value = (parseInt(minorAmount, 10) / Math.pow(10, minorUnit)).toFixed(minorUnit);
    var parts = value.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, meta.currency_thousand_separator || ',');
    var joined = minorUnit > 0 ? parts.join(meta.currency_decimal_separator || '.') : parts[0];
    return (meta.currency_prefix || '') + joined + (meta.currency_suffix || '');
  }

  function isOnSale(product) {
    var p = product.prices;
    return !!p.sale_price && p.sale_price !== '' && p.sale_price !== p.regular_price;
  }

  function priceHTML(product) {
    var p = product.prices;
    if (isOnSale(product)) {
      return '<del>' + formatMoney(p.regular_price, p) + '</del> ' +
             '<ins>' + formatMoney(p.sale_price, p) + '</ins>';
    }
    if (product.type === 'variable' && p.price_range) {
      return formatMoney(p.price_range.min_amount, p) + ' – ' + formatMoney(p.price_range.max_amount, p);
    }
    return formatMoney(p.price, p);
  }

  function isNew(product) {
    if (!product.date_created) return false;
    var created = new Date(product.date_created).getTime();
    return (Date.now() - created) < (30 * 24 * 60 * 60 * 1000);
  }

  function badgeHTML(product) {
    if (!product.is_in_stock) {
      return '<span class="product-card__badge"><span class="badge badge--sold">Sold Out</span></span>';
    }
    var parts = [];
    if (isOnSale(product)) parts.push('<span class="badge badge--sale">Sale</span>');
    if (isNew(product)) parts.push('<span class="badge badge--new">New</span>');
    return parts.length ? '<span class="product-card__badge">' + parts.join('') + '</span>' : '';
  }

  function productImage(product) {
    var img = (product.images && product.images[0]) || {};
    return {
      src: img.src || '',
      alt: img.alt || product.name || '',
    };
  }

  function productCardHTML(product) {
    var img = productImage(product);
    var href = 'product.html?id=' + encodeURIComponent(product.id);
    return (
      '<li class="product-card">' +
        '<div class="product-card__media loupe">' +
          '<a class="product-card__media-link" href="' + href + '" tabindex="-1" aria-hidden="true">' +
            '<img src="' + img.src + '" alt="' + escapeHTML(img.alt) + '" loading="lazy">' +
          '</a>' +
          badgeHTML(product) +
        '</div>' +
        '<a class="product-card__link" href="' + href + '">' +
          '<h3 class="product-card__name">' + escapeHTML(product.name) + '</h3>' +
          '<span class="product-card__price">' + priceHTML(product) + '</span>' +
        '</a>' +
      '</li>'
    );
  }

  function escapeHTML(str) {
    var div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
  }

  function initLoupeOn(root) {
    if (window.matchMedia('(hover: none)').matches) return;
    (root || document).querySelectorAll('.loupe').forEach(function (el) {
      if (el.dataset.loupeInit) return;
      el.dataset.loupeInit = '1';
      if (window.OSInitLoupeEl) window.OSInitLoupeEl(el);
    });
  }

  global.OSRender = {
    formatMoney: formatMoney,
    isOnSale: isOnSale,
    priceHTML: priceHTML,
    badgeHTML: badgeHTML,
    productImage: productImage,
    productCardHTML: productCardHTML,
    escapeHTML: escapeHTML,
    initLoupeOn: initLoupeOn,
  };
})(window);
