<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('WinShirt_CPT') ) {

class WinShirt_CPT {

	public static function init() {
		add_action('init', [__CLASS__, 'register_post_types'], 5);
		add_action('init', [__CLASS__, 'register_taxonomies'], 6);
	}

	public static function register_post_types() {

		// -------- Mockups (ws-mockup) --------
		register_post_type('ws-mockup', [
			'labels' => [
				'name'               => __('Mockups', 'winshirt'),
				'singular_name'      => __('Mockup', 'winshirt'),
				'add_new'            => __('Ajouter', 'winshirt'),
				'add_new_item'       => __('Ajouter un mockup', 'winshirt'),
				'edit_item'          => __('Modifier le mockup', 'winshirt'),
				'new_item'           => __('Nouveau mockup', 'winshirt'),
				'view_item'          => __('Voir le mockup', 'winshirt'),
				'search_items'       => __('Rechercher des mockups', 'winshirt'),
				'not_found'          => __('Aucun mockup', 'winshirt'),
				'not_found_in_trash' => __('Aucun mockup dans la corbeille', 'winshirt'),
				'all_items'          => __('Tous les mockups', 'winshirt'),
			],
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false, // on l’affiche via le menu WinShirt
			'supports'            => ['title','thumbnail','custom-fields'],
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		]);

		// -------- Visuels (ws-design) --------
		register_post_type('ws-design', [
			'labels' => [
				'name'               => __('Visuels', 'winshirt'),
				'singular_name'      => __('Visuel', 'winshirt'),
				'add_new'            => __('Ajouter', 'winshirt'),
				'add_new_item'       => __('Ajouter un visuel', 'winshirt'),
				'edit_item'          => __('Modifier le visuel', 'winshirt'),
				'new_item'           => __('Nouveau visuel', 'winshirt'),
				'view_item'          => __('Voir le visuel', 'winshirt'),
				'search_items'       => __('Rechercher des visuels', 'winshirt'),
				'not_found'          => __('Aucun visuel', 'winshirt'),
				'not_found_in_trash' => __('Aucun visuel dans la corbeille', 'winshirt'),
				'all_items'          => __('Tous les visuels', 'winshirt'),
			],
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false, // via le menu WinShirt
			'supports'            => ['title','thumbnail','custom-fields'],
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		]);
	}

	public static function register_taxonomies() {
		// Catégories pour les visuels
		register_taxonomy('ws-design-category', 'ws-design', [
			'labels' => [
				'name'          => __('Catégories de visuels', 'winshirt'),
				'singular_name' => __('Catégorie de visuel', 'winshirt'),
				'all_items'     => __('Toutes les catégories', 'winshirt'),
				'edit_item'     => __('Modifier la catégorie', 'winshirt'),
				'view_item'     => __('Voir la catégorie', 'winshirt'),
				'update_item'   => __('Mettre à jour la catégorie', 'winshirt'),
				'add_new_item'  => __('Ajouter une catégorie', 'winshirt'),
				'new_item_name' => __('Nouvelle catégorie', 'winshirt'),
				'search_items'  => __('Rechercher des catégories', 'winshirt'),
			],
			'public'        => false,
			'show_ui'       => true,
			'show_admin_column' => true,
			'hierarchical'  => true,
			'show_in_menu'  => false,
		]);
	}
}

WinShirt_CPT::init();
}
