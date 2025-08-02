<?php
// includes/class-winshirt-lottery.php
if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Lottery {

    const TAX_SLUG    = 'loterie';
    const AJAX_ACTION = 'winshirt_send_lottery_participants';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_taxonomy' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_lottery_metabox' ] );
        add_action( 'save_post', [ __CLASS__, 'save_lottery_meta' ], 10, 2 );
        add_filter( 'manage_posts_columns', [ __CLASS__, 'add_columns' ], 10, 2 );
        add_action( 'manage_posts_custom_column', [ __CLASS__, 'render_columns' ], 10, 2 );

        // Hooks pour l'envoi des participants
        add_action( 'edit_form_after_title', [ __CLASS__, 'render_send_button' ] );
        add_action( 'admin_enqueue_scripts',  [ __CLASS__, 'enqueue_admin_script' ] );
        add_action( 'wp_ajax_' . self::AJAX_ACTION, [ __CLASS__, 'ajax_send_participants' ] );
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

    /**
     * Affiche un bouton d'envoi pour les posts de type 'post' ayant la taxonomie 'loterie'
     */
    public static function render_send_button( $post ) {
        if ( $post->post_type !== 'post' ) {
            return;
        }
        if ( ! has_term( '', self::TAX_SLUG, $post ) ) {
            return;
        }

        wp_nonce_field( 'winshirt_send_lottery', 'winshirt_send_lottery_nonce' );
        echo '<div style="margin:10px 0;">';
        echo '<button type="button" class="button button-primary" id="winshirt-send-participants" data-post-id="' . esc_attr( $post->ID ) . '">' . __( 'Envoyer la liste des participants à l’huissier', 'winshirt' ) . '</button>';
        echo '<span id="winshirt-send-status" style="margin-left:10px;"></span>';
        echo '</div>';
    }

    /**
     * Injecte le JS admin nécessaire
     */
    public static function enqueue_admin_script( $hook ) {
        if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) {
            return;
        }
        wp_enqueue_script(
            'winshirt-lottery-admin',
            plugins_url( 'assets/js/admin-lottery.js', WINSHIRT_PATH . 'winshirt.php' ),
            [ 'jquery' ],
            WINSHIRT_VERSION,
            true
        );
        wp_localize_script(
            'winshirt-lottery-admin',
            'WinShirtLottery',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'action'  => self::AJAX_ACTION,
            ]
        );
    }

    /**
     * Handler AJAX pour compiler et envoyer la liste
     */
    public static function ajax_send_participants() {
        if ( ! current_user_can( 'edit_posts' )
            || ! isset( $_POST['nonce'] )
            || ! wp_verify_nonce( $_POST['nonce'], 'winshirt_send_lottery' )
            || empty( $_POST['post_id'] ) ) {
            wp_send_json_error( __( 'Accès refusé', 'winshirt' ), 403 );
        }

        $post_id = intval( $_POST['post_id'] );

        $products = get_post_meta( $post_id, '_winshirt_lottery_products', true );
        if ( empty( $products ) ) {
            wp_send_json_error( __( 'Aucun participant défini.', 'winshirt' ), 400 );
        }

        $participants = [];
        foreach ( $products as $prod_id ) {
            $orders = wc_get_orders([
                'limit'      => -1,
                'status'     => [ 'pending', 'processing', 'completed' ],
                'product_id' => $prod_id,
            ]);
            foreach ( $orders as $order ) {
                $email = $order->get_billing_email();
                $name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
                $participants[ $email ] = $name;
            }
        }
        if ( empty( $participants ) ) {
            wp_send_json_error( __( 'Aucun joueur trouvé.', 'winshirt' ), 404 );
        }

        $body  = 'Liste des participants pour la loterie « ' . get_the_title( $post_id ) . " » :\n\n";
        foreach ( $participants as $email => $name ) {
            $body .= sprintf( '- %s <%s>\n', $name, $email );
        }

        $settings = get_option( 'winshirt_settings', [] );
        $to = $settings['bailiff_email'] ?? get_option( 'admin_email' );

        $subject = sprintf( 'Participants Loterie : %s', get_the_title( $post_id ) );
        $sent = wp_mail( $to, $subject, $body );

        if ( ! $sent ) {
            wp_send_json_error( __( 'Échec de l’envoi.', 'winshirt' ), 500 );
        }

        wp_send_json_success( __( 'Liste envoyée !', 'winshirt' ) );
    }
}

// Lancement
WinShirt_Lottery::init();
