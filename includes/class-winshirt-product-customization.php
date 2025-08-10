<?php
// includes/class-winshirt-product-customization.php
if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Product_Customization {

    const META_KEY = '_winshirt_personnalisable';
    const MOCKUP_META_KEY = '_winshirt_mockup_id';

    public function __construct() {
        // Ajout de la méta-box produit
        add_action( 'add_meta_boxes',     [ $this, 'add_personalizable_metabox' ] );
        add_action( 'save_post',           [ $this, 'save_personalizable_meta' ], 10, 2 );

        // Front-end : bouton + modal
        add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'print_personalize_button' ] );
        add_action( 'wp_footer',                                [ $this, 'print_modal' ] );
        add_action( 'wp_enqueue_scripts',                       [ $this, 'enqueue_assets' ] );
    }

    /** 1️⃣ Méta-box “Personnalisable” dans l’admin produit */
    public function add_personalizable_metabox() {
        add_meta_box(
            'winshirt_personalizable',
            __( 'WinShirt : personnalisable ?', 'winshirt' ),
            [ $this, 'render_personalizable_metabox' ],
            'product',
            'side',
            'default'
        );
    }

    public function render_personalizable_metabox( $post ) {
        wp_nonce_field( 'winshirt_save_personalizable', 'winshirt_personnalisable_nonce' );
        $checked = get_post_meta( $post->ID, self::META_KEY, true ) === 'yes' ? 'checked' : '';
        echo '<p><label><input type="checkbox" name="winshirt_personnalisable" value="yes" ' . $checked . '/> '
            . esc_html__( 'Produit personnalisable', 'winshirt' )
            . '</label></p>';

        $mockups = get_posts( [
            'post_type'      => 'ws-mockup',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ] );
        $current_mockup = get_post_meta( $post->ID, self::MOCKUP_META_KEY, true );
        echo '<p><label for="winshirt_mockup_id">' . esc_html__( 'Mockup associé', 'winshirt' ) . '</label>';
        echo '<select name="winshirt_mockup_id" id="winshirt_mockup_id" class="widefat">';
        echo '<option value="">' . esc_html__( 'Aucun', 'winshirt' ) . '</option>';
        foreach ( $mockups as $m ) {
            $selected = (int) $current_mockup === $m->ID ? 'selected' : '';
            echo '<option value="' . esc_attr( $m->ID ) . '" ' . $selected . '>' . esc_html( $m->post_title ) . '</option>';
        }
        echo '</select></p>';
    }

    public function save_personalizable_meta( $post_id, $post ) {
        if (
            $post->post_type !== 'product'
            || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            || wp_is_post_revision( $post_id )
            || ! isset( $_POST['winshirt_personnalisable_nonce'] )
            || ! wp_verify_nonce( $_POST['winshirt_personnalisable_nonce'], 'winshirt_save_personalizable' )
        ) {
            return;
        }
        $value = ( isset( $_POST['winshirt_personnalisable'] ) && $_POST['winshirt_personnalisable'] === 'yes' ) ? 'yes' : 'no';
        update_post_meta( $post_id, self::META_KEY, $value );

        $mockup_id = isset( $_POST['winshirt_mockup_id'] ) ? intval( $_POST['winshirt_mockup_id'] ) : 0;
        update_post_meta( $post_id, self::MOCKUP_META_KEY, $mockup_id );
    }

    /** 2️⃣ Front-end : n’affiche le bouton QUE si le produit est personnalisable */
    public function print_personalize_button() {
        if ( ! is_product() ) {
            return;
        }

        $product_id = get_queried_object_id();
        if ( ! $product_id || get_post_meta( $product_id, self::META_KEY, true ) !== 'yes' ) {
            return;
        }

        echo '<button id="winshirt-open-modal" class="button alt" type="button">'
             . esc_html__( 'Personnaliser ce produit', 'winshirt' )
             . '</button>';
    }

    /** 3️⃣ Affiche le conteneur modal de personnalisation */
    public function print_modal() {
        if ( ! is_product() ) {
            return;
        }

        $product_id = get_queried_object_id();
        if ( ! $product_id || get_post_meta( $product_id, self::META_KEY, true ) !== 'yes' ) {
            return;
        }

        include WINSHIRT_PATH . 'templates/modal-customizer.php';
    }

    /** 4️⃣ Enqueue CSS + JS du modal */
    public function enqueue_assets() {
        if ( ! is_product() ) {
            return;
        }

        $product_id = get_queried_object_id();
        if ( ! $product_id || get_post_meta( $product_id, self::META_KEY, true ) !== 'yes' ) {
            return;
        }

        // CSS minimal pour le modal
        wp_enqueue_style( 'winshirt-modal-css', plugins_url( 'assets/css/winshirt-modal.css', WINSHIRT_PATH . 'winshirt.php' ), [], WINSHIRT_VERSION );
        wp_enqueue_style( 'jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css', [], '1.13.2' );

        // JS pour ouvrir/fermer le modal
        wp_enqueue_script( 'jquery-ui-draggable' );
        wp_enqueue_script( 'jquery-ui-resizable' );
        wp_enqueue_script( 'jquery-ui-rotatable', 'https://cdn.jsdelivr.net/npm/jquery-ui-rotatable@1.1.2/jquery.ui.rotatable.min.js', [ 'jquery-ui-draggable', 'jquery-ui-resizable' ], '1.1.2', true );
        wp_enqueue_script( 'winshirt-modal-js', plugins_url( 'assets/js/winshirt-modal.js', WINSHIRT_PATH . 'winshirt.php' ), [ 'jquery', 'jquery-ui-draggable', 'jquery-ui-resizable', 'jquery-ui-rotatable' ], WINSHIRT_VERSION, true );
        wp_enqueue_script( 'winshirt-printzones', plugins_url( 'assets/js/printzones.js', WINSHIRT_PATH . 'winshirt.php' ), [ 'jquery' ], WINSHIRT_VERSION, true );

        $mockup_id = get_post_meta( $product_id, self::MOCKUP_META_KEY, true );
        $front = $mockup_id ? get_post_meta( $mockup_id, '_winshirt_mockup_front_image', true ) : '';
        $back  = $mockup_id ? get_post_meta( $mockup_id, '_winshirt_mockup_back_image', true ) : '';
        if ( ! $front && $mockup_id ) {
            $front = get_post_meta( $mockup_id, '_ws_mockup_front', true );
        }
        if ( ! $back && $mockup_id ) {
            $back = get_post_meta( $mockup_id, '_ws_mockup_back', true );
        }
        if ( $front && ! filter_var( $front, FILTER_VALIDATE_URL ) ) {
            $front = wp_get_attachment_url( $front );
        }
        if ( $back && ! filter_var( $back, FILTER_VALIDATE_URL ) ) {
            $back = wp_get_attachment_url( $back );
        }
        $zones_meta = $mockup_id ? get_post_meta( $mockup_id, '_winshirt_mockup_zones', true ) : [];
        if ( empty( $zones_meta ) && $mockup_id ) {
            $zones_meta = get_post_meta( $mockup_id, '_ws_mockup_zones', true );
        }
        if ( ! is_array( $zones_meta ) ) {
            $zones_meta = [];
        }
        wp_localize_script(
            'winshirt-printzones',
            'WinShirtMockup',
            [
                'front' => [
                    'image' => $front,
                    'zones' => $zones_meta['front'] ?? [],
                ],
                'back'  => [
                    'image' => $back,
                    'zones' => $zones_meta['back'] ?? [],
                ],
            ]
        );
    }
}

// Instanciation
new WinShirt_Product_Customization();
