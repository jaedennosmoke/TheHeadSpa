/**
 * The Head Spa — Auth Utilities v1.0
 * Shared authentication module for customer accounts.
 * Manages access tokens, login state, and header UI.
 *
 * Place in: wp-content/themes/hello-elementor-child/js/auth.js
 * Loads sitewide (lightweight — no DOM rendering)
 */
(function () {
  'use strict';

  var CONFIG = {
    storeDomain: 'theheadspa.myshopify.com',
    storefrontToken: '4617f01063d6f1b7503e71a499b36c43',
    apiVersion: '2025-01',
    tokenKey: 'ths-customer-token',
    tokenExpiryKey: 'ths-customer-token-expiry',
    customerKey: 'ths-customer-info',
  };

  var API_URL = 'https://' + CONFIG.storeDomain + '/api/' + CONFIG.apiVersion + '/graphql.json';

  /* ─── Storefront API Helper ─── */

  function api(query, variables) {
    return fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Shopify-Storefront-Access-Token': CONFIG.storefrontToken,
      },
      body: JSON.stringify({ query: query, variables: variables || {} }),
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (j.errors) throw new Error(j.errors.map(function (e) { return e.message; }).join(', '));
        return j.data;
      });
  }

  /* ─── Token Management ─── */

  function getToken() {
    var token = localStorage.getItem(CONFIG.tokenKey);
    var expiry = localStorage.getItem(CONFIG.tokenExpiryKey);
    if (!token) return null;
    /* Check if token has expired */
    if (expiry && new Date(expiry) <= new Date()) {
      clearAuth();
      return null;
    }
    return token;
  }

  function setToken(token, expiresAt) {
    localStorage.setItem(CONFIG.tokenKey, token);
    if (expiresAt) localStorage.setItem(CONFIG.tokenExpiryKey, expiresAt);
  }

  function clearAuth() {
    localStorage.removeItem(CONFIG.tokenKey);
    localStorage.removeItem(CONFIG.tokenExpiryKey);
    localStorage.removeItem(CONFIG.customerKey);
  }

  function isLoggedIn() {
    return !!getToken();
  }

  /* ─── Customer Info (cached) ─── */

  function getCachedCustomer() {
    try {
      return JSON.parse(localStorage.getItem(CONFIG.customerKey) || 'null');
    } catch (e) { return null; }
  }

  function setCachedCustomer(info) {
    localStorage.setItem(CONFIG.customerKey, JSON.stringify(info));
  }

  /* ─── Fetch Customer from API ─── */

  function fetchCustomer() {
    var token = getToken();
    if (!token) return Promise.resolve(null);
    return api(
      'query($token:String!){customer(customerAccessToken:$token){id firstName lastName email phone acceptsMarketing orders(first:10,sortKey:PROCESSED_AT,reverse:true){edges{node{id name processedAt statusUrl fulfillmentStatus financialStatus totalPrice{amount currencyCode}lineItems(first:5){edges{node{title quantity variant{image{url}}}}}}}}addresses(first:10){edges{node{id address1 address2 city province zip country phone firstName lastName company}}}}}',
      { token: token }
    ).then(function (data) {
      if (data && data.customer) {
        setCachedCustomer({
          firstName: data.customer.firstName,
          lastName: data.customer.lastName,
          email: data.customer.email,
        });
        return data.customer;
      }
      /* Token invalid */
      clearAuth();
      return null;
    }).catch(function (e) {
      console.warn('[THS Auth] Could not fetch customer:', e);
      return null;
    });
  }

  /* ─── Login ─── */

  function login(email, password) {
    return api(
      'mutation($input:CustomerAccessTokenCreateInput!){customerAccessTokenCreate(input:$input){customerAccessToken{accessToken expiresAt}customerUserErrors{code field message}}}',
      { input: { email: email, password: password } }
    ).then(function (data) {
      var result = data.customerAccessTokenCreate;
      if (result.customerUserErrors && result.customerUserErrors.length > 0) {
        return { success: false, errors: result.customerUserErrors };
      }
      if (result.customerAccessToken) {
        setToken(result.customerAccessToken.accessToken, result.customerAccessToken.expiresAt);
        return { success: true };
      }
      return { success: false, errors: [{ message: 'Unknown error' }] };
    });
  }

  /* ─── Register ─── */

  function register(firstName, lastName, email, password) {
    return api(
      'mutation($input:CustomerCreateInput!){customerCreate(input:$input){customer{id firstName lastName email}customerUserErrors{code field message}}}',
      { input: { firstName: firstName, lastName: lastName, email: email, password: password, acceptsMarketing: true } }
    ).then(function (data) {
      var result = data.customerCreate;
      if (result.customerUserErrors && result.customerUserErrors.length > 0) {
        return { success: false, errors: result.customerUserErrors };
      }
      if (result.customer) {
        return { success: true, customer: result.customer };
      }
      return { success: false, errors: [{ message: 'Unknown error' }] };
    });
  }

  /* ─── Logout ─── */

  function logout() {
    var token = getToken();
    if (!token) {
      clearAuth();
      return Promise.resolve();
    }
    return api(
      'mutation($token:String!){customerAccessTokenDelete(customerAccessToken:$token){deletedAccessToken userErrors{field message}}}',
      { token: token }
    ).then(function () {
      clearAuth();
    }).catch(function () {
      clearAuth();
    });
  }

  /* ─── Password Recovery ─── */

  function recoverPassword(email) {
    return api(
      'mutation($email:String!){customerRecover(email:$email){customerUserErrors{code field message}}}',
      { email: email }
    ).then(function (data) {
      var result = data.customerRecover;
      if (result.customerUserErrors && result.customerUserErrors.length > 0) {
        return { success: false, errors: result.customerUserErrors };
      }
      return { success: true };
    });
  }

  /* ─── Password Reset ─── */

  function resetPassword(resetUrl, password) {
    /* Extract the reset token and customer ID from the Shopify reset URL */
    return api(
      'mutation($resetUrl:URL!,$password:String!){customerResetByUrl(resetUrl:$resetUrl,password:$password){customer{id}customerAccessToken{accessToken expiresAt}customerUserErrors{code field message}}}',
      { resetUrl: resetUrl, password: password }
    ).then(function (data) {
      var result = data.customerResetByUrl;
      if (result.customerUserErrors && result.customerUserErrors.length > 0) {
        return { success: false, errors: result.customerUserErrors };
      }
      if (result.customerAccessToken) {
        setToken(result.customerAccessToken.accessToken, result.customerAccessToken.expiresAt);
        return { success: true };
      }
      return { success: true };
    });
  }

  /* ─── Header UI ─── */

  function updateHeader() {
    var trigger = document.querySelector('.account-trigger');
    var nameEl = document.querySelector('.account-name');
    var mobileTrigger = document.querySelector('.mobile-account-trigger');

    if (isLoggedIn()) {
      var customer = getCachedCustomer();
      var firstName = customer ? customer.firstName : '';

      /* Update icon link */
      if (trigger) {
        var link = trigger.closest('a') || trigger.querySelector('a');
        if (link) link.setAttribute('href', '/my-account/');
        trigger.setAttribute('title', firstName ? 'Hi, ' + firstName : 'My Account');
        trigger.style.cursor = 'pointer';
      }

      /* Update text widget */
      if (nameEl) {
        var textTarget = nameEl.querySelector('p, span, h1, h2, h3, h4, h5, h6, .elementor-heading-title') || nameEl;
        textTarget.innerHTML = (firstName ? '<strong>Hi ' + firstName + '!</strong>' : '<strong>My Account</strong>') + '<br>Standard Member';
        nameEl.style.cursor = 'pointer';
        nameEl.onclick = function () { window.location.href = '/my-account/'; };
      }

      /* Update mobile nav */
      if (mobileTrigger) {
        var mLink = mobileTrigger.querySelector('a') || mobileTrigger.closest('a');
        if (mLink) mLink.setAttribute('href', '/my-account/');
        mobileTrigger.style.cursor = 'pointer';
        if (!mLink) mobileTrigger.onclick = function () { window.location.href = '/my-account/'; };
      }

    } else {
      /* Logged out */
      if (trigger) {
        var link = trigger.closest('a') || trigger.querySelector('a');
        if (link) link.setAttribute('href', '/login/');
        trigger.setAttribute('title', 'Sign In');
      }

      if (nameEl) {
        var textTarget = nameEl.querySelector('p, span, h1, h2, h3, h4, h5, h6, .elementor-heading-title') || nameEl;
        textTarget.innerHTML = 'Sign In<br><span style="font-size:smaller;opacity:0.7;">or create an account</span>';
        nameEl.style.cursor = 'pointer';
        nameEl.onclick = function () { window.location.href = '/login/'; };
      }

      /* Update mobile nav */
      if (mobileTrigger) {
        var mLink = mobileTrigger.querySelector('a') || mobileTrigger.closest('a');
        if (mLink) mLink.setAttribute('href', '/login/');
        mobileTrigger.style.cursor = 'pointer';
        if (!mLink) mobileTrigger.onclick = function () { window.location.href = '/login/'; };
      }
    }
  }

  /* ─── Auth Redirect Helpers ─── */

  function requireAuth(redirectTo) {
    if (!isLoggedIn()) {
      window.location.href = '/login/' + (redirectTo ? '?redirect=' + encodeURIComponent(redirectTo) : '');
      return false;
    }
    return true;
  }

  function redirectIfLoggedIn(to) {
    if (isLoggedIn()) {
      window.location.href = to || '/my-account/';
      return true;
    }
    return false;
  }

  /* ─── Initialize ─── */

  function init() {
    updateHeader();

    /* Refresh token validity in background */
    if (isLoggedIn()) {
      fetchCustomer().then(function (customer) {
        if (!customer) updateHeader();
      });
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

  /* ─── Public API ─── */

  window.THSAuth = {
    api: api,
    getToken: getToken,
    isLoggedIn: isLoggedIn,
    login: login,
    register: register,
    logout: logout,
    recoverPassword: recoverPassword,
    resetPassword: resetPassword,
    fetchCustomer: fetchCustomer,
    getCachedCustomer: getCachedCustomer,
    clearAuth: clearAuth,
    updateHeader: updateHeader,
    requireAuth: requireAuth,
    redirectIfLoggedIn: redirectIfLoggedIn,
    CONFIG: CONFIG,
  };
})();