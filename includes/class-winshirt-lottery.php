<?php
// includes/class-winshirt-lottery.php
if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Lottery {

    const TAX_SLUG = 'loterie';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_taxonomy' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_lottery_metabox' ] );
        add_action( 'save_post', [ __CLASS__, 'save_lottery_meta' ], 10, 2 );
        add_filter( 'manage_posts_columns', [ __CLASS__, 'add_columns' ], 10, 2 );
        add_action( 'manage_posts_custom_column', [ __CLASS__, 'render_columns' ], 10, 2 );
    }

    // 1) Taxonomie « Loterie » attachée aux articles
    public static function register_taxonomy() {
        $labels = [
            'name'          => __( 'Loteries', 'winshirt' ),
            'singular_name' => __( 'Loterie',  'winshirt' ),
            'search_items'  => __( 'Rechercher une loterie', 'winshirt' ),
            'all_items'     => __( 'Toutes les loteries',    'winshirt' ),
            'edit_item'     => __( 'Éditer la loterie',      'winshirt' ),
            'update_item'   => __( 'Mettre à jour',          'winshirt' ),
            'add_new_item'  => __( 'Ajouter une loterie',    'winshirt' ),
            'new_item_name' => __( 'Nouvelle loterie',       'winshirt' ),
            'menu_name'     => __( 'Loteries',               'winshirt' ),
        ];
        $args = [
            'labels'            => $labels,
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_in_menu'      => false, // on restera sous Articles
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'loterie' ],
        ];
        register_taxonomy( self::TAX_SLUG, 'post', $args );
    }

    // 2) Métabox pour associer un ou plusieurs produits
    public static function add_lottery_metabox() {
        add_meta_box(
            'winshirt_lottery_products',
            __( 'Produits associés', 'winshirt' ),
            [ __CLASS__, 'render_products_metabox' ],
            'post',
            'side',
            'default',
            [ 'taxonomy' => self::TAX_SLUG ] 
        );
    }

    public static function render_products_metabox( $post, $box ) {
        wp_nonce_field( 'winshirt_save_lottery', 'winshirt_lottery_nonce' );
        // Récupère l’association existante
        $associated = get_post_meta( $post->ID, '_winshirt_lottery_products', true ) ?: [];
        // Liste des produits WP
        $args = [ 'post_type' => 'product', 'posts_per_page' => -1 ];
        $products = get_posts( $args );
        echo '<p>'.__( 'Sélectionnez les produits participant à cette loterie :', 'winshirt' ).'</p>';
        echo '<select name="winshirt_lottery_products[]" multiple style="width:100%;">';
        foreach ( $products as $prod ) {
            $sel = in_array( $prod->ID, $associated ) ? 'selected' : '';
            printf( '<option value="%d" %s>%s</option>',
                $prod->ID, $sel, esc_html( $prod->post_title )
            );
        }
        echo '</select>';
    }

    public static function save_lottery_meta( $post_id, $post ) {
        if ( ! isset( $_POST['winshirt_lottery_nonce'] )
            || ! wp_verify_nonce( $_POST['winshirt_lottery_nonce'], 'winshirt_save_lottery' )
            || $post->post_type !== 'post' ) {
            return;
        }
        $prods = array_map( 'intval', (array) ($_POST['winshirt_lottery_products'] ?? []) );
        update_post_meta( $post_id, '_winshirt_lottery_products', $prods );
    }

    // 3) Colonnes personnalisées dans la liste d’articles
    public static function add_columns( $columns, $post_type ) {
        if ( $post_type === 'post' ) {
            $columns['winshirt_lottery_date']    = __( 'Date tirage', 'winshirt' );
            $columns['winshirt_lottery_products']= __( 'Produits liés', 'winshirt' );
        }
        return $columns;
    }

    public static function render_columns( $column, $post_id ) {
        if ( $column === 'winshirt_lottery_date' ) {
            $date = get_the_date( 'Y-m-d', $post_id );
            echo esc_html( $date );
        }
        if ( $column === 'winshirt_lottery_products' ) {
            $ids = get_post_meta( $post_id, '_winshirt_lottery_products', true ) ?: [];
            $titles = array_map( function( $id ){
                return get_the_title( $id );
            }, $ids );
            echo esc_html( implode( ', ', $titles ) );
        }
    }
}

// Lancement
WinShirt_Lottery::init();
