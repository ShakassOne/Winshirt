<?php
if (!defined('ABSPATH')) {
    exit;
}

class WinShirt_Product_Customization {
    public function __construct() {
        // add product option
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_custom_option')); 
        add_action('woocommerce_process_product_meta', array($this, 'save_custom_option'));
        // display button
        add_action('woocommerce_single_product_summary', array($this, 'display_customize_button'), 35);
        // enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function add_custom_option() {
        woocommerce_wp_checkbox(array(
            'id' => '_winshirt_enable_custom',
            'label' => __('Enable WinShirt customization', 'winshirt'),
        ));
    }

    public function save_custom_option($post_id) {
        $value = isset($_POST['_winshirt_enable_custom']) ? 'yes' : 'no';
        update_post_meta($post_id, '_winshirt_enable_custom', $value);
    }

    public function display_customize_button() {
        global $product;
        $enabled = get_post_meta($product->get_id(), '_winshirt_enable_custom', true);
        if ($enabled === 'yes') {
            echo '<button id="winshirt-customize" class="button">' . esc_html__('Customize this product', 'winshirt') . '</button>';
            echo '<div id="winshirt-modal" style="display:none;" class="winshirt-modal"><div class="winshirt-modal-content"><span class="winshirt-close">&times;</span><p>' . esc_html__('Customization interface coming soon...', 'winshirt') . '</p></div></div>';
        }
    }

    public function enqueue_assets() {
        if (is_product()) {
            wp_enqueue_style('winshirt-styles', plugins_url('../assets/css/winshirt.css', __FILE__));
            wp_enqueue_script('winshirt-script', plugins_url('../assets/js/winshirt.js', __FILE__), array('jquery'), null, true);
        }
    }
}
