/**
 * The Head Spa — Register Page v1.0
 * Customer registration form using Shopify Storefront API.
 *
 * Place in: wp-content/themes/hello-elementor-child/js/register-page.js
 * Requires: auth.js loaded first
 *
 * SETUP:
 * 1. Create WP page titled "Create Account" with slug "register"
 * 2. Add HTML widget: <div id="ths-register"></div>
 */
(function () {
  'use strict';

  function init() {
    var el = document.getElementById('ths-register');
    if (!el) return;

    /* Redirect if already logged in */
    if (THSAuth.redirectIfLoggedIn('/my-account/')) return;

    render(el);
  }

  function render(el) {
    el.innerHTML =
      '<div class="ths-auth-container">' +
        '<div class="ths-auth-card">' +
          '<h1 class="ths-auth-heading">Create Account</h1>' +
          '<p class="ths-auth-subtext">Join The Head Spa for a personalized experience.</p>' +
          '<div id="ths-auth-error" class="ths-auth-error" style="display:none;"></div>' +
          '<div id="ths-auth-success" class="ths-auth-success" style="display:none;"></div>' +
          '<div class="ths-auth-form" id="ths-register-form">' +
            '<div class="ths-auth-row">' +
              '<div class="ths-auth-field">' +
                '<label for="ths-reg-first">First Name</label>' +
                '<input type="text" id="ths-reg-first" placeholder="First name" autocomplete="given-name" required>' +
              '</div>' +
              '<div class="ths-auth-field">' +
                '<label for="ths-reg-last">Last Name</label>' +
                '<input type="text" id="ths-reg-last" placeholder="Last name" autocomplete="family-name" required>' +
              '</div>' +
            '</div>' +
            '<div class="ths-auth-field">' +
              '<label for="ths-reg-email">Email Address</label>' +
              '<input type="email" id="ths-reg-email" placeholder="you@example.com" autocomplete="email" required>' +
            '</div>' +
            '<div class="ths-auth-field">' +
              '<label for="ths-reg-password">Password</label>' +
              '<div class="ths-auth-password-wrap">' +
                '<input type="password" id="ths-reg-password" placeholder="At least 5 characters" autocomplete="new-password" required minlength="5">' +
                '<button type="button" class="ths-auth-toggle-pw" aria-label="Toggle password visibility">' +
                  '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>' +
                '</button>' +
              '</div>' +
              '<div class="ths-auth-pw-strength" id="ths-pw-strength"></div>' +
            '</div>' +
            '<div class="ths-auth-field">' +
              '<label for="ths-reg-password-confirm">Confirm Password</label>' +
              '<input type="password" id="ths-reg-password-confirm" placeholder="Re-enter your password" autocomplete="new-password" required>' +
            '</div>' +
            '<div class="ths-auth-actions">' +
              '<button id="ths-register-submit" class="ths-auth-submit">Create Account</button>' +
            '</div>' +
          '</div>' +
          '<div class="ths-auth-divider"><span>Already have an account?</span></div>' +
          '<a href="/login/" class="ths-auth-secondary-btn">Sign In</a>' +
        '</div>' +
      '</div>';

    bindEvents(el);
  }

  function bindEvents(el) {
    var firstInput = document.getElementById('ths-reg-first');
    var lastInput = document.getElementById('ths-reg-last');
    var emailInput = document.getElementById('ths-reg-email');
    var passwordInput = document.getElementById('ths-reg-password');
    var confirmInput = document.getElementById('ths-reg-password-confirm');
    var submitBtn = document.getElementById('ths-register-submit');
    var errorEl = document.getElementById('ths-auth-error');
    var successEl = document.getElementById('ths-auth-success');
    var strengthEl = document.getElementById('ths-pw-strength');

    /* Toggle password visibility */
    el.querySelector('.ths-auth-toggle-pw').addEventListener('click', function () {
      var input = passwordInput;
      var isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      this.innerHTML = isPassword
        ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
        : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    });

    /* Password strength indicator */
    passwordInput.addEventListener('input', function () {
      var pw = passwordInput.value;
      if (!pw) { strengthEl.innerHTML = ''; return; }
      var score = 0;
      if (pw.length >= 5) score++;
      if (pw.length >= 8) score++;
      if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
      if (/[0-9]/.test(pw)) score++;
      if (/[^A-Za-z0-9]/.test(pw)) score++;

      var labels = ['Very weak', 'Weak', 'Fair', 'Good', 'Strong'];
      var colors = ['#e74c3c', '#e67e22', '#f1c40f', '#439E9E', '#27ae60'];
      var idx = Math.min(score, 4);
      strengthEl.innerHTML = '<div class="ths-pw-bar"><div class="ths-pw-bar-fill" style="width:' + ((idx + 1) * 20) + '%;background:' + colors[idx] + ';"></div></div><span style="color:' + colors[idx] + ';">' + labels[idx] + '</span>';
    });

    /* Submit on Enter from last field */
    confirmInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') handleRegister(); });

    /* Submit button */
    submitBtn.addEventListener('click', handleRegister);

    function handleRegister() {
      var firstName = firstInput.value.trim();
      var lastName = lastInput.value.trim();
      var email = emailInput.value.trim();
      var password = passwordInput.value;
      var confirm = confirmInput.value;

      /* Validate */
      errorEl.style.display = 'none';
      successEl.style.display = 'none';

      if (!firstName) return showError('Please enter your first name.');
      if (!lastName) return showError('Please enter your last name.');
      if (!email) return showError('Please enter your email address.');
      if (!password) return showError('Please enter a password.');
      if (password.length < 5) return showError('Password must be at least 5 characters.');
      if (password !== confirm) return showError('Passwords do not match.');

      /* Disable button */
      submitBtn.disabled = true;
      submitBtn.textContent = 'Creating account...';

      THSAuth.register(firstName, lastName, email, password).then(function (result) {
        if (result.success) {
          /* Auto-login after registration */
          THSAuth.login(email, password).then(function (loginResult) {
            if (loginResult.success) {
              THSAuth.fetchCustomer().then(function () {
                THSAuth.updateHeader();
                window.location.href = '/my-account/';
              });
            } else {
              /* Account created but auto-login failed — send to login page */
              showSuccess('Account created! Please sign in.');
              document.getElementById('ths-register-form').style.display = 'none';
              setTimeout(function () { window.location.href = '/login/'; }, 2000);
            }
          });
        } else {
          var msg = result.errors.map(function (e) { return e.message; }).join(' ');
          /* Friendly error messages */
          if (msg.toLowerCase().indexOf('taken') > -1 || msg.toLowerCase().indexOf('already') > -1) {
            showError('An account with this email already exists. <a href="/login/">Sign in instead</a>');
          } else if (msg.toLowerCase().indexOf('too short') > -1 || msg.toLowerCase().indexOf('password') > -1) {
            showError('Password must be at least 5 characters long.');
          } else {
            showError(msg);
          }
          submitBtn.disabled = false;
          submitBtn.textContent = 'Create Account';
        }
      }).catch(function (e) {
        console.error('[THS Register]', e);
        showError('Something went wrong. Please try again.');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Account';
      });
    }

    function showError(msg) {
      errorEl.innerHTML = msg;
      errorEl.style.display = 'block';
      errorEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function showSuccess(msg) {
      successEl.textContent = msg;
      successEl.style.display = 'block';
    }

    /* Focus first input */
    firstInput.focus();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();