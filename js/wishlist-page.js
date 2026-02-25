/**
 * The Head Spa — Wishlist/Favorites Page v1.0
 * Reads saved product handles from localStorage,
 * fetches product data via Storefront API, and renders cards.
 *
 * Place in: wp-content/themes/hello-elementor-child/js/wishlist-page.js
 *
 * SETUP:
 * 1. Create a WP page titled "My Favorites" with slug "favorites"
 * 2. Add an HTML widget in Elementor with: <div id="ths-wishlist"></div>
 * 3. Enqueue via functions.php (see snippet)
 */
(function () {
  'use strict';

  var CONFIG = {
    storeDomain: 'theheadspa.myshopify.com',
    storefrontToken: '4617f01063d6f1b7503e71a499b36c43',
    apiVersion: '2025-01',
  };

  var API_URL = 'https://' + CONFIG.storeDomain + '/api/' + CONFIG.apiVersion + '/graphql.json';
  var STORAGE_KEY = 'ths-wishlist';

  /* ─── Helpers ─── */

  function getWishlist() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }
    catch (e) { return []; }
  }

  function saveWishlist(list) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
    /* Sync to Shopify metafield if logged in */
    if (typeof THSWishlist !== 'undefined' && THSWishlist.syncToServer) {
      THSWishlist.syncToServer();
    }
  }

  function money(a) { return '$' + parseFloat(a).toFixed(2); }

  function esc(str) {
    var d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  async function api(query, variables) {
    var res = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Shopify-Storefront-Access-Token': CONFIG.storefrontToken },
      body: JSON.stringify({ query: query, variables: variables }),
    });
    var json = await res.json();
    if (json.errors) throw new Error(json.errors.map(function (e) { return e.message; }).join(', '));
    return json.data;
  }

  /* ─── Fetch products by handles ─── */

  async function fetchProducts(handles) {
    if (handles.length === 0) return [];

    /* Build individual product queries — Storefront API doesn't support
       fetching multiple products by handle in one query via a list,
       so we use aliases to batch them into a single request */
    var fragments = handles.map(function (handle, i) {
      return 'p' + i + ':product(handle:"' + handle.replace(/"/g, '\\"') + '"){' +
        'id title handle vendor availableForSale ' +
        'featuredImage{url altText}' +
        'priceRange{minVariantPrice{amount currencyCode}}' +
        'compareAtPriceRange{minVariantPrice{amount currencyCode}}' +
        'variants(first:1){edges{node{id availableForSale}}}' +
      '}';
    });

    /* Split into batches of 10 to avoid overly large queries */
    var products = [];
    for (var b = 0; b < fragments.length; b += 10) {
      var batch = fragments.slice(b, b + 10);
      var query = '{' + batch.join(' ') + '}';
      var data = await api(query);
      for (var key in data) {
        if (data[key]) products.push(data[key]);
      }
    }

    return products;
  }

  /* ─── Render Product Card ─── */

  function renderProduct(product) {
    var img = product.featuredImage ? product.featuredImage.url : '';
    var alt = product.featuredImage ? (product.featuredImage.altText || product.title) : product.title;
    var price = money(product.priceRange.minVariantPrice.amount);
    var compareAt = product.compareAtPriceRange && parseFloat(product.compareAtPriceRange.minVariantPrice.amount) > parseFloat(product.priceRange.minVariantPrice.amount)
      ? money(product.compareAtPriceRange.minVariantPrice.amount) : '';
    var vendor = product.vendor || '';
    var available = product.availableForSale;
    var href = '/products/' + product.handle;

    return '<div class="ths-card ths-wl-card" data-handle="' + product.handle + '">' +
      '<div class="ths-card__image-wrapper" style="overflow:hidden;position:relative;">' +
        '<button class="ths-card__wishlist active" onclick="toggleWishlist(this)" aria-label="Remove from favorites">' +
          '<svg width="22" height="22" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>' +
          '</svg>' +
        '</button>' +
        '<a class="ths-card__image-link" href="' + href + '">' +
          (img ? '<img src="' + img + '&width=400" alt="' + esc(alt) + '" loading="lazy" decoding="async" style="object-fit:cover;width:100%;height:100%;display:block;">' : '') +
        '</a>' +
        '<div class="ths-card__overlay" style="position:absolute;bottom:0;left:10px;right:10px;width:calc(100% - 20px);background:rgba(0,0,0,0.75);color:#fff;border:none;border-radius:0;padding:10px;font-size:14px;font-weight:600;letter-spacing:0.5px;cursor:pointer;text-align:center;box-sizing:border-box;z-index:3;opacity:0;transform:translateY(100%);transition:opacity 0.3s,transform 0.3s;">' +
          (available ? 'Buy Now' : 'Sold Out') +
        '</div>' +
      '</div>' +
      '<div class="ths-card__details">' +
        '<p class="ths-card__brand">' + esc(vendor) + '</p>' +
        '<h3 class="ths-card__title"><a href="' + href + '">' + esc(product.title) + '</a></h3>' +
        '<p class="ths-card__price">' +
          (compareAt ? '<span class="ths-sr-compare">' + compareAt + '</span> ' : '') + price +
        '</p>' +
        '<div class="ths-card__action-row">' +
          '<div class="ths-card__rating" aria-label="Product rating">' +
            '<span class="ths-card__stars"><span class="ths-card__stars-filled"></span><span class="ths-card__stars-empty">&#9733;&#9733;&#9733;&#9733;&#9733;</span></span>' +
          '</div>' +
          '<button class="ths-wl-remove" data-handle="' + product.handle + '" aria-label="Remove from favorites" title="Remove">' +
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>' +
          '</button>' +
        '</div>' +
      '</div>' +
    '</div>';
  }

  /* ─── Render Page ─── */

  function renderPage(el, products) {
    var wishlist = getWishlist();
    var count = products.length;

    if (count === 0) {
      el.innerHTML =
        '<div class="ths-wl-empty">' +
          '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">' +
            '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>' +
          '</svg>' +
          '<h2>No favorites yet</h2>' +
          '<p>Tap the heart icon on any product to save it here.</p>' +
          '<a href="/" class="ths-wl-continue">Browse Products</a>' +
        '</div>';
      return;
    }

    el.innerHTML =
      '<div class="ths-wl-header">' +
        '<h1 class="ths-wl-heading">My Favorites</h1>' +
        '<p class="ths-wl-count">' + count + ' item' + (count !== 1 ? 's' : '') + ' saved</p>' +
        '<button class="ths-wl-clear-all">Clear All</button>' +
      '</div>' +
      '<div class="ths-wl-grid">' + products.map(renderProduct).join('') + '</div>';

    bindEvents(el);
  }

  /* ─── Events ─── */

  function bindEvents(el) {
    /* Buy Now overlay hover */
    el.querySelectorAll('.ths-card').forEach(function (card) {
      var overlay = card.querySelector('.ths-card__overlay');
      if (!overlay) return;
      card.addEventListener('mouseenter', function () { overlay.style.opacity = '1'; overlay.style.transform = 'translateY(0)'; });
      card.addEventListener('mouseleave', function () { overlay.style.opacity = '0'; overlay.style.transform = 'translateY(100%)'; });
      overlay.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        window.location.href = '/products/' + card.getAttribute('data-handle');
      });
    });

    /* Remove single item */
    el.querySelectorAll('.ths-wl-remove').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var handle = btn.dataset.handle;
        removeFromWishlist(handle);
        var card = btn.closest('.ths-wl-card');
        if (card) {
          card.style.transition = 'opacity 0.3s, transform 0.3s';
          card.style.opacity = '0';
          card.style.transform = 'scale(0.95)';
          setTimeout(function () {
            card.remove();
            updateCount(el);
          }, 300);
        }
      });
    });

    /* Heart icon removal (already handled by global toggleWishlist, but also remove card) */
    el.querySelectorAll('.ths-card__wishlist').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var card = btn.closest('.ths-wl-card');
        if (card) {
          setTimeout(function () {
            card.style.transition = 'opacity 0.3s, transform 0.3s';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            setTimeout(function () {
              card.remove();
              updateCount(el);
            }, 300);
          }, 100);
        }
      });
    });

    /* Clear All */
    var clearBtn = el.querySelector('.ths-wl-clear-all');
    if (clearBtn) clearBtn.addEventListener('click', function () {
      if (!confirm('Remove all favorites?')) return;
      saveWishlist([]);
      updateBadge();
      init();
    });
  }

  function removeFromWishlist(handle) {
    var list = getWishlist().filter(function (h) { return h !== handle; });
    saveWishlist(list);
    updateBadge();
  }

  function updateCount(el) {
    var remaining = el.querySelectorAll('.ths-wl-card').length;
    var countEl = el.querySelector('.ths-wl-count');
    if (countEl) countEl.textContent = remaining + ' item' + (remaining !== 1 ? 's' : '') + ' saved';
    if (remaining === 0) init(); /* re-render empty state */
    updateBadge();
  }

  /* ─── Badge on header heart ─── */

  function updateBadge() {
    var badge = document.querySelector('.ths-wishlist-badge');
    if (!badge) return;
    var count = getWishlist().length;
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : count;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  }

  /* ─── Init ─── */

  async function init() {
    var el = document.getElementById('ths-wishlist');
    if (!el) return;

    var handles = getWishlist();
    if (handles.length === 0) {
      renderPage(el, []);
      return;
    }

    el.innerHTML = '<div class="ths-wl-loading"><div class="ths-wl-spinner"></div><p>Loading your favorites...</p></div>';

    try {
      var products = await fetchProducts(handles);
      /* Sort products in same order as wishlist */
      products.sort(function (a, b) {
        return handles.indexOf(a.handle) - handles.indexOf(b.handle);
      });
      renderPage(el, products);
    } catch (e) {
      console.error('[THS Wishlist]', e);
      el.innerHTML = '<div class="ths-wl-empty"><h2>Something went wrong</h2><p>Please try refreshing the page.</p><a href="/" class="ths-wl-continue">Browse Products</a></div>';
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();