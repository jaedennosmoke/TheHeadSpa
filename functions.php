<?php
/**
 * Theme functions and definitions - The Head Spa
 *
 * @package HelloElementorChild
 */
/**
 * Load child theme css and optional scripts
 */
function hello_elementor_child_enqueue_scripts() {
	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		'1.0.0'
	);
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts', 20 );
/**
 * Enqueue Shopify product card styles
 */
function headspace_shopify_styles() {
	wp_enqueue_style(
		'ths-shopify-styles',
		get_stylesheet_directory_uri() . '/shopify-styles.css',
		array(),
		'2.1.4'
	);
}
add_action( 'wp_enqueue_scripts', 'headspace_shopify_styles' );
/**
 * Enqueue wishlist functionality
 */
function headspace_wishlist_scripts() {
	wp_enqueue_script(
		'ths-wishlist',
		get_stylesheet_directory_uri() . '/js/wishlist.js',
		array( 'ths-auth' ),
		filemtime( get_stylesheet_directory() . '/js/wishlist.js' ),
		true
	);
	wp_localize_script( 'ths-wishlist', 'ths_ajax', array(
		'url' => admin_url( 'admin-ajax.php' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'headspace_wishlist_scripts' );
/**
 * Enqueue carousel functionality
 */
function headspace_carousel_scripts() {
	wp_enqueue_script(
		'ths-carousel',
		get_stylesheet_directory_uri() . '/js/carousel.js',
		array(),
		'3.0.0',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'headspace_carousel_scripts' );
/**
 * Force Shopify block assets to load on frontend
 */
function headspace_load_shopify_blocks() {
	if ( function_exists( 'register_block_type' ) ) {
		do_action( 'enqueue_block_assets' );
	}
}
add_action( 'wp_enqueue_scripts', 'headspace_load_shopify_blocks' );
/**
 * Custom shortcode to render Shopify collection blocks from Reusable Blocks
 * Usage: [shopify_embed id="390"]
 */
function ths_shopify_collection_shortcode($atts) {
	$atts = shortcode_atts(array(
		'id' => '',
	), $atts);
	if (empty($atts['id'])) return '';

	$block = get_post(intval($atts['id']));
	if (!$block || $block->post_status !== 'publish') return '';

	global $post;
	if ($post && $block->ID === $post->ID) return '';

	$content = $block->post_content;

	if (function_exists('do_blocks')) {
		return '<div class="ths-shopify-embed">' . do_blocks($content) . '</div>';
	}

	return '';
}
add_shortcode('shopify_embed', 'ths_shopify_collection_shortcode');
/**
 * Custom Shopify Cart Page — Storefront API
 * Loads JS + CSS only on the /your-cart page
 */
function headspace_cart_page_assets() {
	if ( ! is_page( 'your-cart' ) ) {
		return;
	}

	wp_enqueue_style(
		'ths-raleway-font',
		'https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'ths-cart-page',
		get_stylesheet_directory_uri() . '/css/cart-page.css',
		array(),
		filemtime( get_stylesheet_directory() . '/css/cart-page.css' )
	);

	wp_enqueue_script(
		'ths-cart-page',
		get_stylesheet_directory_uri() . '/js/cart-page.js',
		array(),
		filemtime( get_stylesheet_directory() . '/js/cart-page.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'headspace_cart_page_assets' );

/**
 * Shopify Search Results — Storefront API
 * Intercepts Elementor search form sitewide, loads results page on /search-results/
 */
function headspace_search_results_assets() {
	wp_enqueue_style(
		'ths-raleway-font',
		'https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'ths-search-results',
		get_stylesheet_directory_uri() . '/css/search-results.css',
		array(),
		filemtime( get_stylesheet_directory() . '/css/search-results.css' )
	);

	wp_enqueue_script(
		'ths-search-results',
		get_stylesheet_directory_uri() . '/js/search-results.js',
		array(),
		filemtime( get_stylesheet_directory() . '/js/search-results.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'headspace_search_results_assets' );

/**
 * Shopify Collection Pages — Storefront API
 * Routes /collections/{handle}/ to a single WP page template
 *
 * SETUP:
 * 1. Create a WP page titled "Collections" with slug "collections"
 * 2. Add an HTML widget in Elementor with: <div id="ths-collection"></div>
 * 3. Go to Settings > Permalinks > Save Changes (flushes rewrite rules)
 */

/* Helper: detect if we're on a collection page */
function ths_is_collection_page() {
	// Check URL path first (works even when is_page fails with rewrites)
	$path = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
	if ( strpos( $path, 'collections/' ) === 0 || $path === 'collections' ) {
		return true;
	}
	// Fallback to WP detection
	if ( is_page( 'collections' ) ) {
		return true;
	}
	// Check query var
	if ( get_query_var( 'collection_handle' ) ) {
		return true;
	}
	return false;
}

/* Rewrite /our-collections/{handle}/ to the our-collections page */
add_action('init', function() {
	add_rewrite_rule(
		'^our-collections/([^/]+)/?$',
		'index.php?pagename=our-collections&collection_handle=$matches[1]',
		'top'
	);
});

add_filter('query_vars', function($vars) {
	$vars[] = 'collection_handle';
	return $vars;
});

/* Enqueue collection page assets — loads everywhere, JS self-detects via #ths-collection */
function headspace_collection_page_assets() {
	wp_enqueue_style(
		'ths-raleway-font',
		'https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'ths-collection-page',
		get_stylesheet_directory_uri() . '/css/collection-page.css',
		array(),
		'2.0'
	);

	wp_enqueue_script(
		'ths-collection-page',
		get_stylesheet_directory_uri() . '/js/collection-page.js',
		array(),
		'2.0',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'headspace_collection_page_assets' );

/* Pass collection handle from URL to the JS container */
function headspace_collection_handle_script() {
	// Parse handle from URL path
	$path = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
	$handle = '';
	if ( preg_match( '#^our-collections/([^/]+)#', $path, $m ) ) {
		$handle = $m[1];
	}
	if ( ! $handle ) {
		$handle = get_query_var( 'collection_handle', '' );
	}

	if ( $handle ) {
		?>
		<script>
		(function() {
			var el = document.getElementById('ths-collection');
			if (el && !el.getAttribute('data-handle')) {
				el.setAttribute('data-handle', <?php echo wp_json_encode( sanitize_text_field( $handle ) ); ?>);
			}
		})();
		</script>
		<?php
	}
}
add_action( 'wp_footer', 'headspace_collection_handle_script' );

/**
 * Shopify cart trigger + item count badge for Elementor header icon
 * Navigates to /your-cart page on click
 * Add CSS class "shopify-cart-trigger" to the Icon widget in Elementor (Advanced > CSS Classes)
 */
function headspace_cart_trigger_script() {
	?>
	<script>
	(function() {
		'use strict';

		var ATTEMPTS = 0;
		var MAX_ATTEMPTS = 30;

		function init() {
			var trigger = document.querySelector('.shopify-cart-trigger');
			if (!trigger) return;

			trigger.style.cursor = 'pointer';

			// Find the icon element inside the widget
			var iconWrap = trigger.querySelector('.elementor-icon');
			if (!iconWrap) {
				iconWrap = trigger;
			}
			iconWrap.style.position = 'relative';

			// Create the count badge
			var badge = document.createElement('span');
			badge.className = 'ths-cart-badge';
			badge.textContent = '0';
			badge.style.display = 'none';
			iconWrap.appendChild(badge);

			// Navigate to cart page on click
			trigger.addEventListener('click', function(e) {
				e.preventDefault();
				window.location.href = '/your-cart';
			});

			// Update badge count from cart contents
			function updateBadge() {
				var cart = document.getElementById('cart');
				if (!cart) return;

				var root = cart.shadowRoot || cart;

				var qtyLabels = root.querySelectorAll('[data-testid="quantity-label"]');
				var totalCount = 0;

				if (qtyLabels.length > 0) {
					qtyLabels.forEach(function(label) {
						var val = parseInt(label.textContent.trim(), 10);
						if (!isNaN(val) && val > 0) totalCount += val;
					});
				} else {
					var lineItems = root.querySelectorAll('.line-item-container');
					totalCount = lineItems.length;
				}

				if (totalCount > 0) {
					badge.textContent = totalCount > 99 ? '99+' : totalCount;
					badge.style.display = 'flex';
				} else {
					badge.style.display = 'none';
				}
			}

			// Watch the cart element for DOM changes
			function watchCart() {
				var cart = document.getElementById('cart');
				if (!cart) {
					ATTEMPTS++;
					if (ATTEMPTS < MAX_ATTEMPTS) {
						setTimeout(watchCart, 500);
					}
					return;
				}

				var root = cart.shadowRoot || cart;

				updateBadge();

				var observer = new MutationObserver(function() {
					updateBadge();
				});

				observer.observe(root, {
					childList: true,
					subtree: true,
					characterData: true,
					attributes: true
				});

				if (cart.shadowRoot) {
					var hostObserver = new MutationObserver(function() {
						updateBadge();
					});
					hostObserver.observe(cart, {
						childList: true,
						subtree: true,
						attributes: true
					});
				}
			}

			watchCart();
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', function() {
				setTimeout(init, 300);
			});
		} else {
			setTimeout(init, 300);
		}
	})();
	</script>
	<?php
}
add_action('wp_footer', 'headspace_cart_trigger_script');

/**
 * Store Info Popup — hover on "My Store" header icon
 * Shows store details, live open/closed status, hours, and services
 */
function headspace_store_popup() {
	?>
	<style>
	.ths-store-trigger{position:relative}
	.ths-store-trigger,
	.ths-store-trigger *,
	[data-id="af5addf"],
	[data-id="af5addf"] *,
	[data-id="af5addf"].e-con,
	.elementor-element:has([data-id="af5addf"]),
	.elementor-section:has([data-id="af5addf"]),
	.e-con:has([data-id="af5addf"]),
	header,
	header .elementor-element,
	header .e-con{overflow:visible !important}
	.ths-store-popup{position:absolute;top:100%;left:50%;transform:translateX(-50%) translateY(10px);margin-top:10px;width:320px;background:#fff;border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,.15);padding:22px 24px;z-index:99999;font-family:'Raleway',sans-serif;opacity:0;visibility:hidden;transition:opacity .25s ease,visibility .25s ease,transform .25s ease;pointer-events:none;box-sizing:border-box}
	.ths-store-popup::before{content:'';position:absolute;top:-6px;left:50%;transform:translateX(-50%) rotate(45deg);width:12px;height:12px;background:#fff;box-shadow:-2px -2px 4px rgba(0,0,0,.04)}
	.ths-store-trigger::after{content:'';position:absolute;bottom:-12px;left:0;width:100%;height:12px;opacity:0;visibility:hidden}
	.ths-store-trigger:hover::after{opacity:1;visibility:visible}
	.ths-store-trigger:hover .ths-store-popup,.ths-store-popup:hover{opacity:1;visibility:visible;pointer-events:auto;transform:translateX(-50%) translateY(0)}
	.ths-store-popup *{box-sizing:border-box;margin:0;padding:0}
	.ths-sp-name{font-size:16px;font-weight:800;color:#111;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px}
	.ths-sp-address{font-size:13px;color:#555;line-height:1.4;margin-bottom:4px}
	.ths-sp-phone a{font-size:13px;color:#439E9E;text-decoration:none;font-weight:600}
	.ths-sp-phone a:hover{text-decoration:underline}
	.ths-sp-status{margin:12px 0;padding:10px 0;border-top:1px solid #eee;border-bottom:1px solid #eee}
	.ths-sp-status-line{font-size:13px;font-weight:600}
	.ths-sp-open{color:#2e9e5a}
	.ths-sp-closed{color:#d44}
	.ths-sp-hours-detail{font-size:12px;color:#777;margin-top:2px}
	.ths-sp-services{display:flex;flex-wrap:wrap;gap:6px 12px;margin-top:12px}
	.ths-sp-service{font-size:12px;color:#333;display:flex;align-items:center;gap:5px;white-space:nowrap}
	.ths-sp-check{color:#2e9e5a;font-size:14px;font-weight:700;line-height:1}
	</style>
	<script>
	(function(){
		'use strict';
		var STORE={
			name:'The Head Spa',
			address:'8335 Westchester Drive',
			city:'Dallas, Texas 75225',
			phone:'469-660-8187',
			hours:[
				{days:'Monday',open:'8:00 AM',close:'5:00 PM'},
				{days:'Tuesday',open:'8:00 AM',close:'5:00 PM'},
				{days:'Wednesday',open:null,close:null},
				{days:'Thursday',open:'9:00 AM',close:'5:00 PM'},
				{days:'Friday',open:'9:00 AM',close:'5:00 PM'},
				{days:'Saturday',open:'9:00 AM',close:'5:00 PM'},
				{days:'Sunday',open:null,close:null}
			],
			services:['In-Store Pickup','Free Consultations','Blow Outs','Dry Bar']
		};
		function getStatus(){
			var now=new Date(),dayIndex=now.getDay(),mappedIndex=dayIndex===0?6:dayIndex-1;
			var today=STORE.hours[mappedIndex];
			if(!today.open)return{isOpen:false,text:'Closed today',detail:''};
			var op=parseTime(today.open),cl=parseTime(today.close);
			var nowMins=now.getHours()*60+now.getMinutes(),openMins=op[0]*60+op[1],closeMins=cl[0]*60+cl[1];
			if(nowMins>=openMins&&nowMins<closeMins){
				var rem=closeMins-nowMins,h=Math.floor(rem/60),m=rem%60;
				var closeStr=h>0?h+' hr '+m+' min':m+' min';
				return{isOpen:true,text:'Open until '+today.close+' today',detail:'Closes in '+closeStr};
			}else if(nowMins<openMins){
				return{isOpen:false,text:'Closed now',detail:'Opens at '+today.open};
			}
			return{isOpen:false,text:'Closed now',detail:''};
		}
		function parseTime(str){
			var parts=str.split(' '),tm=parts[0].split(':'),h=parseInt(tm[0]),m=parseInt(tm[1]);
			if(parts[1]==='PM'&&h!==12)h+=12;if(parts[1]==='AM'&&h===12)h=0;
			return[h,m];
		}
		function init(){
			var trigger=document.querySelector('[data-id="af5addf"]');
			if(!trigger)return;
			trigger.classList.add('ths-store-trigger');
			var status=getStatus();
			var statusClass=status.isOpen?'ths-sp-open':'ths-sp-closed';
			var popup=document.createElement('div');
			popup.className='ths-store-popup';
			popup.innerHTML=
				'<div class="ths-sp-name">'+STORE.name+'</div>'+
				'<div class="ths-sp-address">'+STORE.address+'<br>'+STORE.city+'</div>'+
				'<div class="ths-sp-phone"><a href="tel:'+STORE.phone+'">'+STORE.phone+'</a></div>'+
				'<div class="ths-sp-status">'+
					'<div class="ths-sp-status-line '+statusClass+'">'+status.text+'</div>'+
					(status.detail?'<div class="ths-sp-hours-detail">'+status.detail+'</div>':'')+
				'</div>'+
				'<div class="ths-sp-services">'+
					STORE.services.map(function(s){return'<span class="ths-sp-service"><span class="ths-sp-check">\u2713</span>'+s+'</span>';}).join('')+
				'</div>';
			trigger.appendChild(popup);
			var ancestors=[];
			var el=trigger.parentElement;
			while(el&&el!==document.body){ancestors.push(el);el=el.parentElement;}
			trigger.addEventListener('mouseenter',function(){
				ancestors.forEach(function(a){a.style.setProperty('overflow','visible','important');});
			});
			trigger.addEventListener('mouseleave',function(){
				setTimeout(function(){
					if(!popup.matches(':hover')&&!trigger.matches(':hover')){
						ancestors.forEach(function(a){a.style.removeProperty('overflow');});
					}
				},100);
			});
			popup.addEventListener('mouseleave',function(){
				setTimeout(function(){
					if(!popup.matches(':hover')&&!trigger.matches(':hover')){
						ancestors.forEach(function(a){a.style.removeProperty('overflow');});
					}
				},100);
			});
		}
		if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',init);}else{init();}
	})();
	</script>
	<?php
}
add_action('wp_footer', 'headspace_store_popup');

/**
 * Wishlist/Favorites Page — Storefront API
 * Loads JS + CSS only on the /favorites page
 */
function headspace_wishlist_page_assets() {
	if ( ! is_page( 'favorites' ) ) {
		return;
	}

	wp_enqueue_style(
		'ths-raleway-font',
		'https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'ths-wishlist-page',
		get_stylesheet_directory_uri() . '/css/wishlist-page.css',
		array(),
		filemtime( get_stylesheet_directory() . '/css/wishlist-page.css' )
	);

	wp_enqueue_script(
		'ths-wishlist-page',
		get_stylesheet_directory_uri() . '/js/wishlist-page.js',
		array(),
		filemtime( get_stylesheet_directory() . '/js/wishlist-page.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'headspace_wishlist_page_assets' );

/**
 * Auth — Shared auth module (loads sitewide, lightweight)
 */
function headspace_auth_assets() {
	wp_enqueue_script(
		'ths-auth',
		get_stylesheet_directory_uri() . '/js/auth.js',
		array(),
		filemtime( get_stylesheet_directory() . '/js/auth.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'headspace_auth_assets' );

/**
 * Auth — Login page
 */
function headspace_login_page_assets() {
	if ( ! is_page( 'login' ) ) return;

	wp_enqueue_style(
		'ths-auth-css',
		get_stylesheet_directory_uri() . '/css/auth.css',
		array(),
		filemtime( get_stylesheet_directory() . '/css/auth.css' )
	);

	wp_enqueue_script(
		'ths-login-page',
		get_stylesheet_directory_uri() . '/js/login-page.js',
		array( 'ths-auth' ),
		filemtime( get_stylesheet_directory() . '/js/login-page.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'headspace_login_page_assets' );

/**
 * Auth — Register page
 */
function headspace_register_page_assets() {
	if ( ! is_page( 'register' ) ) return;

	wp_enqueue_style(
		'ths-auth-css',
		get_stylesheet_directory_uri() . '/css/auth.css',
		array(),
		filemtime( get_stylesheet_directory() . '/css/auth.css' )
	);

	wp_enqueue_script(
		'ths-register-page',
		get_stylesheet_directory_uri() . '/js/register-page.js',
		array( 'ths-auth' ),
		filemtime( get_stylesheet_directory() . '/js/register-page.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'headspace_register_page_assets' );

/**
 * Auth — Reset Password page
 */
function headspace_reset_password_page_assets() {
	if ( ! is_page( 'reset-password' ) ) return;

	wp_enqueue_style(
		'ths-auth-css',
		get_stylesheet_directory_uri() . '/css/auth.css',
		array(),
		filemtime( get_stylesheet_directory() . '/css/auth.css' )
	);

	wp_enqueue_script(
		'ths-reset-password-page',
		get_stylesheet_directory_uri() . '/js/reset-password-page.js',
		array( 'ths-auth' ),
		filemtime( get_stylesheet_directory() . '/js/reset-password-page.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'headspace_reset_password_page_assets' );

/**
 * Auth — Set New Password page
 */
function headspace_set_new_password_page_assets() {
	if ( ! is_page( 'set-new-password' ) ) return;

	wp_enqueue_style(
		'ths-auth-css',
		get_stylesheet_directory_uri() . '/css/auth.css',
		array(),
		filemtime( get_stylesheet_directory() . '/css/auth.css' )
	);

	wp_enqueue_script(
		'ths-set-new-password-page',
		get_stylesheet_directory_uri() . '/js/set-new-password-page.js',
		array( 'ths-auth' ),
		filemtime( get_stylesheet_directory() . '/js/set-new-password-page.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'headspace_set_new_password_page_assets' );

/**
 * Auth — My Account page
 */
function headspace_account_page_assets() {
	if ( ! is_page( 'my-account' ) ) return;

	wp_enqueue_style(
		'ths-auth-css',
		get_stylesheet_directory_uri() . '/css/auth.css',
		array(),
		filemtime( get_stylesheet_directory() . '/css/auth.css' )
	);

	wp_enqueue_style(
		'ths-account-page',
		get_stylesheet_directory_uri() . '/css/account-page.css',
		array(),
		filemtime( get_stylesheet_directory() . '/css/account-page.css' )
	);

	wp_enqueue_script(
		'ths-account-page',
		get_stylesheet_directory_uri() . '/js/account-page.js',
		array( 'ths-auth' ),
		filemtime( get_stylesheet_directory() . '/js/account-page.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'headspace_account_page_assets' );

/**
 * ─── Favorites Sync — Shopify Customer Metafields ───
 *
 * IMPORTANT: Add this line to wp-config.php (above "That's all, stop editing!"):
 * define('THS_ADMIN_TOKEN', 'shpat_your_admin_api_token_here');
 */

/* Verify Shopify customer token → returns numeric customer ID or false */
function ths_verify_customer_token( $storefront_token ) {
	$api_url = 'https://theheadspa.myshopify.com/api/2025-01/graphql.json';
	$query   = '{ customer(customerAccessToken: "' . esc_attr( $storefront_token ) . '") { id } }';

	$response = wp_remote_post( $api_url, array(
		'headers' => array(
			'Content-Type'                      => 'application/json',
			'X-Shopify-Storefront-Access-Token' => '4617f01063d6f1b7503e71a499b36c43',
		),
		'body'    => wp_json_encode( array( 'query' => $query ) ),
		'timeout' => 10,
	) );

	if ( is_wp_error( $response ) ) return false;

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $body['data']['customer']['id'] ) ) return false;

	$gid = $body['data']['customer']['id'];
	preg_match( '/(\d+)$/', $gid, $matches );
	return isset( $matches[1] ) ? $matches[1] : false;
}

/* AJAX: Get favorites from Shopify metafield */
function ths_ajax_get_favorites() {
	$token = sanitize_text_field( isset( $_POST['customer_token'] ) ? $_POST['customer_token'] : '' );
	if ( ! $token ) { wp_send_json_error( 'No token', 401 ); }

	$customer_id = ths_verify_customer_token( $token );
	if ( ! $customer_id ) { wp_send_json_error( 'Invalid token', 401 ); }

	$admin_token = defined( 'THS_ADMIN_TOKEN' ) ? THS_ADMIN_TOKEN : '';
	if ( ! $admin_token ) { wp_send_json_error( 'Server config error', 500 ); }

	$api_url  = 'https://theheadspa.myshopify.com/admin/api/2025-01/customers/' . $customer_id . '/metafields.json?namespace=custom&key=favorites';
	$response = wp_remote_get( $api_url, array(
		'headers' => array(
			'X-Shopify-Access-Token' => $admin_token,
			'Content-Type'           => 'application/json',
		),
		'timeout' => 10,
	) );

	if ( is_wp_error( $response ) ) { wp_send_json_error( 'API error', 500 ); }

	$body       = json_decode( wp_remote_retrieve_body( $response ), true );
	$metafields = isset( $body['metafields'] ) ? $body['metafields'] : array();
	$favorites  = array();

	foreach ( $metafields as $mf ) {
		if ( $mf['key'] === 'favorites' && $mf['namespace'] === 'custom' ) {
			$favorites = json_decode( $mf['value'], true );
			if ( ! is_array( $favorites ) ) $favorites = array();
			break;
		}
	}

	wp_send_json_success( $favorites );
}
add_action( 'wp_ajax_ths_get_favorites', 'ths_ajax_get_favorites' );
add_action( 'wp_ajax_nopriv_ths_get_favorites', 'ths_ajax_get_favorites' );

/* AJAX: Save favorites to Shopify metafield */
function ths_ajax_save_favorites() {
	$token     = sanitize_text_field( isset( $_POST['customer_token'] ) ? $_POST['customer_token'] : '' );
	$raw_favs  = isset( $_POST['favorites'] ) ? stripslashes( $_POST['favorites'] ) : '[]';
	$favorites = json_decode( $raw_favs, true );

	if ( ! $token ) { wp_send_json_error( 'No token', 401 ); }
	if ( ! is_array( $favorites ) ) { $favorites = array(); }

	$favorites = array_values( array_unique( array_map( 'sanitize_text_field', $favorites ) ) );

	$customer_id = ths_verify_customer_token( $token );
	if ( ! $customer_id ) { wp_send_json_error( 'Invalid token', 401 ); }

	$admin_token = defined( 'THS_ADMIN_TOKEN' ) ? THS_ADMIN_TOKEN : '';
	if ( ! $admin_token ) { wp_send_json_error( 'Server config error', 500 ); }

	$api_url  = 'https://theheadspa.myshopify.com/admin/api/2025-01/graphql.json';
	$mutation = 'mutation { customerUpdate(input: { id: "gid://shopify/Customer/' . $customer_id . '", metafields: [{ namespace: "custom", key: "favorites", type: "json", value: ' . wp_json_encode( wp_json_encode( $favorites ) ) . ' }] }) { customer { id } userErrors { field message } } }';

	$response = wp_remote_post( $api_url, array(
		'headers' => array(
			'X-Shopify-Access-Token' => $admin_token,
			'Content-Type'           => 'application/json',
		),
		'body'    => wp_json_encode( array( 'query' => $mutation ) ),
		'timeout' => 10,
	) );

	if ( is_wp_error( $response ) ) { wp_send_json_error( 'API error', 500 ); }

	$body   = json_decode( wp_remote_retrieve_body( $response ), true );
	$errors = isset( $body['data']['customerUpdate']['userErrors'] ) ? $body['data']['customerUpdate']['userErrors'] : array();

	if ( ! empty( $errors ) ) {
		wp_send_json_error( $errors[0]['message'], 400 );
	}

	wp_send_json_success( $favorites );
}
add_action( 'wp_ajax_ths_save_favorites', 'ths_ajax_save_favorites' );
add_action( 'wp_ajax_nopriv_ths_save_favorites', 'ths_ajax_save_favorites' );

/**
 * ─── Judge.me Review Submission Proxy ───
 *
 * Add to wp-config.php:
 * define('THS_JUDGEME_PRIVATE_TOKEN', 'NKrvochv4Owr-f7VgKhMBoGQpJE');
 */
function ths_ajax_submit_review() {
	$jm_token = defined( 'THS_JUDGEME_PRIVATE_TOKEN' ) ? THS_JUDGEME_PRIVATE_TOKEN : '';
	if ( ! $jm_token ) { wp_send_json_error( 'Server config error', 500 ); }

	$name       = sanitize_text_field( isset( $_POST['name'] ) ? $_POST['name'] : '' );
	$email      = sanitize_email( isset( $_POST['email'] ) ? $_POST['email'] : '' );
	$rating     = intval( isset( $_POST['rating'] ) ? $_POST['rating'] : 0 );
	$title      = sanitize_text_field( isset( $_POST['title'] ) ? $_POST['title'] : '' );
	$body       = sanitize_textarea_field( isset( $_POST['body'] ) ? $_POST['body'] : '' );
	$product_id = sanitize_text_field( isset( $_POST['product_id'] ) ? $_POST['product_id'] : '' );

	if ( ! $name || ! $email || ! $rating || ! $product_id ) {
		wp_send_json_error( 'Missing required fields', 400 );
	}

	$response = wp_remote_post( 'https://judge.me/api/v1/reviews', array(
		'headers' => array( 'Content-Type' => 'application/json' ),
		'body'    => wp_json_encode( array(
			'shop_domain' => 'theheadspa.myshopify.com',
			'api_token'   => $jm_token,
			'platform'    => 'shopify',
			'id'          => $product_id,
			'name'        => $name,
			'email'       => $email,
			'rating'      => $rating,
			'title'       => $title,
			'body'        => $body,
		) ),
		'timeout' => 15,
	) );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( 'API error: ' . $response->get_error_message(), 500 );
	}

	$status = wp_remote_retrieve_response_code( $response );
	$result = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $status >= 200 && $status < 300 ) {
		wp_send_json_success( $result );
	} else {
		wp_send_json_error( isset( $result['error'] ) ? $result['error'] : 'Submission failed', $status );
	}
}
add_action( 'wp_ajax_ths_submit_review', 'ths_ajax_submit_review' );

/* ─── Mobile Sticky Footer Nav ─── */
add_action('wp_footer', function () {
?>
<nav id="ths-mobile-nav" aria-label="Mobile navigation">
  <a href="/" class="ths-mnav__item">
    <svg width="22" height="22" viewBox="0 0 21.88 24.18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M10.92,0l10.96,8.66v13.72c0,.99-.81,1.8-1.8,1.8h-6.05v-11.5h-6.18v11.46H1.8c-.99,0-1.8-.81-1.8-1.8v-13.72L10.92,0Z"/></svg>
    <span>Home</span>
  </a>
  <a href="javascript:void(0)" class="ths-mnav__item" id="ths-shop-trigger">
    <svg width="22" height="22" viewBox="0 0 17.19 20.08" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><polygon points="2.8 0 14.62 0 16.47 2.89 .72 2.89 2.8 0"/><path d="M0,4.38l.04,13.9c0,.99.81,1.79,1.8,1.79h13.55c.99,0,1.8-.81,1.8-1.8V4.38H0ZM8.59,10.79c-1.85,0-3.71-1.29-3.71-3.75,0-.28.22-.5.5-.5s.5.22.5.5c0,1.89,1.41,2.75,2.71,2.75s2.71-.86,2.71-2.75c0-.28.22-.5.5-.5s.5.22.5.5c0,2.46-1.87,3.75-3.71,3.75Z"/></svg>
    <span>Shop</span>
  </a>
  <a href="/our-collections/sales-and-offers/" class="ths-mnav__item">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M11.707 1.293A1 1 0 0 0 11 1H2a1 1 0 0 0-1 1v9a1 1 0 0 0 .293.707l9.5 9.5a1 1 0 0 0 1.414 0l9-9a1 1 0 0 0 0-1.414l-9.5-9.5ZM7 8a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z"/></svg>
    <span>Offers</span>
  </a>
  <a href="/services-and-memberships/" class="ths-mnav__item">
    <svg width="22" height="22" viewBox="0 0 39.69 40.99" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><polygon points="39.69 2.07 39.69 4.13 25.81 18.44 21.86 14.32 33.74 2.07 39.69 2.07"/><path d="M39.69,38.92h-5.95c-4.63-4.76-9.23-9.55-13.87-14.29l-4.64,4.75c.08.33.25.64.35.97,1.7,5.76-3.08,11.62-9.13,10.49-2.88-.54-5.36-2.93-6.13-5.74-1.53-5.58,2.69-11.04,8.55-10.48.82.08,1.63.29,2.35.68l4.64-4.8-4.61-4.79s-.59.25-.71.3c-2.01.75-4.65.45-6.49-.64C-.49,12.67-1.38,6.38,2.23,2.52,6.03-1.53,12.34-.52,14.94,4.29c1.04,1.93,1.26,4.67.51,6.73-.05.13-.26.51-.22.6l24.47,25.25v2.06ZM11.99,8.21c0-2.23-1.81-4.04-4.04-4.04s-4.04,1.81-4.04,4.04,1.81,4.04,4.04,4.04,4.04-1.81,4.04-4.04ZM20.86,20.5c0-.56-.45-1.01-1.01-1.01s-1.01.45-1.01,1.01.45,1.01,1.01,1.01,1.01-.45,1.01-1.01ZM11.98,32.79c0-2.23-1.81-4.04-4.04-4.04s-4.04,1.81-4.04,4.04,1.81,4.04,4.04,4.04,4.04-1.81,4.04-4.04Z"/></svg>
    <span>Services</span>
  </a>
  <a href="/login/" class="ths-mnav__item mobile-account-trigger">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    <span>Me</span>
  </a>
</nav>

<!-- Shop Collections Overlay -->
<div id="ths-shop-overlay" class="ths-shop-modal__overlay"></div>

<!-- Shop Collections Modal -->
<div id="ths-shop-modal" class="ths-shop-modal">
  <div class="ths-shop-modal__panel">
    <div class="ths-shop-modal__header">
      <span></span>
      <h3>Shop</h3>
      <button class="ths-shop-modal__close" aria-label="Close">&times;</button>
    </div>
    <div class="ths-shop-modal__grid">
      <a href="/our-collections/new/" class="ths-shop-modal__btn">New</a>
      <a href="/our-collections/hair-care/" class="ths-shop-modal__btn">Hair Care</a>
      <a href="/our-collections/scalp-care/" class="ths-shop-modal__btn">Scalp Care</a>
      <a href="/our-collections/face-wash/" class="ths-shop-modal__btn">Face Wash</a>
      <a href="/our-collections/shampoo/" class="ths-shop-modal__btn">Shampoo</a>
      <a href="/our-collections/conditioner/" class="ths-shop-modal__btn">Conditioner</a>
      <a href="/our-collections/serums/" class="ths-shop-modal__btn">Serums</a>
      <a href="/our-collections/tools/" class="ths-shop-modal__btn">Tools</a>
      <a href="/our-collections/lotion/" class="ths-shop-modal__btn">Lotion</a>
      <a href="/our-collections/sales-offers/" class="ths-shop-modal__btn">Sales &amp; Offers</a>
    </div>
  </div>
</div>

<style>
  #ths-mobile-nav {
    display: none;
    position: fixed;
    bottom: 0; left: 0; right: 0;
    background: #fff;
    border-top: 1px solid #e0e0e0;
    z-index: 9999;
    justify-content: space-around;
    align-items: center;
    padding: 18px 0 env(safe-area-inset-bottom, 30px);
    box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
  }
  @media (max-width: 767px) {
    #ths-mobile-nav { display: flex; }
    body { padding-bottom: 112px; }
  }
  .ths-mnav__item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    text-decoration: none !important;
    color: #000 !important;
    font-size: 11px;
    font-family: inherit;
    padding: 8px 14px;
    transition: color 0.2s;
  }
  .ths-mnav__item:hover,
  .ths-mnav__item.active {
    color: #5DA8A8 !important;
  }
  .ths-mnav__item svg { stroke: currentColor !important; }
  .ths-mnav__item svg[fill="currentColor"] { stroke: none !important; fill: currentColor !important; }

  /* Shop Modal */
  .ths-shop-modal {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 9998; /* behind the nav (9999) */
    transform: translateY(100%);
    transition: transform 0.35s ease;
    pointer-events: none;
  }
  .ths-shop-modal.open {
    transform: translateY(0);
    pointer-events: auto;
  }
  .ths-shop-modal__overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.3);
    opacity: 0;
    transition: opacity 0.35s ease;
    z-index: 9997;
    pointer-events: none;
  }
  .ths-shop-modal__overlay.open {
    opacity: 1;
    pointer-events: auto;
  }
  .ths-shop-modal__panel {
    background: #fff;
    width: 100%;
    border-radius: 16px 16px 0 0;
    padding: 18px 16px 110px;
    max-height: 75vh;
    overflow-y: auto;
  }
  .ths-shop-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 0 14px;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 16px;
  }
  .ths-shop-modal__header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #000;
  }
  .ths-shop-modal__header span { width: 32px; }
  .ths-shop-modal__close {
    background: none;
    border: none;
    font-size: 28px;
    color: #999;
    cursor: pointer;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
  }
  .ths-shop-modal__grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }
  .ths-shop-modal__btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px 12px;
    border: 1px solid #ddd;
    border-radius: 12px;
    background: #fff;
    color: #000;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
    transition: background 0.2s, border-color 0.2s;
  }
  .ths-shop-modal__btn:hover {
    background: #f5f5f5;
    border-color: #bbb;
    color: #000;
  }
</style>
<script>
(function(){
  /* Active tab detection */
  var path = window.location.pathname;
  var items = document.querySelectorAll('.ths-mnav__item');
  items.forEach(function(item){
    var href = item.getAttribute('href');
    if (!href || href === 'javascript:void(0)') return;
    if (href === '/' && path === '/') item.classList.add('active');
    else if (href !== '/' && path.indexOf(href) === 0) item.classList.add('active');
  });

  /* Shop on collection pages → highlight Shop tab */
  if (path.indexOf('/our-collections') === 0) {
    var shopTrigger = document.getElementById('ths-shop-trigger');
    if (shopTrigger) shopTrigger.classList.add('active');
  }

  /* Shop modal open/close */
  var modal = document.getElementById('ths-shop-modal');
  var overlay = document.getElementById('ths-shop-overlay');
  var trigger = document.getElementById('ths-shop-trigger');
  if (trigger && modal && overlay) {
    function openShop() { modal.classList.add('open'); overlay.classList.add('open'); }
    function closeShop() { modal.classList.remove('open'); overlay.classList.remove('open'); }
    trigger.addEventListener('click', function(e){
      e.preventDefault();
      if (modal.classList.contains('open')) closeShop();
      else openShop();
    });
    modal.querySelector('.ths-shop-modal__close').addEventListener('click', closeShop);
    overlay.addEventListener('click', closeShop);
  }
})();
</script>
<?php
});
add_action( 'wp_ajax_nopriv_ths_submit_review', 'ths_ajax_submit_review' );