/**
 * The Head Spa — Wishlist v2.0
 * Favorites with Shopify metafield sync for logged-in customers.
 *
 * - localStorage for all users (instant)
 * - Syncs to Shopify customer metafield when logged in (cross-device)
 * - Merges on login: combines localStorage + server, deduplicates
 *
 * Place in: wp-content/themes/hello-elementor-child/js/wishlist.js
 * Requires: auth.js loaded first (for THSAuth)
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'ths-wishlist';
  var SYNCING = false;
  var AJAX_URL = (typeof ths_ajax !== 'undefined') ? ths_ajax.url : '/wp-admin/admin-ajax.php';

  /* ─── LocalStorage ─── */

  function getLocal() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }
    catch (e) { return []; }
  }

  function saveLocal(list) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
  }

  /* ─── Server Sync (Shopify metafield via PHP proxy) ─── */

  function isLoggedIn() {
    return typeof THSAuth !== 'undefined' && THSAuth.isLoggedIn();
  }

  function getToken() {
    return typeof THSAuth !== 'undefined' ? THSAuth.getToken() : null;
  }

  function fetchServerFavorites() {
    var token = getToken();
    if (!token) return Promise.resolve(null);

    var formData = new FormData();
    formData.append('action', 'ths_get_favorites');
    formData.append('customer_token', token);

    return fetch(AJAX_URL, { method: 'POST', body: formData })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (j.success) return j.data;
        return null;
      })
      .catch(function (e) {
        console.warn('[THS Wishlist] Could not fetch server favorites:', e);
        return null;
      });
  }

  function saveServerFavorites(list) {
    var token = getToken();
    if (!token || SYNCING) return;

    SYNCING = true;
    var formData = new FormData();
    formData.append('action', 'ths_save_favorites');
    formData.append('customer_token', token);
    formData.append('favorites', JSON.stringify(list));

    fetch(AJAX_URL, { method: 'POST', body: formData })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.success) console.warn('[THS Wishlist] Save failed:', j);
      })
      .catch(function (e) {
        console.warn('[THS Wishlist] Save error:', e);
      })
      .finally(function () { SYNCING = false; });
  }

  /* ─── Merge & Sync ─── */

  function syncOnLoad() {
    if (!isLoggedIn()) return;

    fetchServerFavorites().then(function (serverList) {
      if (!serverList) return;

      var local = getLocal();

      /* Merge: combine both, deduplicate */
      var merged = local.slice();
      serverList.forEach(function (handle) {
        if (merged.indexOf(handle) === -1) merged.push(handle);
      });

      /* Only save if there's a difference */
      var localChanged = merged.length !== local.length;
      var serverChanged = merged.length !== serverList.length;

      if (localChanged) {
        saveLocal(merged);
        restoreWishlistStates();
        updateBadge();
      }

      if (serverChanged) {
        saveServerFavorites(merged);
      }
    });
  }

  /* ─── Toggle ─── */

  window.toggleWishlist = function (button) {
    var card = button.closest('.ths-card');
    if (!card) return;

    var handle = card.getAttribute('data-handle');
    if (!handle) return;

    var list = getLocal();
    var index = list.indexOf(handle);

    if (index > -1) {
      list.splice(index, 1);
      button.classList.remove('active');
    } else {
      list.push(handle);
      button.classList.add('active');
    }

    saveLocal(list);
    updateBadge();

    /* Sync to server if logged in (non-blocking) */
    if (isLoggedIn()) {
      saveServerFavorites(list);
    }
  };

  /* ─── Restore Active States ─── */

  function restoreWishlistStates() {
    var list = getLocal();
    if (!list.length) return;

    document.querySelectorAll('.ths-card').forEach(function (card) {
      var handle = card.getAttribute('data-handle');
      if (handle && list.indexOf(handle) > -1) {
        var btn = card.querySelector('.ths-card__wishlist');
        if (btn) btn.classList.add('active');
      }
    });
  }

  /* ─── Badge ─── */

  function updateBadge() {
    var badge = document.querySelector('.ths-wishlist-badge');
    if (!badge) return;
    var count = getLocal().length;
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : count;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  }

  /* ─── Header Heart ─── */

  function initHeaderHeart() {
    var trigger = document.querySelector('.wishlist-trigger');
    if (!trigger) return;

    trigger.style.cursor = 'pointer';

    var iconWrap = trigger.querySelector('.elementor-icon') || trigger;
    iconWrap.style.position = 'relative';

    var badge = document.createElement('span');
    badge.className = 'ths-wishlist-badge';
    badge.textContent = '0';
    badge.style.display = 'none';
    iconWrap.appendChild(badge);

    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      window.location.href = '/favorites/';
    });

    updateBadge();
  }

  /* ─── Public API for wishlist-page.js ─── */

  window.THSWishlist = {
    getLocal: getLocal,
    saveLocal: saveLocal,
    updateBadge: updateBadge,
    syncToServer: function () {
      if (isLoggedIn()) saveServerFavorites(getLocal());
    },
    restoreStates: restoreWishlistStates,
  };

  /* ─── Init ─── */

  function init() {
    restoreWishlistStates();
    initHeaderHeart();

    /* Sync with server on load if logged in */
    syncOnLoad();

    /* Watch for dynamic Shopify components */
    var observer = new MutationObserver(function () { restoreWishlistStates(); });
    observer.observe(document.body, { childList: true, subtree: true });
    setTimeout(function () { observer.disconnect(); }, 10000);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();