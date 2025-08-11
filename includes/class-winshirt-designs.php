<?php
/**
 * WinShirt - REST Designs (liste de visuels pour la galerie)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Designs' ) ) {

class WinShirt_Designs {

	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	public static function register_routes() {
		register_rest_route( 'winshirt/v1', '/designs', [
			'methods'  => 'GET',
			'callback' => [ __CLASS__, 'get_designs' ],
			'permission_callback' => '__return_true',
			'args' => [
				'category' => [ 'type'=>'string', 'required'=>false ],
				'page'     => [ 'type'=>'integer', 'required'=>false, 'default'=>1 ],
				'per_page' => [ 'type'=>'integer', 'required'=>false, 'default'=>24 ],
			],
		] );
	}

	public static function get_designs( WP_REST_Request $req ) {
		$tax_query = [];
		$cat = sanitize_text_field( $req->get_param('category') );
		if ( $cat && $cat !== 'all' ) {
			$tax_query[] = [
				'taxonomy' => 'ws-design-category',
				'field'    => 'slug',
				'terms'    => $cat,
			];
		}

		$page     = max(1, (int)$req['page']);
		$per_page = min(60, max(1, (int)$req['per_page']));

		$q = new WP_Query([
			'post_type'      => 'ws-design',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'tax_query'      => $tax_query,
			'no_found_rows'  => false,
		]);

		$items = [];
		foreach ( $q->posts as $p ) {
			$thumb = get_the_post_thumbnail_url( $p, 'medium' );
			if ( ! $thumb ) {
				// fallback meta possible (si l’image est stockée en URL)
				$maybe = get_post_meta( $p->ID, 'image', true );
				if ( is_string($maybe) && preg_match('#^https?://#', $maybe) ) $thumb = $maybe;
			}

			$cats = [];
			$terms = get_the_terms( $p, 'ws-design-category' );
			if ( is_array($terms) ) {
				foreach ( $terms as $t ) $cats[] = [ 'slug'=>$t->slug, 'name'=>$t->name ];
			}

			$items[] = [
				'id'    => (int) $p->ID,
				'title' => get_the_title( $p ),
				'thumb' => $thumb ?: '',
				'full'  => $thumb ?: '',
				'cats'  => $cats,
			];
		}

		// catégories
		$cat_items = [];
		$terms = get_terms([
			'taxonomy'   => 'ws-design-category',
			'hide_empty' => true,
		]);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $t ) {
				$cat_items[] = [ 'slug'=>$t->slug, 'name'=>$t->name, 'count'=>$t->count ];
			}
		}

		return new WP_REST_Response([
			'items'      => $items,
			'categories' => array_merge([['slug'=>'all','name'=>'Tous','count'=>0]], $cat_items),
			'total'      => (int) $q->found_posts,
			'page'       => $page,
			'per_page'   => $per_page,
		]);
	}
}

WinShirt_Designs::init();
}
