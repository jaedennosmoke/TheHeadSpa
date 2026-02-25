/**
 * The Head Spa - Product Carousel
 * v3.1.0
 * 
 * Auto-detects ALL .collection-grid__grid elements, wraps each in a carousel,
 * converts grid to horizontal flex scroll, and adds prev/next arrows.
 * Adds "Buy Now" hover overlay to each card.
 */

(function () {
    'use strict';

    var INIT_ATTEMPTS = 0;
    var MAX_ATTEMPTS = 30;

    function addBuyNowOverlay(card) {
        // Don't add twice
        if (card.querySelector('.ths-card__overlay')) return;

        // Find the image container
        var imgWrap = card.querySelector('.ths-card__image, .ths-card__img, [class*="image"], [class*="img"]');
        if (!imgWrap) {
            // Fallback: use first child div
            imgWrap = card.querySelector('div');
        }
        if (!imgWrap) return;

        // Make sure image container is positioned for absolute overlay
        var pos = window.getComputedStyle(imgWrap).position;
        if (pos === 'static') {
            imgWrap.style.position = 'relative';
        }
        imgWrap.style.overflow = 'hidden';

        // Create overlay
        var overlay = document.createElement('div');
        overlay.className = 'ths-card__overlay';
        overlay.textContent = 'Buy Now';
        imgWrap.appendChild(overlay);
    }

    function initSingleCarousel(grid) {
        // Don't init twice
        if (grid.dataset.carouselInit === 'true') return;
        grid.dataset.carouselInit = 'true';

        // Wait for cards to render
        var cards = grid.querySelectorAll('.ths-card');
        if (cards.length === 0) return false;

        // Add Buy Now overlay to each card
        cards.forEach(function (card) {
            addBuyNowOverlay(card);
        });

        // Create wrapper
        var wrapper = document.createElement('div');
        wrapper.className = 'ths-collection-carousel';
        grid.parentNode.insertBefore(wrapper, grid);
        wrapper.appendChild(grid);

        // Force flex layout
        grid.style.setProperty('display', 'flex', 'important');
        grid.style.setProperty('flex-wrap', 'nowrap', 'important');
        grid.style.setProperty('overflow-x', 'auto', 'important');
        grid.style.setProperty('scroll-snap-type', 'x mandatory', 'important');
        grid.style.setProperty('scroll-behavior', 'smooth', 'important');
        grid.style.setProperty('gap', '12px', 'important');
        grid.style.setProperty('grid-template-columns', 'none', 'important');
        grid.style.scrollbarWidth = 'none';
        grid.style.msOverflowStyle = 'none';

        // Size each card
        cards.forEach(function (card) {
            card.style.setProperty('flex', '0 0 calc(20% - 10px)', 'important');
            card.style.setProperty('min-width', '200px', 'important');
            card.style.setProperty('max-width', 'none', 'important');
            card.style.setProperty('scroll-snap-align', 'start', 'important');

            // Fix Buy Now overlay - may already exist as .ths-card__overlay or .ths-card__quick-shop
            var imgWrapper = card.querySelector('.ths-card__image-wrapper');
            if (imgWrapper) {
                imgWrapper.style.position = 'relative';
                imgWrapper.style.overflow = 'hidden';

                var overlay = imgWrapper.querySelector('.ths-card__overlay, .ths-card__quick-shop');
                if (overlay) {
                    // Force correct positioning on existing overlay
                    overlay.style.cssText = 'position:absolute;bottom:0;left:10px;right:10px;width:calc(100% - 20px);background:rgba(0,0,0,0.75);color:#fff;border:none;border-radius:0;padding:10px;font-size:14px;font-weight:600;letter-spacing:0.5px;cursor:pointer;text-align:center;box-sizing:border-box;z-index:3;opacity:0;transform:translateY(100%);transition:opacity 0.3s ease,transform 0.3s ease;';

                    card.addEventListener('mouseenter', function () {
                        overlay.style.opacity = '1';
                        overlay.style.transform = 'translateY(0)';
                    });
                    card.addEventListener('mouseleave', function () {
                        overlay.style.opacity = '0';
                        overlay.style.transform = 'translateY(100%)';
                    });
                }
            }
        });

        // Create arrows
        var prevBtn = document.createElement('button');
        prevBtn.className = 'ths-carousel__arrow ths-carousel__arrow--prev';
        prevBtn.innerHTML = '&#8249;';
        prevBtn.setAttribute('aria-label', 'Previous products');

        var nextBtn = document.createElement('button');
        nextBtn.className = 'ths-carousel__arrow ths-carousel__arrow--next';
        nextBtn.innerHTML = '&#8250;';
        nextBtn.setAttribute('aria-label', 'Next products');

        prevBtn.addEventListener('click', function () {
            grid.scrollBy({ left: -(grid.offsetWidth * 0.8), behavior: 'smooth' });
        });

        nextBtn.addEventListener('click', function () {
            grid.scrollBy({ left: (grid.offsetWidth * 0.8), behavior: 'smooth' });
        });

        wrapper.appendChild(prevBtn);
        wrapper.appendChild(nextBtn);

        // Show/hide arrows
        function updateArrows() {
            var scrollLeft = Math.round(grid.scrollLeft);
            var maxScroll = grid.scrollWidth - grid.clientWidth;

            if (scrollLeft <= 5) {
                prevBtn.classList.add('ths-carousel__arrow--hidden');
            } else {
                prevBtn.classList.remove('ths-carousel__arrow--hidden');
            }

            if (maxScroll <= 5 || scrollLeft >= maxScroll - 5) {
                nextBtn.classList.add('ths-carousel__arrow--hidden');
            } else {
                nextBtn.classList.remove('ths-carousel__arrow--hidden');
            }
        }

        grid.addEventListener('scroll', updateArrows);
        window.addEventListener('resize', function () {
            var vw = window.innerWidth;
            var cardWidth;
            if (vw <= 480) {
                cardWidth = '100%';
            } else if (vw <= 767) {
                cardWidth = 'calc(50% - 6px)';
            } else if (vw <= 1024) {
                cardWidth = 'calc(33.33% - 8px)';
            } else if (vw <= 1200) {
                cardWidth = 'calc(25% - 9px)';
            } else {
                cardWidth = 'calc(20% - 10px)';
            }
            cards.forEach(function (card) {
                card.style.setProperty('flex', '0 0 ' + cardWidth, 'important');
            });
            updateArrows();
        });

        setTimeout(updateArrows, 100);
        setTimeout(updateArrows, 1000);
        setTimeout(updateArrows, 3000);

        return true;
    }

    function initAllCarousels() {
        var grids = document.querySelectorAll('.collection-grid__grid');
        var uninitCount = 0;

        grids.forEach(function (grid) {
            if (grid.dataset.carouselInit === 'true') return;

            var cards = grid.querySelectorAll('.ths-card');
            if (cards.length === 0) {
                uninitCount++;
                return;
            }

            initSingleCarousel(grid);
        });

        // If some grids don't have cards yet, retry
        if (uninitCount > 0 || grids.length === 0) {
            INIT_ATTEMPTS++;
            if (INIT_ATTEMPTS < MAX_ATTEMPTS) {
                setTimeout(initAllCarousels, 500);
            }
        }
    }

    // Start
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(initAllCarousels, 300);
        });
    } else {
        setTimeout(initAllCarousels, 300);
    }

})();