<?php
/**
 * Plugin Name: WinShirt
 * Description: Loteries WinShirt (CPT + cartes + shortcodes) + liaison WooCommerce Produits ↔ Loteries (tickets).
 * Version: 1.3.0
 * Author: WinShirt Recovery
 * Text Domain: winshirt
 */

if ( ! defined('ABSPATH') ) exit;

/* ---------------------------------------------------------------------------
 * Constantes & prérequis
 * -------------------------------------------------------------------------*/
define( 'WINSHIRT_VERSION',    '1.3.0' );
define( 'WINSHIRT_MIN_PHP',    '7.4' );
define( 'WINSHIRT_DIR',        plugin_dir_path( __FILE__ ) );
define( 'WINSHIRT_URL',        plugin_dir_url( __FILE__ ) );

if ( version_compare( PHP_VERSION, WINSHIRT_MIN_PHP, '<' ) ) {
    add_action('admin_notices', function(){
        echo '<div class="notice notice-error"><p><b>WinShirt</b> requiert PHP '.esc_html(WINSHIRT_MIN_PHP).' ou supérieur.</p></div>';
    });
    return;
}

/* ---------------------------------------------------------------------------
 * Loader sécurisé (notice si fichier manquant)
 * -------------------------------------------------------------------------*/
function winshirt_require( string $rel ): bool {
    $path = WINSHIRT_DIR . ltrim($rel,'/');
    if ( file_exists($path) ) { require_once $path; return true; }
    add_action('admin_notices', function() use ($rel){
        echo '<div class="notice notice-error"><p><b>WinShirt</b> : fichier manquant <code>'.esc_html($rel).'</code></p></div>';
    });
    return false;
}

/* ---------------------------------------------------------------------------
 * Inclusions obligatoires
 * -------------------------------------------------------------------------*/
winshirt_require('includes/class-winshirt-lottery.php');
winshirt_require('includes/class-winshirt-lottery-template.php');
winshirt_require('includes/class-winshirt-lottery-product-link.php');

/* ---------------------------------------------------------------------------
 * Activation / Désactivation
 * -------------------------------------------------------------------------*/
function winshirt_activate(){
    if ( class_exists('\\WinShirt\\Lottery') ) {
        \WinShirt\Lottery::instance()->register_cpt(); // s’assure que le CPT existe
    }
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'winshirt_activate');

register_deactivation_hook(__FILE__, function(){ flush_rewrite_rules(); });

/* ---------------------------------------------------------------------------
 * Bootstrap
 * -------------------------------------------------------------------------*/
add_action('plugins_loaded', function(){
    \WinShirt\Lottery::instance()->init();
    \WinShirt\Lottery_Template::instance()->init();
    \WinShirt\Lottery_Product_Link::instance()->init();
});
