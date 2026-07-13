/**
 * Ora & Stone — customer login/register/session.
 *
 * Talks to the "JWT Authentication for WP REST API" plugin's login endpoint
 * (POST /wp-json/jwt-auth/v1/token) and the custom registration endpoint in
 * mu-plugin-account-api.php (POST /wp-json/oraandstone/v1/register). See
 * headless/README.md for what needs installing on the WordPress side.
 *
 * The token is stored in localStorage (not sessionStorage, like the cart
 * token) so a logged-in customer stays logged in across tabs and browser
 * restarts, same as a normal WooCommerce account would. store-api.js reads
 * the same 'os_auth_token' key directly so Store API requests (checkout,
 * in particular) carry it too.
 */
(function (global) {
  var TOKEN_KEY = 'os_auth_token';
  var USER_KEY = 'os_auth_user';

  function wpRoot() {
    var base = (global.OS_CONFIG && global.OS_CONFIG.API_BASE) || '';
    return base.replace(/\/+$/, '') + '/wp-json';
  }

  function request(path, options) {
    options = options || {};
    var token = localStorage.getItem(TOKEN_KEY);
    var headers = Object.assign(
      { 'Content-Type': 'application/json' },
      token ? { 'Authorization': 'Bearer ' + token } : {},
      options.headers || {}
    );
    return fetch(wpRoot() + path, {
      method: options.method || 'GET',
      headers: headers,
      credentials: 'omit',
      body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
    }).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (data) {
        if (!res.ok) {
          var e = new Error(data.message || (data.data && data.data.params && Object.values(data.data.params)[0]) || ('Request failed (' + res.status + ')'));
          e.status = res.status;
          throw e;
        }
        return data;
      });
    });
  }

  global.OSAuth = {
    isLoggedIn: function () { return !!localStorage.getItem(TOKEN_KEY); },
    getUser: function () {
      try { return JSON.parse(localStorage.getItem(USER_KEY) || 'null'); }
      catch (e) { return null; }
    },
    login: function (email, password) {
      return request('/jwt-auth/v1/token', {
        method: 'POST',
        body: { username: email, password: password },
      }).then(function (data) {
        localStorage.setItem(TOKEN_KEY, data.token);
        var user = { email: data.user_email, name: data.user_display_name };
        localStorage.setItem(USER_KEY, JSON.stringify(user));
        return user;
      });
    },
    register: function (fields) {
      return request('/oraandstone/v1/register', {
        method: 'POST',
        body: {
          email: fields.email,
          password: fields.password,
          first_name: fields.firstName,
          last_name: fields.lastName,
        },
      }).then(function () {
        return global.OSAuth.login(fields.email, fields.password);
      });
    },
    logout: function () {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(USER_KEY);
    },
    // Thin wrapper for the other oraandstone/v1 routes (orders, account),
    // reusing the same Authorization-header plumbing as login/register.
    api: function (path, options) {
      return request('/oraandstone/v1' + path, options);
    },
  };
})(window);
