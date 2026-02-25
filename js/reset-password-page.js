/**
 * The Head Spa — Reset Password Page v1.0
 * Handles "forgot password" email request.
 *
 * Place in: wp-content/themes/hello-elementor-child/js/reset-password-page.js
 * Requires: auth.js loaded first
 *
 * SETUP:
 * 1. Create WP page titled "Reset Password" with slug "reset-password"
 * 2. Add HTML widget: <div id="ths-reset-password"></div>
 */
(function () {
  'use strict';

  function init() {
    var el = document.getElementById('ths-reset-password');
    if (!el) return;
    render(el);
  }

  function render(el) {
    el.innerHTML =
      '<div class="ths-auth-container">' +
        '<div class="ths-auth-card">' +
          '<h1 class="ths-auth-heading">Reset Password</h1>' +
          '<p class="ths-auth-subtext">Enter your email and we\'ll send you a link to reset your password.</p>' +
          '<div id="ths-auth-error" class="ths-auth-error" style="display:none;"></div>' +
          '<div id="ths-auth-success" class="ths-auth-success" style="display:none;"></div>' +
          '<div class="ths-auth-form" id="ths-reset-form">' +
            '<div class="ths-auth-field">' +
              '<label for="ths-reset-email">Email Address</label>' +
              '<input type="email" id="ths-reset-email" placeholder="you@example.com" autocomplete="email" required>' +
            '</div>' +
            '<div class="ths-auth-actions">' +
              '<button id="ths-reset-submit" class="ths-auth-submit">Send Reset Link</button>' +
            '</div>' +
          '</div>' +
          '<div class="ths-auth-links" style="margin-top:20px;">' +
            '<a href="/login/" class="ths-auth-link">Back to Sign In</a>' +
          '</div>' +
        '</div>' +
      '</div>';

    bindEvents(el);
  }

  function bindEvents(el) {
    var emailInput = document.getElementById('ths-reset-email');
    var submitBtn = document.getElementById('ths-reset-submit');
    var errorEl = document.getElementById('ths-auth-error');
    var successEl = document.getElementById('ths-auth-success');

    emailInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') handleReset(); });
    submitBtn.addEventListener('click', handleReset);

    function handleReset() {
      var email = emailInput.value.trim();

      errorEl.style.display = 'none';
      successEl.style.display = 'none';

      if (!email) return showError('Please enter your email address.');

      submitBtn.disabled = true;
      submitBtn.textContent = 'Sending...';

      THSAuth.recoverPassword(email).then(function (result) {
        if (result.success) {
          successEl.textContent = 'If an account exists with that email, you\'ll receive a password reset link shortly. Check your inbox and spam folder.';
          successEl.style.display = 'block';
          document.getElementById('ths-reset-form').style.display = 'none';
        } else {
          /* Shopify always succeeds to prevent email enumeration,
             but handle errors just in case */
          showError(result.errors.map(function (e) { return e.message; }).join(' '));
          submitBtn.disabled = false;
          submitBtn.textContent = 'Send Reset Link';
        }
      }).catch(function (e) {
        console.error('[THS Reset]', e);
        /* Show success anyway to prevent email enumeration */
        successEl.textContent = 'If an account exists with that email, you\'ll receive a password reset link shortly.';
        successEl.style.display = 'block';
        document.getElementById('ths-reset-form').style.display = 'none';
      });
    }

    function showError(msg) {
      errorEl.textContent = msg;
      errorEl.style.display = 'block';
    }

    emailInput.focus();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();