<?php
/**
 * Custom Product Card Template - The Head Spa
 * v1.2.0
 * 
 * Override for the Shopify WordPress plugin product card component.
 * Place this file in: wp-content/themes/hello-elementor-child/shopify/product-card.php
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('render_product_card_client')) {
    function render_product_card_client($product_handle, $has_context = true, $card_behavior = 'both') {

        // Error handling
        if (empty($product_handle) && $has_context === false) {
            echo '<div class="ths-card ths-card--error">';
            echo '<p>You must add a product.</p>';
            echo '</div>';
            return;
        }

        if ($has_context === false) { ?>
            <shopify-context type="product" handle="<?php echo esc_attr($product_handle); ?>">
            <template>
        <?php } ?>

        <div shopify-attr--disabled="!product.availableForSale" class="ths-card" shopify-attr--data-handle="product.handle">

            <!-- Image Container -->
            <div class="ths-card__image-wrapper">

                <!-- Wishlist Heart -->
                <button class="ths-card__wishlist" onclick="toggleWishlist(this)" aria-label="Add to wishlist">
                    <svg width="22" height="22" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <!-- Product Image (linked) -->
                <?php if ($card_behavior === 'quick-shop-only') { ?>
                    <a class="ths-card__image-link"
                       onclick="getElementById('product-modal').showModal(); getElementById('product-modal-context').update(event);">
                        <shopify-media max-images="1" query="product.selectedOrFirstAvailableVariant.image"></shopify-media>
                    </a>
                <?php } else { ?>
                    <a class="ths-card__image-link"
                       shopify-attr--href="'/products/' + product.handle">
                        <shopify-media max-images="1" query="product.selectedOrFirstAvailableVariant.image"></shopify-media>
                    </a>
                <?php } ?>

                <!-- Buy Now overlay on hover -->
                <?php if ($card_behavior === 'both' || $card_behavior === 'quick-shop-only') { ?>
                    <button
                        shopify-attr--disabled="!product.availableForSale"
                        class="ths-card__quick-shop"
                        onclick="getElementById('product-modal').showModal(); getElementById('product-modal-context').update(event);">
                        Buy Now
                    </button>
                <?php } ?>
            </div>

            <!-- Product Details -->
            <div class="ths-card__details">

                <!-- Brand Name -->
                <p class="ths-card__brand">
                    <shopify-data query="product.vendor"></shopify-data>
                </p>

                <!-- Title -->
                <h3 class="ths-card__title">
                    <?php if ($card_behavior === 'quick-shop-only') { ?>
                        <a onclick="getElementById('product-modal').showModal(); getElementById('product-modal-context').update(event);">
                    <?php } else { ?>
                        <a shopify-attr--href="'/products/' + product.handle">
                    <?php } ?>
                        <shopify-data query="product.title"></shopify-data>
                    </a>
                </h3>

                <!-- Price -->
                <p class="ths-card__price">
                    <shopify-money query="product.selectedOrFirstAvailableVariant.price"></shopify-money>
                </p>

                <!-- Star Rating + Cart Icon Row -->
                <div class="ths-card__action-row">
                    <div class="ths-card__rating" aria-label="Product rating">
                        <span class="ths-card__stars">
                            <span class="ths-card__stars-filled"></span><span class="ths-card__stars-empty">★★★★★</span>
                        </span>
                    </div>
                    <button
                        class="ths-card__add-bag"
                        onclick="getElementById('cart').addLine(event); getElementById('cart').showModal();"
                        shopify-attr--disabled="!product.selectedOrFirstAvailableVariant.product.availableForSale"
                        aria-label="Add to bag">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 10a4 4 0 01-8 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <?php if (!$has_context) { ?>
            </template>
            </shopify-context>
        <?php } ?>
    <?php
    }
} ?>