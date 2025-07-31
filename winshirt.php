<?php
/**
 * Plugin Name: WinShirt
 * Description: Plugin WordPress pour personnalisation textile et gestion de loteries.
 * Version: 1.0
 * Author: Shakass
*/

if (!defined('ABSPATH')) {
    exit;
}

define('WINSHIRT_VERSION', '1.0');
define('WINSHIRT_PATH', plugin_dir_path(__FILE__));

autoload();

function autoload() {
    require_once WINSHIRT_PATH . 'includes/class-winshirt-product-customization.php';
}

function winshirt_init() {
    new WinShirt_Product_Customization();
}
add_action('plugins_loaded', 'winshirt_init');
