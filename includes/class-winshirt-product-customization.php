<?php
// includes/class-winshirt-product-customization.php
if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Product_Customization {

    const META_KEY = '_winshirt_personnalisable';

    public function __construct() {
        // Ajout de la méta-box produit
        add_action( 'add_meta_boxes',     [ $this, 'add_personalizable_metabox' ] );
        add_action( 'save_post_product',   [ $this, 'save_personalizable_meta' ], 10, 2 );

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
        wp_nonce_field( 'winshirt_save_personalizable', 'winshirt_personalizable_nonce' );
        $checked = get_post_meta( $post->ID, self::META_KEY, true ) === 'yes' ? 'checked' : '';
        echo '<label><input type="checkbox" name="winshirt_personnalisable" value="yes" ' . $checked . '/> '
            . esc_html__( 'Produit personnalisable', 'winshirt' )
            . '</label>';
    }

    public function save_personalizable_meta( $post_id, $post ) {
        if (
            ! isset( $_POST['winshirt_personnalisable_nonce'] )
            || ! wp_verify_nonce( $_POST['winshirt_personnalisable_nonce'], 'winshirt_save_personalizable' )
            || $post->post_type !== 'product'
        ) {
            return;
        }
        $value = (isset($_POST['winshirt_personnalisable']) && $_POST['winshirt_personnalisable'] === 'yes') ? 'yes' : 'no';
        update_post_meta( $post_id, self::META_KEY, $value );
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

        echo '<button id="winshirt-open-modal" class="button alt">'
             . esc_html__( 'Personnaliser ce produit', 'winshirt' )
             . '</button>';
    }

    /** 3️⃣ Print le conteneur modal (vide pour l’instant) en bas de la page */
    public function print_modal() {
        if ( ! is_product() ) {
            return;
        }

        $product_id = get_queried_object_id();
        if ( ! $product_id || get_post_meta( $product_id, self::META_KEY, true ) !== 'yes' ) {
            return;
        }
        ?>
        <div id="winshirt-modal-overlay" style="display:none;">
          <div id="winshirt-modal-container">
            <button id="winshirt-modal-close">&times;</button>
            <div id="winshirt-modal-content">
              <!-- Ici on injectera le HTML/CANVAS de personnalisation -->
              <p><?php esc_html_e( 'Chargement de l’interface de personnalisation…', 'winshirt' ); ?></p>
            </div>
          </div>
        </div>
        <?php
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

        // JS pour ouvrir/fermer le modal
        wp_enqueue_script( 'winshirt-modal-js', plugins_url( 'assets/js/winshirt-modal.js', WINSHIRT_PATH . 'winshirt.php' ), [ 'jquery' ], WINSHIRT_VERSION, true );
    }
}

// Instanciation
new WinShirt_Product_Customization();
