<?php
/**
 * Custom Product Detail Page (PDP) Template - The Head Spa
 * File: /wp-content/themes/hello-elementor-child/shopify/pdp.php
 * Phase 1: Core product layout
 * Phase 2: Image gallery thumbnails
 */

if (!defined('ABSPATH')) {
    exit;
}

/* --- Shopify Store Config (for Storefront API image fetching) --- */
$ths_shop_domain = get_option('shopify_store_url', '');
$ths_storefront_token = get_option('shopify_for_wordpress_access_code', '');
$ths_api_key = get_option('shopify_api_key', '');

/* Normalize domain */
if (!empty($ths_shop_domain)) {
    $ths_shop_domain = str_replace(array('https://', 'http://', '/'), '', $ths_shop_domain);
    if (strpos($ths_shop_domain, '.myshopify.com') === false) {
        $ths_shop_domain .= '.myshopify.com';
    }
}
?>

<div class="ths-pdp">
    <shopify-context type="product" handle="<?php echo esc_attr($product_handle); ?>">
        <template>

            <!-- Breadcrumbs -->
            <nav class="ths-pdp__breadcrumbs">
                <a href="/">Home</a>
                <span class="ths-pdp__breadcrumbs-sep">/</span>
                <span id="ths-pdp-collection-crumb"></span>
                <span class="ths-pdp__breadcrumbs-current">
                    <shopify-data query="product.title"></shopify-data>
                </span>
            </nav>
            <script>
            (function(){
                var params = new URLSearchParams(window.location.search);
                var colHandle = params.get('collection');
                if (!colHandle) return;
                var crumb = document.getElementById('ths-pdp-collection-crumb');
                if (!crumb) return;
                /* Fetch collection title from Storefront API */
                fetch('https://theheadspa.myshopify.com/api/2025-01/graphql.json', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Shopify-Storefront-Access-Token': '4617f01063d6f1b7503e71a499b36c43'
                    },
                    body: JSON.stringify({
                        query: 'query($h:String!){collection(handle:$h){title}}',
                        variables: { h: colHandle }
                    })
                })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if (data.data && data.data.collection) {
                        var title = data.data.collection.title;
                        crumb.innerHTML = '<a href="/our-collections/' + encodeURIComponent(colHandle) + '/">' + title + '</a>' +
                            '<span class="ths-pdp__breadcrumbs-sep">/</span>';
                    }
                })
                .catch(function(e){ console.warn('[THS Breadcrumb] Error:', e); });
            })();
            </script>

            <!-- Main Product Section -->
            <div class="ths-pdp__main">

                <!-- Left: Image Gallery -->
                <div class="ths-pdp__gallery">
                    <div class="ths-pdp__gallery-thumbs" id="pdp-thumbs"></div>
                    <div class="ths-pdp__gallery-main">
                        <!-- Controlled main image (populated by JS) -->
                        <img id="pdp-main-img" class="ths-pdp__main-img" src="" alt="" style="display:none;">
                        <!-- Fallback: Shopify component (shown until JS loads images) -->
                        <shopify-media
                            id="pdp-shopify-media"
                            layout="constrained"
                            width="600"
                            height="600"
                            query="product.selectedOrFirstAvailableVariant.image"
                        ></shopify-media>
                    </div>
                </div>

                <!-- Right: Product Details -->
                <div class="ths-pdp__details">

                    <!-- Brand -->
                    <p class="ths-pdp__brand">
                        <shopify-data query="product.vendor"></shopify-data>
                    </p>

                    <!-- Title -->
                    <h1 class="ths-pdp__title">
                        <shopify-data query="product.title"></shopify-data>
                    </h1>

                    <!-- Rating Row -->
                    <div class="ths-pdp__rating-row" id="pdp-rating-row">
                        <div class="ths-pdp__stars" id="pdp-top-stars">
                            <span class="ths-pdp__star">&#9733;</span>
                            <span class="ths-pdp__star">&#9733;</span>
                            <span class="ths-pdp__star">&#9733;</span>
                            <span class="ths-pdp__star">&#9733;</span>
                            <span class="ths-pdp__star">&#9733;</span>
                        </div>
                        <span class="ths-pdp__rating-count" id="pdp-rating-count"></span>
                        <span class="ths-pdp__rating-sep">|</span>
                        <a href="#ths-pdp-qna" class="ths-pdp__ask-link">Ask a Question</a>
                        <span class="ths-pdp__rating-sep">|</span>
                        <button class="ths-pdp__favorite-btn" id="pdp-fav-btn" aria-label="Add to favorites">
                            <svg class="ths-pdp__heart-svg" width="16" height="16" viewBox="0 0 24 24" fill="#e74c6f" stroke="none"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            <span class="ths-pdp__fav-count" id="pdp-fav-count"></span>
                        </button>
                    </div>

                    <!-- Highly rated badge (populated by Judge.me) -->
                    <p class="ths-pdp__badge" id="pdp-badge" style="display:none;"></p>


                    <!-- Price -->
                    <div class="ths-pdp__price">
                        <shopify-money query="product.selectedOrFirstAvailableVariant.price"></shopify-money>
                        <shopify-money
                            class="ths-pdp__compare-price"
                            query="product.selectedOrFirstAvailableVariant.compareAtPrice"
                        ></shopify-money>
                    </div>

                    <!-- Variant Selector -->
                    <div class="ths-pdp__variants">
                        <!-- Custom button swatches (built by JS) -->
                        <div class="ths-pdp__variant-swatches" id="pdp-variant-swatches"></div>
                        <!-- Hidden native selector for add-to-cart sync -->
                        <div class="ths-pdp__variants-native" style="display:none;">
                            <shopify-variant-selector></shopify-variant-selector>
                        </div>
                    </div>

                    <!-- Shipping Options -->
                    <div class="ths-pdp__shipping-options">
                        <div class="ths-pdp__shipping-option active" data-option="ship" onclick="selectShippingOption(this)">
                            <div class="ths-pdp__shipping-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 18H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h10l4 4v7a1 1 0 0 1-1 1h-2"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M15 7h4l2 3v5h-2"/></svg>
                            </div>
                            <span class="ths-pdp__shipping-label">Ship it for FREE shipping</span>
                        </div>
                        <div class="ths-pdp__shipping-option" data-option="autoreplenish" onclick="selectShippingOption(this)" style="display:none;">
                            <div class="ths-pdp__shipping-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                            </div>
                            <span class="ths-pdp__shipping-label">Auto-Replenish</span>
                            <span class="ths-pdp__shipping-sub">save 5% on this</span>
                        </div>
                        <div class="ths-pdp__shipping-option" data-option="sameday" onclick="selectShippingOption(this)">
                            <div class="ths-pdp__shipping-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            </div>
                            <span class="ths-pdp__shipping-label">Same-Day Delivery</span>
                        </div>
                        <div class="ths-pdp__shipping-option" data-option="pickup" onclick="selectShippingOption(this)">
                            <div class="ths-pdp__shipping-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            </div>
                            <span class="ths-pdp__shipping-label">Buy Online &amp; Pick Up</span>
                            <span class="ths-pdp__shipping-sub">8311 Westchester Drive</span>
                        </div>
                    </div>

                    <!-- Delivery Estimate -->
                    <div class="ths-pdp__delivery">
                        <p class="ths-pdp__delivery-text">
                            <span class="ths-pdp__delivery-bold">Delivery by Day, Mon 0 to: 12345-6789</span><br>
                            Sign in or create an account to enjoy <span class="ths-pdp__delivery-bold">FREE standard shipping</span>.
                        </p>
                        <a href="#" class="ths-pdp__delivery-link">Shipping &amp; Returns</a>
                    </div>

                    <!-- Quantity + Add to Cart -->
                    <div class="ths-pdp__actions">
                        <div class="ths-pdp__quantity">
                            <button class="ths-pdp__qty-btn" onclick="pdpDecreaseQty()">-</button>
                            <span class="ths-pdp__qty-value" id="pdp-qty">1</span>
                            <button class="ths-pdp__qty-btn" onclick="pdpIncreaseQty()">+</button>
                        </div>
                        <button
                            class="ths-pdp__add-to-cart"
                            onclick="getElementById('cart').addLine(event); getElementById('cart').showModal();"
                            shopify-attr--disabled="!product.selectedOrFirstAvailableVariant.product.availableForSale"
                        >
                            Add to Cart
                        </button>
                        <button class="ths-pdp__wishlist-btn" id="pdp-wishlist-btn" aria-label="Add to wishlist">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        </button>
                    </div>

                    <!-- Auto Renew Banner (populated dynamically from Seal Subscriptions selling plans) -->
                    <div class="ths-pdp__autorenew" id="pdp-autorenew" style="display:none;">
                        <div class="ths-pdp__autorenew-top">
                            <div class="ths-pdp__autorenew-info">
                                <p class="ths-pdp__autorenew-title">We Suggest Auto Renew</p>
                                <p class="ths-pdp__autorenew-sub" id="autorenew-interval">Products Auto Deliver on schedule</p>
                            </div>
                            <span class="ths-pdp__autorenew-badge" id="autorenew-badge"></span>
                        </div>
                        <div class="ths-pdp__autorenew-plans" id="autorenew-plans"></div>
                        <div class="ths-pdp__autorenew-toggle" id="autorenew-toggle">
                            <div class="ths-pdp__autorenew-slider" id="autorenew-slider"></div>
                            <button class="ths-pdp__autorenew-opt" data-value="on" onclick="setAutoRenew('on')">TURN ON AUTO RENEW</button>
                            <button class="ths-pdp__autorenew-opt" data-value="off" onclick="setAutoRenew('off')">TURN OFF AUTO RENEW</button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Highlights Section -->
            <div class="ths-pdp__section">
                <h2 class="ths-pdp__section-title">HIGHLIGHTS</h2>
                <div class="ths-pdp__highlights">
                    <div class="ths-pdp__highlight-item">
                        <div class="ths-pdp__highlight-icon">
                            <img src="" alt="" width="48" height="48">
                        </div>
                        <span>Ingredient 1 +</span>
                    </div>
                    <div class="ths-pdp__highlight-item">
                        <div class="ths-pdp__highlight-icon">
                            <img src="" alt="" width="48" height="48">
                        </div>
                        <span>Ingredient 2 +</span>
                    </div>
                    <div class="ths-pdp__highlight-item">
                        <div class="ths-pdp__highlight-icon">
                            <img src="" alt="" width="48" height="48">
                        </div>
                        <span>Ingredient 3 +</span>
                    </div>
                    <div class="ths-pdp__highlight-item">
                        <div class="ths-pdp__highlight-icon">
                            <img src="" alt="" width="48" height="48">
                        </div>
                        <span>Ingredient 4 +</span>
                    </div>
                    <div class="ths-pdp__highlight-item">
                        <div class="ths-pdp__highlight-icon">
                            <img src="" alt="" width="48" height="48">
                        </div>
                        <span>Ingredient 5 +</span>
                    </div>
                    <div class="ths-pdp__highlight-item">
                        <div class="ths-pdp__highlight-icon">
                            <img src="" alt="" width="48" height="48">
                        </div>
                        <span>Ingredient 6 +</span>
                    </div>
                </div>
            </div>

            <!-- About the Product -->
            <div class="ths-pdp__section">
                <h2 class="ths-pdp__section-title">ABOUT THE PRODUCT</h2>
                <div class="ths-pdp__description">
                    <shopify-data query="product.descriptionHtml"></shopify-data>
                </div>
            </div>

            <!-- Accordions -->
            <div class="ths-pdp__accordions">
                <div class="ths-pdp__accordion">
                    <button class="ths-pdp__accordion-header" onclick="toggleAccordion(this)">
                        <span>INGREDIENTS</span>
                        <span class="ths-pdp__accordion-arrow">&#9660;</span>
                    </button>
                    <div class="ths-pdp__accordion-body">
                        <p>Ingredient details will appear here from product metafields.</p>
                    </div>
                </div>
                <div class="ths-pdp__accordion">
                    <button class="ths-pdp__accordion-header" onclick="toggleAccordion(this)">
                        <span>HOW TO USE</span>
                        <span class="ths-pdp__accordion-arrow">&#9660;</span>
                    </button>
                    <div class="ths-pdp__accordion-body">
                        <p>Usage instructions will appear here from product metafields.</p>
                    </div>
                </div>
            </div>

            <!-- Similar Products -->
            <div class="ths-pdp__section" id="ths-pdp-similar">
                <h2 class="ths-pdp__section-title">SIMILAR PRODUCTS</h2>
                <div class="ths-pdp__carousel-wrap">
                    <button class="ths-pdp__carousel-arrow ths-pdp__carousel-arrow--left" data-carousel="similar" onclick="carouselScroll('similar', -1)" aria-label="Previous">&#8249;</button>
                    <div class="ths-pdp__carousel" id="pdp-carousel-similar"></div>
                    <button class="ths-pdp__carousel-arrow ths-pdp__carousel-arrow--right" data-carousel="similar" onclick="carouselScroll('similar', 1)" aria-label="Next">&#8250;</button>
                </div>
            </div>

            <!-- You May Also Like -->
            <div class="ths-pdp__section" id="ths-pdp-alslike">
                <h2 class="ths-pdp__section-title">YOU MAY ALSO LIKE</h2>
                <div class="ths-pdp__carousel-wrap">
                    <button class="ths-pdp__carousel-arrow ths-pdp__carousel-arrow--left" data-carousel="alsolike" onclick="carouselScroll('alsolike', -1)" aria-label="Previous">&#8249;</button>
                    <div class="ths-pdp__carousel" id="pdp-carousel-alsolike"></div>
                    <button class="ths-pdp__carousel-arrow ths-pdp__carousel-arrow--right" data-carousel="alsolike" onclick="carouselScroll('alsolike', 1)" aria-label="Next">&#8250;</button>
                </div>
            </div>

            <!-- Questions & Answers -->
            <div class="ths-pdp__section" id="ths-pdp-qna">
                <div class="ths-pdp__qna-header">
                    <h2 class="ths-pdp__section-title">QUESTIONS AND ANSWERS</h2>
                </div>

                <h3 class="ths-pdp__qna-subtitle">Most Recent Questions</h3>

                <!-- Q&A List -->
                <div class="ths-pdp__qna-list">
                    <div class="ths-pdp__qna-item">
                        <div class="ths-pdp__qna-q-row">
                            <span class="ths-pdp__qna-q-label">Q:</span>
                            <span class="ths-pdp__qna-q-text">What are the key ingredients in this product? All ingredients are sustainably sourced and free from parabens and sulfates.</span>
                        </div>
                        <div class="ths-pdp__qna-a-row">
                            <span class="ths-pdp__qna-a-label">A:</span>
                            <span class="ths-pdp__qna-a-text">This product features a blend of natural botanicals including argan oil, tea tree extract, and vitamin E. All ingredients are sustainably sourced and free from parabens and sulfates.</span>
                        </div>
                        <a href="#" class="ths-pdp__qna-answer-link" onclick="event.preventDefault();">Answer this question</a>
                    </div>

                    <div class="ths-pdp__qna-item">
                        <div class="ths-pdp__qna-q-row">
                            <span class="ths-pdp__qna-q-label">Q:</span>
                            <span class="ths-pdp__qna-q-text">Is this suitable for sensitive skin or scalp? We recommend doing a small patch test before first use.</span>
                        </div>
                        <div class="ths-pdp__qna-a-row">
                            <span class="ths-pdp__qna-a-label">A:</span>
                            <span class="ths-pdp__qna-a-text">Absolutely! Our formula is dermatologist-tested and specifically designed for sensitive skin.</span>
                        </div>
                        <a href="#" class="ths-pdp__qna-answer-link" onclick="event.preventDefault();">Answer this question</a>
                    </div>

                    <div class="ths-pdp__qna-item">
                        <div class="ths-pdp__qna-q-row">
                            <span class="ths-pdp__qna-q-label">Q:</span>
                            <span class="ths-pdp__qna-q-text">How long does one unit typically last with daily use?</span>
                        </div>
                        <div class="ths-pdp__qna-a-row">
                            <span class="ths-pdp__qna-a-label">A:</span>
                            <span class="ths-pdp__qna-a-text">With daily use, one unit typically lasts 6-8 weeks. We recommend our Auto-Replenish option to save 5% and ensure you never run out!</span>
                        </div>
                        <a href="#" class="ths-pdp__qna-answer-link" onclick="event.preventDefault();">Answer this question</a>
                    </div>
                </div>

                <div class="ths-pdp__qna-show-more-wrap">
                    <button class="ths-pdp__qna-show-more">Show more Questions &amp; Answers</button>
                </div>
            </div>

            <!-- Reviews -->
            <!-- Reviews (populated by Judge.me) -->
            <div class="ths-pdp__section" id="ths-pdp-reviews">
                <div class="ths-pdp__reviews-header">
                    <h2 class="ths-pdp__section-title">Customer Reviews</h2>
                    <button class="ths-pdp__reviews-write-btn" id="pdp-write-review-btn">Write a Review</button>
                </div>

                <!-- Summary: avg rating, bars (populated by JS) -->
                <div class="ths-pdp__reviews-summary" id="pdp-reviews-summary" style="display:none;"></div>

                <!-- Review Cards (populated by JS) -->
                <div class="ths-pdp__reviews-list" id="pdp-reviews-list"></div>

                <!-- No reviews state -->
                <div id="pdp-no-reviews" style="display:none;text-align:center;padding:40px 20px;">
                    <p style="font-size:16px;color:#888;margin:0 0 8px;">No reviews yet</p>
                    <p style="font-size:14px;color:#aaa;margin:0;">Be the first to share your experience with this product.</p>
                </div>

                <!-- Show More -->
                <div class="ths-pdp__reviews-show-more-wrap" id="pdp-reviews-more-wrap" style="display:none;">
                    <button class="ths-pdp__reviews-show-more" id="pdp-reviews-more-btn">Show more Reviews</button>
                </div>

                <!-- Write Review Form (hidden by default) -->
                <div id="pdp-review-form" style="display:none;" class="ths-pdp__review-form-wrap"></div>
            </div>

            </div>

        </template>
    </shopify-context>
