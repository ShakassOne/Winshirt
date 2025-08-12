<?php
/**
 * WinShirt – Assets front (CSS/JS) + WinShirtData
 * - Enregistre tous les assets avec versionning filemtime
 * - Localize WinShirtData AU MOMENT de l'enqueue (ordre garanti)
 * - Enqueue conditionnel (fiche produit Woo ou page /personnalisez)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Assets' ) ) {

class WinShirt_Assets {

	/** @var string */
	private static $version = '1.0.0';

	/** @var string URL base du plugin (racine) */
	private static $base_url;

	/** @var string PATH base du plugin (racine) */
	private static $base_path;

	public static function init() {
		// version du plugin si dispo
		if ( defined( 'WINSHIRT_VERSION' ) ) {
			self::$version = WINSHIRT_VERSION;
		}

		// Déduit la racine du plugin à partir de /includes/
		$plugin_main      = dirname( __DIR__ ) . '/winshirt.php';
		self::$base_url   = trailingslashit( plugins_url( '', $plugin_main ) );
		self::$base_path  = trailingslashit( plugin_dir_path( $plugin_main ) );

		add_action( 'init',                [ __CLASS__, 'register_public_assets' ] );
		add_action( 'wp_enqueue_scripts',  [ __CLASS__, 'enqueue_public_assets' ], 20 );
	}

	/**
	 * Enregistre (uniquement) tous les CSS/JS.
	 * NB: On ne localize PAS ici (pour éviter l'ordre foireux) — on le fera dans enqueue_public_assets().
	 */
	public static function register_public_assets() {

		// --------- CSS ---------
		$styles = [
			'winshirt-helpers' => 'assets/css/winshirt-helpers.css',
			'winshirt-panels'  => 'assets/css/winshirt-panels.css',
			'winshirt-layers'  => 'assets/css/winshirt-layers.css',
			'winshirt-modal'   => 'assets/css/winshirt-modal.css',
			'winshirt-mobile'  => 'assets/css/winshirt-mobile.css',
		];

		foreach ( $styles as $handle => $rel ) {
			wp_register_style(
				$handle,
				self::$base_url . $rel,
				[],
				self::asset_version_rel( $rel )
			);
		}

		// --------- JS ---------
		$jq = [ 'jquery' ];

		$scripts = [
			// cœur state
			'winshirt-state'         => [ 'assets/js/state.js',           $jq ],
			// UI navigation
			'winshirt-ui-router'     => [ 'assets/js/ui-router.js',       array_merge( $jq, [ 'winshirt-state' ] ) ],
			'winshirt-ui-panels'     => [ 'assets/js/ui-panels.js',       array_merge( $jq, [ 'winshirt-state', 'winshirt-ui-router' ] ) ],
			// mockup + zones
			'winshirt-mockup-canvas' => [ 'assets/js/mockup-canvas.js',   array_merge( $jq, [ 'winshirt-state' ] ) ],
			// calques & outils
			'winshirt-layers'        => [ 'assets/js/layers.js',          array_merge( $jq, [ 'winshirt-state', 'winshirt-mockup-canvas' ] ) ],
			'winshirt-text-tools'    => [ 'assets/js/text-tools.js',      array_merge( $jq, [ 'winshirt-state', 'winshirt-layers' ] ) ],
			'winshirt-image-tools'   => [ 'assets/js/image-tools.js',     array_merge( $jq, [ 'winshirt-state', 'winshirt-layers' ] ) ],
			'winshirt-image-bridge'  => [ 'assets/js/image-bridge.js',    array_merge( $jq, [ 'winshirt-state', 'winshirt-layers', 'winshirt-image-tools' ] ) ],
			'winshirt-router-hooks'  => [ 'assets/js/router-hooks.js',    array_merge( $jq, [ 'winshirt-state', 'winshirt-ui-router' ] ) ],
			'winshirt-price'         => [ 'assets/js/price.js',           array_merge( $jq, [ 'winshirt-state' ] ) ],
			'winshirt-uploader'      => [ 'assets/js/uploader.js',        array_merge( $jq, [ 'winshirt-state' ] ) ],
			// glue modal (doit venir en dernier)
			'winshirt-modal'         => [ 'assets/js/winshirt-modal.js',  array_merge( $jq, [ 'winshirt-state', 'winshirt-ui-router', 'winshirt-ui-panels', 'winshirt-mockup-canvas' ] ) ],
		];

		foreach ( $scripts as $handle => $cfg ) {
			list( $rel, $deps ) = $cfg;
			wp_register_script(
				$handle,
				self::$base_url . $rel,
				$deps,
				self::asset_version_rel( $rel ),
				true // in footer
			);
		}
	}

	/**
	 * Enqueue effectif (et localize dans le bon timing).
	 */
	public static function enqueue_public_assets() {
		if ( ! self::should_enqueue() ) {
			return;
		}

		// CSS dans un ordre stable
		wp_enqueue_style( 'winshirt-helpers' );
		wp_enqueue_style( 'winshirt-panels' );
		wp_enqueue_style( 'winshirt-layers' );
		wp_enqueue_style( 'winshirt-modal' );
		wp_enqueue_style( 'winshirt-mobile' );

		// 1) Localize maintenant (avant les scripts consumers)
		wp_localize_script( 'winshirt-state', 'WinShirtData', self::build_front_data() );

		// 2) JS dans l'ordre strict (state → ui → mockup → tools → glue)
		wp_enqueue_script( 'winshirt-state' );
		wp_enqueue_script( 'winshirt-ui-router' );
		wp_enqueue_script( 'winshirt-ui-panels' );
		wp_enqueue_script( 'winshirt-mockup-canvas' );
		wp_enqueue_script( 'winshirt-layers' );
		wp_enqueue_script( 'winshirt-text-tools' );
		wp_enqueue_script( 'winshirt-image-tools' );
		wp_enqueue_script( 'winshirt-image-bridge' );
		wp_enqueue_script( 'winshirt-router-hooks' );
		wp_enqueue_script( 'winshirt-price' );
		wp_enqueue_script( 'winshirt-uploader' );
		wp_enqueue_script( 'winshirt-modal' );
	}

	/**
	 * Décide si on doit charger le customizer.
	 */
	private static function should_enqueue() {
		// Forçage via filtre si besoin
		if ( apply_filters( 'winshirt_force_enqueue', false ) ) return true;

		$is_customize_page = function_exists( 'is_page' ) && is_page( 'personnalisez' );
		$is_product_single = function_exists( 'is_product' ) && is_product();

		if ( $is_customize_page || $is_product_single ) return true;

		// Permettre à d'autres de décider (ex: shortcode)
		return (bool) apply_filters( 'winshirt_should_enqueue', false );
	}

	/**
	 * Construit les données front (source unique), filtrables.
	 * D'autres classes (product-customization, lottery…) complètent via 'winshirt_front_data'.
	 */
	private static function build_front_data() {
		$current_user = get_current_user_id();

		// Optionnel : devine un product_id de base
		$product_id = 0;
		if ( isset( $_GET['product_id'] ) ) {
			$product_id = absint( $_GET['product_id'] );
		} elseif ( function_exists( 'is_product' ) && is_product() ) {
			$product_id = (int) get_the_ID();
		}

		$data = [
			'version'   => self::$version,
			'siteUrl'   => site_url( '/' ),
			'assetsUrl' => self::$base_url . 'assets/',
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'restUrl'   => esc_url_raw( rest_url( 'winshirt/v1' ) ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'locale'    => determine_locale(),

			'user'      => [ 'id' => (int) $current_user ],
			'product'   => [ 'id' => (int) $product_id ],

			'config'    => [
				'maxPreviewSize' => 1600,
				'allowIA'        => true,
				'strictPercent'  => true,
			],

			// Laisser vides : seront injectés par class-winshirt-product-customization
			'mockups'   => [],
			'zones'     => [],
			'colors'    => [],
			'lotteries' => [],
		];

		// Point d'extension global
		$data = apply_filters( 'winshirt_front_data', $data );

		return $data;
	}

	/**
	 * Versionning par filemtime sur un chemin relatif à la racine du plugin.
	 */
	private static function asset_version_rel( $relative_path ) {
		$abs = self::$base_path . ltrim( $relative_path, '/' );
		if ( file_exists( $abs ) ) {
			return self::$version . '.' . (int) filemtime( $abs );
		}
		return self::$version;
	}
}

WinShirt_Assets::init();
}
