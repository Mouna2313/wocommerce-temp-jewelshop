# Ora & Stone — Headless Frontend

A plain HTML/CSS/JS frontend for the Ora & Stone store. No PHP, no build
step, no framework — every page is a static `.html` file with `fetch()`
calls to WordPress/WooCommerce APIs. WordPress + WooCommerce stay installed
on the server and act purely as the backend: product catalog, cart, orders,
customer accounts, all through `wp-admin` as usual.

This lives on the `claude/ora-stone-headless-frontend` branch. The original
PHP theme is untouched on `claude/ora-stone-woocommerce-templates-1xnbln` —
the two are independent; pick whichever one you actually deploy.

**This build is complete end to end**: browsing, cart, checkout (real
orders), login, registration, and order history all work. Getting it live
against a real store is a matter of installing two things on the WordPress
side and changing one line in the frontend — see Setup below. The one thing
that's a genuine, inherent limitation rather than something left unbuilt is
noted in "Payment methods" below (card payments need their own account
with a payment provider — nothing about the frontend can shortcut that).

## Pages

- **Home** (`index.html`) — hero, collections, live bestsellers grid
- **Shop** (`shop.html`) — product grid with category filter, price filter,
  sorting, pagination
- **Product** (`product.html`) — gallery with the signature loupe zoom,
  simple and variable products (attribute selection), add to cart
- **Cart** (`cart.html`) — live cart table, quantity update, remove, coupon
  codes, totals
- **Checkout** (`checkout.html`) — billing/shipping address, payment method,
  places a real WooCommerce order
- **Login / Register** (`login.html`)
- **My Account** (`account.html`) — dashboard, order history, address book,
  account details
- **Order Detail** (`order.html`) — single order with the same fulfillment
  timeline (Placed → Processing → Shipped → Delivered) as the PHP theme

## Try it now with dummy data (no WordPress needed)

`js/config.js` currently points at a bundled dummy backend
(`mock-backend/server.py`) instead of a real WordPress site, so the whole
frontend can be clicked through today with zero setup — browsing, a
variable product (sizes), cart, a full checkout that produces a real order
in the dummy data, login/register, and order history:

```bash
# terminal 1 — the dummy backend
cd headless/mock-backend
python3 server.py            # http://localhost:8090

# terminal 2 — the frontend itself
cd headless
python3 -m http.server 8099  # http://localhost:8099
```

Open `http://localhost:8099/index.html`.

- Coupon code `WELCOME10` works on the cart page.
- Demo login: **demo@orastone.com / demo1234** — has 2 sample orders already
  in its order history, or register a brand-new account from `login.html`.
- All state (accounts, carts, orders) lives in memory in the Python process
  and resets when you stop it — it's a stand-in for a database, not one.

**Swapping to a real WordPress site later is a one-line change** — edit
`API_BASE` in `js/config.js` — plus installing the two backend pieces below.
Nothing else in the frontend changes; it's the same API shapes either way.

## Setup (for the real WordPress backend)

Three things, all on the WordPress side:

### 1. Install "JWT Authentication for WP REST API"