</div>

<style>
/* ============================================
   THE HEAD SPA - PDP STYLES
   ============================================ */

.ths-pdp {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px 60px;
    font-family: 'Raleway', sans-serif !important;
    color: #1a1a1a;
}
.ths-pdp * {
    font-family: 'Raleway', sans-serif !important;
}

/* Global heading reset inside PDP - only reset unstyled headings */
.ths-pdp h1,
.ths-pdp h2,
.ths-pdp h3,
.ths-pdp h4,
.ths-pdp h5,
.ths-pdp h6 {
    margin: 0 !important;
    padding: 0 !important;
    line-height: 1.4 !important;
    color: #1a1a1a !important;
}
.ths-pdp h2.ths-pdp__section-title {
    margin: 0 0 36px 0 !important;
}

/* Hide empty thumbnail container */
.ths-pdp__gallery-thumbs:empty {
    display: none;
}

/* Breadcrumbs */
.ths-pdp__breadcrumbs {
    padding: 16px 0;
    font-size: 13px;
    color: #777;
}
.ths-pdp__breadcrumbs a {
    color: #777;
    text-decoration: none;
}
.ths-pdp__breadcrumbs a:hover {
    color: #1a1a1a;
}
.ths-pdp__breadcrumbs-sep {
    margin: 0 8px;
}
.ths-pdp__breadcrumbs-current {
    color: #1a1a1a;
}

/* Main Layout */
.ths-pdp__main {
    display: flex;
    gap: 48px;
    margin-bottom: 48px;
    align-items: flex-start;
}

/* Gallery */
.ths-pdp__gallery {
    flex: 0 0 50%;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}
.ths-pdp__gallery-thumbs {
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 72px;
    flex-shrink: 0;
    max-height: 100%;
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: none;
}
.ths-pdp__gallery-thumbs::-webkit-scrollbar {
    display: none;
}
.ths-pdp__gallery-thumbs img {
    width: 72px;
    height: 72px;
    object-fit: cover;
    border: 2px solid #e0e0e0;
    border-radius: 4px;
    cursor: pointer;
    flex-shrink: 0;
    transition: border-color 0.2s, opacity 0.2s;
    background: #f5f5f5;
}
.ths-pdp__gallery-thumbs img:hover {
    border-color: #999;
}
.ths-pdp__gallery-thumbs img.active {
    border: 2px solid transparent;
    background:
        linear-gradient(#fff, #fff) padding-box,
        linear-gradient(135deg, #f7d94e, #e8a020, #d4881a) border-box;
}
.ths-pdp__gallery-main {
    flex: 1;
    background: #f5f5f5;
    border-radius: 4px;
    overflow: hidden;
    aspect-ratio: 1 / 1;
    position: relative;
    max-height: none;
    align-self: flex-start;
}
.ths-pdp__gallery-main shopify-media {
    display: block;
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
}
.ths-pdp__gallery-main shopify-media img,
.ths-pdp__gallery-main shopify-media unpic-img img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: contain;
}
.ths-pdp__main-img {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    object-fit: contain;
    z-index: 2;
}
.ths-pdp__main-img[src=""] {
    display: none !important;
}
/* Hide shopify-media when custom img is active */
.ths-pdp__gallery-main .ths-pdp__main-img[style*="block"] ~ #pdp-shopify-media {
    opacity: 0;
}

