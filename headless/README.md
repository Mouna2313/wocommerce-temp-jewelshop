# Ora & Stone — Headless Frontend (Phase 1)

A plain HTML/CSS/JS frontend for the Ora & Stone store. No PHP, no build
step, no framework — every page is a static `.html` file with `fetch()`
calls to WooCommerce's built-in **Store API**. WordPress + WooCommerce stay
installed on the server and act purely as the backend: product catalog,
cart, and (in a later phase) orders/payments/accounts, all through
`wp-admin` as usual.

This lives on the `claude/ora-stone-headless-frontend` branch. The original
PHP theme is untouched on `claude/ora-stone-woocommerce-templates-1xnbln` —
the two are independent; pick whichever one you actually deploy.

## What's included in this phase

- **Home** (`index.html`) — hero, collections, live bestsellers grid
- **Shop** (`shop.html`) — full product grid with category filter, price
  filter, sorting, and pagination
- **Product** (`product.html`) — gallery with the signature loupe zoom,
  simple and variable products (attribute selection), add to cart
- **Cart** (`cart.html`) — live cart table, quantity update, remove,
  coupon codes, totals

All of it talks to WooCommerce's Store API (`/wp-json/wc/store/v1/...`),
which needs no plugins or API keys for this — it's built into WooCommerce
4.5+ and is what WooCommerce's own block-based cart/checkout use.

## What's NOT included yet (Phase 2)

**Checkout (real payment) and My Account (login/orders/addresses)** are not
built in this pass. Both need WordPress to expose customer identity to an
external frontend, which core WordPress doesn't do — Store API cart/browse
works anonymously by design, but placing an order as a logged-in customer,
or viewing order history, requires either:
- a JWT-auth plugin (e.g. "JWT Authentication for WP-API") so the frontend
  can log a customer in and attach that identity to API calls, or
- WordPress Application Passwords, with a small custom endpoint since
  Application Passwords aren't meant for storefront customer login.

That's a real decision for whoever manages the WordPress install, since it
changes what's exposed on the server. Once that's picked, Phase 2 adds
checkout and account pages on top of the same Store API pattern used here.

## Setup

### 1. Point the frontend at your WordPress site

Edit `js/config.js`:

```js
window.OS_CONFIG = {
  API_BASE: 'https://your-actual-wordpress-site.com',
};
```

### 2. Enable CORS on the WordPress backend

The frontend is (almost certainly) hosted on a different origin than
WordPress, and it reads two custom response headers (`Cart-Token`, `Nonce`)
that browsers hide cross-origin by default unless the server explicitly
allows it.

Copy `backend/mu-plugin-cors.php` into `wp-content/mu-plugins/` on the
WordPress site (create that folder if it doesn't exist — files there load
automatically, no plugin activation needed). Then edit the
`$ora_stone_allowed_origins` array at the top of that file to the exact
origin(s) the frontend will be served from, e.g.:

```php
$ora_stone_allowed_origins = array(
    'https://shop.example.com',
);
```

Without this step, every API call from the frontend will fail with a CORS
error in the browser console.

### 3. Serve the `headless/` folder over HTTP(S)

Any static file host works (Netlify, Vercel, GitHub Pages, S3, nginx,
`python3 -m http.server`, etc.) — there's no server-side logic in this
folder at all. Opening the files directly via `file://` will NOT work,
since `fetch()` to a remote API from a `file://` page is blocked by the
browser.

## Why Cart-Token instead of cookies

WooCommerce's default cart is a session cookie. That works fine when
WordPress renders the pages itself, but breaks down for a separately
hosted frontend: modern browsers block third-party cookies by default,
so a cross-origin `fetch()` often can't send/receive WooCommerce's session
cookie at all. The Store API's alternative — a signed `Cart-Token` header
that identifies the guest cart statelessly — sidesteps that entirely. See
`js/store-api.js` for the implementation; it's the pattern WooCommerce
itself documents for headless/mobile clients.

## Known limitation: variable product attribute matching

`js/product.js` matches selected attribute dropdowns (size, metal, etc.)
against the product's declared variations using the shape the Store API
docs describe. It's built correctly against the spec, but hasn't been
exercised against a real store's data — if a product uses an unusual
attribute setup (e.g. mixed global/custom attributes), double-check this
once it's wired up to real products, per the comment at the top of
`product.js`.

## File map

```
headless/
  index.html, shop.html, product.html, cart.html   — pages
  css/            — same theme CSS as the PHP version, unchanged
  js/
    config.js       — API_BASE, edit this first
    store-api.js     — Store API client (Cart-Token/Nonce handling)
    render-utils.js  — price formatting, product card markup
    cart-badge.js    — header cart count, shared by every page
    home.js / shop.js / product.js / cart.js — per-page logic
    loupe.js, mobile-nav.js — copied unchanged from the PHP theme
  assets/         — same placeholder photography as the PHP version
  backend/
    mu-plugin-cors.php — install on WordPress, see Setup step 2
```
