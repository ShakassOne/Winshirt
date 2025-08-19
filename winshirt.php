<?php
/**
 * Plugin Name: WinShirt
 * Description: Loteries WinShirt (CPT + cartes + shortcodes) + liaison WooCommerce Produits ↔ Loteries (tickets).
 * Version: 1.4.0
 * Author: WinShirt Recovery
 * Text Domain: winshirt
 */
if ( ! defined('ABSPATH') ) exit;

define('WINSHIRT_VERSION','1.4.0');
define('WINSHIRT_DIR', plugin_dir_path(__FILE__));
define('WINSHIRT_URL', plugin_dir_url(__FILE__));

function winshirt_require(string $rel): bool {
    $p = WINSHIRT_DIR . ltrim($rel,'/');
    if ( file_exists($p) ) { require_once $p; return true; }
    add_action('admin_notices', function() use ($rel){
        echo '<div class="notice notice-error"><p><b>WinShirt</b> – fichier manquant : <code>'.esc_html($rel).'</code></p></div>';
    });
    return false;
}

/* Inclusions coeur */
winshirt_require('includes/class-winshirt-lottery.php');
winshirt_require('includes/class-winshirt-lottery-template.php');
winshirt_require('includes/class-winshirt-lottery-product-link.php');
winshirt_require('includes/class-winshirt-lottery-order.php'); // ⚡ nouveau

/* Activation / Désactivation */
register_activation_hook(__FILE__, function(){
    if ( class_exists('\\WinShirt\\Lottery') ) { \WinShirt\Lottery::instance()->register_cpt(); }
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function(){ flush_rewrite_rules(); });

/* Bootstrap */
add_action('plugins_loaded', function(){
    \WinShirt\Lottery::instance()->init();
    \WinShirt\Lottery_Template::instance()->init();
    \WinShirt\Lottery_Product_Link::instance()->init();
    \WinShirt\Lottery_Order::instance()->init(); // ⚡ gestion commandes/emails/participants
});
