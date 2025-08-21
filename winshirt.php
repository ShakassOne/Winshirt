<?php
/*
Plugin Name: WinShirt
Description: Module de personnalisation, produits et loteries pour WooCommerce.
Version: 1.0.0
Author: Winshirt by Shakass Communication
*/

if (!defined('ABSPATH')) { exit; }

define('WINSHIRT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WINSHIRT_PLUGIN_URL', plugin_dir_url(__FILE__));

register_activation_hook(__FILE__, function () {
    require_once WINSHIRT_PLUGIN_DIR . 'includes/class-winshirt-lottery.php';
    \WinShirt\Lottery::register_post_type();
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function () { flush_rewrite_rules(); });

require_once WINSHIRT_PLUGIN_DIR . 'includes/class-winshirt-lottery.php';
require_once WINSHIRT_PLUGIN_DIR . 'includes/class-winshirt-lottery-template.php';
require_once WINSHIRT_PLUGIN_DIR . 'includes/class-winshirt-lottery-product-link.php';

add_action('plugins_loaded', function () {
    \WinShirt\Lottery::instance()->init();
    \WinShirt\Lottery_Template::instance()->init();
    \WinShirt\Lottery_Product_Link::instance()->init();
});
