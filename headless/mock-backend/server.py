#!/usr/bin/env python3
"""
Ora & Stone — dummy Store API server.

Speaks the same wire format as WooCommerce's real Store API
(/wp-json/wc/store/v1/...), with an in-memory catalog and cart instead of
a real WordPress + WooCommerce install. Point headless/js/config.js's
API_BASE at this server (default http://localhost:8090) to click through
the whole frontend — browse, filter, add to cart, change quantities,
apply a coupon — before a real WordPress site exists.

Swapping to the real backend later is a one-line change in config.js;
nothing else in the frontend needs to know the difference, since this
server returns the same JSON shapes and the same Cart-Token/Nonce
headers the real Store API does.

Zero dependencies — stdlib only, so it runs anywhere Python 3 does:
    python3 server.py [port]      # default port 8090

Demo-only. Cart state lives in memory and resets when the process
restarts. Not meant to be exposed publicly or used in production.
"""
import datetime
import json
import re
import sys
import uuid
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import urlparse, parse_qs

PORT = int(sys.argv[1]) if len(sys.argv) > 1 else 8090
CURRENCY = {
    "currency_code": "USD",
    "currency_symbol": "$",
    "currency_minor_unit": 2,
    "currency_decimal_separator": ".",
    "currency_thousand_separator": ",",
    "currency_prefix": "$",
    "currency_suffix": "",
}


def money(amount):
    """Dollars -> Store API's integer-minor-units string, e.g. 1299.5 -> '129950'."""
    return str(int(round(amount * 100)))


def img(filename, alt):
    return {"src": "http://localhost:%d/assets/%s" % (PORT, filename), "alt": alt}


CATEGORIES = [
    {"id": 1, "name": "Rings", "slug": "rings", "count": 3},
    {"id": 2, "name": "Necklaces", "slug": "necklaces", "count": 2},
    {"id": 3, "name": "Earrings", "slug": "earrings", "count": 2},
    {"id": 4, "name": "Bracelets", "slug": "bracelets", "count": 1},
]

SIZE_ATTR = {
    "id": 1, "name": "Size", "taxonomy": "pa_size", "has_variations": True,
    "terms": [{"id": i, "name": str(4 + i), "slug": str(4 + i)} for i in range(1, 5)],
}

