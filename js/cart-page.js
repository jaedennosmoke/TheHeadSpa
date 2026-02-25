/**
 * The Head Spa — Custom Shopify Cart Page v2.1
 * Matches THS cart mockup precisely
 */
(function () {
  'use strict';

  const CONFIG = {
    storeDomain: 'theheadspa.myshopify.com',
    storefrontToken: '2fdc858a424bb881522a7dbf92a4efaf',
    apiVersion: '2025-01',
    cartIdKey: '__shopify:cartId',
  };

  const API_URL = `https://${CONFIG.storeDomain}/api/${CONFIG.apiVersion}/graphql.json`;

  /* ─── GraphQL ─── */

  const CART_FIELDS = `
    id checkoutUrl totalQuantity
    cost {
      totalAmount { amount currencyCode }
      subtotalAmount { amount currencyCode }
      totalTaxAmount { amount currencyCode }
    }
    lines(first: 50) {
      edges { node {
        id quantity
        cost { totalAmount { amount currencyCode } }
        merchandise { ... on ProductVariant {
          id title
          image { url altText }
          product { title handle description vendor }
          price { amount currencyCode }
        }}
        sellingPlanAllocation {
          sellingPlan { name options { name value } }
          priceAdjustments {
            price { amount currencyCode }
            compareAtPrice { amount currencyCode }
          }
        }
      }}
    }`;

  const Q_CART = `query($cartId:ID!){cart(id:$cartId){${CART_FIELDS}}}`;
  const M_UPDATE = `mutation($cartId:ID!,$lines:[CartLineUpdateInput!]!){cartLinesUpdate(cartId:$cartId,lines:$lines){cart{${CART_FIELDS}}userErrors{field message}}}`;
  const M_REMOVE = `mutation($cartId:ID!,$lineIds:[ID!]!){cartLinesRemove(cartId:$cartId,lineIds:$lineIds){cart{${CART_FIELDS}}userErrors{field message}}}`;
  const M_DISCOUNT = `mutation($cartId:ID!,$discountCodes:[String!]!){cartDiscountCodesUpdate(cartId:$cartId,discountCodes:$discountCodes){cart{${CART_FIELDS}}userErrors{field message}}}`;
  const M_ADD = `mutation($cartId:ID!,$lines:[CartLineInput!]!){cartLinesAdd(cartId:$cartId,lines:$lines){cart{${CART_FIELDS}}userErrors{field message}}}`;

  /* ─── Helpers ─── */

  async function api(query, variables = {}) {
    const r = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Shopify-Storefront-Access-Token': CONFIG.storefrontToken },
      body: JSON.stringify({ query, variables }),
    });
    const j = await r.json();
    if (j.errors) throw new Error(j.errors.map(e => e.message).join(', '));
    return j.data;
  }

  function cartId() { const r = localStorage.getItem(CONFIG.cartIdKey); return r ? r.split('?')[0] : null; }
  function money(a, c = 'USD') { return new Intl.NumberFormat('en-US', { style: 'currency', currency: c }).format(parseFloat(a)); }
  function truncate(t, m = 80) { if (!t) return ''; const s = t.replace(/<[^>]*>/g, ''); return s.length <= m ? s : s.substring(0, m).trim() + '...'; }

  /* ─── SVG Icons ─── */

  const ICONS = {
    truck: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
    sameDay: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 12l2 2 4-4"/></svg>',
    shipped: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
    pickup: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
    auto: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>',
    gift: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>',
    trash: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>',
  };

  /* ─── Render: Cart ─── */

  function renderCart(cart) {
    const el = document.getElementById('ths-cart-page');
    if (!el) return;
    const lines = cart.lines.edges.map(e => e.node);
    if (!lines.length) { el.innerHTML = renderEmpty(); return; }

    el.innerHTML = `
      <div class="ths2-layout">
        <div class="ths2-main">
          <h1 class="ths2-heading">SHIPPING AND DELIVERY CART (${cart.totalQuantity})</h1>
          <div class="ths2-ship-group">
            <div class="ths2-ship-header">
              <span class="ths2-ship-icon">${ICONS.truck}</span>
              <strong>Get It Shipped (${cart.totalQuantity})</strong>
            </div>
            <p class="ths2-members">Members enjoy <strong style="color:#e74c3c;">FREE Standard Shipping</strong> on all orders.</p>
            <div class="ths2-lines">${lines.map(l => renderLine(l)).join('')}</div>
          </div>
          <div class="ths2-gift">
            <span class="ths2-gift-icon">${ICONS.gift}</span>
            <span>Add a Gift Message</span>
            <span class="ths2-gift-arrow">›</span>
          </div>
        </div>
        <div class="ths2-sidebar">
          <div class="ths2-summary">
            <div class="ths2-summary-top">
              <span>Estimated Total (${cart.totalQuantity} Item${cart.totalQuantity !== 1 ? 's' : ''})</span>
              <strong>${money(cart.cost.totalAmount.amount)}</strong>
            </div>
            <p class="ths2-summary-note">Shipping and taxes calculated during checkout.</p>
            <a href="${cart.checkoutUrl}" class="ths2-checkout" target="_top">Checkout</a>
            <a href="#" class="ths2-featured-offers">View Featured Offers</a>
            <div class="ths2-promo">
              <div class="ths2-promo-row">
                <input type="text" id="ths2-promo-input" placeholder="Promo Code">
                <button id="ths2-promo-apply">Apply</button>
              </div>
            </div>
            <div class="ths2-details">
              <div class="ths2-row">
                <span>Subtotal</span>
                <span>${money(cart.cost.subtotalAmount.amount)}</span>
              </div>
              <div class="ths2-row">
                <span class="ths2-row-label">Shipping <span class="ths2-info-icon">i</span></span>
                <span class="ths2-free">FREE</span>
              </div>
              <div class="ths2-row">
                <span class="ths2-row-label">Estimated Tax <span class="ths2-info-icon">i</span></span>
                <span>${cart.cost.totalTaxAmount && parseFloat(cart.cost.totalTaxAmount.amount) > 0 ? money(cart.cost.totalTaxAmount.amount) : 'TBD'}</span>
              </div>
              <div class="ths2-row ths2-total">
                <strong>Estimated Total</strong>
                <strong>${money(cart.cost.totalAmount.amount)}</strong>
              </div>
            </div>
          </div>
        </div>
      </div>`;

    attachEvents();
  }

  /* ─── Render: Line Item ─── */

  function renderLine(line) {
    const m = line.merchandise;
    const title = m.product.title;
    const variant = m.title !== 'Default Title' ? m.title : '';
    const vendor = m.product.vendor || '';
    const handle = m.product.handle || '';
    const pdpUrl = handle ? '/products/' + handle : '#';
    const img = m.image?.url || '';
    const price = money(m.price.amount);
    let opts = '';
    for (let i = 1; i <= 10; i++) opts += `<option value="${i}" ${i === line.quantity ? 'selected' : ''}>${i}</option>`;

    /* Subscription / selling plan detection */
    const sp = line.sellingPlanAllocation;
    const isSub = !!sp;
    if (isSub) console.log('[THS Cart] Selling plan data:', JSON.stringify(sp));
    const subPlanName = sp?.sellingPlan?.name || '';
    let subPrice = '';
    let subCompare = '';
    let subDiscount = '';
    if (isSub && sp.priceAdjustments && sp.priceAdjustments.length > 0) {
      const adj = sp.priceAdjustments[0];
      subPrice = money(adj.price.amount);
      if (adj.compareAtPrice && parseFloat(adj.compareAtPrice.amount) > parseFloat(adj.price.amount)) {
        subCompare = money(adj.compareAtPrice.amount);
        const pct = Math.round((1 - parseFloat(adj.price.amount) / parseFloat(adj.compareAtPrice.amount)) * 100);
        if (pct > 0) subDiscount = pct + '% OFF';
      }
    }

    /* Extract interval from selling plan options or name */
    let interval = '';
    if (sp?.sellingPlan?.options?.length) {
      const deliveryOpt = sp.sellingPlan.options.find(o => o.value && o.value !== sp.sellingPlan.name);
      interval = deliveryOpt ? deliveryOpt.value : sp.sellingPlan.options[0].value || '';
    }
    if (!interval) {
      const intervalMatch = subPlanName.match(/every\s+(.+)/i);
      interval = intervalMatch ? intervalMatch[1] : '';
    }

    return `
      <div class="ths2-line" data-line-id="${line.id}">
        <div class="ths2-line-img">
          <a href="${pdpUrl}">${img ? `<img src="${img}&width=300" alt="${title}" loading="lazy">` : '<div class="ths2-no-img"></div>'}</a>
        </div>
        <div class="ths2-line-info">
          ${vendor ? `<p class="ths2-line-vendor">${vendor}</p>` : ''}
          <h3 class="ths2-line-title"><a href="${pdpUrl}">${title}</a></h3>
          ${variant ? `<p class="ths2-line-variant">${variant}</p>` : ''}
          ${isSub ? `
            <div class="ths2-line-sub-badge"><span class="ths2-autorenew-badge">AUTO-REPLENISH ON</span></div>
            <p class="ths2-line-interval">${interval ? 'Delivers every ' + interval : 'Auto-Replenish active'}</p>
            <div class="ths2-line-sub-pricing">
              ${subCompare ? `<span class="ths2-line-original-price">${subCompare}</span>` : ''}
              <span class="ths2-line-sub-price">${subPrice || price}</span>
              ${subDiscount ? `<span class="ths2-line-discount-tag">${subDiscount}</span>` : ''}
            </div>
          ` : `
            <p class="ths2-line-price">${price}</p>
          `}

          <div class="ths2-line-actions">
            <span class="ths2-qty-wrap"><select class="ths2-qty" data-line-id="${line.id}" aria-label="Quantity">${opts}</select></span>
            <button class="ths2-remove" data-line-id="${line.id}" aria-label="Remove">${ICONS.trash}</button>
          </div>
        </div>
        <div class="ths2-delivery">
          <label class="ths2-del-opt">
            <input type="radio" name="del-${CSS.escape(line.id)}" value="same-day">
            <span><strong>Same-Day Delivery</strong><small>Order in 2 hours</small></span>
            <span class="ths2-del-icon">${ICONS.sameDay}</span>
          </label>
          <label class="ths2-del-opt ths2-del-active">
            <input type="radio" name="del-${CSS.escape(line.id)}" value="shipped" checked>
            <span><strong>Get It Shipped</strong><small style="color:#5bb5a2;">Estimated delivery</small></span>
            <span class="ths2-del-icon">${ICONS.shipped}</span>
          </label>
          <label class="ths2-del-opt">
            <input type="radio" name="del-${CSS.escape(line.id)}" value="pickup">
            <span><strong>Buy Online and Pick Up</strong><small>Pick up at 8335 Westchester</small></span>
            <span class="ths2-del-icon">${ICONS.pickup}</span>
          </label>
        </div>
      </div>`;
  }

  /* ─── Render: Empty / Loading / Error ─── */

  function renderEmpty() {
    return `<div class="ths2-empty"><svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg><h2>Your cart is empty</h2><p>Looks like you haven't added anything yet.</p><a href="/shop" class="ths2-continue">Continue Shopping</a></div>`;
  }

  function renderLoading() {
    const c = document.getElementById('ths-cart-page');
    if (c) c.innerHTML = '<div class="ths2-loading"><div class="ths2-spinner"></div><p>Loading your cart...</p></div>';
  }

  function renderError(msg) {
    const c = document.getElementById('ths-cart-page');
    if (c) c.innerHTML = `<div class="ths2-empty"><h2>Something went wrong</h2><p>${msg}</p><a href="/shop" class="ths2-continue">Continue Shopping</a></div>`;
  }

  /* ─── Render: Recommendations ─── */

  /* ─── Events ─── */

  function attachEvents() {
    document.querySelectorAll('.ths2-qty').forEach(s => s.addEventListener('change', onQty));
    document.querySelectorAll('.ths2-remove').forEach(b => b.addEventListener('click', onRemove));
    const promo = document.getElementById('ths2-promo-apply');
    if (promo) promo.addEventListener('click', onPromo);
    document.querySelectorAll('.ths2-del-opt input').forEach(r => {
      r.addEventListener('change', function () {
        this.closest('.ths2-delivery').querySelectorAll('.ths2-del-opt').forEach(o => o.classList.remove('ths2-del-active'));
        this.closest('.ths2-del-opt').classList.add('ths2-del-active');
      });
    });
  }

  async function onQty(e) {
    const id = e.target.dataset.lineId, qty = parseInt(e.target.value, 10);
    if (qty < 1) return removeById(id);
    setLoad(id, true);
    try {
      const d = await api(M_UPDATE, { cartId: cartId(), lines: [{ id, quantity: qty }] });
      if (d.cartLinesUpdate.userErrors.length) { alert('Could not update quantity.'); setLoad(id, false); return; }
      renderCart(d.cartLinesUpdate.cart); badge(d.cartLinesUpdate.cart.totalQuantity);
    } catch (e) { console.error(e); setLoad(id, false); }
  }

  async function onRemove(e) { await removeById(e.currentTarget.dataset.lineId); }

  async function removeById(id) {
    setLoad(id, true);
    try {
      const d = await api(M_REMOVE, { cartId: cartId(), lineIds: [id] });
      if (d.cartLinesRemove.userErrors.length) { alert('Could not remove item.'); setLoad(id, false); return; }
      renderCart(d.cartLinesRemove.cart); badge(d.cartLinesRemove.cart.totalQuantity);
    } catch (e) { console.error(e); setLoad(id, false); }
  }

  async function onPromo() {
    const code = document.getElementById('ths2-promo-input')?.value.trim();
    if (!code) return;
    const btn = document.getElementById('ths2-promo-apply');
    btn.textContent = '...'; btn.disabled = true;
    try {
      const d = await api(M_DISCOUNT, { cartId: cartId(), discountCodes: [code] });
      if (d.cartDiscountCodesUpdate.userErrors.length) alert('Invalid promo code.');
      else { renderCart(d.cartDiscountCodesUpdate.cart); badge(d.cartDiscountCodesUpdate.cart.totalQuantity); }
    } catch (e) { console.error(e); alert('Could not apply promo.'); }
    btn.textContent = 'Apply'; btn.disabled = false;
  }

  function setLoad(id, on) { const el = document.querySelector(`.ths2-line[data-line-id="${id}"]`); if (el) el.classList.toggle('ths2-line-loading', on); }

  function badge(qty) {
    const b = document.querySelector('.ths-cart-badge');
    if (b) { if (qty > 0) { b.textContent = qty > 99 ? '99+' : qty; b.style.display = 'flex'; } else b.style.display = 'none'; }
  }

  /* ─── Init ─── */

  async function init() {
    const el = document.getElementById('ths-cart-page'); if (!el) return;
    const cid = cartId();
    if (!cid) { el.innerHTML = renderEmpty(); return; }
    renderLoading();
    try {
      const d = await api(Q_CART, { cartId: cid });
      if (!d.cart) { localStorage.removeItem(CONFIG.cartIdKey); el.innerHTML = renderEmpty(); return; }
      renderCart(d.cart);
    } catch (e) { console.error(e); renderError('Unable to load your cart. Please try refreshing.'); }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();