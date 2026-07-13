/**
 * Ora & Stone — headless product detail page.
 *
 * Variable-product attribute -> variation matching is best-effort: the
 * Store API returns each variation's selected attribute values as
 * {attribute, value} pairs, and this matches them against the parent's
 * declared attributes by taxonomy/name. It's built to the documented
 * Store API v1 shape, but hasn't been run against a live store — if a
 * product uses unusual attribute setups, double check this once
 * wired up to real data (see headless/README.md).
 */
(function () {
  var root = document.querySelector('[data-product-root]');
  var productId = new URLSearchParams(location.search).get('id');
  var currentProduct = null;
  var currentVariation = null;

  function attributeKey(attr) {
    if (attr.taxonomy) return 'attribute_' + attr.taxonomy;
    return 'attribute_' + String(attr.name).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
  }

  function bareAttributeName(attr) {
    return (attr.taxonomy || attr.name || '').replace(/^attribute_/, '').toLowerCase();
  }

  function galleryHTML(product) {
    var images = product.images && product.images.length ? product.images : [{ src: '', alt: product.name }];
    var main = images[0];
    var thumbs = images.length > 1
      ? '<div class="product-gallery__thumbs" role="tablist" aria-label="Product images">' +
        images.map(function (img, i) {
          return '<button type="button" class="product-gallery__thumb' + (i === 0 ? ' is-active' : '') + '" ' +
            'data-full="' + img.src + '" data-alt="' + OSRender.escapeHTML(img.alt) + '" aria-pressed="' + (i === 0 ? 'true' : 'false') + '">' +
            '<img src="' + img.src + '" alt="' + OSRender.escapeHTML(img.alt) + '" loading="lazy"></button>';
        }).join('') + '</div>'
      : '';

    return (
      '<div class="product-gallery">' +
        '<div class="product-gallery__main loupe" id="product-gallery-main">' +
          (OSRender.isOnSale(product) ? '<span class="product-gallery__badge"><span class="badge badge--sale">Sale</span></span>' : '') +
          '<img class="product-gallery__image" src="' + main.src + '" alt="' + OSRender.escapeHTML(main.alt) + '">' +
        '</div>' +
        thumbs +
      '</div>'
    );
  }

  function variationsTableHTML(product) {
    var variableAttrs = (product.attributes || []).filter(function (a) { return a.has_variations; });
    if (product.type !== 'variable' || !variableAttrs.length) return '';

    var rows = variableAttrs.map(function (attr) {
      var options = attr.terms.map(function (t) {
        return '<option value="' + OSRender.escapeHTML(t.slug || t.name) + '">' + OSRender.escapeHTML(t.name) + '</option>';
      }).join('');
      return (
        '<tr>' +
          '<th class="label">' + OSRender.escapeHTML(attr.name) + '</th>' +
          '<td class="value">' +
            '<select data-variation-attr="' + attributeKey(attr) + '" data-attr-name="' + OSRender.escapeHTML(bareAttributeName(attr)) + '">' +
              '<option value="">Choose an option</option>' + options +
            '</select>' +
          '</td>' +
        '</tr>'
      );
    }).join('');

    return (
      '<table class="variations" data-variations-table><tbody>' + rows + '</tbody></table>' +
      '<a class="reset_variations" href="#" data-reset-variations hidden>Clear selection</a>' +
      '<div class="woocommerce-variation-price" data-variation-price hidden></div>' +
      '<div class="woocommerce-variation-availability" data-variation-availability hidden></div>'
    );
  }

  function summaryHTML(product) {
    var isVariable = product.type === 'variable';
    return (
      '<h1 class="product_title">' + OSRender.escapeHTML(product.name) + '</h1>' +
      '<p class="price" data-price-display>' + (isVariable ? '' : OSRender.priceHTML(product)) + '</p>' +
      '<div class="woocommerce-product-details__short-description">' + (product.short_description || '') + '</div>' +
      variationsTableHTML(product) +
      '<form class="cart" data-add-to-cart-form>' +
        '<div class="quantity">' +
          '<label class="visually-hidden" for="qty">Quantity</label>' +
          '<input type="number" id="qty" class="qty" min="1" value="1">' +
        '</div>' +
        '<button type="submit" class="button" data-add-to-cart-btn' + (isVariable ? ' disabled' : '') + '>' +
          (product.is_in_stock ? 'Add to Cart' : 'Sold Out') +
        '</button>' +
      '</form>' +
      '<p data-cart-feedback role="status" aria-live="polite" style="margin-top:var(--space-sm)"></p>'
    );
  }

  function render(product) {
    currentProduct = product;
    document.querySelector('[data-title]').textContent = product.name + ' — Ora & Stone';
    root.innerHTML =
      '<div id="product-' + product.id + '" class="product-single">' +
        '<div class="product-single__layout">' +
          '<div class="product-single__gallery">' + galleryHTML(product) + '</div>' +
          '<div class="summary entry-summary product-single__summary">' + summaryHTML(product) + '</div>' +
        '</div>' +
      '</div>';

    OSRender.initLoupeOn(root);
    wireGallery();
    if (product.type === 'variable') wireVariations();
    wireAddToCart();
  }

  function wireGallery() {
    var mainImg = root.querySelector('.product-gallery__main img');
    var thumbs = root.querySelectorAll('.product-gallery__thumb');
    thumbs.forEach(function (thumb) {
      thumb.addEventListener('click', function () {
        if (!thumb.dataset.full) return;
        mainImg.src = thumb.dataset.full;
        mainImg.alt = thumb.dataset.alt || '';
        thumbs.forEach(function (t) { t.classList.remove('is-active'); t.setAttribute('aria-pressed', 'false'); });
        thumb.classList.add('is-active');
        thumb.setAttribute('aria-pressed', 'true');
      });
    });
  }

  function findMatchingVariationStub(selected) {
    // selected: { attrName: slugValue, ... } — all must be chosen.
    var keys = Object.keys(selected);
    if (keys.some(function (k) { return !selected[k]; })) return null;

    return (currentProduct.variations || []).find(function (variation) {
      return keys.every(function (attrName) {
        var match = (variation.attributes || []).find(function (a) {
          return (a.attribute || '').replace(/^attribute_/, '').toLowerCase() === attrName;
        });
        if (!match) return false;
        // Empty value on the variation means "any" for that attribute.
        return match.value === '' || String(match.value).toLowerCase() === String(selected[attrName]).toLowerCase();
      });
    });
  }

  function wireVariations() {
    var selects = root.querySelectorAll('[data-variation-attr]');
    var resetLink = root.querySelector('[data-reset-variations]');
    var priceEl = root.querySelector('[data-variation-price]');
    var availEl = root.querySelector('[data-variation-availability]');
    var addBtn = root.querySelector('[data-add-to-cart-btn]');

    function currentSelection() {
      var sel = {};
      selects.forEach(function (s) { sel[s.dataset.attrName] = s.value; });
      return sel;
    }

    function updateFromSelection() {
      var selection = currentSelection();
      var anyChosen = Object.keys(selection).some(function (k) { return selection[k]; });
      resetLink.hidden = !anyChosen;

      var stub = findMatchingVariationStub(selection);
      if (!stub) {
        currentVariation = null;
        priceEl.hidden = true;
        availEl.hidden = true;
        addBtn.disabled = true;
        addBtn.textContent = 'Add to Cart';
        return;
      }

      StoreAPI.getProduct(stub.id).then(function (variation) {
        currentVariation = variation;
        priceEl.hidden = false;
        priceEl.innerHTML = OSRender.priceHTML(variation);
        availEl.hidden = !!variation.is_in_stock;
        availEl.textContent = variation.is_in_stock ? '' : 'Out of stock';
        addBtn.disabled = !variation.is_in_stock;
        addBtn.textContent = variation.is_in_stock ? 'Add to Cart' : 'Sold Out';
      }).catch(function () {
        addBtn.disabled = true;
      });
    }

    selects.forEach(function (s) { s.addEventListener('change', updateFromSelection); });
    resetLink.addEventListener('click', function (e) {
      e.preventDefault();
      selects.forEach(function (s) { s.value = ''; });
      updateFromSelection();
    });
  }

  function wireAddToCart() {
    var form = root.querySelector('[data-add-to-cart-form]');
    var feedback = root.querySelector('[data-cart-feedback]');
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var qty = parseInt(document.getElementById('qty').value, 10) || 1;
      var btn = root.querySelector('[data-add-to-cart-btn]');
      var variationPayload = [];

      if (currentProduct.type === 'variable') {
        if (!currentVariation) {
          feedback.textContent = 'Choose an option for every attribute first.';
          return;
        }
        root.querySelectorAll('[data-variation-attr]').forEach(function (s) {
          variationPayload.push({ attribute: s.dataset.variationAttr, value: s.value });
        });
      }

      btn.disabled = true;
      StoreAPI.addItem(currentProduct.id, qty, variationPayload).then(function (cart) {
        feedback.textContent = 'Added to cart.';
        window.OSUpdateCartBadge(cart);
      }).catch(function (err) {
        feedback.textContent = 'Could not add to cart: ' + err.message;
      }).finally(function () {
        btn.disabled = false;
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var yearEl = document.querySelector('[data-year]');
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    if (!productId) {
      root.innerHTML = '<p>No product specified. <a href="shop.html">Back to shop</a>.</p>';
      return;
    }

    StoreAPI.getProduct(productId).then(render).catch(function (err) {
      root.innerHTML = '<p>Could not load this product: ' + OSRender.escapeHTML(err.message) + '. <a href="shop.html">Back to shop</a>.</p>';
    });
  });
})();