PRODUCTS = [
    {
        "id": 1, "name": "Aurora Solitaire Ring", "type": "simple",
        "categories": [CATEGORIES[0]],
        "images": [img("collection-rings.jpg", "Aurora Solitaire Ring")],
        "prices": {"price": money(1299), "regular_price": money(1499), "sale_price": money(1299), **CURRENCY},
        "is_in_stock": True, "date_created": "2024-01-15T00:00:00",
        "short_description": "<p>A single brilliant-cut stone set in solid 14k gold — the everyday staple.</p>",
        "attributes": [], "variations": [],
    },
    {
        "id": 2, "name": "Meridian Band", "type": "variable",
        "categories": [CATEGORIES[0]],
        "images": [img("collection-rings.jpg", "Meridian Band")],
        "prices": {
            "price": money(890), "regular_price": money(890), "sale_price": "",
            "price_range": {"min_amount": money(890), "max_amount": money(950)}, **CURRENCY,
        },
        "is_in_stock": True, "date_created": "2024-01-15T00:00:00",
        "short_description": "<p>A slim stacking band in brushed gold. Sized 5 through 8.</p>",
        "attributes": [SIZE_ATTR],
        "variations": [{"id": 100 + i, "attributes": [{"attribute": "pa_size", "value": str(4 + i)}]} for i in range(1, 5)],
    },
    {
        "id": 3, "name": "Halo Cluster Ring", "type": "simple",
        "categories": [CATEGORIES[0]],
        "images": [img("collection-rings.jpg", "Halo Cluster Ring")],
        "prices": {"price": money(1050), "regular_price": money(1050), "sale_price": "", **CURRENCY},
        "is_in_stock": True, "date_created": "2023-06-01T00:00:00",
        "short_description": "<p>A cluster of pavé stones haloed around a central gem.</p>",
        "attributes": [], "variations": [],
    },
    {
        "id": 4, "name": "Lumen Pendant Necklace", "type": "simple",
        "categories": [CATEGORIES[1]],
        "images": [img("collection-necklaces.jpg", "Lumen Pendant Necklace")],
        "prices": {"price": money(780), "regular_price": money(890), "sale_price": money(780), **CURRENCY},
        "is_in_stock": True, "date_created": "2024-06-20T00:00:00",
        "short_description": "<p>A single bezel-set stone on a fine cable chain.</p>",
        "attributes": [], "variations": [],
    },
    {
        "id": 5, "name": "Chain Layer Necklace", "type": "simple",
        "categories": [CATEGORIES[1]],
        "images": [img("collection-necklaces.jpg", "Chain Layer Necklace")],
        "prices": {"price": money(620), "regular_price": money(620), "sale_price": "", **CURRENCY},
        "is_in_stock": True, "date_created": "2023-03-01T00:00:00",
        "short_description": "<p>Two fine chains, one length, layered so you don't have to.</p>",
        "attributes": [], "variations": [],
    },
    {
        "id": 6, "name": "Drop Stone Earrings", "type": "simple",
        "categories": [CATEGORIES[2]],
        "images": [img("collection-earrings.jpg", "Drop Stone Earrings")],
        "prices": {"price": money(540), "regular_price": money(540), "sale_price": "", **CURRENCY},
        "is_in_stock": True, "date_created": "2023-09-01T00:00:00",
        "short_description": "<p>A faceted drop stone on a delicate gold wire.</p>",
        "attributes": [], "variations": [],
    },
    {
        "id": 7, "name": "Hoop Earrings", "type": "simple",
        "categories": [CATEGORIES[2]],
        "images": [img("collection-earrings.jpg", "Hoop Earrings")],
        "prices": {"price": money(390), "regular_price": money(390), "sale_price": "", **CURRENCY},
        "is_in_stock": False, "date_created": "2023-01-01T00:00:00",
        "short_description": "<p>Classic huggie hoops in solid gold.</p>",
        "attributes": [], "variations": [],
    },
    {
        "id": 8, "name": "Tennis Bracelet", "type": "simple",
        "categories": [CATEGORIES[3]],
        "images": [img("collection-earrings.jpg", "Tennis Bracelet")],
        "prices": {"price": money(2100), "regular_price": money(2100), "sale_price": "", **CURRENCY},
        "is_in_stock": True, "date_created": "2024-07-01T00:00:00",
        "short_description": "<p>A continuous line of matched stones, hand-set in gold.</p>",
        "attributes": [], "variations": [],
    },
]

VARIATIONS_BY_ID = {}
for i, size in enumerate(("5", "6", "7", "8"), start=1):
    VARIATIONS_BY_ID[100 + i] = {
        "id": 100 + i, "name": "Meridian Band — Size %s" % size, "type": "variation", "parent_id": 2,
        "categories": [CATEGORIES[0]],
        "images": [img("collection-rings.jpg", "Meridian Band")],
        "prices": {"price": money(890 + (i - 1) * 20), "regular_price": money(890 + (i - 1) * 20), "sale_price": "", **CURRENCY},
        "is_in_stock": size != "8",
        "date_created": "2024-01-15T00:00:00",
        "short_description": "",
        "attributes": [], "variations": [],
    }

PRODUCTS_BY_ID = {p["id"]: p for p in PRODUCTS}
PRODUCTS_BY_ID.update(VARIATIONS_BY_ID)

CARTS = {}
COUPONS = {"WELCOME10": 0.10}

