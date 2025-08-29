<?php
/**
 * Plugin Name: WinShirt
 * Description: Gestion des loteries WinShirt (articles/portfolios), slug Portfolio, overlay miniatures, et liaison WooCommerce.
 * Version: 2.1.0
 * Author: WinShirt by Shakass Communication
 * Text Domain: winshirt
 */

if ( ! defined('ABSPATH') ) exit;

// Constantes
define('WINSHIRT_VERSION', '2.1.0');
define('WINSHIRT_FILE', __FILE__);
define('WINSHIRT_DIR', plugin_dir_path(__FILE__));
define('WINSHIRT_URL', plugin_dir_url(__FILE__));

if ( is_admin() ) {
    require_once WINSHIRT_DIR . 'admin/class-winshirt-simulator.php';
}

// Inclusions obligatoires
require_once WINSHIRT_DIR . 'includes/class-winshirt-admin.php';
require_once WINSHIRT_DIR . 'includes/class-winshirt-lottery-meta.php';
require_once WINSHIRT_DIR . 'includes/class-winshirt-product-link.php';
require_once WINSHIRT_DIR . 'includes/class-winshirt-slugs.php';
require_once WINSHIRT_DIR . 'includes/class-winshirt-archive-overlay.php';

if ( is_admin() ) {
    require_once WINSHIRT_DIR . 'admin/class-winshirt-simulator.php';
}




// Initialisation
add_action('plugins_loaded', function () {
    if ( class_exists('WS_Admin') ) WS_Admin::init();
    if ( class_exists('WS_Lottery_Meta') ) WS_Lottery_Meta::init();
    if ( class_exists('WS_Product_Link') ) WS_Product_Link::init();
    if ( class_exists('WS_Slugs') ) WS_Slugs::init();
    if ( class_exists('WS_Archive_Overlay') ) WS_Archive_Overlay::init();
});

// Activation
register_activation_hook(__FILE__, function () {
    if ( get_option('winshirt_portfolio_slug', null) === null ) {
        add_option('winshirt_portfolio_slug', '');
    }
    if ( get_option('winshirt_lottery_category', null) === null ) {
        add_option('winshirt_lottery_category', 'loterie');
    }
    flush_rewrite_rules();
});

// Désactivation
register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