In `wp-admin`: Plugins → Add New → search that exact name → Install →
Activate (it's free, on the WordPress.org plugin directory). Then add to
`wp-config.php` (anywhere above the `/* That's all, stop editing! */` line):

```php
define( 'JWT_AUTH_SECRET_KEY', 'put-a-long-random-string-here' );
define( 'JWT_AUTH_CORS_ENABLE', true );
```

This is what makes `login.html` work — it gives the frontend a
`POST /wp-json/jwt-auth/v1/token` endpoint to log a customer in, and makes
`wp_get_current_user()` resolve correctly for any REST request (including
WooCommerce's own Store API) that carries the token it returns.

**Common gotcha**: some Apache/shared hosts strip the `Authorization`
header before PHP ever sees it. If login works but every authenticated
request afterward gets rejected, add this to the site's `.htaccess`:

```apache
RewriteEngine On
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

### 2. Install the two mu-plugins

Copy both files from `headless/backend/` into `wp-content/mu-plugins/` on
the WordPress site (create that folder if it doesn't exist — files there
load automatically, no plugin activation needed):

- **`mu-plugin-cors.php`** — lets the separately-hosted frontend call these
  APIs cross-origin at all. Edit the `$ora_stone_allowed_origins` array at
  the top to the frontend's real domain, e.g.:
  ```php
  $ora_stone_allowed_origins = array( 'https://shop.example.com' );
  ```
  Without this, every API call fails with a CORS error in the browser
  console.
- **`mu-plugin-account-api.php`** — adds the routes WooCommerce doesn't
  have out of the box: registration, "my orders", single order detail, and
  address-book/profile editing. Requires step 1 above (it reads the
  logged-in customer via the JWT plugin).

### 3. Enable a payment method that supports Store API checkout

In `wp-admin`: WooCommerce → Settings → Payments, enable **Cash on
Delivery** and/or **Direct Bank Transfer** — both are built into WooCommerce
core, need no API keys, and both support the Blocks/Store API checkout that
`checkout.html` uses. `checkout.html` offers exactly these two.

**Card payments (Stripe, PayPal, etc.) are a separate, real requirement**:
they need an actual merchant account with that provider and its own API
keys — that's not something any amount of frontend code can stand in for.
If/when that's set up with a Blocks-compatible WooCommerce extension, the
same `StoreAPI.checkout()` call in `js/checkout.js` already sends a generic
`payment_method` id, so adding the option to `checkout.html`'s payment
method list is the only frontend change needed.

### 4. Point the frontend at your WordPress site

Edit `js/config.js`:

```js
window.OS_CONFIG = {
  API_BASE: 'https://your-actual-wordpress-site.com', // was mock-backend's localhost:8090
};
```

### 5. Serve the `headless/` folder over HTTP(S)

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

Login works the same way: the JWT plugin gives a bearer token instead of a
session cookie, stored in `localStorage` (`js/auth.js`) so it persists
across tabs and restarts, unlike the per-tab `sessionStorage` cart token.

## Known limitations

- **Variable product attribute matching** (`js/product.js`) is built to the
  documented Store API shape and has been exercised end-to-end against the
  dummy backend's variable product (a ring with four sizes) — selecting an
  option correctly resolves to that exact variation's price/stock. What's
  *not* verified is a real store's data: if a product uses an unusual
  attribute setup (mixed global/custom attributes), double-check this once
  it's wired up to real WooCommerce products.
- **Shipping cost is always $0** at checkout — the Store API can calculate
  real shipping rates, but that needs the store's actual shipping zones
  configured first; wiring that up is a small follow-up once real zones
  exist.
- **Password reset** goes through WordPress's own `wp-login.php` page
  (linked from Account Details), not a custom-built frontend flow — that's
  a deliberate choice, since WP's built-in flow already handles the
  security-sensitive parts (emailing a reset link) correctly.

## File map

```
headless/
  index.html, shop.html, product.html, cart.html,
  checkout.html, login.html, account.html, order.html   — pages
  css/            — same theme CSS as the PHP version, unchanged
  js/
    config.js        — API_BASE, edit this first
    store-api.js      — Store API client (Cart-Token/Nonce/Auth headers)
    auth.js           — login/register/logout, JWT storage
    render-utils.js   — price formatting, product card markup
    cart-badge.js, nav-auth.js — shared header state, every page
    home.js / shop.js / product.js / cart.js
    checkout.js / login.js / account.js / order.js
    loupe.js, mobile-nav.js — copied unchanged from the PHP theme
  assets/         — same placeholder photography as the PHP version
  backend/
    mu-plugin-cors.php         — install on WordPress, see Setup step 2
    mu-plugin-account-api.php  — install on WordPress, see Setup step 2
  mock-backend/
    server.py       — dummy backend (catalog + cart + accounts + orders)
                       for demoing without WordPress
```