# ---- Auth / accounts / orders (stands in for JWT-auth plugin + mu-plugin-account-api.php) ----
USERS = {
    "demo@orastone.com": {
        "id": 1, "password": "demo1234", "first_name": "Demo", "last_name": "Customer",
        "billing": {"first_name": "Demo", "last_name": "Customer", "address_1": "123 Market St", "address_2": "",
                    "city": "Portland", "state": "OR", "postcode": "97201", "country": "US",
                    "phone": "555-0100", "email": "demo@orastone.com"},
        "shipping": {"first_name": "Demo", "last_name": "Customer", "address_1": "123 Market St", "address_2": "",
                     "city": "Portland", "state": "OR", "postcode": "97201", "country": "US", "phone": "555-0100"},
    },
}
TOKENS = {}
ORDER_SEQ = [1000]
ORDERS = [
    {
        "id": 1000, "number": "1000", "customer_email": "demo@orastone.com", "status": "completed",
        "date_created": "2026-06-02T10:00:00", "payment_method_title": "Cash on Delivery",
        "items": [{"name": "Aurora Solitaire Ring", "quantity": 1, "total": "1299.00",
                   "image": "http://localhost:%d/assets/collection-rings.jpg" % PORT, "product_id": 1}],
        "subtotal": "1299.00", "shipping_total": "0.00", "total_tax": "0.00", "total": "1299.00",
        "billing_address": "Demo Customer<br/>123 Market St<br/>Portland, OR 97201",
        "shipping_address": "Demo Customer<br/>123 Market St<br/>Portland, OR 97201",
    },
    {
        "id": 999, "number": "999", "customer_email": "demo@orastone.com", "status": "processing",
        "date_created": "2026-07-01T14:30:00", "payment_method_title": "Direct Bank Transfer",
        "items": [{"name": "Drop Stone Earrings", "quantity": 1, "total": "540.00",
                   "image": "http://localhost:%d/assets/collection-earrings.jpg" % PORT, "product_id": 6}],
        "subtotal": "540.00", "shipping_total": "0.00", "total_tax": "0.00", "total": "540.00",
        "billing_address": "Demo Customer<br/>123 Market St<br/>Portland, OR 97201",
        "shipping_address": "Demo Customer<br/>123 Market St<br/>Portland, OR 97201",
    },
]


def order_timeline(status):
    if status in ("cancelled", "failed", "refunded"):
        messages = {"cancelled": "This order was cancelled.", "failed": "Payment failed for this order.", "refunded": "This order was refunded."}
        return True, messages[status], []
    reached = {"placed": True, "processing": status in ("processing", "completed"), "shipped": status == "completed", "delivered": status == "completed"}
    labels = {"placed": "Order Placed", "processing": "Processing", "shipped": "Shipped", "delivered": "Delivered"}
    current = "placed"
    for key in ("placed", "processing", "shipped", "delivered"):
        if reached[key]:
            current = key
    timeline = [{"key": k, "label": labels[k], "reached": reached[k], "is_current": k == current} for k in labels]
    return False, "", timeline


def format_order_summary(order):
    return {"id": order["id"], "number": order["number"], "status": order["status"],
            "date_created": order["date_created"], "total": order["total"], "currency": "USD",
            "item_count": sum(i["quantity"] for i in order["items"])}


def format_order_detail(order):
    is_stopped, stopped_message, timeline = order_timeline(order["status"])
    return dict(order, status_label=order["status"].capitalize(), is_stopped=is_stopped,
                stopped_message=stopped_message, timeline=timeline, tracking_number="", tracking_carrier="",
                currency="USD")


def new_cart():
    return {"items": {}, "coupons": []}


def cart_response(cart):
    items = []
    subtotal = 0.0
    for key, entry in cart["items"].items():
        product = PRODUCTS_BY_ID[entry["id"]]
        unit_price = int(product["prices"]["price"]) / 100.0
        line_subtotal = unit_price * entry["quantity"]
        subtotal += line_subtotal
        items.append({
            "key": key, "id": entry["id"], "quantity": entry["quantity"],
            "name": product["name"], "images": product["images"],
            "permalink": "product.html?id=%d" % product.get("parent_id", product["id"]),
            "prices": product["prices"],
            "totals": {"line_subtotal": money(line_subtotal), "line_total": money(line_subtotal), **CURRENCY},
            "quantity_limits": {"minimum": 1, "maximum": 20, "multiple_of": 1, "editable": True},
        })

    discount = subtotal * sum(COUPONS.get(c, 0) for c in cart["coupons"])
    total = subtotal - discount

    return {
        "items": items,
        "items_count": sum(e["quantity"] for e in cart["items"].values()),
        "coupons": [{"code": c} for c in cart["coupons"]],
        "needs_payment": total > 0,
        "needs_shipping": True,
        "totals": {
            "total_items": money(subtotal), "total_shipping": money(0), "total_tax": money(0),
            "total_discount": money(discount), "total_price": money(total), **CURRENCY,
        },
        "errors": [],
    }


