/* ─── The Head Spa — Shopify Collection Page v2.0 ─── */
(function() {
  'use strict';
  console.log('[THS Collection] JS loaded');

  var CONFIG = {
    storeDomain: 'theheadspa.myshopify.com',
    storefrontToken: '4617f01063d6f1b7503e71a499b36c43',
    apiVersion: '2025-01',
    resultsPerPage: 24,
  };

  var API_URL = 'https://' + CONFIG.storeDomain + '/api/' + CONFIG.apiVersion + '/graphql.json';

  /* ─── State ─── */
  var state = {
    handle: '',
    collectionTitle: '',
    collectionDescription: '',
    sortKey: 'COLLECTION_DEFAULT',
    reverse: false,
    inStockOnly: false,
    priceMin: null,
    priceMax: null,
    vendors: [],
    allVendors: {},
    productTypes: [],
    allProductTypes: {},
    tags: [],
    allTags: {},
    variantOptions: {},
    allVariantOptions: {},
    cursor: null,
    hasNextPage: false,
    totalCount: 0,
    filterSections: {
      availability: false,
      price: false,
      brand: true,
      productType: false,
      tags: false,
      variantOptions: {},
    },
    brandSearch: '',
  };

  /* ─── GraphQL ─── */
  function buildGQL() {
    return 'query collection($handle:String!,$first:Int!,$after:String,$sortKey:ProductCollectionSortKeys!,$reverse:Boolean,$filters:[ProductFilter!])' +
      '{collection(handle:$handle){id title description image{url altText}' +
      'products(first:$first,after:$after,sortKey:$sortKey,reverse:$reverse,filters:$filters)' +
      '{edges{node{id title handle vendor productType tags availableForSale ' +
      'options{name values}' +
      'featuredImage{url altText}priceRange{minVariantPrice{amount currencyCode}maxVariantPrice{amount currencyCode}}' +
      'compareAtPriceRange{minVariantPrice{amount currencyCode}}variants(first:1){edges{node{id availableForSale}}}}}' +
      'pageInfo{hasNextPage endCursor}}}}';
  }

  function buildFilters() {
    var f = [];
    if (state.inStockOnly) f.push({ available: true });
    if (state.priceMin !== null || state.priceMax !== null) {
      var p = {};
      if (state.priceMin !== null) p.min = state.priceMin;
      if (state.priceMax !== null) p.max = state.priceMax;
      f.push({ price: p });
    }
    state.vendors.forEach(function(v) { f.push({ productVendor: v }); });
    state.productTypes.forEach(function(t) { f.push({ productType: t }); });
    state.tags.forEach(function(t) { f.push({ tag: t }); });
    Object.keys(state.variantOptions).forEach(function(optName) {
      state.variantOptions[optName].forEach(function(optVal) {
        f.push({ variantOption: { name: optName, value: optVal } });
      });
    });
    return f.length > 0 ? f : null;
  }

  function money(a) { return '$' + parseFloat(a).toFixed(2); }

  async function api(query, variables) {
    var res = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Shopify-Storefront-Access-Token': CONFIG.storefrontToken },
      body: JSON.stringify({ query: query, variables: variables }),
    });
    var json = await res.json();
    if (json.errors) throw new Error(json.errors.map(function(e) { return e.message; }).join(', '));
    return json.data;
  }

  function esc(str) {
    var d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  /* ─── Get collection handle from URL ─── */
  function getCollectionHandle() {
    var path = window.location.pathname;
    console.log('[THS Collection] Parsing handle from path:', path);
    var match = path.match(/\/collections\/([^\/]+)/);
    if (match) return decodeURIComponent(match[1]);
    var params = new URLSearchParams(window.location.search);
    return params.get('collection') || '';
  }

  /* ─── Collect filter data ─── */
  function collectFilterData(products) {
    products.forEach(function(p) {
      if (p.vendor) state.allVendors[p.vendor] = (state.allVendors[p.vendor] || 0) + 1;
      if (p.productType) state.allProductTypes[p.productType] = (state.allProductTypes[p.productType] || 0) + 1;
      if (p.tags && p.tags.length) {
        p.tags.forEach(function(t) { state.allTags[t] = (state.allTags[t] || 0) + 1; });
      }
      if (p.options && p.options.length) {
        p.options.forEach(function(opt) {
          if (opt.name === 'Title') return;
          if (!state.allVariantOptions[opt.name]) state.allVariantOptions[opt.name] = {};
          opt.values.forEach(function(val) {
            state.allVariantOptions[opt.name][val] = (state.allVariantOptions[opt.name][val] || 0) + 1;
          });
        });
      }
    });
  }

  function sortedKeys(obj) {
    return Object.keys(obj).sort(function(a, b) { return a.toLowerCase().localeCompare(b.toLowerCase()); });
  }

  /* ─── Chevron ─── */
  function chevron(open) {
    return '<svg class="ths-sr-chevron' + (open ? ' ths-sr-chevron--open' : '') + '" width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
      '<path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  }

  /* ─── Checkbox list builder ─── */
  function buildCheckboxList(items, counts, selectedArray, dataAttr) {
    return items.map(function(item) {
      var checked = selectedArray.indexOf(item) > -1 ? ' checked' : '';
      var count = counts[item] || 0;
      return '<label class="ths-sr-cb-label">' +
        '<input type="checkbox" class="ths-sr-cb" data-' + dataAttr + '="' + esc(item) + '"' + checked + '>' +
        '<span class="ths-sr-cb-text">' + esc(item) + ' <span class="ths-sr-cb-count">(' + count + ')</span></span>' +
      '</label>';
    }).join('');
  }

  /* ─── Collapsible section ─── */
  function buildSection(key, title, bodyHtml, isOpen) {
    return '<div class="ths-sr-filter-section" data-section="' + key + '">' +
      '<button class="ths-sr-section-toggle"><span>' + title + '</span>' + chevron(isOpen) + '</button>' +
      '<div class="ths-sr-section-body' + (isOpen ? ' ths-sr-section-open' : '') + '">' + bodyHtml + '</div>' +
    '</div>';
  }

  /* ─── Breadcrumbs ─── */
  function renderBreadcrumbs() {
    return '<nav class="ths-col-breadcrumbs" aria-label="Breadcrumb">' +
      '<a href="/">Home</a>' +
      '<span class="ths-col-bc-sep">/</span>' +
      '<span class="ths-col-bc-current">' + esc(state.collectionTitle) + '</span>' +
    '</nav>';
  }

  /* ─── Sidebar ─── */
  function renderSidebar() {
    var vendors = sortedKeys(state.allVendors);
    var brandSearch = state.brandSearch.toLowerCase();
    var filteredVendors = brandSearch ? vendors.filter(function(v) { return v.toLowerCase().indexOf(brandSearch) > -1; }) : vendors;
    var productTypes = sortedKeys(state.allProductTypes);
    var tagsList = sortedKeys(state.allTags);
    var optionNames = sortedKeys(state.allVariantOptions);

    /* Availability */
    var availBody = '<label class="ths-sr-cb-label">' +
      '<input type="checkbox" class="ths-sr-cb" id="ths-col-instock"' + (state.inStockOnly ? ' checked' : '') + '>' +
      '<span class="ths-sr-cb-text">In Stock Only</span></label>';

    /* Price */
    var priceBody = '<div class="ths-sr-price-row">' +
      '<div class="ths-sr-price-field"><span class="ths-sr-price-label">$</span>' +
        '<input type="number" id="ths-col-pmin" class="ths-sr-price-input" placeholder="Min" min="0" step="1"' + (state.priceMin !== null ? ' value="' + state.priceMin + '"' : '') + '></div>' +
      '<span class="ths-sr-price-to">to</span>' +
      '<div class="ths-sr-price-field"><span class="ths-sr-price-label">$</span>' +
        '<input type="number" id="ths-col-pmax" class="ths-sr-price-input" placeholder="Max" min="0" step="1"' + (state.priceMax !== null ? ' value="' + state.priceMax + '"' : '') + '></div>' +
      '<button id="ths-col-price-go" class="ths-sr-price-go" aria-label="Apply price filter"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>' +
    '</div>';

    /* Brand */
    var brandBody = (vendors.length > 5
      ? '<div class="ths-sr-brand-search-wrap">' +
          '<svg class="ths-sr-brand-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="#999" stroke-width="2"/><path d="M21 21l-4.35-4.35" stroke="#999" stroke-width="2" stroke-linecap="round"/></svg>' +
          '<input type="text" id="ths-col-brand-search" class="ths-sr-brand-search" placeholder="Search Brand" value="' + esc(state.brandSearch) + '">' +
        '</div>' : '') +
      '<div class="ths-sr-brand-list">' +
        buildCheckboxList(filteredVendors, state.allVendors, state.vendors, 'vendor') +
        (filteredVendors.length === 0 ? '<p class="ths-sr-brand-none">No brands found</p>' : '') +
      '</div>';

    /* Product Type */
    var typeBody = '<div class="ths-sr-scrollable-list">' +
      buildCheckboxList(productTypes, state.allProductTypes, state.productTypes, 'ptype') + '</div>';

    /* Tags */
    var tagsBody = '<div class="ths-sr-scrollable-list">' +
      buildCheckboxList(tagsList, state.allTags, state.tags, 'tag') + '</div>';

    /* Variant Options */
    var variantSections = '';
    optionNames.forEach(function(optName) {
      var values = sortedKeys(state.allVariantOptions[optName]);
      var selected = state.variantOptions[optName] || [];
      var sectionKey = 'vo_' + optName.replace(/\s+/g, '_').toLowerCase();
      var isOpen = state.filterSections.variantOptions[optName] || false;
      var body = '<div class="ths-sr-scrollable-list">' +
        values.map(function(val) {
          var checked = selected.indexOf(val) > -1 ? ' checked' : '';
          var count = state.allVariantOptions[optName][val] || 0;
          return '<label class="ths-sr-cb-label">' +
            '<input type="checkbox" class="ths-sr-cb" data-vo-name="' + esc(optName) + '" data-vo-value="' + esc(val) + '"' + checked + '>' +
            '<span class="ths-sr-cb-text">' + esc(val) + ' <span class="ths-sr-cb-count">(' + count + ')</span></span>' +
          '</label>';
        }).join('') + '</div>';
      variantSections += '<div class="ths-sr-filter-section" data-section="' + esc(sectionKey) + '" data-vo-section="' + esc(optName) + '">' +
        '<button class="ths-sr-section-toggle"><span>' + esc(optName) + '</span>' + chevron(isOpen) + '</button>' +
        '<div class="ths-sr-section-body' + (isOpen ? ' ths-sr-section-open' : '') + '">' + body + '</div>' +
      '</div>';
    });

    return '<aside class="ths-sr-sidebar">' +
      '<h1 class="ths-sr-sidebar-heading">' + esc(state.collectionTitle) + '</h1>' +
      (state.collectionDescription ? '<p class="ths-col-description">' + esc(state.collectionDescription) + '</p>' : '') +
      '<p class="ths-sr-filters-label">Filters</p>' +
      buildSection('availability', 'Availability', availBody, state.filterSections.availability) +
      buildSection('price', 'Price Range', priceBody, state.filterSections.price) +
      buildSection('brand', 'Brand', brandBody, state.filterSections.brand) +
      (productTypes.length > 0 ? buildSection('productType', 'Product Type', typeBody, state.filterSections.productType) : '') +
      (tagsList.length > 0 ? buildSection('tags', 'Tags', tagsBody, state.filterSections.tags) : '') +
      variantSections +
      renderActiveFilters() +
    '</aside>';
  }

  /* ─── Active Filters ─── */
  function renderActiveFilters() {
    var t = [];
    if (state.inStockOnly) t.push({ label: 'In Stock', key: 'instock' });
    if (state.priceMin !== null || state.priceMax !== null) {
      var lbl = '';
      if (state.priceMin !== null && state.priceMax !== null) lbl = '$' + state.priceMin + ' – $' + state.priceMax;
      else if (state.priceMin !== null) lbl = '$' + state.priceMin + '+';
      else lbl = 'Up to $' + state.priceMax;
      t.push({ label: lbl, key: 'price' });
    }
    state.vendors.forEach(function(v) { t.push({ label: v, key: 'vendor:' + v }); });
    state.productTypes.forEach(function(v) { t.push({ label: v, key: 'ptype:' + v }); });
    state.tags.forEach(function(v) { t.push({ label: v, key: 'tag:' + v }); });
    Object.keys(state.variantOptions).forEach(function(name) {
      state.variantOptions[name].forEach(function(val) {
        t.push({ label: name + ': ' + val, key: 'vo:' + name + ':' + val });
      });
    });
    if (t.length === 0) return '';
    var html = '<div class="ths-sr-active-filters">';
    t.forEach(function(tag) {
      html += '<span class="ths-sr-active-tag" data-key="' + esc(tag.key) + '">' + esc(tag.label) + '<button class="ths-sr-tag-remove">&times;</button></span>';
    });
    html += '<button class="ths-sr-clear-all">Clear All</button></div>';
    return html;
  }

  /* ─── Sort Bar ─── */
  function renderSortBar(productCount) {
    return '<div class="ths-sr-sort-bar">' +
      '<span class="ths-sr-result-text">' + productCount + ' product' + (productCount !== 1 ? 's' : '') + '</span>' +
      '<div class="ths-sr-sort-wrap">' +
        '<label class="ths-sr-sort-label">Sort by:</label>' +
        '<select id="ths-col-sort" class="ths-sr-sort-select">' +
          '<option value="COLLECTION_DEFAULT"' + (state.sortKey === 'COLLECTION_DEFAULT' ? ' selected' : '') + '>Featured</option>' +
          '<option value="BEST_SELLING"' + (state.sortKey === 'BEST_SELLING' ? ' selected' : '') + '>Best Selling</option>' +
          '<option value="PRICE_ASC"' + (state.sortKey === 'PRICE' && !state.reverse ? ' selected' : '') + '>Price: Low to High</option>' +
          '<option value="PRICE_DESC"' + (state.sortKey === 'PRICE' && state.reverse ? ' selected' : '') + '>Price: High to Low</option>' +
          '<option value="TITLE_ASC"' + (state.sortKey === 'TITLE' && !state.reverse ? ' selected' : '') + '>Name: A – Z</option>' +
          '<option value="TITLE_DESC"' + (state.sortKey === 'TITLE' && state.reverse ? ' selected' : '') + '>Name: Z – A</option>' +
          '<option value="CREATED_DESC"' + (state.sortKey === 'CREATED' && state.reverse ? ' selected' : '') + '>Newest</option>' +
        '</select>' +
      '</div>' +
    '</div>';
  }

  /* ─── Product Card ─── */
  function renderProduct(product) {
    var img = product.featuredImage ? product.featuredImage.url : '';
    var alt = product.featuredImage ? (product.featuredImage.altText || product.title) : product.title;
    var price = money(product.priceRange.minVariantPrice.amount);
    var compareAt = product.compareAtPriceRange && parseFloat(product.compareAtPriceRange.minVariantPrice.amount) > parseFloat(product.priceRange.minVariantPrice.amount)
      ? money(product.compareAtPriceRange.minVariantPrice.amount) : '';
    var vendor = product.vendor || '';
    var available = product.availableForSale;
    var href = '/products/' + product.handle + '?collection=' + encodeURIComponent(state.handle);

    return '<div class="ths-card" data-handle="' + product.handle + '">' +
      '<div class="ths-card__image-wrapper" style="overflow:hidden;position:relative;">' +
        '<button class="ths-card__wishlist" onclick="toggleWishlist(this)" aria-label="Add to wishlist">' +
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
          '<button class="ths-card__add-bag"' + (!available ? ' disabled' : '') + ' aria-label="Add to bag">' +
            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
              '<path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>' +
              '<line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></line>' +
              '<path d="M16 10a4 4 0 01-8 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>' +
            '</svg>' +
          '</button>' +
        '</div>' +
      '</div>' +
    '</div>';
  }

  /* ─── Full Render ─── */
  function renderPage(el, products, productCount) {
    var grid;
    if (products.length === 0) {
      grid = '<div class="ths-sr-empty"><p>No products found matching your filters.</p>' +
        '<p>Try adjusting your filters or <a href="/shop">browse all products</a>.</p></div>';
    } else {
      grid = '<div class="ths-sr-grid">' + products.map(renderProduct).join('') + '</div>';
    }

    el.innerHTML = renderBreadcrumbs() +
      '<div class="ths-sr-layout">' +
        renderSidebar() +
        '<div class="ths-sr-main">' + renderSortBar(productCount) + grid + '</div>' +
      '</div>';

    bindAllEvents(el);
    bindCardEvents(el);
  }

  /* ─── Bind Events ─── */
  function bindAllEvents(container) {

    /* Section toggles */
    container.querySelectorAll('.ths-sr-section-toggle').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var section = btn.closest('.ths-sr-filter-section');
        var key = section.getAttribute('data-section');
        var voName = section.getAttribute('data-vo-section');
        var body = section.querySelector('.ths-sr-section-body');
        var chev = btn.querySelector('.ths-sr-chevron');
        if (voName) { state.filterSections.variantOptions[voName] = !state.filterSections.variantOptions[voName]; }
        else { state.filterSections[key] = !state.filterSections[key]; }
        body.classList.toggle('ths-sr-section-open');
        chev.classList.toggle('ths-sr-chevron--open');
      });
    });

    /* Sort */
    var sortEl = container.querySelector('#ths-col-sort');
    if (sortEl) sortEl.addEventListener('change', function() {
      var v = sortEl.value;
      if (v === 'COLLECTION_DEFAULT') { state.sortKey = 'COLLECTION_DEFAULT'; state.reverse = false; }
      else if (v === 'BEST_SELLING') { state.sortKey = 'BEST_SELLING'; state.reverse = false; }
      else if (v === 'PRICE_ASC') { state.sortKey = 'PRICE'; state.reverse = false; }
      else if (v === 'PRICE_DESC') { state.sortKey = 'PRICE'; state.reverse = true; }
      else if (v === 'TITLE_ASC') { state.sortKey = 'TITLE'; state.reverse = false; }
      else if (v === 'TITLE_DESC') { state.sortKey = 'TITLE'; state.reverse = true; }
      else if (v === 'CREATED_DESC') { state.sortKey = 'CREATED'; state.reverse = true; }
      doSearch();
    });

    /* In Stock */
    var stockEl = container.querySelector('#ths-col-instock');
    if (stockEl) stockEl.addEventListener('change', function() {
      state.inStockOnly = stockEl.checked;
      doSearch();
    });

    /* Price */
    var priceGo = container.querySelector('#ths-col-price-go');
    if (priceGo) priceGo.addEventListener('click', function() {
      var mn = container.querySelector('#ths-col-pmin');
      var mx = container.querySelector('#ths-col-pmax');
      state.priceMin = mn.value ? parseFloat(mn.value) : null;
      state.priceMax = mx.value ? parseFloat(mx.value) : null;
      doSearch();
    });
    container.querySelectorAll('#ths-col-pmin, #ths-col-pmax').forEach(function(inp) {
      inp.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); priceGo.click(); } });
    });

    /* Brand search */
    var brandSearchEl = container.querySelector('#ths-col-brand-search');
    if (brandSearchEl) brandSearchEl.addEventListener('input', function() {
      state.brandSearch = brandSearchEl.value;
      var list = container.querySelector('.ths-sr-brand-list');
      if (list) {
        var vendors = sortedKeys(state.allVendors);
        var search = state.brandSearch.toLowerCase();
        var filtered = search ? vendors.filter(function(v) { return v.toLowerCase().indexOf(search) > -1; }) : vendors;
        list.innerHTML = buildCheckboxList(filtered, state.allVendors, state.vendors, 'vendor') +
          (filtered.length === 0 ? '<p class="ths-sr-brand-none">No brands found</p>' : '');
        bindCheckboxGroup(container, 'vendor', state.vendors, doSearch);
      }
    });

    /* Checkbox groups */
    bindCheckboxGroup(container, 'vendor', state.vendors, doSearch);
    bindCheckboxGroup(container, 'ptype', state.productTypes, doSearch);
    bindCheckboxGroup(container, 'tag', state.tags, doSearch);

    /* Variant options */
    container.querySelectorAll('.ths-sr-cb[data-vo-name]').forEach(function(cb) {
      cb.addEventListener('change', function() {
        var name = cb.getAttribute('data-vo-name');
        var val = cb.getAttribute('data-vo-value');
        if (!state.variantOptions[name]) state.variantOptions[name] = [];
        if (cb.checked) {
          if (state.variantOptions[name].indexOf(val) === -1) state.variantOptions[name].push(val);
        } else {
          state.variantOptions[name] = state.variantOptions[name].filter(function(x) { return x !== val; });
          if (state.variantOptions[name].length === 0) delete state.variantOptions[name];
        }
        doSearch();
      });
    });

    /* Active filter removal */
    var af = container.querySelector('.ths-sr-active-filters');
    if (af) af.addEventListener('click', function(e) {
      var removeBtn = e.target.closest('.ths-sr-tag-remove');
      var clearAll = e.target.closest('.ths-sr-clear-all');
      if (clearAll) {
        state.inStockOnly = false; state.priceMin = null; state.priceMax = null;
        state.vendors = []; state.productTypes = []; state.tags = [];
        state.variantOptions = {};
        doSearch();
      } else if (removeBtn) {
        var tag = removeBtn.closest('.ths-sr-active-tag');
        var key = tag.getAttribute('data-key');
        if (key === 'instock') state.inStockOnly = false;
        else if (key === 'price') { state.priceMin = null; state.priceMax = null; }
        else if (key.indexOf('vendor:') === 0) state.vendors = state.vendors.filter(function(x) { return x !== key.replace('vendor:', ''); });
        else if (key.indexOf('ptype:') === 0) state.productTypes = state.productTypes.filter(function(x) { return x !== key.replace('ptype:', ''); });
        else if (key.indexOf('tag:') === 0) state.tags = state.tags.filter(function(x) { return x !== key.replace('tag:', ''); });
        else if (key.indexOf('vo:') === 0) {
          var parts = key.split(':');
          var name = parts[1]; var val = parts.slice(2).join(':');
          if (state.variantOptions[name]) {
            state.variantOptions[name] = state.variantOptions[name].filter(function(x) { return x !== val; });
            if (state.variantOptions[name].length === 0) delete state.variantOptions[name];
          }
        }
        doSearch();
      }
    });
  }

  function bindCheckboxGroup(container, dataAttr, stateArray, callback) {
    container.querySelectorAll('.ths-sr-cb[data-' + dataAttr + ']').forEach(function(cb) {
      cb.addEventListener('change', function() {
        var v = cb.getAttribute('data-' + dataAttr);
        if (cb.checked) { if (stateArray.indexOf(v) === -1) stateArray.push(v); }
        else { var idx = stateArray.indexOf(v); if (idx > -1) stateArray.splice(idx, 1); }
        callback();
      });
    });
  }

  /* ─── Card Events ─── */
  function bindCardEvents(container) {
    container.querySelectorAll('.ths-card').forEach(function(card) {
      var overlay = card.querySelector('.ths-card__overlay');
      if (!overlay) return;
      card.addEventListener('mouseenter', function() { overlay.style.opacity = '1'; overlay.style.transform = 'translateY(0)'; });
      card.addEventListener('mouseleave', function() { overlay.style.opacity = '0'; overlay.style.transform = 'translateY(100%)'; });
      overlay.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); window.location.href = '/products/' + card.getAttribute('data-handle') + '?collection=' + encodeURIComponent(state.handle); });
    });
    container.querySelectorAll('.ths-card__add-bag:not([disabled])').forEach(function(btn) {
      btn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); var card = btn.closest('.ths-card'); if (card) window.location.href = '/products/' + card.getAttribute('data-handle') + '?collection=' + encodeURIComponent(state.handle); });
    });
  }

  /* ─── Search / Fetch ─── */
  var currentProductCount = 0;

  async function doSearch(append) {
    var el = document.getElementById('ths-collection');
    if (!el) return;

    if (!append) {
      state.allVendors = {};
      state.allProductTypes = {};
      state.allTags = {};
      state.allVariantOptions = {};
      var gridEl = el.querySelector('.ths-sr-grid');
      var lm = el.querySelector('.ths-sr-load-more-wrap');
      if (lm) lm.remove();
      if (gridEl) gridEl.innerHTML = '<div class="ths-sr-loading-inline"><div class="ths-sr-spinner"></div></div>';
    }

    var variables = {
      handle: state.handle, first: CONFIG.resultsPerPage,
      after: append ? state.cursor : null,
      sortKey: state.sortKey, reverse: state.reverse,
    };
    var filters = buildFilters();
    if (filters) variables.filters = filters;

    try {
      var data = await api(buildGQL(), variables);
      if (!data.collection) {
        el.innerHTML = '<div class="ths-sr-empty"><p>Collection not found.</p><p><a href="/shop">Browse all products</a></p></div>';
        return;
      }
      var products = data.collection.products.edges.map(function(e) { return e.node; });
      state.cursor = data.collection.products.pageInfo.endCursor;
      state.hasNextPage = data.collection.products.pageInfo.hasNextPage;
      collectFilterData(products);

      if (append) {
        currentProductCount += products.length;
        var grid = el.querySelector('.ths-sr-grid');
        grid.insertAdjacentHTML('beforeend', products.map(renderProduct).join(''));
        bindCardEvents(grid);
      } else {
        currentProductCount = products.length;
        renderPage(el, products, currentProductCount);
      }

      /* Load more */
      var existingLM = el.querySelector('.ths-sr-load-more-wrap');
      if (existingLM) existingLM.remove();
      if (state.hasNextPage) {
        var wrap = document.createElement('div');
        wrap.className = 'ths-sr-load-more-wrap';
        var btn = document.createElement('button');
        btn.className = 'ths-sr-load-more';
        btn.textContent = 'Load More Products';
        wrap.appendChild(btn);
        el.querySelector('.ths-sr-main').appendChild(wrap);
        btn.addEventListener('click', async function() { btn.textContent = 'Loading...'; btn.disabled = true; await doSearch(true); });
      }
    } catch (e) {
      console.error('[THS Collection] Error:', e);
      var grid = el.querySelector('.ths-sr-grid');
      if (grid) grid.innerHTML = '<div class="ths-sr-empty"><p>Something went wrong. Please try again.</p></div>';
    }
  }

  /* ─── Init ─── */
  async function init() {
    var el = document.getElementById('ths-collection');
    console.log('[THS Collection] init(), element found:', !!el);
    if (!el) return;

    state.handle = el.getAttribute('data-handle') || getCollectionHandle();
    console.log('[THS Collection] handle:', state.handle);
    if (!state.handle) {
      el.innerHTML = '<div class="ths-sr-empty"><p>No collection specified.</p><p><a href="/shop">Browse all products</a></p></div>';
      return;
    }

    el.innerHTML = '<div class="ths-sr-loading"><div class="ths-sr-spinner"></div><p>Loading collection...</p></div>';

    try {
      var data = await api(buildGQL(), {
        handle: state.handle, first: CONFIG.resultsPerPage, after: null,
        sortKey: 'COLLECTION_DEFAULT', reverse: false,
      });

      if (!data.collection) {
        el.innerHTML = '<div class="ths-sr-empty"><p>Collection not found.</p><p><a href="/shop">Browse all products</a></p></div>';
        return;
      }

      state.collectionTitle = data.collection.title;
      state.collectionDescription = data.collection.description || '';

      var products = data.collection.products.edges.map(function(e) { return e.node; });
      state.cursor = data.collection.products.pageInfo.endCursor;
      state.hasNextPage = data.collection.products.pageInfo.hasNextPage;
      currentProductCount = products.length;
      collectFilterData(products);

      document.title = state.collectionTitle + ' \u2013 The Head Spa';

      renderPage(el, products, currentProductCount);

      if (state.hasNextPage) {
        var wrap = document.createElement('div');
        wrap.className = 'ths-sr-load-more-wrap';
        var btn = document.createElement('button');
        btn.className = 'ths-sr-load-more';
        btn.textContent = 'Load More Products';
        wrap.appendChild(btn);
        el.querySelector('.ths-sr-main').appendChild(wrap);
        btn.addEventListener('click', async function() { btn.textContent = 'Loading...'; btn.disabled = true; await doSearch(true); });
      }

      console.log('[THS Collection] Rendered', products.length, 'products');
    } catch (e) {
      console.error('[THS Collection] Init error:', e);
      el.innerHTML = '<div class="ths-sr-empty"><p>Something went wrong. Please try again.</p></div>';
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else { init(); }
})();