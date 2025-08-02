<?php
/**
 * Plugin Name: WinShirt
 * Description: Plugin WordPress pour personnalisation textile et gestion de loteries.
 * Version: 3.0
 * Author: Shakass
*/

if (!defined('ABSPATH')) {
    exit;
}

define('WINSHIRT_VERSION', '3.0');
define('WINSHIRT_PATH', plugin_dir_path(__FILE__));

autoload();

function autoload() {
    require_once WINSHIRT_PATH . 'includes/class-winshirt-product-customization.php';
    require_once WINSHIRT_PATH . 'includes/class-winshirt-modal.php';
    require_once WINSHIRT_PATH . 'includes/class-winshirt-admin.php';
}

function winshirt_init() {
    new WinShirt_Product_Customization();
    WinShirt_Modal::init();             // ← appel statique
    new WinShirt_Admin();
}
add_action('plugins_loaded', 'winshirt_init');

function winshirt_plugin_row_meta($links, $file) {
    if ($file === plugin_basename(__FILE__)) {
        $links[] = '<a href="https://shakass.com/" target="_blank">' . esc_html__('Site Web', 'winshirt') . '</a>';
        $links[] = '<a href="' . esc_url(plugins_url('readme.txt', __FILE__)) . '" target="_blank">' . esc_html__('Readme', 'winshirt') . '</a>';
    }
    return $links;
}
add_filter('plugin_row_meta', 'winshirt_plugin_row_meta', 10, 2);
