<?php
/**
 * WinShirt - Données produit => WinShirtData (mockups & zones)
 * Lit les métas du produit (_winshirt_enable, _winshirt_mockup_id) et, si présent,
 * récupère sur le CPT Mockup les URLs et zones d’impression.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Product_Customization' ) ) {

class WinShirt_Product_Customization {

	public static function init() {
		add_filter( 'winshirt_mockups_data', [ __CLASS__, 'provide_mockups' ] );
		add_filter( 'winshirt_zones_data',   [ __CLASS__, 'provide_zones' ] );
	}

	private static function current_product() {
		if ( function_exists('is_product') && is_product() ) {
			$p = wc_get_product( get_the_ID() );
			if ( $p ) return $p;
		}
		// fallback paramètre GET
		if ( isset($_GET['product_id']) ) {
			return wc_get_product( absint($_GET['product_id']) );
		}
		return null;
	}

	private static function get_mockup_id_for_product( $product ) {
		if ( ! $product ) return 0;
		return (int) $product->get_meta( '_winshirt_mockup_id' );
	}

	// ---- Mockups ----

	public static function provide_mockups( $mockups ) {
		$product = self::current_product();
		$mockup_id = self::get_mockup_id_for_product( $product );

		// Shortcode/front_img/back_img déjà fournis ? → ne rien écraser
		if ( ! empty( $mockups['front'] ) || ! empty( $mockups['back'] ) ) {
			return $mockups;
		}

		// Si mockup sélectionné sur le produit, lire ses metas
		if ( $mockup_id ) {
			$front = self::read_first_image_meta( $mockup_id, [ '_winshirt_mockup_front', 'image_avant', 'front_image', '_mockup_front' ] );
			$back  = self::read_first_image_meta( $mockup_id, [ '_winshirt_mockup_back',  'image_arriere','back_image',  '_mockup_back'  ] );

			if ( $front || $back ) {
				return [
					'front' => esc_url_raw( $front ?: '' ),
					'back'  => esc_url_raw( $back  ?: '' ),
				];
			}
		}

		// Fallback : images du plugin si présentes
		$fallback_front = WINSHIRT_URL . 'assets/img/mockup-front.png';
		$fallback_back  = WINSHIRT_URL . 'assets/img/mockup-back.png';
		return [
			'front' => $fallback_front,
			'back'  => $fallback_back,
		];
	}

	// ---- Zones ----

	public static function provide_zones( $zones ) {
		$product   = self::current_product();
		$mockup_id = self::get_mockup_id_for_product( $product );

		// Si déjà fourni (shortcode ou autre), ne rien écraser
		if ( isset($zones['front']) && isset($zones['back']) ) return $zones;

		if ( $mockup_id ) {
			// Essayer plusieurs clés possibles (ACF / meta custom)
			$json = self::read_first_json_meta( $mockup_id, [ '_winshirt_zones', 'zones', 'zones_impression' ] );
			if ( is_array( $json ) && isset( $json['front'] ) && isset( $json['back'] ) ) {
				return [
					'front' => self::sanitize_zone_array( $json['front'] ),
					'back'  => self::sanitize_zone_array( $json['back'] ),
				];
			}
		}

		// Fallback sobre
		return [
			'front' => [ [ 'xPct'=>20, 'yPct'=>20, 'wPct'=>60, 'hPct'=>45 ] ],
			'back'  => [ [ 'xPct'=>20, 'yPct'=>20, 'wPct'=>60, 'hPct'=>45 ] ],
		];
	}

	// ---- Helpers ----

	private static function read_first_image_meta( $post_id, $keys = [] ) {
		foreach ( (array) $keys as $k ) {
			$val = get_post_meta( $post_id, $k, true );
			if ( is_string($val) && $val ) return $val;
			// ACF image peut renvoyer array
			if ( is_array($val) ) {
				if ( ! empty($val['url']) ) return $val['url'];
				if ( ! empty($val[0]) && is_string($val[0]) ) return $val[0];
			}
		}
		// Balayage large : première URL d'image trouvée dans les metas
		$meta = get_post_meta( $post_id );
		foreach ( $meta as $v ) {
			$v = is_array($v) ? $v[0] : $v;
			if ( is_string($v) && preg_match('#^https?://#', $v) && preg_match('#\.(png|jpe?g|webp|gif|svg)$#i', $v) ) {
				return $v;
			}
		}
		return '';
	}

	private static function read_first_json_meta( $post_id, $keys = [] ) {
		foreach ( (array) $keys as $k ) {
			$raw = get_post_meta( $post_id, $k, true );
			if ( ! $raw ) continue;
			$arr = is_array($raw) ? $raw : json_decode( (string) $raw, true );
			if ( is_array($arr) ) return $arr;
		}
		return null;
	}

	private static function sanitize_zone_array( $arr ) {
		$out = [];
		if ( is_array( $arr ) ) {
			foreach ( $arr as $z ) {
				$out[] = [
					'xPct' => isset($z['xPct']) ? (float)$z['xPct'] : 20.0,
					'yPct' => isset($z['yPct']) ? (float)$z['yPct'] : 20.0,
					'wPct' => isset($z['wPct']) ? (float)$z['wPct'] : 60.0,
					'hPct' => isset($z['hPct']) ? (float)$z['hPct'] : 45.0,
				];
			}
		}
		return $out ?: [ [ 'xPct'=>20, 'yPct'=>20, 'wPct'=>60, 'hPct'=>45 ] ];
	}
}

WinShirt_Product_Customization::init();
}
