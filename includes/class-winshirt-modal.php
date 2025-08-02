<?php
// includes/class-winshirt-modal.php
if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Modal {

    public function __construct() {
        add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'add_button' ] );
        add_action( 'wp_footer',                           [ $this, 'print_modal' ] );
        add_action( 'wp_enqueue_scripts',                  [ $this, 'enqueue_assets' ] );
    }

    public function add_button() {
        if ( is_product() ) {
            global $product;
            if ( 'yes' === get_post_meta( $product->get_id(), '_winshirt_personnalisable', true ) ) {
                echo '<button class="btn-personnaliser" id="winshirt-open-modal">Personnaliser ce produit</button>';
            }
        }
    }

    public function print_modal() {
        if ( is_product() ) {
            global $product;
            if ( 'yes' === get_post_meta( $product->get_id(), '_winshirt_personnalisable', true ) ) {
                include WINSHIRT_PATH . 'templates/modal-customizer.php';
            }
        }
    }

    public function enqueue_assets() {
        if ( is_product() ) {
            global $product;
            if ( 'yes' === get_post_meta( $product->get_id(), '_winshirt_personnalisable', true ) ) {
                wp_enqueue_style(
                    'winshirt-modal',
                    plugins_url( 'assets/css/winshirt-modal.css', WINSHIRT_PATH . 'winshirt.php' ),
                    [],
                    WINSHIRT_VERSION
                );
                wp_enqueue_script(
                    'winshirt-modal',
                    plugins_url( 'assets/js/winshirt-modal.js', WINSHIRT_PATH . 'winshirt.php' ),
                    [],
                    WINSHIRT_VERSION,
                    true
                );
            }
        }
    }
}

// Instanciation
new WinShirt_Modal();

