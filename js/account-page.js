/**
 * The Head Spa — My Account Page v2.0
 * Customer dashboard with sidebar navigation.
 *
 * Place in: wp-content/themes/hello-elementor-child/js/account-page.js
 * Requires: auth.js loaded first
 */
(function () {
  'use strict';

  var customer = null;
  var el = null;

  function init() {
    el = document.getElementById('ths-account');
    if (!el) return;
    if (!THSAuth.requireAuth('/my-account/')) return;

    el.innerHTML = '<div class="ths-acct-loading"><div class="ths-acct-spinner"></div><p>Loading your account...</p></div>';

    THSAuth.fetchCustomer().then(function (data) {
      if (!data) { THSAuth.clearAuth(); window.location.href = '/login/?redirect=/my-account/'; return; }
      customer = data;
      renderDashboard();
    }).catch(function (e) {
      console.error('[THS Account]', e);
      el.innerHTML = '<div class="ths-acct-error"><h2>Something went wrong</h2><p>Please try again.</p><a href="/login/" class="ths-acct-btn-primary">Sign In Again</a></div>';
    });
  }

  /* ─── Dashboard Shell ─── */

  function renderDashboard() {
    el.innerHTML =
      '<div class="ths-acct">' +
        '<nav class="ths-acct-sidebar">' +
          '<ul class="ths-acct-nav">' +
            '<li><button class="ths-acct-nav-item active" data-tab="info">Account Information</button></li>' +
            '<li><button class="ths-acct-nav-item" data-tab="orders">Recent Orders</button></li>' +
            '<li><button class="ths-acct-nav-item" data-tab="addresses">Saved Addresses</button></li>' +
            '<li><button class="ths-acct-nav-item" data-tab="favorites">Favorites</button></li>' +
          '</ul>' +
          '<button class="ths-acct-nav-logout" id="ths-logout-btn">Sign Out</button>' +
        '</nav>' +
        '<main class="ths-acct-main">' +
          '<div class="ths-acct-panel active" id="ths-tab-info">' + renderInfo() + '</div>' +
          '<div class="ths-acct-panel" id="ths-tab-orders">' + renderOrders() + '</div>' +
          '<div class="ths-acct-panel" id="ths-tab-addresses">' + renderAddresses() + '</div>' +
          '<div class="ths-acct-panel" id="ths-tab-favorites">' + renderFavorites() + '</div>' +
        '</main>' +
      '</div>';

    bindEvents();
  }

  /* ─── Account Information ─── */

  function renderInfo() {
    var fullName = [customer.firstName, customer.lastName].filter(Boolean).join(' ') || '—';
    return '<h2 class="ths-acct-page-title">Account Information</h2>' +
      '<div class="ths-acct-divider"></div>' +
      '<table class="ths-acct-table"><tbody>' +
        row('Name', esc(fullName), 'name') +
        row('Email', esc(customer.email || '—'), 'email') +
        row('Phone', esc(customer.phone || '—'), 'phone') +
        row('Password', '••••••••', 'password') +
      '</tbody></table>' +
      '<div id="ths-acct-edit-area"></div>';
  }

  function row(label, value, field) {
    return '<tr class="ths-acct-row">' +
      '<td class="ths-acct-label">' + label + '</td>' +
      '<td class="ths-acct-value">' + value + '</td>' +
      '<td class="ths-acct-action"><button class="ths-acct-edit" data-field="' + field + '">Edit</button></td>' +
    '</tr>';
  }

  /* ─── Recent Orders ─── */

  function renderOrders() {
    var orders = customer.orders?.edges || [];
    var html = '<h2 class="ths-acct-page-title">Recent Orders</h2><div class="ths-acct-divider"></div>';

    if (!orders.length) {
      return html + '<div class="ths-acct-empty-tab"><h3>No orders yet</h3><p>When you place an order, it will appear here.</p><a href="/" class="ths-acct-btn-primary">Start Shopping</a></div>';
    }

    html += '<table class="ths-acct-orders-table"><thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr></thead><tbody>';
    orders.forEach(function (edge) {
      var o = edge.node;
      var date = new Date(o.processedAt).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
      var total = '$' + parseFloat(o.totalPrice.amount).toFixed(2);
      var s = formatStatus(o.fulfillmentStatus);
      html += '<tr><td class="ths-acct-order-id">' + esc(o.name) + '</td><td>' + date + '</td><td><span class="ths-acct-badge ths-acct-badge--' + s.type + '">' + s.label + '</span></td><td class="ths-acct-order-total">' + total + '</td><td class="ths-acct-action">' + (o.statusUrl ? '<a href="' + o.statusUrl + '" target="_blank" class="ths-acct-edit">View</a>' : '') + '</td></tr>';
    });
    html += '</tbody></table>';
    return html;
  }

  function formatStatus(s) {
    return { FULFILLED: { label: 'Fulfilled', type: 'success' }, UNFULFILLED: { label: 'Processing', type: 'pending' }, PARTIALLY_FULFILLED: { label: 'Partial', type: 'pending' }, IN_PROGRESS: { label: 'In Progress', type: 'pending' } }[s] || { label: s || 'Processing', type: 'pending' };
  }

  /* ─── Addresses ─── */

  function renderAddresses() {
    var addresses = customer.addresses?.edges || [];
    var html = '<h2 class="ths-acct-page-title">Saved Addresses</h2><div class="ths-acct-divider"></div>';
    if (!addresses.length) return html + '<div class="ths-acct-empty-tab"><h3>No saved addresses</h3><p>Addresses from your orders will appear here.</p></div>';

    html += '<div class="ths-acct-addresses">';
    addresses.forEach(function (edge) {
      var a = edge.node;
      var name = [a.firstName, a.lastName].filter(Boolean).join(' ');
      var cityLine = [a.city, a.province, a.zip].filter(Boolean).join(', ');
      html += '<div class="ths-acct-address-card">' +
        (name ? '<p class="ths-acct-address-name">' + esc(name) + '</p>' : '') +
        (a.company ? '<p class="ths-acct-address-line">' + esc(a.company) + '</p>' : '') +
        (a.address1 ? '<p class="ths-acct-address-line">' + esc(a.address1) + '</p>' : '') +
        (a.address2 ? '<p class="ths-acct-address-line">' + esc(a.address2) + '</p>' : '') +
        (cityLine ? '<p class="ths-acct-address-line">' + esc(cityLine) + '</p>' : '') +
        (a.country ? '<p class="ths-acct-address-line">' + esc(a.country) + '</p>' : '') +
        (a.phone ? '<p class="ths-acct-address-line">' + esc(a.phone) + '</p>' : '') +
      '</div>';
    });
    return html + '</div>';
  }

  /* ─── Favorites ─── */

  function renderFavorites() {
    return '<h2 class="ths-acct-page-title">Favorites</h2><div class="ths-acct-divider"></div>' +
      '<p style="color:#666;margin-top:16px;">View and manage your favorite products.</p>' +
      '<a href="/favorites/" class="ths-acct-btn-primary" style="margin-top:16px;display:inline-block;">View My Favorites</a>';
  }

  /* ─── Inline Edit ─── */

  function showEditForm(field) {
    var area = document.getElementById('ths-acct-edit-area');
    if (!area) return;
    var html = '<div class="ths-acct-edit-form">';

    if (field === 'name') {
      html += '<h3 class="ths-acct-edit-title">Edit Name</h3>' +
        '<div class="ths-acct-edit-row">' +
          '<div class="ths-acct-edit-field"><label>First Name</label><input type="text" id="ths-edit-first" value="' + esc(customer.firstName || '') + '"></div>' +
          '<div class="ths-acct-edit-field"><label>Last Name</label><input type="text" id="ths-edit-last" value="' + esc(customer.lastName || '') + '"></div>' +
        '</div>';
    } else if (field === 'email') {
      html += '<h3 class="ths-acct-edit-title">Edit Email</h3>' +
        '<div class="ths-acct-edit-field"><label>Email Address</label><input type="email" id="ths-edit-email" value="' + esc(customer.email || '') + '"></div>';
    } else if (field === 'phone') {
      html += '<h3 class="ths-acct-edit-title">Edit Phone</h3>' +
        '<div class="ths-acct-edit-field"><label>Phone Number</label><input type="tel" id="ths-edit-phone" value="' + esc(customer.phone || '') + '" placeholder="+1 (555) 123-4567"></div>';
    } else if (field === 'password') {
      html += '<h3 class="ths-acct-edit-title">Change Password</h3>' +
        '<p style="color:#666;font-size:14px;margin:0 0 16px;">We\'ll send a password reset link to <strong>' + esc(customer.email) + '</strong></p>';
    }

    html += '<div id="ths-edit-msg" class="ths-acct-msg" style="display:none;"></div>' +
      '<div class="ths-acct-edit-btns">';

    if (field === 'password') {
      html += '<button class="ths-acct-btn-primary" id="ths-edit-send-reset">Send Reset Link</button>';
    } else {
      html += '<button class="ths-acct-btn-primary" id="ths-edit-save">Save</button>';
    }
    html += '<button class="ths-acct-btn-cancel" id="ths-edit-cancel">Cancel</button></div></div>';

    area.innerHTML = html;
    area.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    document.getElementById('ths-edit-cancel').addEventListener('click', function () { area.innerHTML = ''; });

    var saveBtn = document.getElementById('ths-edit-save');
    if (saveBtn) saveBtn.addEventListener('click', function () { saveField(field); });

    var resetBtn = document.getElementById('ths-edit-send-reset');
    if (resetBtn) resetBtn.addEventListener('click', function () {
      resetBtn.disabled = true; resetBtn.textContent = 'Sending...';
      THSAuth.recoverPassword(customer.email).finally(function () {
        showMsg(document.getElementById('ths-edit-msg'), 'Reset link sent! Check your inbox.', 'success');
        resetBtn.textContent = 'Sent!';
      });
    });
  }

  function saveField(field) {
    var saveBtn = document.getElementById('ths-edit-save');
    var msgEl = document.getElementById('ths-edit-msg');
    var input = {};

    if (field === 'name') {
      input.firstName = document.getElementById('ths-edit-first').value.trim();
      input.lastName = document.getElementById('ths-edit-last').value.trim();
      if (!input.firstName || !input.lastName) return showMsg(msgEl, 'Please fill in both fields.', 'error');
    } else if (field === 'email') {
      input.email = document.getElementById('ths-edit-email').value.trim();
      if (!input.email) return showMsg(msgEl, 'Please enter an email.', 'error');
    } else if (field === 'phone') {
      input.phone = document.getElementById('ths-edit-phone').value.trim();
    }

    saveBtn.disabled = true; saveBtn.textContent = 'Saving...';

    THSAuth.api(
      'mutation($token:String!,$input:CustomerUpdateInput!){customerUpdate(customerAccessToken:$token,customer:$input){customer{id firstName lastName email phone}customerUserErrors{code field message}}}',
      { token: THSAuth.getToken(), input: input }
    ).then(function (data) {
      var result = data.customerUpdate;
      if (result.customerUserErrors && result.customerUserErrors.length > 0) {
        showMsg(msgEl, result.customerUserErrors.map(function (e) { return e.message; }).join(' '), 'error');
        saveBtn.disabled = false; saveBtn.textContent = 'Save';
      } else {
        if (input.firstName !== undefined) customer.firstName = input.firstName;
        if (input.lastName !== undefined) customer.lastName = input.lastName;
        if (input.email !== undefined) customer.email = input.email;
        if (input.phone !== undefined) customer.phone = input.phone;
        THSAuth.updateHeader();
        document.getElementById('ths-tab-info').innerHTML = renderInfo();
        rebindEditButtons();
      }
    }).catch(function (e) {
      console.error('[THS Account]', e);
      showMsg(msgEl, 'Something went wrong.', 'error');
      saveBtn.disabled = false; saveBtn.textContent = 'Save';
    });
  }

  /* ─── Events ─── */

  function bindEvents() {
    el.querySelectorAll('.ths-acct-nav-item').forEach(function (btn) {
      btn.addEventListener('click', function () {
        el.querySelectorAll('.ths-acct-nav-item').forEach(function (b) { b.classList.remove('active'); });
        el.querySelectorAll('.ths-acct-panel').forEach(function (p) { p.classList.remove('active'); });
        btn.classList.add('active');
        document.getElementById('ths-tab-' + btn.dataset.tab).classList.add('active');
      });
    });

    document.getElementById('ths-logout-btn').addEventListener('click', function () {
      this.textContent = 'Signing out...'; this.disabled = true;
      THSAuth.logout().then(function () { THSAuth.updateHeader(); window.location.href = '/'; });
    });

    rebindEditButtons();
  }

  function rebindEditButtons() {
    el.querySelectorAll('.ths-acct-edit').forEach(function (btn) {
      btn.addEventListener('click', function () { showEditForm(btn.dataset.field); });
    });
  }

  /* ─── Helpers ─── */

  function esc(s) { if (!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

  function showMsg(el, msg, type) {
    if (!el) return;
    el.innerHTML = msg;
    el.className = 'ths-acct-msg ths-acct-msg--' + type;
    el.style.display = 'block';
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();