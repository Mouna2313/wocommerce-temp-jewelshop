# Ora & Stone

Custom WordPress + WooCommerce theme for Ora & Stone, a fine jewelry e-commerce brand.

Design tokens live in `css/tokens.css` — background, ink, and accent colors (coral,
teal, pale blue, red) as CSS custom properties, everything else in the theme reads
from those. The signature interaction is the jeweler's-loupe cursor-zoom on product
imagery (`js/loupe.js`), applied via the `.loupe` class on any element containing an
`<img>`.

## Template Preview

`preview/index.html` is a **static, self-contained mock** of the theme's page
templates — Home, Shop, Product, Cart, Checkout, Account, Order Detail, Wishlist,
and About. It exists so the design can be reviewed without a running WordPress +
WooCommerce install.

**To view it:** download or clone the repo and open `preview/index.html` directly
in a browser. No build step, server, or dependencies required.

Use the dark switcher bar at the top of the page to jump between templates.

**What it is:**
- The theme's real `css/*.css` and `js/*.js` files, concatenated verbatim into the
  page — not a redrawn approximation. If it looks right here, it's using the actual
  theme styles.
- Real placeholder photography (`assets/*.jpg`) embedded for the images that have
  fixed paths (hero, collections, story teasers). Generic product photos (shop
  grid, single product gallery, cart thumbnails) use a small inline script that
  generates on-brand gradient placeholders, since there's no way to mock distinct
  photography per product without real inventory.
- The loupe zoom, mobile nav toggle, and wishlist button states are all live and
  interactive — hover a product photo, resize the window, click a heart icon.

**What it isn't:**
- Not connected to WordPress or WooCommerce. There's no database, no cart state,
  no real checkout, no plugin behavior (payment gateways, shipping calculations,
  coupon validation, AJAX cart updates). Layout and interaction fidelity, not data
  fidelity.
- Not auto-updated. It's a snapshot generated from the theme files at a point in
  time. If the theme's CSS/JS/templates change afterward, `preview/index.html`
  will drift out of sync until it's regenerated.

**Regenerating it after a design change:** concatenate the theme's CSS files in
their `functions.php` enqueue order (tokens → base → buttons → navbar →
mobile-nav → hero → footer → woocommerce → wishlist → page) and inline the result
into a copy of the mock's HTML shell in place of the theme `<style>` block. There
isn't a build script for this in the repo yet — it was assembled by hand for this
round of design review.

## Structure

```
functions.php              Theme setup, asset enqueueing, hooks
header.php / footer.php    Site chrome
front-page.php             Homepage
page.php / page-about.php  Generic page + Brand Story page templates
inc/                       Attribute registration, wishlist, order helpers
css/                       tokens.css first — everything else depends on it
js/                        loupe.js, mobile-nav.js, product-gallery.js, wishlist.js
woocommerce/               Template overrides (shop, product, cart, checkout, account, wishlist)
assets/                    Placeholder photography referenced by front-page.php / page-about.php
preview/                   Static HTML preview of the templates (see above)
```
