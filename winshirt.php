<?php
/**
 * Plugin Name: WinShirt
 * Description: Gestion des loteries WinShirt (articles/portfolios), slug Portfolio, overlay miniatures, et liaison WooCommerce.
 * Version: 2.1.0
 * Author: WinShirt by Shakass Communication
 * Text Domain: winshirt
 */

if ( ! defined('ABSPATH') ) exit;

// Définition des constantes
define('WINSHIRT_VERSION', '2.1.0');
define('WINSHIRT_FILE', __FILE__);
define('WINSHIRT_DIR', plugin_dir_path(__FILE__));
define('WINSHIRT_URL', plugin_dir_url(__FILE__));

// Chargement des classes
require_once WINSHIRT_DIR . 'includes/class-winshirt-admin.php';
require_once WINSHIRT_DIR . 'includes/class-winshirt-lottery-meta.php';
require_once WINSHIRT_DIR . 'includes/class-winshirt-product-link.php';
require_once WINSHIRT_DIR . 'includes/class-winshirt-slugs.php';
require_once WINSHIRT_DIR . 'includes/class-winshirt-archive-overlay.php';

// Chargement des templates utilitaires
if ( file_exists(WINSHIRT_DIR . 'includes/template-tags.php') ) {
    require_once WINSHIRT_DIR . 'includes/template-tags.php';
}

// Initialisation
add_action('plugins_loaded', function () {
    WS_Admin::init();
    WS_Lottery_Meta::init();
    WS_Product_Link::init();
    WS_Slugs::init();
    WS_Archive_Overlay::init();
});

// Activation / désactivation
register_activation_hook(__FILE__, function () {
    if ( get_option('winshirt_portfolio_slug', null) === null ) {
        add_option('winshirt_portfolio_slug', '');
    }
    if ( get_option('winshirt_lottery_category', null) === null ) {
        add_option('winshirt_lottery_category', 'loterie');
    }
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function(){
    flush_rewrite_rules();
});

// Chargement des shortcodes existants (si tu en avais)
if ( file_exists(WINSHIRT_DIR . 'includes/shortcodes.php') ) {
    require_once WINSHIRT_DIR . 'includes/shortcodes.php';
}
