<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WinShirt_Designs {

    public function __construct() {
        add_action( 'init',              [ $this, 'register_post_type' ] );
        add_action( 'init',              [ $this, 'register_taxonomy' ] );
        add_action( 'after_setup_theme', [ $this, 'ensure_thumbnails' ] );
    }

    /**
     * Enregistre le Custom Post Type ws-design (Visuels)
     */
    public function register_post_type() {
        $labels = [
            'name'               => __( 'Visuels', 'winshirt' ),
            'singular_name'      => __( 'Visuel', 'winshirt' ),
            'add_new'            => __( 'Ajouter', 'winshirt' ),
            'add_new_item'       => __( 'Ajouter un visuel', 'winshirt' ),
            'edit_item'          => __( 'Modifier le visuel', 'winshirt' ),
            'new_item'           => __( 'Nouveau visuel', 'winshirt' ),
            'view_item'          => __( 'Voir le visuel', 'winshirt' ),
            'search_items'       => __( 'Rechercher des visuels', 'winshirt' ),
            'not_found'          => __( 'Aucun visuel trouvé', 'winshirt' ),
            'not_found_in_trash' => __( 'Aucun visuel dans la corbeille', 'winshirt' ),
            'menu_name'          => __( 'Visuels', 'winshirt' ),
        ];

        $args = [
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'winshirt',            // Place sous le menu WinShirt
            'supports'        => [ 'title', 'thumbnail' ],
            'capability_type' => 'post',
        ];

        register_post_type( 'ws-design', $args );
    }

    /**
     * Enregistre la taxonomie ws-design-category (Catégories de visuels)
     */
    public function register_taxonomy() {
        $labels = [
            'name'              => __( 'Catégories de visuels', 'winshirt' ),
            'singular_name'     => __( 'Catégorie de visuel', 'winshirt' ),
            'search_items'      => __( 'Rechercher des catégories', 'winshirt' ),
            'all_items'         => __( 'Toutes les catégories', 'winshirt' ),
            'edit_item'         => __( 'Modifier la catégorie', 'winshirt' ),
            'update_item'       => __( 'Mettre à jour la catégorie', 'winshirt' ),
            'add_new_item'      => __( 'Ajouter une nouvelle catégorie', 'winshirt' ),
            'new_item_name'     => __( 'Nom de la nouvelle catégorie', 'winshirt' ),
            'menu_name'         => __( 'Catégories de visuels', 'winshirt' ),
        ];

        $args = [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'ws-design-category' ],
        ];

        register_taxonomy( 'ws-design-category', [ 'ws-design' ], $args );
    }

    /**
     * Active le support des vignettes pour ce CPT
     */
    public function ensure_thumbnails() {
        add_theme_support( 'post-thumbnails', [ 'ws-design' ] );
    }
}

// Instanciation
new WinShirt_Designs();
