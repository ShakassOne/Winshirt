<?php
/**
 * Charge et prépare tous les assets publics + WinShirtData (front).
 * - Enqueue conditionnel (page /personnalisez ou fiche produit WooCommerce)
 * - Localisation des données (REST, nonces, produit courant, mockups/zones via filtres)
 * - Déclaration des handles CSS/JS pour les prochains fichiers (même s'ils n'existent pas encore)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Assets' ) ) {

	class WinShirt_Assets {

		/** @var string */
		private static $version = '1.0.0';

		/** @var string URL du répertoire racine du plugin */
		private static $base_url;

		/** @var string PATH du répertoire racine du plugin */
		private static $base_path;

		public static function init() {
			// Version du plugin si définie ailleurs
			if ( defined( 'WINSHIRT_VERSION' ) ) {
				self::$version = WINSHIRT_VERSION;
			}

			// Base URL/PATH fiables même depuis /includes
			// Hypothèse: fichier principal = /winshirt.php (racine du plugin)
			$plugin_main = dirname( __DIR__ ) . '/winshirt.php';
			self::$base_url  = plugins_url( '', $plugin_main ) . '/';
			self::$base_path = plugin_dir_path( $plugin_main );

			add_action( 'init', [ __CLASS__, 'register_public_assets' ] );
			add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_public_assets' ], 20 );
		}

		/**
		 * Enregistre tous les assets (déclarés une fois, utilisées selon contexte).
		 */
		public static function register_public_assets() {
			$css = [
				'winshirt-modal'   => 'assets/css/winshirt-modal.css',     // existant
				'winshirt-panels'  => 'assets/css/winshirt-panels.css',    // nouveau
				'winshirt-mobile'  => 'assets/css/winshirt-mobile.css',    // nouveau
				'winshirt-layers'  => 'assets/css/winshirt-layers.css',    // nouveau
				'winshirt-helpers' => 'assets/css/winshirt-helpers.css',   // nouveau
			];

			foreach ( $css as $handle => $rel ) {
				wp_register_style(
					$handle,
					self::$base_url . $rel,
					[],
					self::asset_version( self::$base_path . $rel )
				);
			}

			$deps_jq = [ 'jquery' ];

		$scripts = [
    // noyau / state
    'winshirt-state'         => [ 'assets/js/state.js',            $deps_jq, true ],

    // UI navigation
    'winshirt-ui-router'     => [ 'assets/js/ui-router.js',        array_merge( $deps_jq, [ 'winshirt-state' ] ), true ],
    'winshirt-ui-panels'     => [ 'assets/js/ui-panels.js',        array_merge( $deps_jq, [ 'winshirt-state', 'winshirt-ui-router' ] ), true ],

    // mockup + zones (NOUVEAU)
    'winshirt-mockup-canvas' => [ 'assets/js/mockup-canvas.js',    array_merge( $deps_jq, [ 'winshirt-state' ] ), true ],

    // calques & outils
    'winshirt-layers'        => [ 'assets/js/layers.js',           array_merge( $deps_jq, [ 'winshirt-state' ] ), true ],
    'winshirt-text-tools'    => [ 'assets/js/text-tools.js',       array_merge( $deps_jq, [ 'winshirt-state', 'winshirt-layers' ] ), true ],
    'winshirt-image-tools'   => [ 'assets/js/image-tools.js',      array_merge( $deps_jq, [ 'winshirt-state', 'winshirt-layers' ] ), true ],
    'winshirt-qr-tools'      => [ 'assets/js/qr-tools.js',         array_merge( $deps_jq, [ 'winshirt-state' ] ), true ],
    'winshirt-price'         => [ 'assets/js/price.js',            array_merge( $deps_jq, [ 'winshirt-state' ] ), true ],
    'winshirt-uploader'      => [ 'assets/js/uploader.js',         array_merge( $deps_jq, [ 'winshirt-state' ] ), true ],

    // hooks & modal (le modal dépend DU mockup)
    'winshirt-router-hooks'  => [ 'assets/js/router-hooks.js',     array_merge( $deps_jq, [ 'winshirt-state', 'winshirt-ui-router' ] ), true ],
    'winshirt-modal'         => [ 'assets/js/winshirt-modal.js',   array_merge( $deps_jq, [ 'winshirt-state', 'winshirt-ui-router', 'winshirt-ui-panels', 'winshirt-mockup-canvas' ] ), true ],
];



			foreach ( $scripts as $handle => $cfg ) {
				list( $rel, $deps, $in_footer ) = $cfg;
				wp_register_script(
					$handle,
					self::$base_url . $rel,
					$deps,
					self::asset_version( self::$base_path . $rel ),
					$in_footer
				);
			}

			// Localisation WinShirtData sur le state (source unique)
			wp_localize_script( 'winshirt-state', 'WinShirtData', self::build_front_data() );
		}

		/**
		 * Enqueue effectif si contexte "customizer" détecté.
		 */
		public static function enqueue_public_assets() {
			if ( ! self::should_enqueue() ) {
				return;
			}

			// CSS d'abord (ordre logique)
			wp_enqueue_style( 'winshirt-helpers' );
			wp_enqueue_style( 'winshirt-panels' );
			wp_enqueue_style( 'winshirt-layers' );
			wp_enqueue_style( 'winshirt-modal' );   // style existant
			wp_enqueue_style( 'winshirt-mobile' );  // overrides mobiles ciblés

			// JS (cœur → outils → glue)
			wp_enqueue_script( 'winshirt-state' );
			wp_enqueue_script( 'winshirt-ui-router' );
			wp_enqueue_script( 'winshirt-ui-panels' );
			wp_enqueue_script( 'winshirt-layers' );
			wp_enqueue_script( 'winshirt-text-tools' );
			wp_enqueue_script( 'winshirt-image-tools' );
			wp_enqueue_script( 'winshirt-qr-tools' );
			wp_enqueue_script( 'winshirt-price' );
			wp_enqueue_script( 'winshirt-uploader' );
			wp_enqueue_script( 'winshirt-router-hooks' );
			wp_enqueue_script( 'winshirt-modal' );
		}

		/**
		 * Construit les données front centralisées (filter-friendly).
		 * NB: mockups/zones/lotteries sont injectables via filtres pour éviter la dépendance forte dès maintenant.
		 */
		private static function build_front_data() {
			$current_user = get_current_user_id();
			$product_id   = self::detect_product_id();

			$data = [
				'version'   => self::$version,
				'siteUrl'   => site_url( '/' ),
				'assetsUrl' => self::$base_url . 'assets/',
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'restUrl'   => esc_url_raw( rest_url( 'winshirt/v1' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'locale'    => determine_locale(),

				'user'      => [
					'id' => (int) $current_user,
				],

				'product'   => [
					'id' => (int) $product_id,
				],

				'config'    => [
					'maxPreviewSize' => 1600,       // limite px pour html2canvas export
					'allowIA'        => true,       // extensible
					'strictPercent'  => true,       // zones en %
				],

				// Ouverts aux autres classes via filtres (non bloquants si vides)
				'mockups'   => apply_filters( 'winshirt_mockups_data', [] ),
				'zones'     => apply_filters( 'winshirt_zones_data', [] ),
				'lotteries' => apply_filters( 'winshirt_lotteries_front', [] ),
			];

			/**
			 * Filtre global pour surcharger/compléter toutes les données envoyées au front.
			 * Utile à class-winshirt-product-customization, -lottery, etc.
			 */
			$data = apply_filters( 'winshirt_front_data', $data );

			return $data;
		}

		/**
		 * Détecte si on est dans un contexte où charger le customizer (fiche produit ou page dédiée /personnalisez).
		 * Filtrable : 'winshirt_should_enqueue'
		 */
		private static function should_enqueue() {
			$by_filter = apply_filters( 'winshirt_force_enqueue', false );
			if ( $by_filter ) return true;

			$is_customize_page = function_exists( 'is_page' ) && is_page( 'personnalisez' );
			$is_product_single = function_exists( 'is_product' ) && is_product();

			// On tolère la présence d'un paramètre product_id sur /personnalisez
			if ( $is_customize_page ) return true;
			if ( $is_product_single ) return true;

			return (bool) apply_filters( 'winshirt_should_enqueue', false );
		}

		/**
		 * Devine un product_id (page produit Woo ou paramètre sur la page dédiée).
		 */
		private static function detect_product_id() {
			// 1) Paramètre explicite
			if ( isset( $_GET['product_id'] ) ) {
				return absint( $_GET['product_id'] );
			}
			// 2) Contexte WooCommerce
			if ( function_exists( 'is_product' ) && is_product() ) {
				global $product;
				if ( $product && method_exists( $product, 'get_id' ) ) {
					return (int) $product->get_id();
				}
				// Fallback via query
				$post_id = get_the_ID();
				if ( $post_id ) return (int) $post_id;
			}
			return 0;
		}

		/**
		 * Version avec cache-busting si le fichier existe sur disque.
		 */
		private static function asset_version( $absolute_path ) {
			if ( file_exists( $absolute_path ) ) {
				$mtime = (int) filemtime( $absolute_path );
				return self::$version . '.' . $mtime;
			}
			return self::$version;
		}
	}

	// Boot
	WinShirt_Assets::init();
}