class Handler(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        sys.stderr.write("%s - %s\n" % (self.address_string(), fmt % args))

    def _cors(self):
        origin = self.headers.get("Origin", "*")
        self.send_header("Access-Control-Allow-Origin", origin)
        self.send_header("Vary", "Origin")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type, Authorization, Nonce, Cart-Token")
        self.send_header("Access-Control-Expose-Headers", "Nonce, Cart-Token, X-WP-Total, X-WP-TotalPages")

    def _cart_token(self):
        token = self.headers.get("Cart-Token")
        if not token or token not in CARTS:
            token = str(uuid.uuid4())
            CARTS[token] = new_cart()
        return token

    def _current_user_email(self):
        """Stands in for the JWT plugin's determine_current_user hook."""
        auth = self.headers.get("Authorization", "")
        if not auth.startswith("Bearer "):
            return None
        return TOKENS.get(auth[len("Bearer "):])

    def _send_json(self, payload, status=200, extra_headers=None, cart_token=None):
        body = json.dumps(payload).encode("utf-8")
        self.send_response(status)
        self._cors()
        self.send_header("Content-Type", "application/json")
        self.send_header("Nonce", str(uuid.uuid4()))
        if cart_token:
            self.send_header("Cart-Token", cart_token)
        for k, v in (extra_headers or {}).items():
            self.send_header(k, v)
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def _send_static(self, path):
        import os
        assets_dir = os.path.join(os.path.dirname(__file__), "..", "assets")
        filename = os.path.basename(path)
        full = os.path.join(assets_dir, filename)
        if not os.path.isfile(full):
            self.send_response(404)
            self._cors()
            self.end_headers()
            return
        with open(full, "rb") as f:
            data = f.read()
        self.send_response(200)
        self._cors()
        self.send_header("Content-Type", "image/jpeg")
        self.send_header("Content-Length", str(len(data)))
        self.end_headers()
        self.wfile.write(data)

    def do_OPTIONS(self):
        self.send_response(200)
        self._cors()
        self.end_headers()

    def do_GET(self):
        parsed = urlparse(self.path)
        path = parsed.path
        qs = {k: v[0] for k, v in parse_qs(parsed.query).items()}

        if path.startswith("/assets/"):
            return self._send_static(path)

        if path == "/wp-json/wc/store/v1/products/categories":
            return self._send_json(CATEGORIES, cart_token=self._cart_token())

        m = re.match(r"^/wp-json/wc/store/v1/products/(\d+)$", path)
        if m:
            product = PRODUCTS_BY_ID.get(int(m.group(1)))
            if not product:
                return self._send_json({"message": "Product not found"}, status=404, cart_token=self._cart_token())
            return self._send_json(product, cart_token=self._cart_token())

        if path == "/wp-json/wc/store/v1/products":
            items = list(PRODUCTS)
            if qs.get("category"):
                items = [p for p in items if any(c["slug"] == qs["category"] for c in p["categories"])]
            if qs.get("min_price"):
                items = [p for p in items if int(p["prices"]["price"]) >= float(qs["min_price"]) * 100]
            if qs.get("max_price"):
                items = [p for p in items if int(p["prices"]["price"]) <= float(qs["max_price"]) * 100]

            orderby = qs.get("orderby", "menu_order")
            reverse = qs.get("order") == "desc"
            if orderby == "price":
                items.sort(key=lambda p: int(p["prices"]["price"]), reverse=reverse)
            elif orderby == "date":
                items.sort(key=lambda p: p["date_created"], reverse=True)
            elif orderby == "popularity":
                items.sort(key=lambda p: p["id"])

            per_page = int(qs.get("per_page", 12))
            page = int(qs.get("page", 1))
            total = len(items)
            start = (page - 1) * per_page
            page_items = items[start:start + per_page]

            headers = {
                "X-WP-Total": str(total),
                "X-WP-TotalPages": str(max(1, -(-total // per_page))),
            }
            return self._send_json(page_items, extra_headers=headers, cart_token=self._cart_token())

        if path == "/wp-json/wc/store/v1/cart":
            token = self._cart_token()
            return self._send_json(cart_response(CARTS[token]), cart_token=token)

        if path == "/wp-json/oraandstone/v1/orders":
            email = self._current_user_email()
            if not email:
                return self._send_json({"message": "You must be logged in."}, status=401)
            mine = [o for o in ORDERS if o["customer_email"] == email]
            mine.sort(key=lambda o: o["date_created"], reverse=True)
            return self._send_json([format_order_summary(o) for o in mine])

        m = re.match(r"^/wp-json/oraandstone/v1/orders/(\d+)$", path)
        if m:
            email = self._current_user_email()
            if not email:
                return self._send_json({"message": "You must be logged in."}, status=401)
            order = next((o for o in ORDERS if o["id"] == int(m.group(1))), None)
            if not order or order["customer_email"] != email:
                return self._send_json({"message": "Order not found."}, status=404)
            return self._send_json(format_order_detail(order))

        if path == "/wp-json/oraandstone/v1/account":
            email = self._current_user_email()
            if not email:
                return self._send_json({"message": "You must be logged in."}, status=401)
            user = USERS[email]
            return self._send_json({"first_name": user["first_name"], "last_name": user["last_name"],
                                     "email": email, "billing": user["billing"], "shipping": user["shipping"]})

        self.send_response(404)
        self._cors()
        self.end_headers()

    def do_POST(self):
        length = int(self.headers.get("Content-Length", 0))
        raw = self.rfile.read(length) if length else b"{}"
        try:
            data = json.loads(raw or b"{}")
        except ValueError:
            data = {}

        token = self._cart_token()
        cart = CARTS[token]
        path = urlparse(self.path).path

        if path == "/wp-json/wc/store/v1/cart/add-item":
            product_id = int(data.get("id"))
            quantity = int(data.get("quantity", 1))
            variation = data.get("variation") or []
            resolved_id = product_id
            if variation:
                size = next((v["value"] for v in variation if v.get("attribute", "").endswith("size")), None)
                parent = PRODUCTS_BY_ID.get(product_id, {})
                stub = next((v for v in parent.get("variations", [])
                             if any(a["attribute"] == "pa_size" and a["value"] == size for a in v["attributes"])), None)
                if stub:
                    resolved_id = stub["id"]
            key = "item_%d" % resolved_id
            if key in cart["items"]:
                cart["items"][key]["quantity"] += quantity
            else:
                cart["items"][key] = {"id": resolved_id, "quantity": quantity}
            return self._send_json(cart_response(cart), cart_token=token)

        if path == "/wp-json/wc/store/v1/cart/update-item":
            key = data.get("key")
            quantity = int(data.get("quantity", 1))
            if key in cart["items"]:
                if quantity <= 0:
                    del cart["items"][key]
                else:
                    cart["items"][key]["quantity"] = quantity
            return self._send_json(cart_response(cart), cart_token=token)

        if path == "/wp-json/wc/store/v1/cart/remove-item":
            key = data.get("key")
            cart["items"].pop(key, None)
            return self._send_json(cart_response(cart), cart_token=token)

        if path == "/wp-json/wc/store/v1/cart/apply-coupon":
            code = (data.get("code") or "").upper()
            if code not in COUPONS:
                return self._send_json({"message": "Invalid coupon code. Try WELCOME10."}, status=400, cart_token=token)
            if code not in cart["coupons"]:
                cart["coupons"].append(code)
            return self._send_json(cart_response(cart), cart_token=token)

        if path == "/wp-json/wc/store/v1/cart/remove-coupon":
            code = (data.get("code") or "").upper()
            if code in cart["coupons"]:
                cart["coupons"].remove(code)
            return self._send_json(cart_response(cart), cart_token=token)

        if path == "/wp-json/wc/store/v1/checkout":
            if not cart["items"]:
                return self._send_json({"message": "Your cart is empty."}, status=400, cart_token=token)

            billing = data.get("billing_address") or {}
            shipping = data.get("shipping_address") or billing
            payment_method = data.get("payment_method") or "cod"
            method_titles = {"cod": "Cash on Delivery", "bacs": "Direct Bank Transfer"}

            cart_data = cart_response(cart)
            order_id = ORDER_SEQ[0] + 1
            ORDER_SEQ[0] = order_id
            email = self._current_user_email() or billing.get("email", "guest@example.com")

            def fmt_addr(a):
                return "%s %s<br/>%s<br/>%s, %s %s" % (
                    a.get("first_name", ""), a.get("last_name", ""), a.get("address_1", ""),
                    a.get("city", ""), a.get("state", ""), a.get("postcode", ""))

            order = {
                "id": order_id, "number": str(order_id), "customer_email": email, "status": "processing",
                "date_created": datetime.datetime.utcnow().isoformat(),
                "payment_method_title": method_titles.get(payment_method, payment_method),
                "items": [{"name": it["name"], "quantity": it["quantity"],
                           "total": str(int(it["totals"]["line_subtotal"]) / 100.0),
                           "image": (it["images"][0]["src"] if it["images"] else ""), "product_id": it["id"]} for it in cart_data["items"]],
                "subtotal": str(int(cart_data["totals"]["total_items"]) / 100.0),
                "shipping_total": "0.00", "total_tax": "0.00",
                "total": str(int(cart_data["totals"]["total_price"]) / 100.0),
                "billing_address": fmt_addr(billing), "shipping_address": fmt_addr(shipping),
            }
            ORDERS.append(order)
            cart["items"] = {}
            cart["coupons"] = []

            return self._send_json({
                "order_id": order_id, "order_key": "wc_order_demo_%d" % order_id, "status": "processing",
                "payment_result": {"payment_status": "success", "payment_details": [], "redirect_url": ""},
            }, cart_token=token)

        if path == "/wp-json/jwt-auth/v1/token":
            email = (data.get("username") or "").lower()
            password = data.get("password") or ""
            user = USERS.get(email)
            if not user or user["password"] != password:
                return self._send_json({"message": "Incorrect email or password."}, status=403)
            tok = str(uuid.uuid4())
            TOKENS[tok] = email
            return self._send_json({
                "token": tok, "user_email": email,
                "user_nicename": email.split("@")[0],
                "user_display_name": "%s %s" % (user["first_name"], user["last_name"]),
            })

        if path == "/wp-json/oraandstone/v1/register":
            email = (data.get("email") or "").lower()
            password = data.get("password") or ""
            if not email or "@" not in email:
                return self._send_json({"message": "A valid email address is required."}, status=400)
            if len(password) < 8:
                return self._send_json({"message": "Password must be at least 8 characters."}, status=400)
            if email in USERS:
                return self._send_json({"message": "An account with this email already exists."}, status=409)
            USERS[email] = {
                "id": len(USERS) + 1, "password": password,
                "first_name": data.get("first_name") or "", "last_name": data.get("last_name") or "",
                "billing": {"email": email}, "shipping": {},
            }
            return self._send_json({"success": True, "user_id": USERS[email]["id"]})

        if path == "/wp-json/oraandstone/v1/account":
            email = self._current_user_email()
            if not email:
                return self._send_json({"message": "You must be logged in."}, status=401)
            user = USERS[email]
            if "first_name" in data: user["first_name"] = data["first_name"]
            if "last_name" in data: user["last_name"] = data["last_name"]
            if isinstance(data.get("billing"), dict): user["billing"].update(data["billing"])
            if isinstance(data.get("shipping"), dict): user["shipping"].update(data["shipping"])
            return self._send_json({"first_name": user["first_name"], "last_name": user["last_name"],
                                     "email": email, "billing": user["billing"], "shipping": user["shipping"]})

        self.send_response(404)
        self._cors()
        self.end_headers()


if __name__ == "__main__":
    server = ThreadingHTTPServer(("0.0.0.0", PORT), Handler)
    print("Ora & Stone dummy Store API running at http://localhost:%d" % PORT)
    print("Point headless/js/config.js API_BASE at this URL to demo the frontend.")
    print("Try coupon code WELCOME10 on the cart page.")
    print("Demo login: demo@orastone.com / demo1234 (has 2 sample orders). Ctrl+C to stop.")
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
