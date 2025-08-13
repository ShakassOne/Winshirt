<?php
/**
 * WinShirt – Mockups (CPT) + Payload helper
 *
 * - CPT: ws-mockup (visible en BO, pas public)
 * - Meta keys standardisées:
 *     _winshirt_mockup_front   (string URL)
 *     _winshirt_mockup_back    (string URL)
 *     _winshirt_mockup_colors  (CSV "#000000,#FFFFFF,blue")
 *     _winshirt_zones          (JSON {"front":[{left,top,width,height}], "back":[...]} en %)
 *
 * - API interne:
 *     WinShirt_Mockups::get_payload( $mockup_id ) => array
 *     WinShirt_Mockups::get_latest_mockup_id()    => int|0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Mockups' ) ) {

class WinShirt_Mockups {

	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_cpt' ] );
	}

	public static function register_cpt() {
		$labels = [
			'name'               => __( 'Mockups', 'winshirt' ),
			'singular_name'      => __( 'Mockup', 'winshirt' ),
			'add_new'            => __( 'Ajouter', 'winshirt' ),
			'add_new_item'       => __( 'Ajouter un mockup', 'winshirt' ),
			'edit_item'          => __( 'Modifier le mockup', 'winshirt' ),
			'new_item'           => __( 'Nouveau mockup', 'winshirt' ),
			'view_item'          => __( 'Voir le mockup', 'winshirt' ),
			'search_items'       => __( 'Rechercher des mockups', 'winshirt' ),
			'not_found'          => __( 'Aucun mockup', 'winshirt' ),
			'not_found_in_trash' => __( 'Aucun mockup dans la corbeille', 'winshirt' ),
			'menu_name'          => __( 'Mockups', 'winshirt' ),
		];

		register_post_type( 'ws-mockup', [
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false, // le menu est géré via class-winshirt-admin.php
			'supports'            => [ 'title' ],
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'rewrite'             => false,
			'show_in_rest'        => false,
		] );
	}

	/**
	 * Renvoie un payload normalisé pour le front.
	 *
	 * @param int $mockup_id
	 * @return array{
	 *   id:int,
	 *   images: array{front:string,back:string},
	 *   zones:  array{front:array<int, array{left:float,top:float,width:float,height:float}>, back:array<int, array{left:float,top:float,width:float,height:float}>},
	 *   colors: array<int,string>
	 * }
	 */
	public static function get_payload( $mockup_id ) {
		$mockup_id = absint( $mockup_id );
		if ( ! $mockup_id || 'ws-mockup' !== get_post_type( $mockup_id ) ) {
			return [];
		}

		// URLs images (support des anciennes metas pour compat)
		$front = get_post_meta( $mockup_id, '_winshirt_mockup_front', true );
		if ( ! $front ) $front = get_post_meta( $mockup_id, 'ws_mockup_front', true );

		$back  = get_post_meta( $mockup_id, '_winshirt_mockup_back', true );
		if ( ! $back ) $back = get_post_meta( $mockup_id, 'ws_mockup_back', true );

		$images = [
			'front' => is_string( $front ) ? esc_url_raw( $front ) : '',
			'back'  => is_string( $back )  ? esc_url_raw( $back )  : '',
		];

		// Couleurs CSV
		$colors_csv = get_post_meta( $mockup_id, '_winshirt_mockup_colors', true );
		if ( ! $colors_csv ) $colors_csv = get_post_meta( $mockup_id, 'ws_mockup_colors', true );
		$colors = [];
		if ( is_string( $colors_csv ) && $colors_csv !== '' ) {
			$tmp = array_map( 'trim', explode( ',', $colors_csv ) );
			$colors = array_values( array_filter( $tmp, static function( $v ){ return $v !== ''; } ) );
		}

		// Zones JSON
		$zones_json = get_post_meta( $mockup_id, '_winshirt_zones', true );
		if ( ! $zones_json ) $zones_json = get_post_meta( $mockup_id, 'ws_mockup_zones', true );

		$zones = [ 'front' => [], 'back' => [] ];
		if ( is_string( $zones_json ) && $zones_json !== '' ) {
			$decoded = json_decode( $zones_json, true );
			if ( is_array( $decoded ) ) {
				$zones['front'] = self::sanitize_zones_array( $decoded['front'] ?? [] );
				$zones['back']  = self::sanitize_zones_array( $decoded['back']  ?? [] );
			}
		}

		$payload = [
			'id'     => $mockup_id,
			'images' => $images,
			'zones'  => $zones,
			'colors' => $colors,
		];

		/**
		 * Laisse d’autres modules enrichir/modifier le payload.
		 *
		 * @param array $payload
		 * @param int   $mockup_id
		 */
		return apply_filters( 'winshirt_mockup_payload', $payload, $mockup_id );
	}

	/**
	 * Renvoie l’ID du dernier mockup publié (fallback).
	 */
	public static function get_latest_mockup_id() {
		$q = new WP_Query( [
			'post_type'      => 'ws-mockup',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			'fields'         => 'ids',
		] );
		if ( ! empty( $q->posts ) ) {
			return (int) $q->posts[0];
		}
		return 0;
	}

	/**
	 * Nettoie un tableau de zones [{left,top,width,height}] (en %).
	 *
	 * @param array $list
	 * @return array<int, array{left:float,top:float,width:float,height:float}>
	 */
	private static function sanitize_zones_array( $list ) {
		if ( ! is_array( $list ) ) return [];

		$out = [];
		foreach ( $list as $z ) {
			$left   = isset( $z['left'] )   ? floatval( $z['left'] )   : ( isset( $z['xPct'] ) ? floatval( $z['xPct'] ) : 0.0 );
			$top    = isset( $z['top'] )    ? floatval( $z['top'] )    : ( isset( $z['yPct'] ) ? floatval( $z['yPct'] ) : 0.0 );
			$width  = isset( $z['width'] )  ? floatval( $z['width'] )  : ( isset( $z['wPct'] ) ? floatval( $z['wPct'] ) : 0.0 );
			$height = isset( $z['height'] ) ? floatval( $z['height'] ) : ( isset( $z['hPct'] ) ? floatval( $z['hPct'] ) : 0.0 );

			$out[] = [
				'left'   => max( 0.0, min( 100.0, $left   ) ),
				'top'    => max( 0.0, min( 100.0, $top    ) ),
				'width'  => max( 0.0, min( 100.0, $width  ) ),
				'height' => max( 0.0, min( 100.0, $height ) ),
			];
		}
		return $out;
	}
}

WinShirt_Mockups::init();

}
