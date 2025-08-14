<?php
/**
 * WinShirt - Product Customization
 * - Unifie les meta-keys lues/écrites
 * - Fournit les données mockup/zones/couleurs au front via filtres
 * - (Hooks add_to_cart prêts, sérialisation design JSON)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Product_Customization' ) ) {

class WinShirt_Product_Customization {

	public static function init() {
		// Front filters
		add_filter( 'winshirt_mockups_data', [ __CLASS__, 'front_mockups' ] );
		add_filter( 'winshirt_zones_data',   [ __CLASS__, 'front_zones' ] );
		add_filter( 'winshirt_colors_data',  [ __CLASS__, 'front_colors' ] );

		// Add to cart meta (design JSON)
		add_filter( 'woocommerce_add_cart_item_data', [ __CLASS__, 'add_cart_item_data' ], 10, 3 );
		add_filter( 'woocommerce_get_item_data',      [ __CLASS__, 'display_item_data' ], 10, 2 );

		add_action( 'woocommerce_add_order_item_meta', [ __CLASS__, 'order_item_meta' ], 10, 3 );
	}

	private static function current_mockup_id( $product_id ) {
		// ici : méta au niveau du produit qui pointe vers un mockup associé (ou utilisation d’un mockup par défaut)
		$mockup_id = (int) get_post_meta( $product_id, '_winshirt_mockup_id', true );
		return $mockup_id ?: 0;
	}

	/** FRONT DATA **/

	public static function front_mockups( $data ) {
		$product_id = is_singular('product') ? get_the_ID() : 0;
		if ( ! $product_id ) return $data;

		$mockup_id = self::current_mockup_id( $product_id );
		if ( ! $mockup_id ) return $data;

		$front = get_post_meta( $mockup_id, '_winshirt_mockup_front', true );
		$back  = get_post_meta( $mockup_id, '_winshirt_mockup_back',  true );

		$data = [
			'id'    => $mockup_id,
			'front' => $front,
			'back'  => $back,
		];
		return $data;
	}

	public static function front_zones( $data ) {
		$product_id = is_singular('product') ? get_the_ID() : 0;
		if ( ! $product_id ) return $data;

		$mockup_id = self::current_mockup_id( $product_id );
		if ( ! $mockup_id ) return $data;

		$json = get_post_meta( $mockup_id, '_ws_mockup_zones', true ); // éditeur visuel admin
		$zones = [];
		if ( $json ) {
			$decoded = json_decode( $json, true );
			if ( is_array( $decoded ) ) $zones = $decoded;
		}
		return $zones;
	}

	public static function front_colors( $data ) {
		$product_id = is_singular('product') ? get_the_ID() : 0;
		if ( ! $product_id ) return $data;

		$mockup_id = self::current_mockup_id( $product_id );
		if ( ! $mockup_id ) return $data;

		$csv   = (string) get_post_meta( $mockup_id, '_ws_mockup_colors_csv', true );
		$list  = array_filter( array_map( 'trim', explode( ',', $csv ) ) );
		$rows  = (array) get_post_meta( $mockup_id, '_ws_mockup_color_rows', true ); // array: [ [label,hex,frontUrl,backUrl], ... ]

		$out = [];
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$out[] = [
					'label' => $row['label'] ?? '',
					'hex'   => $row['hex']   ?? '',
					'front' => $row['front'] ?? '',
					'back'  => $row['back']  ?? '',
				];
			}
		} elseif ( $list ) {
			foreach ( $list as $hex ) {
				$out[] = [ 'label' => $hex, 'hex' => $hex, 'front' => '', 'back' => '' ];
			}
		}
		return $out;
	}

	/** CART / ORDER **/

	public static function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		if ( empty( $_POST['winshirt_design'] ) ) return $cart_item_data;

		$design = wp_unslash( $_POST['winshirt_design'] );
		$cart_item_data['winshirt_design'] = $design;
		return $cart_item_data;
	}

	public static function display_item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item['winshirt_design'] ) ) return $item_data;
		$item_data[] = [
			'key'   => __( 'Personnalisation', 'winshirt' ),
			'value' => '<code>Design enregistré</code>',
		];
		return $item_data;
	}

	public static function order_item_meta( $item_id, $values, $cart_item_key ) {
		if ( empty( $values['winshirt_design'] ) ) return;
		wc_add_order_item_meta( $item_id, '_winshirt_design', wp_kses_post( $values['winshirt_design'] ) );
	}
}

WinShirt_Product_Customization::init();
}
