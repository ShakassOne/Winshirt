<?php
/**
 * WinShirt - Router (page/shortcode du customizer)
 * - Shortcode [winshirt_customizer]
 * - Helper pour charger le template 'templates/modal-customizer.php'
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Router' ) ) {

class WinShirt_Router {

	public static function init() {
		add_shortcode( 'winshirt_customizer', [ __CLASS__, 'shortcode_customizer' ] );
	}

	/**
	 * [winshirt_customizer product_id="123"]
	 */
	public static function shortcode_customizer( $atts = [] ) {
		$atts = shortcode_atts( [
			'product_id' => 0,
			'front_img'  => '',
			'back_img'   => '',
		], $atts, 'winshirt_customizer' );

		// Si product_id passé en shortcode, on le pousse dans l’URL (utilisé par WinShirtData)
		if ( $atts['product_id'] && empty($_GET['product_id']) ) {
			$_GET['product_id'] = (int) $atts['product_id'];
		}

		ob_start();
		self::load_template( 'modal-customizer.php', [
			'front_img' => $atts['front_img'],
			'back_img'  => $atts['back_img'],
		] );
		return ob_get_clean();
	}

	/**
	 * Inclut un template depuis /templates
	 */
	private static function load_template( $file, $args = [] ) {
		$path = WINSHIRT_PATH . 'templates/' . ltrim( $file, '/\\' );
		if ( ! file_exists( $path ) ) {
			echo '<div class="notice notice-error">Template WinShirt introuvable: '.esc_html($file).'</div>';
			return;
		}
		if ( is_array( $args ) ) {
			extract( $args, EXTR_SKIP );
		}
		include $path;
	}
}

WinShirt_Router::init();
}
