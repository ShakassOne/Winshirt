<?php
/**
 * WinShirt - Données produit => WinShirtData (mockups & zones)
 * - Alimente les filtres : winshirt_mockups_data, winshirt_zones_data
 * - Cherche des metas produit (optionnel), sinon fallback génériques
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Product_Customization' ) ) {

class WinShirt_Product_Customization {

	public static function init() {
		add_filter( 'winshirt_mockups_data', [ __CLASS__, 'provide_mockups' ] );
		add_filter( 'winshirt_zones_data',   [ __CLASS__, 'provide_zones' ] );
	}

	/**
	 * Retourne URLs mockups front/back
	 * Priorité : meta produit > options > fallback image plugin
	 */
	public static function provide_mockups( $mockups ) {
		$product_id = self::current_product_id();

		$front = '';
		$back  = '';

		// Exemple de metas (adapte si tu as déjà des clés) :
		if ( $product_id ) {
			$front = get_post_meta( $product_id, '_winshirt_mockup_front', true );
			$back  = get_post_meta( $product_id, '_winshirt_mockup_back',  true );
		}

		// Fallback : une image “placeholder” du plugin (met la tienne dans assets/)
		if ( ! $front ) $front = WINSHIRT_URL . 'assets/img/mockup-front.png';
		if ( ! $back  ) $back  = WINSHIRT_URL . 'assets/img/mockup-back.png';

		return [
			'front' => esc_url_raw( $front ),
			'back'  => esc_url_raw( $back ),
		];
	}

	/**
	 * Zones d’impression en pourcentage (x/y/w/h en % du canvas)
	 * Structure attendue :
	 * [
	 *   'front' => [ [ 'xPct'=>20,'yPct'=>20,'wPct'=>60,'hPct'=>45 ] ],
	 *   'back'  => [ [ 'xPct'=>20,'yPct'=>20,'wPct'=>60,'hPct'=>45 ] ]
	 * ]
	 */
	public static function provide_zones( $zones ) {
		$product_id = self::current_product_id();

		// Si tu stockes en meta (JSON), lis-les ici :
		$meta = $product_id ? get_post_meta( $product_id, '_winshirt_zones', true ) : '';
		$data = is_string( $meta ) ? json_decode( $meta, true ) : ( is_array( $meta ) ? $meta : null );

		if ( is_array( $data ) && isset( $data['front'] ) && isset( $data['back'] ) ) {
			return [
				'front' => self::sanitize_zone_array( $data['front'] ),
				'back'  => self::sanitize_zone_array( $data['back'] ),
			];
		}

		// Fallback sobre : 60% de largeur au centre, ratio ~0.75
		return [
			'front' => [ [ 'xPct'=>20, 'yPct'=>20, 'wPct'=>60, 'hPct'=>45 ] ],
			'back'  => [ [ 'xPct'=>20, 'yPct'=>20, 'wPct'=>60, 'hPct'=>45 ] ],
		];
	}

	// ================= Helpers =================

	private static function current_product_id() {
		// Lecture par GET d’abord (page /personnalisez?product_id=)
		if ( isset( $_GET['product_id'] ) ) {
			return absint( $_GET['product_id'] );
		}
		// Puis contexte Woo
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