/* Details */
.ths-pdp__details {
    flex: 1;
}
/* Remove any theme-injected dividers/borders */
.ths-pdp__details hr,
.ths-pdp__details .elementor-divider {
    display: none !important;
}
.ths-pdp__details > .ths-pdp__price,
.ths-pdp__details > .ths-pdp__badge,
.ths-pdp__details > .ths-pdp__rating-row,
.ths-pdp__details > .ths-pdp__actions,
.ths-pdp__details > .ths-pdp__delivery,
.ths-pdp__details > .ths-pdp__autorenew,
.ths-pdp__details > .ths-pdp__variants,
.ths-pdp__details > .ths-pdp__shipping-options,
.ths-pdp__details shopify-money {
    border: none !important;
    border-top: none !important;
    border-bottom: none !important;
}
/* Restore borders we actually want */
.ths-pdp__shipping-option {
    border: 2px solid #e0e0e0 !important;
    border-radius: 4px !important;
}
.ths-pdp__shipping-option.active {
    border: 2px solid transparent !important;
    background:
        linear-gradient(#fff, #fff) padding-box,
        linear-gradient(135deg, #f7d94e, #e8a020, #d4881a) border-box !important;
}
.ths-pdp__quantity {
    border: 1px solid #e0e0e0 !important;
}
.ths-pdp__wishlist-btn {
    border: 1px solid #e0e0e0 !important;
}

/* Brand */
.ths-pdp__brand {
    font-size: 12px !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #1a1a1a !important;
    font-weight: 700 !important;
    margin: 0 0 4px !important;
    cursor: pointer;
    transition: color 0.2s;
}
.ths-pdp__brand:hover {
    color: #6BBAB2 !important;
}

/* Title */
.ths-pdp__title {
    font-size: 20px !important;
    font-weight: 400 !important;
    margin: 0 0 8px !important;
    color: #1a1a1a !important;
    line-height: 1.3 !important;
}

/* Rating Row */
.ths-pdp__rating-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 13px;
    flex-wrap: wrap;
}
.ths-pdp__stars {
    display: flex;
    gap: 2px;
}
.ths-pdp__star {
    color: #ddd;
    font-size: 16px;
}
.ths-pdp__star.filled {
    color: #1a1a1a;
}
.ths-pdp__rating-count {
    color: #555;
}
.ths-pdp__rating-sep {
    color: #ccc;
}
.ths-pdp__ask-link {
    color: #1a1a1a;
    text-decoration: underline;
}
.ths-pdp__favorite-btn {
    background: none !important;
    background-color: transparent !important;
    border: none !important;
    cursor: pointer;
    color: #e74c6f !important;
    font-size: 16px !important;
    padding: 0 !important;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 0 !important;
    min-width: auto !important;
    width: auto !important;
    height: auto !important;
    line-height: 1 !important;
    box-shadow: none !important;
}
.ths-pdp__fav-count {
    color: #555;
    font-size: 13px;
}
.ths-pdp__heart-icon {
    display: inline-block;
    transform: none !important;
    font-size: 16px !important;
    line-height: 1 !important;
}
.ths-pdp__heart-svg {
    display: block;
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

/* Badge */
.ths-pdp__badge {
    font-size: 12px;
    color: #777;
    margin: 0 0 12px;
}
.ths-pdp__badge a {
    color: #1a1a1a;
    text-decoration: underline;
}

/* Price */
.ths-pdp__price {
    font-size: 20px !important;
    font-weight: 700 !important;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.ths-pdp__compare-price {
    font-size: 16px;
    color: #999;
    text-decoration: line-through;
    font-weight: 400;
}

/* Variants - JS handles show/hide based on shadow DOM content */
.ths-pdp__variants {
    margin-bottom: 20px;
    border: none !important;
    box-shadow: none !important;
    outline: none !important;
    background: transparent !important;
}
.ths-pdp__variant-group {
    margin-bottom: 12px;
}
.ths-pdp__variant-group-label {
    font-size: 13px !important;
    font-weight: 600 !important;
    color: #1a1a1a !important;
    margin: 0 0 8px !important;
    display: block;
}
.ths-pdp__variant-group-label span {
    font-weight: 400 !important;
    color: #666 !important;
}
.ths-pdp__variant-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.ths-pdp__variant-btn {
    font-family: 'Raleway', sans-serif !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    padding: 8px 20px !important;
    border: 2px solid #e0e0e0 !important;
    border-radius: 4px !important;
    background: #fff !important;
    color: #1a1a1a !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
    outline: none !important;
    box-shadow: none !important;
    line-height: 1.4 !important;
    text-transform: none !important;
    letter-spacing: normal !important;
}
.ths-pdp__variant-btn:hover {
    border-color: #999 !important;
}
.ths-pdp__variant-btn.active {
    border: 2px solid transparent !important;
    background:
        linear-gradient(#fff, #fff) padding-box,
        linear-gradient(135deg, #f7d94e, #e8a020, #d4881a) border-box !important;
    font-weight: 600 !important;
}
.ths-pdp__variant-btn.disabled {
    opacity: 0.4 !important;
    cursor: not-allowed !important;
    border-color: #e0e0e0 !important;
    background: #f5f5f5 !important;
    text-decoration: line-through;
}
.ths-pdp__variants shopify-variant-selector {
    display: block;
    border: none !important;
    padding: 0 !important;
    margin: 0 !important;
    background: transparent !important;
}
.ths-pdp__variants-native {
    position: absolute;
    width: 0;
    height: 0;
    overflow: hidden;
    opacity: 0;
    pointer-events: none;
}
/* Native variant selector is hidden - custom swatches handle display */
shopify-variant-selector {
    border: none !important;
    box-shadow: none !important;
    outline: none !important;
}

/* Kill dividers on price area too */
.ths-pdp__price {
    border: none !important;
    box-shadow: none !important;
    outline: none !important;
}
.ths-pdp__price::before,
.ths-pdp__price::after {
    display: none !important;
    content: none !important;
}
shopify-money {
    border: none !important;
    box-shadow: none !important;
    outline: none !important;
}
shopify-money::before,
shopify-money::after {
    display: none !important;
    content: none !important;
}

/* Shipping Options */
.ths-pdp__shipping-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 8px;
    margin-bottom: 16px;
}
.ths-pdp__shipping-option[data-option="autoreplenish"] {
    display: none !important;
}
.ths-pdp__shipping-option[data-option="autoreplenish"].ths-sub-visible {
    display: flex !important;
}
.ths-pdp__shipping-option {
    border: 2px solid #e0e0e0 !important;
    border-radius: 4px !important;
    padding: 12px 8px !important;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 4px;
    background: #fff !important;
    min-height: 100px;
    position: relative;
}
.ths-pdp__shipping-option:hover {
    border-color: #ccc !important;
}
.ths-pdp__shipping-option.active {
    border: 2px solid transparent !important;
    background:
        linear-gradient(#fff, #fff) padding-box,
        linear-gradient(135deg, #f7d94e, #e8a020, #d4881a) border-box !important;
}
.ths-pdp__shipping-icon {
    color: #1a1a1a;
}
.ths-pdp__shipping-icon svg {
    width: 24px;
    height: 24px;
}
.ths-pdp__shipping-label {
    font-size: 11px;
    font-weight: 600;
    line-height: 1.3;
}
.ths-pdp__shipping-sub {
    font-size: 10px;
    color: #999;
}

/* Delivery */
.ths-pdp__delivery {
    background: rgba(107, 186, 178, 0.08);
    border-radius: 4px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 13px;
    line-height: 1.5;
}
.ths-pdp__delivery-text {
    margin: 0 0 4px;
}
.ths-pdp__delivery-bold {
    font-weight: 600 !important;
}
.ths-pdp__delivery-link {
    color: #1a1a1a;
    text-decoration: underline;
    font-size: 12px;
}

/* Quantity + Add to Cart */
.ths-pdp__actions {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}
.ths-pdp__quantity {
    display: flex;
    align-items: center;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}
.ths-pdp__qty-btn {
    width: 40px;
    height: 44px;
    background: #fff !important;
    border: none !important;
    font-size: 18px !important;
    cursor: pointer;
    color: #1a1a1a !important;
    transition: background 0.2s;
    padding: 0 !important;
    border-radius: 0 !important;
}
.ths-pdp__qty-btn:hover {
    background: #f5f5f5 !important;
}
.ths-pdp__qty-value {
    width: 40px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 600;
}
.ths-pdp__add-to-cart {
    flex: 1;
    height: 44px;
    background: transparent !important;
    color: #1a1a1a !important;
    border: 2px solid #1a1a1a !important;
    border-radius: 300px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.2s;
}
.ths-pdp__add-to-cart:hover {
    background: #1a1a1a !important;
    color: #fff !important;
}
.ths-pdp__add-to-cart[disabled] {
    background: transparent !important;
    border-color: #ccc !important;
    color: #ccc !important;
    cursor: not-allowed;
}
.ths-pdp__wishlist-btn {
    width: 44px;
    height: 44px;
    border: 1px solid #e0e0e0 !important;
    border-radius: 4px !important;
    background: #fff !important;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 !important;
    flex-shrink: 0;
}
.ths-pdp__wishlist-btn svg {
    width: 20px;
    height: 20px;
    transition: all 0.2s;
}
.ths-pdp__wishlist-btn:hover svg {
    stroke: #e74c6f;
}
.ths-pdp__wishlist-btn.active svg {
    fill: #e74c6f;
    stroke: #e74c6f;
}
.ths-pdp__wishlist-btn.active:hover svg {
    fill: #c43a5a;
    stroke: #c43a5a;
}

/* Auto Renew Banner */
.ths-pdp__autorenew {
    background: #6BBAB2;
    border-radius: 4px;
    padding: 20px;
    color: #fff;
}
.ths-pdp__autorenew-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.ths-pdp__autorenew-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.ths-pdp__autorenew-title {
    font-weight: 800 !important;
    font-size: 16px !important;
    font-style: normal;
    margin: 0 !important;
    color: #fff !important;
}
.ths-pdp__autorenew-sub {
    font-size: 13px !important;
    margin: 0 !important;
    color: rgba(255,255,255,0.9) !important;
}
.ths-pdp__autorenew-sub a {
    color: #fff !important;
    text-decoration: underline;
}
.ths-pdp__autorenew-badge {
    background: #e74c3c;
    color: #fff;
    font-size: 13px !important;
    font-weight: 700 !important;
    padding: 6px 14px;
    border-radius: 4px;
    white-space: nowrap;
    flex-shrink: 0;
}

/* Sliding Toggle */
.ths-pdp__autorenew-toggle {
    display: flex;
    position: relative;
    background: rgba(0,0,0,0.2);
    border-radius: 300px;
    padding: 4px;
    overflow: hidden;
}
.ths-pdp__autorenew-slider {
    position: absolute;
    top: 4px;
    left: 4px;
    width: calc(50% - 4px);
    height: calc(100% - 8px);
    background: #fff;
    border-radius: 300px;
    transition: transform 0.3s ease;
    z-index: 1;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}
.ths-pdp__autorenew-toggle.off .ths-pdp__autorenew-slider {
    transform: translateX(100%);
}
.ths-pdp__autorenew-opt {
    flex: 1;
    position: relative;
    z-index: 2;
    padding: 10px 12px !important;
    background: transparent !important;
    border: none !important;
    border-radius: 300px !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    cursor: pointer;
    text-transform: uppercase;
    transition: color 0.3s ease;
    text-align: center;
    white-space: nowrap;
    color: #fff !important;
}
.ths-pdp__autorenew-opt[data-value="on"] {
    color: #1a1a1a !important;
}
.ths-pdp__autorenew-toggle.off .ths-pdp__autorenew-opt[data-value="on"] {
    color: #fff !important;
}
.ths-pdp__autorenew-toggle.off .ths-pdp__autorenew-opt[data-value="off"] {
    color: #1a1a1a !important;
}

/* Selling plan options */
.ths-pdp__autorenew-plans {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}
.ths-pdp__plan-option {
    padding: 8px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 20px;
    background: #fff;
    font-size: 12px;
    font-weight: 600;
    color: #555;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}
.ths-pdp__plan-option:hover {
    border-color: #999;
}
.ths-pdp__plan-option.active {
    border-color: #439E9E;
    background: #f0fafa;
    color: #2d7a7a;
}

/* Sections */
.ths-pdp__section {
    padding: 32px 0;
    border-top: 1px solid #e8e8e8;
}
.ths-pdp__section-title {
    font-size: 24px !important;
    font-weight: 800 !important;
    margin: 0 0 36px !important;
    padding: 0 0 0 0 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.4 !important;
    color: #1a1a1a !important;
}
.ths-pdp .ths-pdp__section h2.ths-pdp__section-title {
    margin-bottom: 36px !important;
}

/* Highlights */
.ths-pdp__highlights {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    justify-items: center;
    text-align: center;
}
.ths-pdp__highlight-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    font-size: 14px;
}
.ths-pdp__highlight-icon {
    width: 48px;
    height: 48px;
    background: #f5f5f5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.ths-pdp__highlight-icon img {
    border-radius: 50%;
    width: 48px;
    height: 48px;
    object-fit: cover;
}

/* Description */
.ths-pdp__description {
    font-size: 14px !important;
    line-height: 1.7 !important;
    color: #444 !important;
}
.ths-pdp__description p {
    font-size: 14px !important;
    line-height: 1.7 !important;
    margin: 0 0 12px !important;
}

/* Override theme paragraph styles inside PDP */
.ths-pdp p {
    font-size: 14px;
    line-height: 1.6;
}
.ths-pdp a {
    color: inherit;
}

/* Accordions */
.ths-pdp__accordions {
    border-top: 1px solid #e8e8e8;
}
.ths-pdp__accordion {
    border-bottom: 1px solid #e8e8e8;
}
.ths-pdp__accordion-header {
    width: 100%;
    background: none !important;
    background-color: transparent !important;
    border: none !important;
    padding: 18px 0 !important;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    font-size: 24px !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #1a1a1a !important;
    border-radius: 0 !important;
    margin: 0 !important;
    text-align: left !important;
}
.ths-pdp__accordion-header > span:first-child {
    flex: 1;
    text-align: left !important;
}
.ths-pdp__accordion-arrow {
    font-size: 12px !important;
    line-height: 1 !important;
    transition: transform 0.3s;
    flex-shrink: 0;
    display: inline-block !important;
}
.ths-pdp__accordion-arrow svg {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
}
.ths-pdp__accordion.open .ths-pdp__accordion-arrow {
    transform: rotate(180deg);
}
.ths-pdp__accordion-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}
.ths-pdp__accordion.open .ths-pdp__accordion-body {
    max-height: 500px;
}
.ths-pdp__accordion-body p {
    padding: 0 0 18px;
    margin: 0;
    font-size: 14px;
    line-height: 1.6;
    color: #555;
}

/* Placeholder for Phase 2 sections */
.ths-pdp__placeholder {
    color: #999;
    font-style: italic;
    font-size: 14px;
}

/* ============================================
   PRODUCT CAROUSEL
   ============================================ */
.ths-pdp__carousel-wrap {
    position: relative;
    overflow: hidden;
}
.ths-pdp__carousel {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding: 4px 0 12px;
}
.ths-pdp__carousel::-webkit-scrollbar {
    display: none;
}
.ths-pdp__carousel-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 5;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid #e0e0e0 !important;
    background: rgba(255,255,255,0.95) !important;
    color: #1a1a1a !important;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 0 !important;
}
.ths-pdp__carousel-arrow:hover {
    background: #fff !important;
    border-color: #999 !important;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
}
.ths-pdp__carousel-arrow--left {
    left: 0;
}
.ths-pdp__carousel-arrow--right {
    right: 0;
}
.ths-pdp__carousel-arrow[disabled] {
    opacity: 0;
    pointer-events: none;
}

/* Product Card */
.ths-pdp__product-card {
    flex: 0 0 220px;
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    text-decoration: none;
    color: #1a1a1a;
    transition: box-shadow 0.2s, transform 0.2s;
    cursor: pointer;
    border: 1px solid #e8e8e8;
}
.ths-pdp__product-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.ths-pdp__product-card-img {
    width: calc(100% - 16px);
    margin: 8px auto 0;
    aspect-ratio: 1/1;
    overflow: hidden;
    background: #f0f0f0;
    position: relative;
    border-radius: 6px;
}
.ths-pdp__product-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}
.ths-pdp__product-card:hover .ths-pdp__product-card-img img {
    transform: scale(1.05);
}
/* Buy Now hover overlay */
.ths-pdp__product-card-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.65);
    color: #fff;
    text-align: center;
    padding: 10px;
    font-family: 'Raleway', sans-serif;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    opacity: 0;
    transform: translateY(100%);
    transition: opacity 0.3s, transform 0.3s;
    z-index: 3;
    border-radius: 0 0 6px 6px;
}
.ths-pdp__product-card:hover .ths-pdp__product-card-overlay {
    opacity: 1;
    transform: translateY(0);
}
/* Heart / Wishlist icon */
.ths-pdp__product-card-heart {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 2;
    background: none !important;
    border: none !important;
    padding: 0 !important;
}
.ths-pdp__product-card-heart svg {
    width: 22px;
    height: 22px;
    stroke: #999;
    stroke-width: 2;
    fill: none;
    transition: all 0.2s;
}
.ths-pdp__product-card-heart:hover svg {
    stroke: #e74c6f;
}
.ths-pdp__product-card-heart.active svg {
    fill: #e74c6f;
    stroke: #e74c6f;
}
/* Card info section */
.ths-pdp__product-card-info {
    padding: 14px 14px 4px;
    display: flex;
    flex-direction: column;
    gap: 3px;
    flex: 1;
}
.ths-pdp__product-card-vendor {
    font-family: 'Raleway', sans-serif;
    font-size: 10px !important;
    font-weight: 500 !important;
    color: #999 !important;
    margin: 0 0 2px !important;
    line-height: 1.3 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.ths-pdp__product-card-title {
    font-family: 'Raleway', sans-serif;
    font-size: 16px !important;
    font-weight: 600 !important;
    color: #1a1a1a;
    line-height: 1.35 !important;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin: 0 !important;
}
.ths-pdp__product-card-price {
    font-family: 'Raleway', sans-serif;
    font-size: 16px !important;
    font-weight: 600 !important;
    color: #1a1a1a;
    margin: 8px 0 0 !important;
    line-height: 1.3 !important;
}
.ths-pdp__product-card-price .compare-price {
    font-weight: 400;
    font-size: 12px;
    color: #999;
    text-decoration: line-through;
    margin-left: 6px;
}
/* Bottom row: stars + quick-add */
.ths-pdp__product-card-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 14px 14px;
    margin-top: auto;
}
.ths-pdp__product-card-stars {
    display: flex;
    align-items: center;
    gap: 1px;
    font-size: 14px;
    color: #1a1a1a;
    letter-spacing: 1px;
    line-height: 1;
}
.ths-pdp__product-card-stars .star-empty {
    color: #1a1a1a;
    opacity: 0.3;
}
.ths-pdp__product-card-add {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 2px solid #1a1a1a !important;
    background: transparent !important;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    padding: 0 !important;
    flex-shrink: 0;
}
.ths-pdp__product-card-add svg {
    width: 14px;
    height: 14px;
    stroke: #1a1a1a;
    stroke-width: 2;
    fill: none;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.ths-pdp__product-card-add:hover {
    background: #1a1a1a !important;
}
.ths-pdp__product-card-add:hover svg {
    stroke: #fff;
}

/* ============================================
   QUESTIONS & ANSWERS
   ============================================ */
.ths-pdp__qna-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}
.ths-pdp__qna-header .ths-pdp__section-title {
    margin-bottom: 0 !important;
}
/* Subtitle */
.ths-pdp__qna-subtitle {
    font-size: 16px !important;
    font-weight: 700 !important;
    color: #1a1a1a !important;
    margin: 0 0 20px !important;
}
/* Q&A List - clean, no backgrounds */
.ths-pdp__qna-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.ths-pdp__qna-item {
    padding: 20px 0;
    border-bottom: 1px solid #e8e8e8;
}
.ths-pdp__qna-item:first-child {
    padding-top: 0;
}
/* Question row */
.ths-pdp__qna-q-row {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
}
.ths-pdp__qna-q-label {
    font-size: 16px !important;
    font-weight: 700 !important;
    color: #1a1a1a !important;
    flex-shrink: 0;
    line-height: 1.5 !important;
}
.ths-pdp__qna-q-text {
    font-size: 16px !important;
    font-weight: 400 !important;
    color: #1a1a1a !important;
    line-height: 1.5 !important;
}
/* Answer row */
.ths-pdp__qna-a-row {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}
.ths-pdp__qna-a-label {
    font-size: 16px !important;
    font-weight: 700 !important;
    color: #1a1a1a !important;
    flex-shrink: 0;
    line-height: 1.5 !important;
}
.ths-pdp__qna-a-text {
    font-size: 16px !important;
    font-weight: 400 !important;
    color: #1a1a1a !important;
    line-height: 1.5 !important;
}
/* Answer this question link */
.ths-pdp__qna-answer-link {
    font-size: 13px !important;
    color: #1a1a1a !important;
    text-decoration: underline !important;
    font-weight: 500 !important;
    margin-left: 26px;
    display: inline-block;
}
.ths-pdp__qna-answer-link:hover {
    color: #6BBAB2 !important;
}
/* Show more button */
.ths-pdp__qna-show-more-wrap {
    margin-top: 24px;
}
.ths-pdp__qna-show-more {
    font-family: 'Raleway', sans-serif;
    font-size: 14px !important;
    font-weight: 700 !important;
    padding: 12px 28px;
    background: #6BBAB2;
    color: #fff;
    border: none !important;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
    letter-spacing: 0.3px;
}
.ths-pdp__qna-show-more:hover {
    background: #5aa9a1;
}

/* ============================================
   RATINGS & REVIEWS
   ============================================ */
.ths-pdp__reviews-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}
.ths-pdp__reviews-header .ths-pdp__section-title {
    margin-bottom: 0 !important;
}
.ths-pdp__reviews-write-btn {
    font-family: 'Raleway', sans-serif;
    font-size: 14px !important;
    font-weight: 700 !important;
    padding: 10px 24px;
    background: #6BBAB2;
    color: #fff;
    border: none !important;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
    letter-spacing: 0.3px;
    white-space: nowrap;
}
.ths-pdp__reviews-write-btn:hover {
    background: #5aa9a1;
}
/* Summary */
.ths-pdp__reviews-summary {
    display: flex;
    gap: 48px;
    align-items: flex-start;
    margin-bottom: 32px;
    padding-bottom: 32px;
    border-bottom: 1px solid #e8e8e8;
}
.ths-pdp__reviews-overall {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
    min-width: 120px;
}
.ths-pdp__reviews-avg {
    font-size: 48px !important;
    font-weight: 800 !important;
    color: #1a1a1a;
    line-height: 1 !important;
}
.ths-pdp__reviews-overall-stars {
    display: flex;
    gap: 2px;
    font-size: 18px;
}
.ths-pdp__reviews-overall-stars .ths-pdp__star.filled {
    color: #1a1a1a;
}
.ths-pdp__reviews-overall-stars .ths-pdp__star {
    color: #ddd;
}
.ths-pdp__reviews-total {
    font-size: 13px !important;
    color: #999;
    margin-top: 4px;
}
/* Breakdown bars */
.ths-pdp__reviews-bars {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.ths-pdp__reviews-bar-row {
    display: flex;
    align-items: center;
    gap: 10px;
}
.ths-pdp__reviews-bar-label {
    font-size: 13px !important;
    font-weight: 600 !important;
    color: #1a1a1a;
    min-width: 36px;
    text-align: right;
}
.ths-pdp__reviews-bar-track {
    flex: 1;
    height: 10px;
    background: #f0f0f0;
    border-radius: 5px;
    overflow: hidden;
}
.ths-pdp__reviews-bar-fill {
    height: 100%;
    background: #1a1a1a;
    border-radius: 5px;
    transition: width 0.3s ease;
}
.ths-pdp__reviews-bar-count {
    font-size: 13px !important;
    color: #999;
    min-width: 28px;
}
/* Review Cards */
.ths-pdp__reviews-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.ths-pdp__review-card {
    padding: 24px 0;
    border-bottom: 1px solid #e8e8e8;
}
.ths-pdp__review-card:first-child {
    padding-top: 0;
}
.ths-pdp__review-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.ths-pdp__review-user {
    display: flex;
    align-items: center;
    gap: 10px;
}
.ths-pdp__review-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #6BBAB2;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px !important;
    font-weight: 700 !important;
    flex-shrink: 0;
}
.ths-pdp__review-user-info {
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.ths-pdp__review-username {
    font-size: 14px !important;
    font-weight: 700 !important;
    color: #1a1a1a;
}
.ths-pdp__review-verified {
    font-size: 11px !important;
    color: #6BBAB2;
    font-weight: 600 !important;
}
.ths-pdp__review-date {
    font-size: 13px !important;
    color: #999;
}
.ths-pdp__review-stars {
    display: flex;
    gap: 2px;
    font-size: 14px;
    margin-bottom: 8px;
}
.ths-pdp__review-stars .ths-pdp__star.filled {
    color: #1a1a1a;
}
.ths-pdp__review-stars .ths-pdp__star {
    color: #ddd;
}
.ths-pdp__review-title {
    font-size: 15px !important;
    font-weight: 700 !important;
    color: #1a1a1a !important;
    margin: 0 0 6px !important;
}
.ths-pdp__review-body {
    font-size: 14px !important;
    font-weight: 400 !important;
    color: #444 !important;
    line-height: 1.6 !important;
    margin: 0 !important;
}
/* Review photos */
.ths-pdp__review-photo-wrap {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}
.ths-pdp__review-photo {
    width: 72px;
    height: 72px;
    border-radius: 6px;
    overflow: hidden;
    background: #f0f0f0;
}
.ths-pdp__review-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
/* Show more */
.ths-pdp__reviews-show-more-wrap {
    margin-top: 24px;
}
.ths-pdp__reviews-show-more {
    font-family: 'Raleway', sans-serif;
    font-size: 14px !important;
    font-weight: 700 !important;
    padding: 12px 28px;
    background: #6BBAB2;
    color: #fff;
    border: none !important;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
    letter-spacing: 0.3px;
}
.ths-pdp__reviews-show-more:hover {
    background: #5aa9a1;
}

/* Review Form */
.ths-pdp__review-form-wrap {
    margin-top: 24px;
}
.ths-pdp__review-form {
    background: #f9f9f9;
    border: 1px solid #e8e8e8;
    border-radius: 10px;
    padding: 28px;
    max-width: 600px;
}
.ths-pdp__rf-field {
    margin-bottom: 16px;
}
.ths-pdp__rf-field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #444;
    margin-bottom: 6px;
}
.ths-pdp__rf-field input,
.ths-pdp__rf-field textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #ddd;
    border-radius: 8px;
    font-size: 15px;
    font-family: 'Raleway', sans-serif;
    color: #333;
    background: #fff;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.2s;
}
.ths-pdp__rf-field input:focus,
.ths-pdp__rf-field textarea:focus {
    border-color: #439E9E;
    box-shadow: 0 0 0 3px rgba(67, 158, 158, 0.1);
}
.ths-pdp__rf-stars {
    display: flex;
    gap: 4px;
}
.ths-pdp__rf-star {
    font-size: 28px;
    color: #ccc;
    cursor: pointer;
    transition: color 0.15s;
    user-select: none;
}
.ths-pdp__rf-star:hover {
    transform: scale(1.1);
}

