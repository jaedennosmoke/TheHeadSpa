/**
 * The Head Spa — Set New Password Page v1.0
 * Handles the password reset confirmation (after clicking email link).
 *
 * Shopify's reset email links to: theheadspa.myshopify.com/account/reset/CUSTOMER_ID/TOKEN
 * You need to configure Shopify to redirect reset links to your WP page instead.
 * See README for setup instructions.
 *
 * Place in: wp-content/themes/hello-elementor-child/js/set-new-password-page.js
 * Requires: auth.js loaded first
 *
 * SETUP:
 * 1. Create WP page titled "Set New Password" with slug "set-new-password"
 * 2. Add HTML widget: <div id="ths-set-new-password"></div>
 * 3. URL should receive ?token=RESET_URL parameter
 */
(function () {
  'use strict';

  function init() {
    var el = document.getElementById('ths-set-new-password');
    if (!el) return;

    var params = new URLSearchParams(window.location.search);
    var resetUrl = params.get('token');

    if (!resetUrl) {
      el.innerHTML =
        '<div class="ths-auth-container"><div class="ths-auth-card">' +
          '<h1 class="ths-auth-heading">Invalid Reset Link</h1>' +
          '<p class="ths-auth-subtext">This password reset link is invalid or has expired.</p>' +
          '<a href="/reset-password/" class="ths-auth-secondary-btn" style="margin-top:20px;">Request a New Link</a>' +
        '</div></div>';
      return;
    }

    render(el, resetUrl);
  }

  function render(el, resetUrl) {
    el.innerHTML =
      '<div class="ths-auth-container">' +
        '<div class="ths-auth-card">' +
          '<h1 class="ths-auth-heading">Set New Password</h1>' +
          '<p class="ths-auth-subtext">Enter your new password below.</p>' +
          '<div id="ths-auth-error" class="ths-auth-error" style="display:none;"></div>' +
          '<div id="ths-auth-success" class="ths-auth-success" style="display:none;"></div>' +
          '<div class="ths-auth-form" id="ths-set-pw-form">' +
            '<div class="ths-auth-field">' +
              '<label for="ths-new-password">New Password</label>' +
              '<div class="ths-auth-password-wrap">' +
                '<input type="password" id="ths-new-password" placeholder="At least 5 characters" autocomplete="new-password" required minlength="5">' +
                '<button type="button" class="ths-auth-toggle-pw" aria-label="Toggle password visibility">' +
                  '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>' +
                '</button>' +
              '</div>' +
            '</div>' +
            '<div class="ths-auth-field">' +
              '<label for="ths-new-password-confirm">Confirm New Password</label>' +
              '<input type="password" id="ths-new-password-confirm" placeholder="Re-enter your password" autocomplete="new-password" required>' +
            '</div>' +
            '<div class="ths-auth-actions">' +
              '<button id="ths-set-pw-submit" class="ths-auth-submit">Reset Password</button>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>';

    bindEvents(el, resetUrl);
  }

  function bindEvents(el, resetUrl) {
    var passwordInput = document.getElementById('ths-new-password');
    var confirmInput = document.getElementById('ths-new-password-confirm');
    var submitBtn = document.getElementById('ths-set-pw-submit');
    var errorEl = document.getElementById('ths-auth-error');
    var successEl = document.getElementById('ths-auth-success');

    /* Toggle password visibility */
    el.querySelector('.ths-auth-toggle-pw').addEventListener('click', function () {
      var input = passwordInput;
      var isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      this.innerHTML = isPassword
        ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
        : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    });

    confirmInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') handleReset(); });
    submitBtn.addEventListener('click', handleReset);

    function handleReset() {
      var password = passwordInput.value;
      var confirm = confirmInput.value;

      errorEl.style.display = 'none';
      successEl.style.display = 'none';

      if (!password) return showError('Please enter a new password.');
      if (password.length < 5) return showError('Password must be at least 5 characters.');
      if (password !== confirm) return showError('Passwords do not match.');

      submitBtn.disabled = true;
      submitBtn.textContent = 'Resetting...';

      THSAuth.resetPassword(resetUrl, password).then(function (result) {
        if (result.success) {
          successEl.textContent = 'Password reset successfully! Redirecting to your account...';
          successEl.style.display = 'block';
          document.getElementById('ths-set-pw-form').style.display = 'none';
          THSAuth.updateHeader();
          setTimeout(function () {
            window.location.href = THSAuth.isLoggedIn() ? '/my-account/' : '/login/';
          }, 2000);
        } else {
          var msg = result.errors.map(function (e) { return e.message; }).join(' ');
          if (msg.toLowerCase().indexOf('expired') > -1 || msg.toLowerCase().indexOf('invalid') > -1) {
            showError('This reset link has expired. <a href="/reset-password/">Request a new one</a>');
          } else {
            showError(msg);
          }
          submitBtn.disabled = false;
          submitBtn.textContent = 'Reset Password';
        }
      }).catch(function (e) {
        console.error('[THS Set Password]', e);
        showError('Something went wrong. Please try again or <a href="/reset-password/">request a new link</a>.');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Reset Password';
      });
    }

    function showError(msg) {
      errorEl.innerHTML = msg;
      errorEl.style.display = 'block';
    }

    passwordInput.focus();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();