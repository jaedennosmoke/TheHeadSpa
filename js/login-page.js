/**
 * The Head Spa — Login Page v1.0
 * Customer login form using Shopify Storefront API.
 *
 * Place in: wp-content/themes/hello-elementor-child/js/login-page.js
 * Requires: auth.js loaded first
 *
 * SETUP:
 * 1. Create WP page titled "Login" with slug "login"
 * 2. Add HTML widget: <div id="ths-login"></div>
 */
(function () {
  'use strict';

  function init() {
    var el = document.getElementById('ths-login');
    if (!el) return;

    /* Redirect if already logged in */
    var params = new URLSearchParams(window.location.search);
    var redirect = params.get('redirect') || '/my-account/';
    if (THSAuth.redirectIfLoggedIn(redirect)) return;

    render(el, redirect);
  }

  function render(el, redirect) {
    el.innerHTML =
      '<div class="ths-auth-container">' +
        '<div class="ths-auth-card">' +
          '<h1 class="ths-auth-heading">Sign In</h1>' +
          '<p class="ths-auth-subtext">Welcome back! Sign in to your account.</p>' +
          '<div id="ths-auth-error" class="ths-auth-error" style="display:none;"></div>' +
          '<div class="ths-auth-form">' +
            '<div class="ths-auth-field">' +
              '<label for="ths-login-email">Email Address</label>' +
              '<input type="email" id="ths-login-email" placeholder="you@example.com" autocomplete="email" required>' +
            '</div>' +
            '<div class="ths-auth-field">' +
              '<label for="ths-login-password">Password</label>' +
              '<div class="ths-auth-password-wrap">' +
                '<input type="password" id="ths-login-password" placeholder="Enter your password" autocomplete="current-password" required>' +
                '<button type="button" class="ths-auth-toggle-pw" aria-label="Toggle password visibility">' +
                  '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>' +
                '</button>' +
              '</div>' +
            '</div>' +
            '<div class="ths-auth-actions">' +
              '<button id="ths-login-submit" class="ths-auth-submit">Sign In</button>' +
            '</div>' +
            '<div class="ths-auth-links">' +
              '<a href="/reset-password/" class="ths-auth-link">Forgot your password?</a>' +
            '</div>' +
          '</div>' +
          '<div class="ths-auth-divider"><span>New here?</span></div>' +
          '<a href="/register/" class="ths-auth-secondary-btn">Create an Account</a>' +
        '</div>' +
      '</div>';

    bindEvents(el, redirect);
  }

  function bindEvents(el, redirect) {
    var emailInput = document.getElementById('ths-login-email');
    var passwordInput = document.getElementById('ths-login-password');
    var submitBtn = document.getElementById('ths-login-submit');
    var errorEl = document.getElementById('ths-auth-error');

    /* Toggle password visibility */
    el.querySelector('.ths-auth-toggle-pw').addEventListener('click', function () {
      var input = passwordInput;
      var isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      this.innerHTML = isPassword
        ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
        : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    });

    /* Submit on Enter */
    function onEnter(e) { if (e.key === 'Enter') handleLogin(); }
    emailInput.addEventListener('keydown', onEnter);
    passwordInput.addEventListener('keydown', onEnter);

    /* Submit button */
    submitBtn.addEventListener('click', handleLogin);

    function handleLogin() {
      var email = emailInput.value.trim();
      var password = passwordInput.value;

      /* Validate */
      errorEl.style.display = 'none';
      if (!email) return showError('Please enter your email address.');
      if (!password) return showError('Please enter your password.');

      /* Disable button */
      submitBtn.disabled = true;
      submitBtn.textContent = 'Signing in...';

      THSAuth.login(email, password).then(function (result) {
        if (result.success) {
          /* Fetch customer info before redirecting */
          THSAuth.fetchCustomer().then(function () {
            THSAuth.updateHeader();
            window.location.href = redirect;
          });
        } else {
          var msg = result.errors.map(function (e) { return e.message; }).join(' ');
          /* Friendly error messages */
          if (msg.toLowerCase().indexOf('unidentified') > -1 || msg.toLowerCase().indexOf('credentials') > -1) {
            showError('Incorrect email or password. Please try again.');
          } else {
            showError(msg);
          }
          submitBtn.disabled = false;
          submitBtn.textContent = 'Sign In';
        }
      }).catch(function (e) {
        console.error('[THS Login]', e);
        showError('Something went wrong. Please try again.');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Sign In';
      });
    }

    function showError(msg) {
      errorEl.textContent = msg;
      errorEl.style.display = 'block';
    }

    /* Focus email input */
    emailInput.focus();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();