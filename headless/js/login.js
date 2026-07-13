(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var yearEl = document.querySelector('[data-year]');
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    if (OSAuth.isLoggedIn()) {
      location.href = 'account.html';
      return;
    }

    var redirectTo = new URLSearchParams(location.search).get('redirect') || 'account.html';

    document.querySelector('[data-login-form]').addEventListener('submit', function (e) {
      e.preventDefault();
      var feedback = document.querySelector('[data-login-feedback]');
      feedback.textContent = 'Logging in…';
      OSAuth.login(document.getElementById('username').value, document.getElementById('password').value)
        .then(function () { location.href = redirectTo; })
        .catch(function (err) { feedback.textContent = err.message; });
    });

    document.querySelector('[data-register-form]').addEventListener('submit', function (e) {
      e.preventDefault();
      var feedback = document.querySelector('[data-register-feedback]');
      feedback.textContent = 'Creating your account…';
      OSAuth.register({
        firstName: document.getElementById('reg_first_name').value,
        lastName: document.getElementById('reg_last_name').value,
        email: document.getElementById('reg_email').value,
        password: document.getElementById('reg_password').value,
      }).then(function () { location.href = redirectTo; })
        .catch(function (err) { feedback.textContent = err.message; });
    });
  });
})();
