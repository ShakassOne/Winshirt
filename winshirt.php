<?php
/**
 * Plugin Name: WinShirt
 * Description: Loteries WinShirt (CPT + template premium) + liaison WooCommerce Produits → Loteries (tickets).
 * Version: 1.2.0
 * Author: Shakass Com
 * Text Domain: winshirt
 */

if ( ! defined('ABSPATH') ) {
    exit;
}

define( 'WINSHIRT_VERSION',      '1.2.0' );
define( 'WINSHIRT_PLUGIN_FILE',  __FILE__ );
define( 'WINSHIRT_PLUGIN_DIR',   plugin_dir_path( __FILE__ ) );
define( 'WINSHIRT_PLUGIN_URL',   plugin_dir_url( __FILE__ ) );

/* ---------------------------------------------------------
 * Vérifier PHP
 * ---------------------------------------------------------*/
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
    if ( is_admin() ) {
        add_action('admin_notices', function(){
            echo '<div class="notice notice-error"><p><strong>WinShirt :</strong> PHP 7.4 minimum requis.</p></div>';
        });
    }
    return;
}

/* ---------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------*/
function winshirt_require( string $relative_path ): bool {
    $file = WINSHIRT_PLUGIN_DIR . ltrim( $relative_path, '/' );
    if ( file_exists( $file ) ) {
        require_once $file;
        return true;
    }
    if ( is_admin() ) {
        add_action('admin_notices', function() use ($relative_path) {
            echo '<div class="notice notice-error"><p><strong>WinShirt :</strong> fichier manquant : '.esc_html($relative_path).'</p></div>';
        });
    }
    return false;
}

/* ---------------------------------------------------------
 * Inclusions
 * ---------------------------------------------------------*/
winshirt_require( 'includes/class-winshirt-lottery.php' );
winshirt_require( 'includes/class-winshirt-lottery-template.php' );
winshirt_require( 'includes/class-winshirt-lottery-product-link.php' );

/* ---------------------------------------------------------
 * Activation / désactivation
 * ---------------------------------------------------------*/
function winshirt_activate(){
    if ( class_exists('\\WinShirt\\Lottery') ) {
        \WinShirt\Lottery::register_post_type();
    }
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'winshirt_activate' );

function winshirt_deactivate(){
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'winshirt_deactivate' );

/* ---------------------------------------------------------
 * Bootstrap
 * ---------------------------------------------------------*/
add_action('plugins_loaded', function(){
    if ( class_exists('\\WinShirt\\Lottery') ) \WinShirt\Lottery::instance()->init();
    if ( class_exists('\\WinShirt\\Lottery_Template') ) \WinShirt\Lottery_Template::instance()->init();
    if ( class_exists('\\WinShirt\\Lottery_Product_Link') ) \WinShirt\Lottery_Product_Link::instance()->init();
});
