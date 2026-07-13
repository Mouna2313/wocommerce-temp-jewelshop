/**
 * Ora & Stone — header account icon, shared by every page.
 * Points at the login page when signed out, the account dashboard when
 * signed in (mirrors js/cart-badge.js's role for the cart icon).
 */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var link = document.querySelector('[data-account-link]');
    if (!link) return;
    if (window.OSAuth && OSAuth.isLoggedIn()) {
      link.href = 'account.html';
      link.setAttribute('aria-label', 'My account');
    } else {
      link.href = 'login.html';
      link.setAttribute('aria-label', 'Log in');
    }
  });
})();
