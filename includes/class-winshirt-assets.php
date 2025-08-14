<?php
/**
 * WinShirt - Enqueue & Front Data
 * - Unifie le chargement CSS/JS (supprime les conflits)
 * - Injecte WinShirtData (produit, mockups, zones, couleurs…)
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
	}

	private static function v( $rel ) {
		$path = self::$base_path . ltrim( $rel, '/' );
		return file_exists( $path ) ? self::$version . '.' . filemtime( $path ) : self::$version;
	}

	public static function register_assets() {
		// CSS — garder uniquement les feuilles utiles (évite les conflits)
		wp_register_style( 'winshirt-modal',   self::$base_url . 'assets/css/winshirt-modal.css',   [], self::v('assets/css/winshirt-modal.css') );
		wp_register_style( 'winshirt-panels',  self::$base_url . 'assets/css/winshirt-panels.css',  [], self::v('assets/css/winshirt-panels.css') );
		wp_register_style( 'winshirt-layers',  self::$base_url . 'assets/css/winshirt-layers.css',  [], self::v('assets/css/winshirt-layers.css') );
		// ⚠️ ne pas charger winshirt-helpers.css ni anciens styles conflictuels

		$jq = [ 'jquery' ];

		// State de base
		wp_register_script( 'winshirt-state', self::$base_url . 'assets/js/state.js', $jq, self::v('assets/js/state.js'), true );

		// UI simple (pas le router complexe)
		wp_register_script( 'winshirt-ui-panels', self::$base_url . 'assets/js/ui-panels.js', [ 'winshirt-state', 'jquery' ], self::v('assets/js/ui-panels.js'), true );

		// NOUVEAU : moteur mockup + zones (front)
		wp_register_script( 'winshirt-mockup-canvas', self::$base_url . 'assets/js/mockup-canvas.js', [ 'winshirt-state', 'jquery' ], self::v('assets/js/mockup-canvas.js'), true );

		// Gestionnaire de calques (déplacement / redim / ordre)
		// (peut remplacer l’ancien layers.js)
		wp_register_script( 'winshirt-layers', self::$base_url . 'assets/js/layer-manager.js', [ 'winshirt-state', 'jquery' ], self::v('assets/js/layer-manager.js'), true );

		// Outils
		wp_register_script( 'winshirt-image-tools', self::$base_url . 'assets/js/image-tools.js', [ 'winshirt-layers', 'jquery' ], self::v('assets/js/image-tools.js'), true );
		wp_register_script( 'winshirt-text-tools',  self::$base_url . 'assets/js/text-tools.js',  [ 'winshirt-layers', 'jquery' ], self::v('assets/js/text-tools.js'),  true );

		// Glue de la modale
		wp_register_script( 'winshirt-modal', self::$base_url . 'assets/js/winshirt-modal.js',
			[ 'winshirt-state', 'winshirt-ui-panels', 'winshirt-mockup-canvas', 'winshirt-layers', 'winshirt-image-tools', 'winshirt-text-tools' ],
			self::v('assets/js/winshirt-modal.js'), true
		);

		// Données
		wp_localize_script( 'winshirt-state', 'WinShirtData', self::front_data() );
	}

	private static function should_load() {
		if ( apply_filters( 'winshirt_force_enqueue', false ) ) return true;
		if ( function_exists('is_product') && is_product() ) return true;
		if ( function_exists('is_page')    && is_page('personnalisez') ) return true;
		return apply_filters( 'winshirt_should_enqueue', false );
	}

	public static function maybe_enqueue_front() {
		if ( ! self::should_load() ) return;

		// CSS
		wp_enqueue_style( 'winshirt-modal' );
		wp_enqueue_style( 'winshirt-panels' );
		wp_enqueue_style( 'winshirt-layers' );

		// JS
		wp_enqueue_script( 'winshirt-state' );
		wp_enqueue_script( 'winshirt-ui-panels' );
		wp_enqueue_script( 'winshirt-mockup-canvas' );
		wp_enqueue_script( 'winshirt-layers' );
		wp_enqueue_script( 'winshirt-image-tools' );
		wp_enqueue_script( 'winshirt-text-tools' );
		wp_enqueue_script( 'winshirt-modal' );

		// SAFETY NET : bind les selectors historiques pour ouvrir la modale
		$openers = implode(',', [
			'.winshirt-customize-btn',
			'button[data-winshirt-modal="open"]',
			'#winshirt-open',
			'.single_add_to_cart_button ~ .winshirt-btn',
			'.product .button.winshirt-open'
		]);
		wp_add_inline_script( 'winshirt-modal', "jQuery(function($){ $('{$openers}').off('.ws').on('click.ws',function(e){e.preventDefault();$(document).trigger('winshirt:open');});});" );
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

		$base = [
			'version'   => self::$version,
			'siteUrl'   => site_url('/'),
			'assetsUrl' => self::$base_url . 'assets/',
			'ajaxUrl'   => admin_url('admin-ajax.php'),
			'restUrl'   => esc_url_raw( rest_url('winshirt/v1') ),
			'nonce'     => wp_create_nonce('wp_rest'),
			'locale'    => determine_locale(),
			'product'   => [ 'id' => (int) $product_id ],
			'config'    => [ 'strictPercent' => true, 'maxPreviewSize' => 1600 ],
			'mockups'   => apply_filters( 'winshirt_mockups_data', [] ),
			'zones'     => apply_filters( 'winshirt_zones_data', [] ),
			'colors'    => apply_filters( 'winshirt_colors_data', [] ),
		];

		return apply_filters( 'winshirt_front_data', $base );
	}
}

WinShirt_Assets::init();
}
