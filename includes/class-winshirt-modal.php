<?php
if (!defined('ABSPATH')) {
    exit;
}

class WinShirt_Modal {
    /**
     * Initialise les hooks du module.
     *
     * @return self
     */
    public static function init() {
        return new self();
    }

    public function __construct() {
        add_action('woocommerce_single_product_summary', array($this, 'insert_button'), 31);
        add_action('wp_footer', array($this, 'add_modal_template'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    private function is_customizable_product() {
        if (!is_product()) {
            return false;
        }
        global $product;
        if (!$product) {
            return false;
        }
        return get_post_meta($product->get_id(), '_winshirt_personnalisable', true) === 'yes';
    }

    public function insert_button() {
        if ($this->is_customizable_product()) {
            echo '<button type="button" class="btn-personnaliser">' . esc_html__( 'Personnaliser ce produit', 'winshirt' ) . '</button>';
        }
    }

    public function add_modal_template() {
        if ($this->is_customizable_product()) {
            include WINSHIRT_PATH . 'templates/modal-customizer.php';
        }
    }

    public function enqueue_assets() {
        if ($this->is_customizable_product()) {
            wp_enqueue_style(
                'winshirt-modal',
                plugins_url('../assets/css/winshirt-modal.css', __FILE__),
                array(),
                WINSHIRT_VERSION
            );
            wp_enqueue_script(
                'winshirt-modal',
                plugins_url('../assets/js/winshirt-modal.js', __FILE__),
                array(),
                WINSHIRT_VERSION,
                true
            );
        }
    }
}