/* Half star support */
.ths-pdp__star.half {
    background: linear-gradient(90deg, #1a1a1a 50%, #ccc 50%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 768px) {
    .ths-pdp__main {
        flex-direction: column;
        gap: 24px;
    }
    .ths-pdp__gallery {
        flex: none;
        flex-direction: column-reverse;
    }
    .ths-pdp__gallery-main {
        width: 100%;
        flex: none;
    }
    .ths-pdp__gallery-thumbs {
        flex-direction: row;
        width: auto;
        max-height: none;
        overflow-x: auto;
        overflow-y: hidden;
    }
    .ths-pdp__gallery-thumbs img {
        width: 56px;
        height: 56px;
    }
    .ths-pdp__shipping-options {
        grid-template-columns: repeat(2, 1fr);
    }
    .ths-pdp__highlights {
        grid-template-columns: repeat(2, 1fr);
    }
    .ths-pdp__autorenew-top {
        flex-wrap: wrap;
        gap: 8px;
    }
    .ths-pdp__autorenew-opt {
        font-size: 10px !important;
        padding: 8px 6px !important;
    }
    .ths-pdp__product-card {
        flex: 0 0 160px;
    }
    .ths-pdp__carousel-arrow {
        width: 30px;
        height: 30px;
        font-size: 18px;
    }
    .ths-pdp__reviews-summary {
        flex-direction: column;
        gap: 24px;
    }
    .ths-pdp__reviews-overall {
        flex-direction: row;
        gap: 12px;
    }
    .ths-pdp__reviews-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    .ths-pdp__qna-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    .ths-pdp__qna-answer {
        padding-left: 0;
    }
}

/* FINAL OVERRIDES - must be last to win specificity wars with Shopify plugin */
.ths-pdp .ths-pdp__details .ths-pdp__price,
.ths-pdp .ths-pdp__details .ths-pdp__badge,
.ths-pdp .ths-pdp__details .ths-pdp__rating-row,
.ths-pdp .ths-pdp__details .ths-pdp__autorenew,
.ths-pdp .ths-pdp__details shopify-variant-selector {
    border-top: none !important;
    border-bottom: none !important;
    box-shadow: none !important;
    outline: none !important;
}
</style>

<script>
/* PDP Quantity Controls */
function pdpIncreaseQty() {
    var el = document.getElementById('pdp-qty');
    var val = parseInt(el.textContent) || 1;
    el.textContent = val + 1;
}
function pdpDecreaseQty() {
    var el = document.getElementById('pdp-qty');
    var val = parseInt(el.textContent) || 1;
    if (val > 1) el.textContent = val - 1;
}

/* Accordion Toggle */
function toggleAccordion(btn) {
    var accordion = btn.parentElement;
    accordion.classList.toggle('open');
}

/* Auto Renew Sliding Toggle */
/* ─── Auto-Renew / Selling Plans ─── */
var selectedSellingPlanId = null;

function setAutoRenew(value) {
    var toggle = document.getElementById('autorenew-toggle');
    if (value === 'off') {
        toggle.classList.add('off');
        selectedSellingPlanId = null;
        console.log('[THS Subscriptions] Auto-renew OFF');
    } else {
        toggle.classList.remove('off');
        /* Select the first plan if none selected */
        if (!selectedSellingPlanId && window.__thsSellingPlans && window.__thsSellingPlans.length > 0) {
            var firstActive = document.querySelector('.ths-pdp__plan-option.active');
            if (firstActive) {
                selectedSellingPlanId = firstActive.getAttribute('data-plan-id');
            } else {
                selectedSellingPlanId = window.__thsSellingPlans[0].id;
            }
        }
        console.log('[THS Subscriptions] Auto-renew ON, plan:', selectedSellingPlanId);
    }
}

function selectSellingPlan(el) {
    var planId = el.getAttribute('data-plan-id');
    selectedSellingPlanId = planId;
    /* Update active state */
    var allPlans = document.querySelectorAll('.ths-pdp__plan-option');
    allPlans.forEach(function(p) { p.classList.remove('active'); });
    el.classList.add('active');
    /* Update badge with this plan's discount */
    var plans = window.__thsSellingPlans || [];
    for (var i = 0; i < plans.length; i++) {
        if (plans[i].id === planId && plans[i].discount > 0) {
            var badge = document.getElementById('autorenew-badge');
            if (badge) badge.textContent = plans[i].discount + '% SAVINGS';
            break;
        }
    }
    /* Make sure toggle is ON */
    var toggle = document.getElementById('autorenew-toggle');
    if (toggle) toggle.classList.remove('off');
    console.log('[THS Subscriptions] Selected plan:', planId);
}

/* Shipping Option Selection */
function selectShippingOption(el) {
    var allOptions = document.querySelectorAll('.ths-pdp__shipping-option');
    allOptions.forEach(function(o) { o.classList.remove('active'); });
    el.classList.add('active');
}

/* Shipping Option Toggle - using event delegation for Shopify compatibility */
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        var option = e.target.closest('.ths-pdp__shipping-option');
        if (option) {
            var allOptions = document.querySelectorAll('.ths-pdp__shipping-option');
            allOptions.forEach(function(o) { o.classList.remove('active'); });
            option.classList.add('active');
        }
    });

    /* ============================
       IMAGE GALLERY - Phase 2
       ============================ */
    var shopDomain = '<?php echo esc_js($ths_shop_domain); ?>';
    var storefrontToken = '<?php echo esc_js($ths_storefront_token); ?>';
    var apiKey = '<?php echo esc_js($ths_api_key); ?>';
    var productHandle = '<?php echo esc_js($product_handle); ?>';
    var mainImg = null;
    var shopifyMedia = null;
    var thumbsContainer = null;
    var galleryImages = [];
    var currentIndex = 0;

    function findGalleryElements() {
        /* Try regular DOM first */
        mainImg = document.getElementById('pdp-main-img') || document.querySelector('.ths-pdp__main-img');
        shopifyMedia = document.getElementById('pdp-shopify-media') || document.querySelector('.ths-pdp__gallery-main shopify-media');
        thumbsContainer = document.getElementById('pdp-thumbs') || document.querySelector('.ths-pdp__gallery-thumbs');
        
        /* If not found, check inside shopify-context shadow DOM */
        if (!thumbsContainer) {
            var ctx = document.querySelector('shopify-context');
            if (ctx && ctx.shadowRoot) {
                mainImg = mainImg || ctx.shadowRoot.getElementById('pdp-main-img') || ctx.shadowRoot.querySelector('.ths-pdp__main-img');
                shopifyMedia = shopifyMedia || ctx.shadowRoot.getElementById('pdp-shopify-media') || ctx.shadowRoot.querySelector('.ths-pdp__gallery-main shopify-media');
                thumbsContainer = thumbsContainer || ctx.shadowRoot.getElementById('pdp-thumbs') || ctx.shadowRoot.querySelector('.ths-pdp__gallery-thumbs');
            }
            /* Also try querying inside .shopify-product-content */
            if (!thumbsContainer) {
                var productContent = document.querySelector('.shopify-product-content, .shopify-product-details, [class*="shopify-product"]');
                if (productContent) {
                    mainImg = mainImg || productContent.querySelector('#pdp-main-img, .ths-pdp__main-img');
                    shopifyMedia = shopifyMedia || productContent.querySelector('#pdp-shopify-media, .ths-pdp__gallery-main shopify-media');
                    thumbsContainer = thumbsContainer || productContent.querySelector('#pdp-thumbs, .ths-pdp__gallery-thumbs');
                }
            }
        }
        
        console.log('[THS Gallery] Elements found - mainImg:', !!mainImg, '| shopifyMedia:', !!shopifyMedia, '| thumbs:', !!thumbsContainer);
    }

    console.log('[THS Gallery] Domain:', shopDomain, '| Handle:', productHandle, '| Token:', storefrontToken ? storefrontToken.substring(0,6) + '...' : 'NONE', '| API Key:', apiKey ? apiKey.substring(0,6) + '...' : 'NONE');

    var buildRetries = 0;
    function buildThumbnails(images) {
        if (!images || images.length === 0) return;
        
        /* Re-find elements in case Shopify re-rendered */
        findGalleryElements();
        
        if (!thumbsContainer) {
            buildRetries++;
            if (buildRetries < 10) {
                console.warn('[THS Gallery] thumbsContainer not found, retry ' + buildRetries + '/10...');
                setTimeout(function() { buildThumbnails(images); }, 500);
            } else {
                console.error('[THS Gallery] Gave up finding thumbsContainer after 10 retries');
            }
            return;
        }
        
        galleryImages = images;
        thumbsContainer.innerHTML = '';

        /* Single image — let shopify-media handle it natively (Safari fix) */
        if (images.length <= 1) {
            if (mainImg) mainImg.style.display = 'none';
            if (shopifyMedia) shopifyMedia.style.display = '';
            return;
        }

        /* Set first image as main */
        mainImg.src = images[0].url;
        mainImg.alt = images[0].alt || 'Product image';
        mainImg.style.display = 'block';
        if (shopifyMedia) shopifyMedia.style.display = 'none';

        images.forEach(function(img, idx) {
            var thumb = document.createElement('img');
            thumb.src = img.url.replace(/(\.\w+)(\?|$)/, '_72x72$1$2');
            /* Fallback if resized URL fails */
            thumb.onerror = function() { this.src = img.url; };
            thumb.alt = img.alt || 'Product thumbnail ' + (idx + 1);
            thumb.setAttribute('data-index', idx);
            thumb.setAttribute('data-full', img.url);
            if (idx === 0) thumb.classList.add('active');
            thumb.addEventListener('click', function() {
                selectGalleryImage(idx);
            });
            thumbsContainer.appendChild(thumb);
        });
    }

    function selectGalleryImage(idx) {
        currentIndex = idx;
        var img = galleryImages[idx];
        if (!img) return;
        if (!mainImg) findGalleryElements();
        if (!mainImg) return;
        mainImg.src = img.url;
        mainImg.alt = img.alt || 'Product image';

        /* Update active thumbnail */
        var thumbs = thumbsContainer ? thumbsContainer.querySelectorAll('img') : [];
        thumbs.forEach(function(t) { t.classList.remove('active'); });
        if (thumbs[idx]) thumbs[idx].classList.add('active');
    }

    /* ========================
       HIGHLIGHTS FROM METAFIELDS
       ======================== */
    function buildHighlights() {
        var metafields = window.__thsProductMetafields || {};
        var highlightsMf = metafields['product_highlights'];
        if (!highlightsMf || !highlightsMf.value) {
            console.log('[THS Highlights] No highlights metafield found');
            return;
        }

        var highlights = [];
        try {
            highlights = JSON.parse(highlightsMf.value);
        } catch (e) {
            console.warn('[THS Highlights] Could not parse highlights:', e);
            return;
        }

        if (!Array.isArray(highlights) || highlights.length === 0) return;

        /* Get icons if available via references */
        var iconsMf = metafields['product_highlight_images'];
        var icons = [];
        if (iconsMf && iconsMf.references && iconsMf.references.edges) {
            icons = iconsMf.references.edges.map(function(edge) {
                if (edge.node && edge.node.image) return edge.node.image.url;
                return null;
            }).filter(Boolean);
        }
        console.log('[THS Highlights] Found', icons.length, 'icon images');

        var container = document.querySelector('.ths-pdp__highlights');
        if (!container) return;

        container.innerHTML = '';

        highlights.forEach(function(text, idx) {
            var item = document.createElement('div');
            item.className = 'ths-pdp__highlight-item';

            var iconHtml = '';
            if (icons[idx]) {
                iconHtml = '<div class="ths-pdp__highlight-icon"><img src="' + icons[idx] + '" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:50%;"></div>';
            } else {
                /* Default placeholder icon */
                iconHtml = '<div class="ths-pdp__highlight-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="1.5"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg></div>';
            }

            item.innerHTML = iconHtml + '<span>' + text + '</span>';
            container.appendChild(item);
        });

        console.log('[THS Highlights] Built', highlights.length, 'highlights');
    }

    /* ========================
       ACCORDIONS FROM METAFIELDS
       ======================== */
    function buildAccordions() {
        var metafields = window.__thsProductMetafields || {};

        /* Ingredients */
        var ingredientsMf = metafields['ingredients'];
        if (ingredientsMf && ingredientsMf.value) {
            var ingredientsBody = document.querySelector('.ths-pdp__accordion:first-child .ths-pdp__accordion-body');
            if (ingredientsBody) {
                ingredientsBody.innerHTML = '<p>' + ingredientsMf.value.replace(/\n/g, '<br>') + '</p>';
                console.log('[THS Accordions] Ingredients populated');
            }
        }

        /* How To Use */
        var howToUseMf = metafields['how_to_use'];
        if (howToUseMf && howToUseMf.value) {
            var howToUseBody = document.querySelector('.ths-pdp__accordion:nth-child(2) .ths-pdp__accordion-body');
            if (howToUseBody) {
                howToUseBody.innerHTML = '<p>' + howToUseMf.value.replace(/\n/g, '<br>') + '</p>';
                console.log('[THS Accordions] How To Use populated');
            }
        }
    }

    /* ========================
       VARIANT SWATCHES
       ======================== */
    var selectedOptions = {};

    function buildVariantSwatches() {
        var options = window.__thsProductOptions;
        var variants = window.__thsProductVariants;
        var container = document.getElementById('pdp-variant-swatches');

        if (!options || !variants || !container) {
            console.log('[THS Variants] No variant data or container');
            return;
        }

        /* If only "Title" with "Default Title", product has no real variants */
        if (options.length === 1 && options[0].name === 'Title' && options[0].values.length === 1 && options[0].values[0] === 'Default Title') {
            console.log('[THS Variants] Default title only, hiding variants');
            container.closest('.ths-pdp__variants').style.display = 'none';
            return;
        }

        console.log('[THS Variants] Building swatches for', options.length, 'option groups,', variants.length, 'variants');

        container.innerHTML = '';

        /* Pre-select the first available variant's options */
        var firstAvailable = variants.find(function(v) { return v.availableForSale; }) || variants[0];
        if (firstAvailable) {
            firstAvailable.selectedOptions.forEach(function(opt) {
                selectedOptions[opt.name] = opt.value;
            });
        }

        /* Build a group for each option (e.g., Model, Size, Color) */
        options.forEach(function(option) {
            var group = document.createElement('div');
            group.className = 'ths-pdp__variant-group';

            var label = document.createElement('label');
            label.className = 'ths-pdp__variant-group-label';
            label.innerHTML = option.name + ': <span id="ths-variant-selected-' + option.name.replace(/\s+/g, '-') + '">' + (selectedOptions[option.name] || option.values[0]) + '</span>';
            group.appendChild(label);

            var buttonsWrap = document.createElement('div');
            buttonsWrap.className = 'ths-pdp__variant-buttons';

            option.values.forEach(function(value) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ths-pdp__variant-btn';
                btn.textContent = value;
                btn.setAttribute('data-option-name', option.name);
                btn.setAttribute('data-option-value', value);

                /* Mark active */
                if (selectedOptions[option.name] === value) {
                    btn.classList.add('active');
                }

                /* Check availability */
                var isAvailable = checkOptionAvailability(option.name, value);
                if (!isAvailable) {
                    btn.classList.add('disabled');
                }

                btn.addEventListener('click', function() {
                    if (btn.classList.contains('disabled')) return;
                    selectVariantOption(option.name, value);
                });

                buttonsWrap.appendChild(btn);
            });

            group.appendChild(buttonsWrap);
            container.appendChild(group);
        });

        /* Make sure variants wrapper is visible */
        container.closest('.ths-pdp__variants').style.display = 'block';

        /* Sync initial selection */
        syncVariantToNative();
    }

    function checkOptionAvailability(optionName, optionValue) {
        var variants = window.__thsProductVariants || [];
        /* Check if any variant with this option value is available */
        return variants.some(function(v) {
            var hasOption = v.selectedOptions.some(function(o) {
                return o.name === optionName && o.value === optionValue;
            });
            /* Also check that it's compatible with other selected options */
            if (!hasOption) return false;
            var matchesOthers = true;
            Object.keys(selectedOptions).forEach(function(name) {
                if (name === optionName) return;
                var matches = v.selectedOptions.some(function(o) {
                    return o.name === name && o.value === selectedOptions[name];
                });
                if (!matches) matchesOthers = false;
            });
            return matchesOthers && v.availableForSale;
        });
    }

    function selectVariantOption(optionName, optionValue) {
        selectedOptions[optionName] = optionValue;

        /* Update button states */
        var container = document.getElementById('pdp-variant-swatches');
        if (!container) return;

        /* Update active state for this option group */
        var buttons = container.querySelectorAll('[data-option-name="' + optionName + '"]');
        buttons.forEach(function(btn) {
            btn.classList.remove('active');
            if (btn.getAttribute('data-option-value') === optionValue) {
                btn.classList.add('active');
            }
        });

        /* Update selected label */
        var labelSpan = document.getElementById('ths-variant-selected-' + optionName.replace(/\s+/g, '-'));
        if (labelSpan) labelSpan.textContent = optionValue;

        /* Recalculate availability of other options */
        var allGroups = container.querySelectorAll('.ths-pdp__variant-group');
        allGroups.forEach(function(group) {
            var groupBtns = group.querySelectorAll('.ths-pdp__variant-btn');
            groupBtns.forEach(function(btn) {
                var name = btn.getAttribute('data-option-name');
                var val = btn.getAttribute('data-option-value');
                var avail = checkOptionAvailability(name, val);
                if (avail) {
                    btn.classList.remove('disabled');
                } else {
                    btn.classList.add('disabled');
                }
            });
        });

        /* Find the matching variant */
        var matchedVariant = findMatchingVariant();
        if (matchedVariant) {
            /* Update price */
            updatePriceFromVariant(matchedVariant);
            /* Switch to variant image if it has one */
            if (matchedVariant.image && matchedVariant.image.url && galleryImages.length > 0) {
                var imgIdx = galleryImages.findIndex(function(gi) {
                    return gi.url === matchedVariant.image.url;
                });
                if (imgIdx > -1) selectGalleryImage(imgIdx);
            }
        }

        /* Sync to native Shopify selector */
        syncVariantToNative();
    }

    function findMatchingVariant() {
        var variants = window.__thsProductVariants || [];
        return variants.find(function(v) {
            return v.selectedOptions.every(function(o) {
                return selectedOptions[o.name] === o.value;
            });
        });
    }

    function updatePriceFromVariant(variant) {
        if (!variant || !variant.price) return;
        var priceEl = document.querySelector('.ths-pdp__price shopify-money');
        if (!priceEl) return;
        /* Try to update the price display */
        var amount = parseFloat(variant.price.amount);
        var formatted = '$' + amount.toFixed(2);
        /* Check shadow root first */
        if (priceEl.shadowRoot) {
            var shadowSpan = priceEl.shadowRoot.querySelector('span, .money, [class*="price"]');
            if (shadowSpan) shadowSpan.textContent = formatted;
        }
        /* Also try light DOM */
        if (priceEl.textContent) {
            priceEl.textContent = formatted;
        }
        console.log('[THS Variants] Price updated to', formatted);
    }

    function syncVariantToNative() {
        /* Sync our selection to the hidden native Shopify selector */
        var nativeSelector = document.querySelector('.ths-pdp__variants-native shopify-variant-selector');
        if (!nativeSelector) return;

        /* Try to find and update the native select dropdown */
        var updateSelect = function(root) {
            var selects = root.querySelectorAll('select');
            selects.forEach(function(sel) {
                /* Match by option group */
                var options = sel.querySelectorAll('option');
                options.forEach(function(opt) {
                    var val = opt.value || opt.textContent;
                    /* Check if this option matches any selected value */
                    Object.values(selectedOptions).forEach(function(selectedVal) {
                        if (val.indexOf(selectedVal) > -1 || opt.textContent.indexOf(selectedVal) > -1) {
                            sel.value = opt.value;
                            sel.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                });
            });
        };

        /* Check both light DOM and shadow DOM */
        updateSelect(nativeSelector);
        if (nativeSelector.shadowRoot) {
            updateSelect(nativeSelector.shadowRoot);
        }

        console.log('[THS Variants] Synced to native:', JSON.stringify(selectedOptions));
    }

    /* ========================
       AUTO-RENEW FROM SELLING PLANS
       ======================== */
    function buildAutoRenew() {
        var plans = window.__thsSellingPlans || [];
        var container = document.getElementById('pdp-autorenew');
        var plansWrap = document.getElementById('autorenew-plans');
        var badge = document.getElementById('autorenew-badge');
        var intervalEl = document.getElementById('autorenew-interval');
        var shippingOption = document.querySelector('[data-option="autoreplenish"]');
        var shippingSub = shippingOption ? shippingOption.querySelector('.ths-pdp__shipping-sub') : null;

        if (!plans.length || !container) {
            /* No selling plans — hide auto-renew UI */
            if (container) container.style.display = 'none';
            if (shippingOption) shippingOption.style.display = 'none';
            console.log('[THS Subscriptions] No selling plans found, hiding auto-renew');
            return;
        }

        /* Show the section */
        container.style.display = '';

        /* Set badge from first plan's discount */
        var firstDiscount = plans[0].discount;
        if (firstDiscount > 0) {
            badge.textContent = firstDiscount + '% SAVINGS';
            badge.style.display = '';
            if (shippingSub) shippingSub.textContent = 'save ' + firstDiscount + '% on this';
        } else {
            badge.style.display = 'none';
            if (shippingSub) shippingSub.textContent = 'subscribe & save';
        }

        /* Set interval text from first plan */
        if (plans[0].interval) {
            intervalEl.innerHTML = 'Products Auto Deliver every <strong>' + plans[0].interval + '</strong>';
        }

        /* Build plan option buttons if multiple plans */
        if (plans.length > 1 && plansWrap) {
            plansWrap.innerHTML = '';
            plans.forEach(function(plan, idx) {
                var btn = document.createElement('button');
                btn.className = 'ths-pdp__plan-option' + (idx === 0 ? ' active' : '');
                btn.setAttribute('data-plan-id', plan.id);
                btn.setAttribute('onclick', 'selectSellingPlan(this)');
                var label = plan.interval || plan.name;
                if (plan.discount > 0) label += ' — ' + plan.discount + '% off';
                btn.textContent = label;
                plansWrap.appendChild(btn);
            });
        }

        /* Pre-select first plan */
        selectedSellingPlanId = plans[0].id;
        /* Start with toggle OFF — user must opt-in */
        var toggle = document.getElementById('autorenew-toggle');
        if (toggle) toggle.classList.add('off');
        selectedSellingPlanId = null;

        console.log('[THS Subscriptions] Auto-renew UI built with ' + plans.length + ' plan(s)');
    }

    /* ========================
       CART OVERRIDE FOR SELLING PLANS
       ======================== */
    function initCartOverride() {
        var addBtn = document.querySelector('.ths-pdp__add-to-cart');
        if (!addBtn) return;

        addBtn.removeAttribute('onclick');
        addBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var toggle = document.getElementById('autorenew-toggle');
            var isAutoRenew = toggle && !toggle.classList.contains('off') && selectedSellingPlanId;

            if (!isAutoRenew) {
                /* Normal add to cart — use Shopify plugin */
                var cart = document.getElementById('cart');
                if (cart) {
                    cart.addLine(e);
                    cart.showModal();
                }
                return;
            }

            /* Get variant ID */
            var variantId = null;
            var variants = window.__thsProductVariants || [];
            var nativeSelector = document.querySelector('.ths-pdp__variants-native shopify-variant-selector');
            if (nativeSelector) {
                var findSelected = function(root) {
                    var selects = root.querySelectorAll('select');
                    for (var i = 0; i < selects.length; i++) {
                        var opt = selects[i].options[selects[i].selectedIndex];
                        if (opt && opt.value) return opt.value;
                    }
                    return null;
                };
                variantId = findSelected(nativeSelector);
                if (!variantId && nativeSelector.shadowRoot) variantId = findSelected(nativeSelector.shadowRoot);
            }
            if (!variantId && variants.length > 0) variantId = variants[0].id;
            if (!variantId) {
                var cart = document.getElementById('cart');
                if (cart) { cart.addLine(e); cart.showModal(); }
                return;
            }

            /* Ensure variant ID is in GID format */
            if (variantId && variantId.indexOf('gid://') === -1) {
                variantId = 'gid://shopify/ProductVariant/' + variantId.replace(/\D/g, '');
            }

            var qty = parseInt(document.getElementById('pdp-qty').textContent) || 1;

            addBtn.disabled = true;
            addBtn.textContent = 'Adding...';

            console.log('[THS Cart] Subscription add — variant:', variantId, 'plan:', selectedSellingPlanId, 'qty:', qty);

            /* Check for existing cart */
            var existingCartId = localStorage.getItem('__shopify:cartId');
            if (existingCartId) existingCartId = existingCartId.split('?')[0];

            var lineInput = '{ merchandiseId: "' + variantId + '", quantity: ' + qty + ', sellingPlanId: "' + selectedSellingPlanId + '" }';

            var doAdd = function() {
                if (existingCartId) {
                    /* Add to existing cart */
                    var mutation = 'mutation { cartLinesAdd(cartId: "' + existingCartId + '", lines: [' + lineInput + ']) { cart { id checkoutUrl totalQuantity } userErrors { field message } } }';
                    return fetch('https://' + shopDomain + '/api/2025-01/graphql.json', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Shopify-Storefront-Access-Token': apiKey
                        },
                        body: JSON.stringify({ query: mutation })
                    }).then(function(r) { return r.json(); }).then(function(data) {
                        console.log('[THS Cart] cartLinesAdd response:', JSON.stringify(data).substring(0, 500));
                        var result = data.data && data.data.cartLinesAdd;
                        if (result && result.cart && (!result.userErrors || result.userErrors.length === 0)) {
                            return { cart: result.cart, isNew: false };
                        }
                        /* cartLinesAdd failed — recover existing lines before creating new cart */
                        console.warn('[THS Cart] cartLinesAdd failed, recovering existing cart lines...');
                        return recoverAndCreate();
                    }).catch(function(err) {
                        console.warn('[THS Cart] cartLinesAdd network error, recovering...', err);
                        return recoverAndCreate();
                    });
                } else {
                    return doCreate([lineInput]);
                }
            };

            /* Fetch existing cart lines and create a new cart with them + the new line */
            var recoverAndCreate = function() {
                var fetchQuery = '{ cart(id: "' + existingCartId + '") { lines(first: 50) { edges { node { quantity merchandise { ... on ProductVariant { id } } sellingPlanAllocation { sellingPlan { id } } } } } } }';
                return fetch('https://' + shopDomain + '/api/2025-01/graphql.json', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Shopify-Storefront-Access-Token': apiKey
                    },
                    body: JSON.stringify({ query: fetchQuery })
                }).then(function(r) { return r.json(); }).then(function(data) {
                    var allLines = [lineInput]; /* start with the new item */
                    if (data.data && data.data.cart && data.data.cart.lines) {
                        data.data.cart.lines.edges.forEach(function(edge) {
                            var n = edge.node;
                            var mid = n.merchandise.id;
                            var q = n.quantity;
                            var sp = n.sellingPlanAllocation ? n.sellingPlanAllocation.sellingPlan.id : null;
                            var existing = '{ merchandiseId: "' + mid + '", quantity: ' + q;
                            if (sp) existing += ', sellingPlanId: "' + sp + '"';
                            existing += ' }';
                            allLines.push(existing);
                        });
                        console.log('[THS Cart] Recovered ' + (allLines.length - 1) + ' existing line(s), creating new cart with all');
                    }
                    return doCreate(allLines);
                }).catch(function(err) {
                    console.warn('[THS Cart] Could not recover cart, creating with new item only:', err);
                    return doCreate([lineInput]);
                });
            };

            var doCreate = function(linesArray) {
                var allLinesStr = linesArray.join(', ');
                var mutation = 'mutation { cartCreate(input: { lines: [' + allLinesStr + '] }) { cart { id checkoutUrl totalQuantity } userErrors { field message } } }';
                return fetch('https://' + shopDomain + '/api/2025-01/graphql.json', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Shopify-Storefront-Access-Token': apiKey
                    },
                    body: JSON.stringify({ query: mutation })
                }).then(function(r) { return r.json(); }).then(function(data) {
                    console.log('[THS Cart] cartCreate response:', JSON.stringify(data).substring(0, 500));
                    var result = data.data && data.data.cartCreate;
                    if (result && result.userErrors && result.userErrors.length > 0) {
                        throw new Error(result.userErrors.map(function(e) { return e.message; }).join(', '));
                    }
                    if (data.errors) {
                        throw new Error(data.errors.map(function(e) { return e.message; }).join(', '));
                    }
                    return { cart: result.cart, isNew: true };
                });
            };

            doAdd().then(function(result) {
                addBtn.disabled = false;
                addBtn.textContent = 'Add to Cart';

                if (result && result.cart) {
                    /* Store cart ID in the SAME key the cart page uses */
                    localStorage.setItem('__shopify:cartId', result.cart.id);
                    console.log('[THS Cart] Subscription added! Cart:', result.cart.id, 'Qty:', result.cart.totalQuantity);

                    /* Update cart badge */
                    var badge = document.querySelector('.ths-cart-badge');
                    if (badge && result.cart.totalQuantity) {
                        badge.textContent = result.cart.totalQuantity;
                        badge.style.display = 'flex';
                    }

                    /* Navigate to cart page */
                    window.location.href = '/your-cart/';
                }
            }).catch(function(err) {
                addBtn.disabled = false;
                addBtn.textContent = 'Add to Cart';
                console.error('[THS Cart] Error:', err);
                alert('Could not add subscription item. Please try again.');
            });
        });
    }

    /* ========================
       PRODUCT CAROUSELS
       ======================== */

    function fetchRecommendations() {
        var productId = window.__thsProductId;
        if (!productId || !shopDomain || !apiKey) {
            console.log('[THS Carousel] Missing product ID or config, skipping recommendations');
            return;
        }

        console.log('[THS Carousel] Fetching recommendations for', productId);

        /* Use Storefront API productRecommendations query */
        var query = '{ productRecommendations(productId: "' + productId + '") { id handle title vendor featuredImage { url altText } priceRange { minVariantPrice { amount currencyCode } } compareAtPriceRange { minVariantPrice { amount currencyCode } } onlineStoreUrl } }';

        fetch('https://' + shopDomain + '/api/2024-01/graphql.json', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Shopify-Storefront-Access-Token': apiKey
            },
            body: JSON.stringify({ query: query })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.data && data.data.productRecommendations) {
                var products = data.data.productRecommendations;
                console.log('[THS Carousel] Got', products.length, 'recommendations');

                if (products.length > 0) {
                    /* Split: first 5 for Similar, rest for Also Like */
                    var similarProducts = products.slice(0, 5);
                    var alsoLikeProducts = products.slice(5, 10);

                    buildCarousel('similar', similarProducts);

                    if (alsoLikeProducts.length > 0) {
                        buildCarousel('alsolike', alsoLikeProducts);
                    } else {
                        /* If not enough, fetch more from collection */
                        fetchCollectionProducts(alsoLikeProducts);
                    }
                }
            } else {
                console.warn('[THS Carousel] No recommendations in response, trying collection fallback');
                fetchCollectionProducts([]);
            }
        })
        .catch(function(err) {
            console.error('[THS Carousel] Recommendations failed:', err);
            fetchCollectionProducts([]);
        });
    }

    /* Fallback: fetch products from the shop */
    function fetchCollectionProducts(existingProducts) {
        var query = '{ products(first: 10, sortKey: BEST_SELLING) { edges { node { id handle title vendor featuredImage { url altText } priceRange { minVariantPrice { amount currencyCode } } compareAtPriceRange { minVariantPrice { amount currencyCode } } onlineStoreUrl } } } }';

        fetch('https://' + shopDomain + '/api/2024-01/graphql.json', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Shopify-Storefront-Access-Token': apiKey
            },
            body: JSON.stringify({ query: query })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.data && data.data.products && data.data.products.edges) {
                var products = data.data.products.edges.map(function(e) { return e.node; });
                /* Filter out current product */
                var currentId = window.__thsProductId;
                products = products.filter(function(p) { return p.id !== currentId; });

                console.log('[THS Carousel] Got', products.length, 'collection products');

                /* Fill whichever carousel needs products */
                var similarEl = document.getElementById('pdp-carousel-similar');
                var alsoLikeEl = document.getElementById('pdp-carousel-alsolike');

                if (similarEl && similarEl.children.length === 0) {
                    buildCarousel('similar', products.slice(0, 5));
                }
                if (alsoLikeEl && alsoLikeEl.children.length === 0) {
                    var remaining = existingProducts.length > 0 ? existingProducts : products.slice(5, 10);
                    if (remaining.length > 0) {
                        buildCarousel('alsolike', remaining);
                    } else {
                        /* Hide the section if no products */
                        var section = document.getElementById('ths-pdp-alslike');
                        if (section) section.style.display = 'none';
                    }
                }
            }
        })
        .catch(function(err) {
            console.error('[THS Carousel] Collection fetch failed:', err);
        });
    }

    function buildCarousel(carouselId, products) {
        var container = document.getElementById('pdp-carousel-' + carouselId);
        if (!container) return;

        if (products.length === 0) {
            /* Hide the whole section if no products */
            var section = container.closest('.ths-pdp__section');
            if (section) section.style.display = 'none';
            return;
        }

        container.innerHTML = '';

        products.forEach(function(product) {
            var card = document.createElement('a');
            card.className = 'ths-pdp__product-card';
            /* Use current site's product URL pattern, not Shopify's storefront URL */
            card.href = window.location.origin + '/products/' + product.handle;
            card.setAttribute('data-handle', product.handle);

            var imgUrl = '';
            var imgAlt = product.title;
            if (product.featuredImage) {
                imgUrl = product.featuredImage.url.replace(/(\.\w+)(\?|$)/, '_400x400$1$2');
                imgAlt = product.featuredImage.altText || product.title;
            }

            var price = '';
            var comparePrice = '';
            if (product.priceRange && product.priceRange.minVariantPrice) {
                var amount = parseFloat(product.priceRange.minVariantPrice.amount);
                price = '$' + amount.toFixed(2);
            }
            if (product.compareAtPriceRange && product.compareAtPriceRange.minVariantPrice) {
                var compAmt = parseFloat(product.compareAtPriceRange.minVariantPrice.amount);
                if (compAmt > 0 && compAmt > parseFloat(product.priceRange.minVariantPrice.amount)) {
                    comparePrice = '$' + compAmt.toFixed(2);
                }
            }

            var vendor = product.vendor || '';

            /* Stars default to empty/grey — will be populated when Judge.me has data */
            var starCount = 0;
            var starHtml = '';
            for (var s = 0; s < 5; s++) {
                starHtml += '<span class="star-empty">★</span>';
            }

            var heartSvg = '<svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
            var cartSvg = '<svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>';

            card.innerHTML =
                '<div class="ths-pdp__product-card-img">' +
                    (imgUrl ? '<img src="' + imgUrl + '" alt="' + imgAlt.replace(/"/g, '&quot;') + '" loading="lazy">' : '<div style="width:100%;height:100%;background:#e0e0e0;"></div>') +
                    '<button class="ths-pdp__product-card-heart" onclick="event.preventDefault();event.stopPropagation();this.classList.toggle(\'active\')" aria-label="Add to wishlist">' + heartSvg + '</button>' +
                    '<div class="ths-pdp__product-card-overlay">Buy Now</div>' +
                '</div>' +
                '<div class="ths-pdp__product-card-info">' +
                    (vendor ? '<p class="ths-pdp__product-card-vendor">' + vendor + '</p>' : '') +
                    '<p class="ths-pdp__product-card-title">' + product.title + '</p>' +
                    '<p class="ths-pdp__product-card-price">' + price +
                        (comparePrice ? '<span class="compare-price">' + comparePrice + '</span>' : '') +
                    '</p>' +
                '</div>' +
                '<div class="ths-pdp__product-card-bottom">' +
                    '<div class="ths-pdp__product-card-stars">' + starHtml + '</div>' +
                    '<button class="ths-pdp__product-card-add" onclick="event.preventDefault();event.stopPropagation();" aria-label="Quick add">' + cartSvg + '</button>' +
                '</div>';

            container.appendChild(card);
        });

        /* Update arrow visibility */
        updateCarouselArrows(carouselId);

        /* Listen for scroll to update arrows */
        container.addEventListener('scroll', function() {
            updateCarouselArrows(carouselId);
        });

        console.log('[THS Carousel] Built', carouselId, 'with', products.length, 'cards');
    }

    function updateCarouselArrows(carouselId) {
        var container = document.getElementById('pdp-carousel-' + carouselId);
        if (!container) return;

        var leftArrow = container.parentElement.querySelector('.ths-pdp__carousel-arrow--left');
        var rightArrow = container.parentElement.querySelector('.ths-pdp__carousel-arrow--right');

        if (leftArrow) {
            leftArrow.disabled = container.scrollLeft <= 5;
        }
        if (rightArrow) {
            rightArrow.disabled = container.scrollLeft + container.clientWidth >= container.scrollWidth - 5;
        }
    }

    /* Global scroll function for onclick handlers */
    window.carouselScroll = function(carouselId, direction) {
        var container = document.getElementById('pdp-carousel-' + carouselId);
        if (!container) return;
        var scrollAmount = container.clientWidth * 0.7;
        container.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
        /* Update arrows after scroll animation */
        setTimeout(function() { updateCarouselArrows(carouselId); }, 400);
    };

    /* Method 1: Fetch via Storefront API */
    function fetchImagesFromAPI() {
        if (!shopDomain || !productHandle) return Promise.reject('No domain or handle');

        var query = '{ product(handle: "' + productHandle + '") { id images(first: 20) { edges { node { url altText } } } options { name values } variants(first: 100) { edges { node { id title availableForSale selectedOptions { name value } image { url altText } price { amount currencyCode } compareAtPrice { amount currencyCode } } } } sellingPlanGroups(first: 5) { edges { node { name sellingPlans(first: 10) { edges { node { id name recurringDeliveries priceAdjustments { adjustmentValue { ... on SellingPlanPercentagePriceAdjustment { adjustmentPercentage } } } options { name value } } } } } } } metafields(identifiers: [{namespace: "custom", key: "product_highlights"}, {namespace: "custom", key: "product_highlight_images"}, {namespace: "custom", key: "ingredients"}, {namespace: "custom", key: "how_to_use"}]) { key value type references(first: 20) { edges { node { ... on MediaImage { image { url altText } } } } } } } }';

        /* Try tokens in order: storefront token, api key, token from page */
        var tokensToTry = [];
        if (storefrontToken) tokensToTry.push(storefrontToken);
        if (apiKey && apiKey !== storefrontToken) tokensToTry.push(apiKey);

        /* Also look for token in page source */
        var pageToken = findTokenInPage();
        if (pageToken && tokensToTry.indexOf(pageToken) === -1) tokensToTry.push(pageToken);

        if (tokensToTry.length === 0) return Promise.reject('No tokens available');

        function tryToken(idx) {
            if (idx >= tokensToTry.length) return Promise.reject('All tokens failed');
            var token = tokensToTry[idx];
            console.log('[THS Gallery] Trying token ' + (idx + 1) + '/' + tokensToTry.length + ': ' + token.substring(0, 6) + '...');

            return fetch('https://' + shopDomain + '/api/2024-01/graphql.json', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Shopify-Storefront-Access-Token': token
                },
                body: JSON.stringify({ query: query })
            })
            .then(function(res) {
                console.log('[THS Gallery] API response status:', res.status);
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function(data) {
                if (data.errors) {
                    console.warn('[THS Gallery] GraphQL errors:', data.errors);
                    throw new Error('GraphQL error');
                }
                if (data.data && data.data.product) {
                    var product = data.data.product;
                    var images = [];
                    if (product.id) window.__thsProductId = product.id;
                    if (product.images && product.images.edges) {
                        images = product.images.edges.map(function(edge) {
                            return { url: edge.node.url, alt: edge.node.altText || '' };
                        });
                    }
                    /* Store metafield data globally (including references for file types) */
                    if (product.metafields) {
                        window.__thsProductMetafields = {};
                        product.metafields.forEach(function(mf) {
                            if (mf && mf.key) window.__thsProductMetafields[mf.key] = mf;
                        });
                        console.log('[THS Metafields] Stored:', Object.keys(window.__thsProductMetafields).join(', '));
                    }
                    /* Store variant data globally */
                    if (product.options) window.__thsProductOptions = product.options;
                    if (product.variants && product.variants.edges) {
                        window.__thsProductVariants = product.variants.edges.map(function(edge) {
                            return edge.node;
                        });
                    }
                    /* Store selling plans globally */
                    window.__thsSellingPlans = [];
                    if (product.sellingPlanGroups && product.sellingPlanGroups.edges) {
                        product.sellingPlanGroups.edges.forEach(function(groupEdge) {
                            var group = groupEdge.node;
                            if (group.sellingPlans && group.sellingPlans.edges) {
                                group.sellingPlans.edges.forEach(function(planEdge) {
                                    var plan = planEdge.node;
                                    var discount = 0;
                                    if (plan.priceAdjustments && plan.priceAdjustments.length > 0) {
                                        var adj = plan.priceAdjustments[0].adjustmentValue;
                                        if (adj && adj.adjustmentPercentage) discount = adj.adjustmentPercentage;
                                    }
                                    var interval = '';
                                    if (plan.options && plan.options.length > 0) interval = plan.options[0].value;
                                    window.__thsSellingPlans.push({
                                        id: plan.id,
                                        name: plan.name,
                                        interval: interval,
                                        discount: discount,
                                        groupName: group.name
                                    });
                                });
                            }
                        });
                        console.log('[THS Subscriptions] Found ' + window.__thsSellingPlans.length + ' selling plans');
                    }
                    /* Show Auto-Replenish shipping option only if selling plans exist */
                    if (window.__thsSellingPlans && window.__thsSellingPlans.length > 0) {
                        var arOpt = document.querySelector('.ths-pdp__shipping-option[data-option="autoreplenish"]');
                        if (arOpt) arOpt.classList.add('ths-sub-visible');
                    }
                    if (images.length > 0) return images;
                }
                throw new Error('No images in response');
            })
            .catch(function(err) {
                console.warn('[THS Gallery] Token ' + (idx + 1) + ' failed:', err.message);
                return tryToken(idx + 1);
            });
        }

        return tryToken(0);
    }

    /* Find storefront token from page scripts or elements */
    function findTokenInPage() {
        var token = null;

        /* Check shopify-context element */
        var ctx = document.querySelector('shopify-context');
        if (ctx) {
            var allAttrs = ctx.getAttributeNames ? ctx.getAttributeNames() : [];
            allAttrs.forEach(function(a) {
                var val = ctx.getAttribute(a);
                if (val && (a.indexOf('token') > -1 || a.indexOf('access') > -1)) {
                    token = val;
                }
            });
            console.log('[THS Gallery] shopify-context attributes:', allAttrs.join(', '));
        }

        /* Check script tags for storefront token */
        if (!token) {
            var scripts = document.querySelectorAll('script:not([src])');
            for (var i = 0; i < scripts.length; i++) {
                var text = scripts[i].textContent;
                /* Look for common patterns */
                var patterns = [
                    /storefrontAccessToken["'\s:=]+["']([a-f0-9]{30,})["']/,
                    /storefront_access_token["'\s:=]+["']([a-f0-9]{30,})["']/,
                    /accessToken["'\s:=]+["']([a-f0-9]{30,})["']/,
                    /access.token["'\s:=]+["']([a-f0-9]{30,})["']/
                ];
                for (var p = 0; p < patterns.length; p++) {
                    var match = text.match(patterns[p]);
                    if (match) {
                        token = match[1];
                        console.log('[THS Gallery] Found token in script tag');
                        break;
                    }
                }
                if (token) break;
            }
        }

        /* Check meta tags */
        if (!token) {
            var metas = document.querySelectorAll('meta[name*="shopify"], meta[property*="shopify"]');
            metas.forEach(function(m) {
                if (m.content && m.content.length > 20) {
                    token = m.content;
                }
            });
        }

        return token;
    }

    /* Method 2: Extract from shopify-media shadow DOM */
    function extractFromShopifyMedia() {
        return new Promise(function(resolve) {
            var attempts = 0;
            var maxAttempts = 10;

            function tryExtract() {
                attempts++;
                if (!shopifyMedia) { resolve([]); return; }

                var imgSrc = null;

                /* Check shadow DOM */
                if (shopifyMedia.shadowRoot) {
                    var shadowImg = shopifyMedia.shadowRoot.querySelector('img');
                    if (shadowImg && shadowImg.src) {
                        imgSrc = shadowImg.src;
                    }
                }

                /* Check direct children */
                if (!imgSrc) {
                    var directImg = shopifyMedia.querySelector('img');
                    if (directImg && directImg.src) {
                        imgSrc = directImg.src;
                    }
                }

                if (imgSrc) {
                    /* We found one image - return it */
                    resolve([{ url: imgSrc, alt: '' }]);
                } else if (attempts < maxAttempts) {
                    setTimeout(tryExtract, 300);
                } else {
                    resolve([]);
                }
            }

            tryExtract();
        });
    }

    /* Initialize gallery */
    function initGallery() {
        console.log('[THS Gallery] Initializing...');
        findGalleryElements();

        fetchImagesFromAPI()
            .then(function(images) {
                if (images.length > 0) {
                    console.log('[THS Gallery] Success! Found ' + images.length + ' images');
                    buildThumbnails(images);
                } else {
                    throw new Error('No images from API');
                }
                /* Build highlights from metafields */
                buildHighlights();
                /* Build accordion content from metafields */
                buildAccordions();
                /* Build variant swatches from the same API response */
                buildVariantSwatches();
                /* Fetch product recommendations for carousels */
                fetchRecommendations();
                /* Build auto-renew UI from selling plans */
                buildAutoRenew();
                /* Override cart for selling plan support */
                initCartOverride();
            })
            .catch(function(err) {
                console.warn('[THS Gallery] API failed:', err);
                /* Fallback: extract from rendered component */
                extractFromShopifyMedia().then(function(images) {
                    if (images.length > 0) {
                        console.log('[THS Gallery] Fallback: found image from shopify-media');
                        if (mainImg) mainImg.style.display = 'none';
                        if (shopifyMedia) shopifyMedia.style.display = '';
                    } else {
                        console.warn('[THS Gallery] No images found at all');
                    }
                });
            });
    }

    /* Delay to let Shopify components initialize, with retry */
    function tryInitGallery(attempt) {
        findGalleryElements();
        if (thumbsContainer) {
            initGallery();
        } else if (attempt < 5) {
            console.log('[THS Gallery] Elements not ready, retry ' + (attempt + 1) + '/5...');
            setTimeout(function() { tryInitGallery(attempt + 1); }, 500);
        } else {
            console.error('[THS Gallery] Could not find gallery elements after 5 attempts');
        }
    }
    setTimeout(function() { tryInitGallery(0); }, 500);

    /* Kill borders inside Shopify web component shadow DOMs */
    function nukeShopifyShadowBorders() {
        var shopifyEls = document.querySelectorAll('shopify-variant-selector, shopify-money, shopify-data, shopify-media, shopify-context');
        shopifyEls.forEach(function(el) {
            if (el.shadowRoot && !el.shadowRoot.querySelector('[data-ths-override]')) {
                var style = document.createElement('style');
                style.setAttribute('data-ths-override', 'true');
                var tag = el.tagName.toLowerCase();
                var css = '';

                if (tag === 'shopify-variant-selector') {
                    /* Native selector is hidden, just clean up */
                    css = '*, *::before, *::after { border: none !important; box-shadow: none !important; }';

                } else if (tag === 'shopify-media') {
                    css = '*, *::before, *::after { border: none !important; border-top: none !important; border-bottom: none !important; box-shadow: none !important; outline: none !important; } hr, [role="separator"] { display: none !important; }';
                    css += ' img { width: 100% !important; height: 100% !important; object-fit: contain !important; }';

                } else {
                    css = '*, *::before, *::after { border: none !important; border-top: none !important; border-bottom: none !important; box-shadow: none !important; outline: none !important; } hr, [role="separator"] { display: none !important; }';
                }

                style.textContent = css;
                el.shadowRoot.appendChild(style);
            }
        });

        /* Variant visibility handled by buildVariantSwatches */
    }

    /* Run immediately, then again after Shopify components initialize */
    nukeShopifyShadowBorders();
    setTimeout(nukeShopifyShadowBorders, 500);
    setTimeout(nukeShopifyShadowBorders, 1500);
    setTimeout(nukeShopifyShadowBorders, 3000);

    /* Also watch for shadow roots being attached */
    var observer = new MutationObserver(function() {
        nukeShopifyShadowBorders();
    });
    observer.observe(document.querySelector('.ths-pdp') || document.body, {
        childList: true,
        subtree: true
    });
    /* ============================
       PDP WISHLIST / FAVORITES
       ============================ */

    function initPdpWishlist() {
        var handle = productHandle;
        if (!handle) return;

        var STORAGE_KEY = 'ths-wishlist';
        function getWishlist() {
            try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); } catch (e) { return []; }
        }
        function saveWishlist(list) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
            if (typeof THSWishlist !== 'undefined' && THSWishlist.updateBadge) THSWishlist.updateBadge();
            if (typeof THSWishlist !== 'undefined' && THSWishlist.syncToServer) THSWishlist.syncToServer();
        }

        /* Set initial state */
        var wishlist = getWishlist();
        var isInWishlist = wishlist.indexOf(handle) > -1;

        var wishlistBtn = document.getElementById('pdp-wishlist-btn');
        var favBtn = document.getElementById('pdp-fav-btn');

        function updateButtons(active) {
            if (wishlistBtn) {
                wishlistBtn.classList.toggle('active', active);
                var svg = wishlistBtn.querySelector('svg');
                if (svg) {
                    svg.setAttribute('fill', active ? '#e74c6f' : 'none');
                    svg.setAttribute('stroke', active ? '#e74c6f' : '#999');
                }
            }
            if (favBtn) {
                favBtn.classList.toggle('active', active);
            }
        }

        function togglePdpWishlist() {
            var list = getWishlist();
            var idx = list.indexOf(handle);
            if (idx > -1) {
                list.splice(idx, 1);
                updateButtons(false);
            } else {
                list.push(handle);
                updateButtons(true);
            }
            saveWishlist(list);
        }

        /* Set initial visual state */
        updateButtons(isInWishlist);

        /* Event delegation for both buttons (they're inside a template) */
        document.addEventListener('click', function(e) {
            if (e.target.closest('#pdp-wishlist-btn') || e.target.closest('#pdp-fav-btn')) {
                e.preventDefault();
                togglePdpWishlist();
            }
        });
    }

    /* Init after Shopify components render */
    setTimeout(initPdpWishlist, 500);
    setTimeout(initPdpWishlist, 2000);

    /* ============================
       JUDGE.ME REVIEWS INTEGRATION
       ============================ */

    var JUDGEME_PUBLIC_TOKEN = 'Lv3_dVCnJehcJsFNgGSYi1CnkS8';
    var JUDGEME_DOMAIN = 'theheadspa.myshopify.com';
    var JUDGEME_API = 'https://judge.me/api/v1';
    var jmReviewsPage = 1;
    var jmPerPage = 5;
    var jmInternalProductId = null;
    var jmTotalReviews = 0;

    function initJudgeMe() {
        /* Need the Shopify product GID to get the external_id */
        var gid = window.__thsProductId;
        if (!gid) {
            /* Retry — product data might not be loaded yet */
            setTimeout(initJudgeMe, 500);
            return;
        }

        /* Extract numeric ID from gid://shopify/Product/123456789 */
        var externalId = gid.replace(/\D/g, '');
        if (!externalId) return;

        /* First: get Judge.me internal product ID */
        fetch(JUDGEME_API + '/products/-1?shop_domain=' + JUDGEME_DOMAIN + '&api_token=' + JUDGEME_PUBLIC_TOKEN + '&external_id=' + externalId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data && data.product) {
                jmInternalProductId = data.product.id;
                var avg = data.product.review_average || 0;
                var count = data.product.review_count || 0;
                jmTotalReviews = count;

                /* Update top rating stars */
                updateTopStars(avg, count);

                if (count > 0) {
                    /* Fetch and show reviews */
                    fetchReviews(1);
                } else {
                    showNoReviews();
                }
            } else {
                showNoReviews();
            }
        })
        .catch(function(e) {
            console.warn('[THS Judge.me] Product lookup failed:', e);
            showNoReviews();
        });
    }

    function updateTopStars(avg, count) {
        var container = document.getElementById('pdp-top-stars');
        if (!container) return;

        var stars = container.querySelectorAll('.ths-pdp__star');
        var rounded = Math.round(avg * 2) / 2; /* Round to nearest 0.5 */

        stars.forEach(function(star, i) {
            if (i + 1 <= Math.floor(rounded)) {
                star.classList.add('filled');
            } else if (i + 0.5 <= rounded) {
                star.classList.add('half');
            }
        });

        var countEl = document.getElementById('pdp-rating-count');
        if (countEl && count > 0) {
            countEl.textContent = count + (count === 1 ? ' Review' : ' Reviews');
            countEl.style.cursor = 'pointer';
            countEl.addEventListener('click', function() {
                document.getElementById('ths-pdp-reviews').scrollIntoView({ behavior: 'smooth' });
            });
        }
    }

    function showNoReviews() {
        var noReviews = document.getElementById('pdp-no-reviews');
        if (noReviews) noReviews.style.display = 'block';

        var summary = document.getElementById('pdp-reviews-summary');
        if (summary) summary.style.display = 'none';
    }

    function fetchReviews(page) {
        if (!jmInternalProductId) return;

        fetch(JUDGEME_API + '/reviews?shop_domain=' + JUDGEME_DOMAIN + '&api_token=' + JUDGEME_PUBLIC_TOKEN + '&product_id=' + jmInternalProductId + '&per_page=' + jmPerPage + '&page=' + page)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var reviews = data.reviews || [];
            jmReviewsPage = page;

            if (page === 1) {
                /* Build summary */
                buildReviewsSummary(data);
            }

            /* Render review cards */
            renderReviewCards(reviews, page === 1);

            /* Show/hide load more */
            var moreWrap = document.getElementById('pdp-reviews-more-wrap');
            if (moreWrap) {
                var loaded = page * jmPerPage;
                moreWrap.style.display = loaded < jmTotalReviews ? 'block' : 'none';
            }
        })
        .catch(function(e) {
            console.warn('[THS Judge.me] Reviews fetch failed:', e);
        });
    }

    function buildReviewsSummary(data) {
        var summary = document.getElementById('pdp-reviews-summary');
        if (!summary) return;

        /* Compute star distribution from reviews if available */
        /* Judge.me API doesn't return distribution directly, so we'll use the product data */
        var product = null;
        fetch(JUDGEME_API + '/products/-1?shop_domain=' + JUDGEME_DOMAIN + '&api_token=' + JUDGEME_PUBLIC_TOKEN + '&external_id=' + (window.__thsProductId || '').replace(/\D/g, ''))
        .then(function(r) { return r.json(); })
        .then(function(d) {
            product = d.product || {};
            var avg = (product.review_average || 0).toFixed(1);
            var total = product.review_count || 0;

            var starsHtml = '';
            for (var i = 1; i <= 5; i++) {
                starsHtml += '<span class="ths-pdp__star' + (i <= Math.round(parseFloat(avg)) ? ' filled' : '') + '">&#9733;</span>';
            }

            summary.innerHTML =
                '<div class="ths-pdp__reviews-overall">' +
                    '<span class="ths-pdp__reviews-avg">' + avg + '</span>' +
                    '<div class="ths-pdp__reviews-overall-stars">' + starsHtml + '</div>' +
                    '<span class="ths-pdp__reviews-total">Based on ' + total + ' review' + (total !== 1 ? 's' : '') + '</span>' +
                '</div>' +
                '<div class="ths-pdp__reviews-bars" id="pdp-reviews-bars"></div>';

            summary.style.display = 'flex';

            /* Build bar chart from fetched reviews */
            buildBarChart(total);
        });
    }

    function buildBarChart(total) {
        /* Fetch all reviews to compute distribution */
        /* For efficiency, we'll tally from the reviews we have + estimate */
        var barsContainer = document.getElementById('pdp-reviews-bars');
        if (!barsContainer || total === 0) return;

        /* Fetch reviews to compute distribution */
        fetch(JUDGEME_API + '/reviews?shop_domain=' + JUDGEME_DOMAIN + '&api_token=' + JUDGEME_PUBLIC_TOKEN + '&product_id=' + jmInternalProductId + '&per_page=250&page=1')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var dist = [0, 0, 0, 0, 0]; /* 1-star through 5-star */
            (data.reviews || []).forEach(function(r) {
                if (r.rating >= 1 && r.rating <= 5) dist[r.rating - 1]++;
            });

            var html = '';
            for (var s = 5; s >= 1; s--) {
                var count = dist[s - 1];
                var pct = total > 0 ? Math.round((count / total) * 100) : 0;
                html += '<div class="ths-pdp__reviews-bar-row">' +
                    '<span class="ths-pdp__reviews-bar-label">' + s + ' &#9733;</span>' +
                    '<div class="ths-pdp__reviews-bar-track"><div class="ths-pdp__reviews-bar-fill" style="width:' + pct + '%"></div></div>' +
                    '<span class="ths-pdp__reviews-bar-count">' + count + '</span>' +
                '</div>';
            }
            barsContainer.innerHTML = html;
        });
    }

    function renderReviewCards(reviews, clear) {
        var list = document.getElementById('pdp-reviews-list');
        if (!list) return;
        if (clear) list.innerHTML = '';

        reviews.forEach(function(r) {
            var name = r.reviewer && r.reviewer.name ? r.reviewer.name : 'Anonymous';
            var initials = name.split(' ').map(function(n) { return n.charAt(0).toUpperCase(); }).join('').substring(0, 2);
            var date = new Date(r.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            var verified = r.verified === 'buyer' || r.verified === 'verified_buyer';

            var starsHtml = '';
            for (var i = 1; i <= 5; i++) {
                starsHtml += '<span class="ths-pdp__star' + (i <= r.rating ? ' filled' : '') + '">&#9733;</span>';
            }

            var photoHtml = '';
            if (r.pictures && r.pictures.length > 0) {
                photoHtml = '<div class="ths-pdp__review-photo-wrap">';
                r.pictures.forEach(function(pic) {
                    var url = pic.urls && pic.urls.compact ? pic.urls.compact : (pic.urls && pic.urls.original ? pic.urls.original : '');
                    if (url) {
                        photoHtml += '<div class="ths-pdp__review-photo"><img src="' + url + '" alt="Review photo" loading="lazy"></div>';
                    }
                });
                photoHtml += '</div>';
            }

            var card = '<div class="ths-pdp__review-card">' +
                '<div class="ths-pdp__review-top">' +
                    '<div class="ths-pdp__review-user">' +
                        '<div class="ths-pdp__review-avatar">' + initials + '</div>' +
                        '<div class="ths-pdp__review-user-info">' +
                            '<span class="ths-pdp__review-username">' + escHtml(name) + '</span>' +
                            (verified ? '<span class="ths-pdp__review-verified">Verified Buyer</span>' : '') +
                        '</div>' +
                    '</div>' +
                    '<span class="ths-pdp__review-date">' + date + '</span>' +
                '</div>' +
                '<div class="ths-pdp__review-stars">' + starsHtml + '</div>' +
                (r.title ? '<p class="ths-pdp__review-title">' + escHtml(r.title) + '</p>' : '') +
                (r.body ? '<p class="ths-pdp__review-body">' + escHtml(r.body) + '</p>' : '') +
                photoHtml +
            '</div>';

            list.insertAdjacentHTML('beforeend', card);
        });
    }

    function escHtml(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    /* Write a Review button — use event delegation since it's inside a template */
    document.addEventListener('click', function(e) {
        var writeBtn = e.target.closest('#pdp-write-review-btn');
        if (!writeBtn) return;
            var formWrap = document.getElementById('pdp-review-form');
            if (!formWrap) return;

            if (formWrap.style.display === 'block') {
                formWrap.style.display = 'none';
                return;
            }

            formWrap.style.display = 'block';
            formWrap.innerHTML =
                '<div class="ths-pdp__review-form">' +
                    '<h3 style="font-size:18px;font-weight:700;margin:0 0 16px;">Write a Review</h3>' +
                    '<div class="ths-pdp__rf-field">' +
                        '<label>Your Name</label>' +
                        '<input type="text" id="jm-review-name" placeholder="Enter your name">' +
                    '</div>' +
                    '<div class="ths-pdp__rf-field">' +
                        '<label>Email</label>' +
                        '<input type="email" id="jm-review-email" placeholder="your@email.com">' +
                    '</div>' +
                    '<div class="ths-pdp__rf-field">' +
                        '<label>Rating</label>' +
                        '<div class="ths-pdp__rf-stars" id="jm-review-stars">' +
                            '<span class="ths-pdp__rf-star" data-rating="1">&#9733;</span>' +
                            '<span class="ths-pdp__rf-star" data-rating="2">&#9733;</span>' +
                            '<span class="ths-pdp__rf-star" data-rating="3">&#9733;</span>' +
                            '<span class="ths-pdp__rf-star" data-rating="4">&#9733;</span>' +
                            '<span class="ths-pdp__rf-star" data-rating="5">&#9733;</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="ths-pdp__rf-field">' +
                        '<label>Review Title</label>' +
                        '<input type="text" id="jm-review-title" placeholder="Give your review a title">' +
                    '</div>' +
                    '<div class="ths-pdp__rf-field">' +
                        '<label>Review</label>' +
                        '<textarea id="jm-review-body" placeholder="Share your experience with this product" rows="4"></textarea>' +
                    '</div>' +
                    '<div id="jm-review-msg" style="display:none;padding:10px;border-radius:6px;margin-bottom:12px;font-size:14px;"></div>' +
                    '<button class="ths-pdp__reviews-write-btn" id="jm-review-submit" style="margin-top:8px;">Submit Review</button>' +
                '</div>';

            formWrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            /* Star selection */
            var selectedRating = 0;
            var starEls = formWrap.querySelectorAll('.ths-pdp__rf-star');
            starEls.forEach(function(star) {
                star.addEventListener('click', function() {
                    selectedRating = parseInt(star.dataset.rating);
                    starEls.forEach(function(s, i) {
                        s.style.color = i < selectedRating ? '#1a1a1a' : '#ccc';
                    });
                });
                star.addEventListener('mouseenter', function() {
                    var r = parseInt(star.dataset.rating);
                    starEls.forEach(function(s, i) {
                        s.style.color = i < r ? '#1a1a1a' : '#ccc';
                    });
                });
            });
            formWrap.querySelector('.ths-pdp__rf-stars').addEventListener('mouseleave', function() {
                starEls.forEach(function(s, i) {
                    s.style.color = i < selectedRating ? '#1a1a1a' : '#ccc';
                });
            });

            /* Submit */
            document.getElementById('jm-review-submit').addEventListener('click', function() {
                var name = document.getElementById('jm-review-name').value.trim();
                var email = document.getElementById('jm-review-email').value.trim();
                var title = document.getElementById('jm-review-title').value.trim();
                var body = document.getElementById('jm-review-body').value.trim();
                var msgEl = document.getElementById('jm-review-msg');

                if (!name || !email || !selectedRating) {
                    msgEl.textContent = 'Please fill in your name, email, and rating.';
                    msgEl.style.display = 'block';
                    msgEl.style.background = '#fef2f2';
                    msgEl.style.color = '#dc2626';
                    return;
                }

                this.disabled = true;
                this.textContent = 'Submitting...';

                var externalId = (window.__thsProductId || '').replace(/\D/g, '');
                var ajaxUrl = (typeof ths_ajax !== 'undefined') ? ths_ajax.url : '/wp-admin/admin-ajax.php';

                var formData = new FormData();
                formData.append('action', 'ths_submit_review');
                formData.append('product_id', externalId);
                formData.append('name', name);
                formData.append('email', email);
                formData.append('rating', selectedRating);
                formData.append('title', title);
                formData.append('body', body);

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    msgEl.textContent = 'Thank you! Your review has been submitted and is pending approval.';
                    msgEl.style.display = 'block';
                    msgEl.style.background = '#f0fdf4';
                    msgEl.style.color = '#16a34a';
                    document.getElementById('jm-review-submit').textContent = 'Submitted!';
                })
                .catch(function(e) {
                    console.error('[THS Judge.me] Submit error:', e);
                    msgEl.textContent = 'Something went wrong. Please try again.';
                    msgEl.style.display = 'block';
                    msgEl.style.background = '#fef2f2';
                    msgEl.style.color = '#dc2626';
                    document.getElementById('jm-review-submit').disabled = false;
                    document.getElementById('jm-review-submit').textContent = 'Submit Review';
                });
            });
    });

    /* Show More button — event delegation */
    document.addEventListener('click', function(e) {
        var moreBtn = e.target.closest('#pdp-reviews-more-btn');
        if (!moreBtn) return;
        fetchReviews(jmReviewsPage + 1);
    });

    /* Kick off Judge.me after product data is loaded */
    setTimeout(initJudgeMe, 1000);
    setTimeout(initJudgeMe, 3000);

});
</script>