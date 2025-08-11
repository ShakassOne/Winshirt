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
			if ( defined( 'WINSHIRT_VERSION' ) ) {
				self::$version = WINSHIRT_VERSION;
			}

			$plugin_main = dirname( __DIR__ ) . '/winshirt.php';
			self::$base_url  = plugins_url( '', $plugin_main ) . '/';
			self::$base_path = plugin_dir_path( $plugin_main );

			add_action( 'init', [ __CLASS__, 'register_public_assets' ] );
			add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_public_assets' ], 20 );
		}

		public static function register_public_assets() {
			$css = [
				'winshirt-modal'   => 'assets/css/winshirt-modal.css',
				'winshirt-panels'  => 'assets/css/winshirt-panels.css',
				'winshirt-mobile'  => 'assets/css/winshirt-mobile.css',
				'winshirt-layers'  => 'assets/css/winshirt-layers.css',
				'winshirt-helpers' => 'assets/css/winshirt-helpers.css',
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
				'winshirt-state'         => [ 'assets/js/state.js',            $deps_jq, true ],
				'winshirt-ui-router'     => [ 'assets/js/ui-router.js',        array_merge( $deps_jq, [ 'winshirt-state' ] ), true ],
				'winshirt-ui-panels'     => [ 'assets/js/ui-panels.js',        array_merge( $deps_jq, [ 'winshirt-state', 'winshirt-ui-router' ] ), true ],
				'winshirt-mockup-canvas' => [ 'assets/js/mockup-canvas.js',    array_merge( $deps_jq, [ 'winshirt-state' ] ), true ],
				'winshirt-layers'        => [ 'assets/js/layers.js',           array_merge( $deps_jq, [ 'winshirt-state' ] ), true ],
				'winshirt-text-tools'    => [ 'assets/js/text-tools.js',       array_merge( $deps_jq, [ 'winshirt-state', 'winshirt-layers' ] ), true ],
				'winshirt-image-tools'   => [ 'assets/js/image-tools.js',      array_merge( $deps_jq, [ 'winshirt-state', 'winshirt-layers' ] ), true ],
				'winshirt-qr-tools'      => [ 'assets/js/qr-tools.js',         array_merge( $deps_jq, [ 'winshirt-state', 'winshirt-layers' ] ), true ],
				'winshirt-price'         => [ 'assets/js/price.js',            array_merge( $deps_jq, [ 'winshirt-state', 'winshirt-layers' ] ), true ],
				'winshirt-uploader'      => [ 'assets/js/uploader.js',         array_merge( $deps_jq, [ 'winshirt-state', 'winshirt-layers' ] ), true ],
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

			wp_localize_script( 'winshirt-state', 'WinShirtData', self::build_front_data() );
		}

		public static function enqueue_public_assets() {
			if ( ! self::should_enqueue() ) return;

			wp_enqueue_style( 'winshirt-helpers' );
			wp_enqueue_style( 'winshirt-panels' );
			wp_enqueue_style( 'winshirt-layers' );
			wp_enqueue_style( 'winshirt-modal' );
			wp_enqueue_style( 'winshirt-mobile' );

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
					'maxPreviewSize' => 1600,
					'allowIA'        => true,
					'strictPercent'  => true,
				],

				// Les filtres injectent mockups/zones depuis class-winshirt-product-customization
				'mockups'   => apply_filters( 'winshirt_mockups_data', [ 'front' => '', 'back' => '' ] ),
				'zones'     => apply_filters( 'winshirt_zones_data',   [ 'front' => [], 'back' => [] ] ),
				'lotteries' => apply_filters( 'winshirt_lotteries_front', [] ),
			];

			return apply_filters( 'winshirt_front_data', $data );
		}

		private static function should_enqueue() {
			$by_filter = apply_filters( 'winshirt_force_enqueue', false );
			if ( $by_filter ) return true;

			$is_customize_page = function_exists( 'is_page' ) && is_page( 'personnalisez' );
			$is_product_single = function_exists( 'is_product' ) && is_product();

			if ( $is_customize_page ) return true;
			if ( $is_product_single ) return true;

			return (bool) apply_filters( 'winshirt_should_enqueue', false );
		}

		private static function detect_product_id() {
			if ( isset( $_GET['product_id'] ) ) {
				return absint( $_GET['product_id'] );
			}
			if ( function_exists( 'is_product' ) && is_product() ) {
				global $product;
				if ( $product && method_exists( $product, 'get_id' ) ) {
					return (int) $product->get_id();
				}
				$post_id = get_the_ID();
				if ( $post_id ) return (int) $post_id;
			}
			return 0;
		}

		private static function asset_version( $absolute_path ) {
			if ( file_exists( $absolute_path ) ) {
				$mtime = (int) filemtime( $absolute_path );
				return self::$version . '.' . $mtime;
			}
			return self::$version;
		}
	}

	WinShirt_Assets::init();
}
