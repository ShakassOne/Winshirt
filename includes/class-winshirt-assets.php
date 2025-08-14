<?php
/**
 * WinShirt - Assets Management Unifié (SANS CONFLITS)
 * Version de récupération - 2025-01-14
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Assets' ) ) {

class WinShirt_Assets {

	private static $version   = '1.0.0';
	private static $base_url  = '';
	private static $base_path = '';

	public static function init() {
		if ( defined( 'WINSHIRT_VERSION' ) ) {
			self::$version = WINSHIRT_VERSION;
		}
		$plugin_main     = dirname( __DIR__ ) . '/winshirt.php';
		self::$base_url  = trailingslashit( plugins_url( '', $plugin_main ) );
		self::$base_path = plugin_dir_path( $plugin_main );

		add_action( 'init',               [ __CLASS__, 'register_assets' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'maybe_enqueue_front' ], 20 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin' ] );
	}

	private static function v( $rel ) {
		$path = self::$base_path . ltrim( $rel, '/' );
		return file_exists( $path ) ? self::$version . '.' . filemtime( $path ) : self::$version;
	}

	public static function register_assets() {
		// CSS unifié (SUPPRESSION DES CONFLITS)
		wp_register_style( 'winshirt-frontend', 
			self::$base_url . 'assets/css/frontend.css', 
			[], 
			self::v('assets/css/frontend.css') 
		);

		// JS unifié (DÉPENDANCES CORRECTES)
		wp_register_script( 'winshirt-customizer', 
			self::$base_url . 'assets/js/customizer.js', 
			[ 'jquery' ], 
			self::v('assets/js/customizer.js'), 
			true 
		);

		// Data localization
		wp_localize_script( 'winshirt-customizer', 'WinShirtData', self::front_data() );
	}

	public static function maybe_enqueue_front() {
		if ( ! self::should_load() ) return;

		// CSS
		wp_enqueue_style( 'winshirt-frontend' );

		// JS
		wp_enqueue_script( 'winshirt-customizer' );

		// Trigger pour ouvrir modal
		$openers = implode(',', [
			'.winshirt-customize-btn',
			'button[data-winshirt-open]',
			'[data-winshirt-open]'
		]);
		wp_add_inline_script( 'winshirt-customizer', 
			"jQuery(function($){ $('{$openers}').off('.ws').on('click.ws',function(e){e.preventDefault();WinShirtCustomizer.openModal();});});" 
		);
	}

	public static function enqueue_admin( $hook ) {
		// Assets admin pour mockups uniquement
		if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
			global $post;
			if ( isset( $post->post_type ) && 'ws-mockup' === $post->post_type ) {
				wp_enqueue_style( 'winshirt-admin-zones', 
					self::$base_url . 'assets/css/admin-zones.css', 
					[], 
					self::v('assets/css/admin-zones.css') 
				);
				wp_enqueue_script( 'winshirt-admin-zones', 
					self::$base_url . 'assets/js/admin-zones.js', 
					[], 
					self::v('assets/js/admin-zones.js'), 
					true 
				);
			}
		}
	}

	private static function should_load() {
		if ( apply_filters( 'winshirt_force_enqueue', false ) ) return true;
		if ( function_exists('is_product') && is_product() ) {
			return self::product_has_customization();
		}
		return false;
	}

	private static function product_has_customization() {
		global $product;
		if ( ! $product ) return false;
		return 'yes' === $product->get_meta( '_winshirt_enable' );
	}

	private static function detect_product_id() {
		if ( isset($_GET['product_id']) ) return absint($_GET['product_id']);
		if ( function_exists('is_product') && is_product() ) {
			$post_id = get_the_ID();
			return $post_id ? (int) $post_id : 0;
		}
		return 0;
	}

	private static function front_data() {
		$product_id = self::detect_product_id();
		$mockup_id = 0;
		$mockup_data = array();

		if ( $product_id ) {
			$mockup_id = (int) get_post_meta( $product_id, '_winshirt_mockup_id', true );
			if ( $mockup_id ) {
				// Essayer nouvelle structure unifiée en priorité
				$mockup_data = get_post_meta( $mockup_id, '_ws_mockup_data', true );
				
				// Fallback vers anciennes meta keys
				if ( ! $mockup_data ) {
					$front = get_post_meta( $mockup_id, '_winshirt_mockup_front', true );
					$back = get_post_meta( $mockup_id, '_winshirt_mockup_back', true );
					$zones_json = get_post_meta( $mockup_id, '_winshirt_zones', true );
					
					$mockup_data = array(
						'images' => array(
							'front' => $front ?: '',
							'back' => $back ?: ''
						),
						'zones' => json_decode( $zones_json, true ) ?: array( 'front' => array(), 'back' => array() )
					);
				}
			}
		}

		$base = [
			'version'     => self::$version,
			'ajaxUrl'     => admin_url('admin-ajax.php'),
			'restUrl'     => esc_url_raw( rest_url('winshirt/v1') ),
			'nonce'       => wp_create_nonce('winshirt_nonce'),
			'productId'   => $product_id,
			'mockupId'    => $mockup_id,
			'mockupData'  => $mockup_data
		];

		return apply_filters( 'winshirt_front_data', $base );
	}
}

WinShirt_Assets::init();
}
