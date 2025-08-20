<?php
/**
 * Plugin Name: WinShirt
 * Description: Loteries WinShirt (CPT + tickets en base + cartes + shortcodes) + liaison WooCommerce Produits ↔ Loteries (tickets).
 * Version: 2.0.0
 * Author: WinShirt Recovery
 * Text Domain: winshirt
 */
if ( ! defined('ABSPATH') ) exit;

define('WINSHIRT_VERSION','2.0.0');
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

/* ===== Inclusions cœur ===== */
winshirt_require('includes/class-winshirt-lottery.php');               // CPT + shortcodes + form
winshirt_require('includes/class-winshirt-lottery-product-link.php');  // Produits ↔ Loteries
winshirt_require('includes/class-winshirt-tickets.php');               // ✅ Tickets en base
winshirt_require('includes/class-winshirt-lottery-order.php');         // Crédit tickets depuis commandes (utilise Tickets)
winshirt_require('includes/class-winshirt-lottery-display.php');       // ✅ Options d'affichage / rendu

/* ===== Activation / Désactivation ===== */
register_activation_hook(__FILE__, function(){
    // 1) Registre le CPT pour la réécriture
    if ( class_exists('\\WinShirt\\Lottery') ) { \WinShirt\Lottery::instance()->register_cpt(); }
    // 2) Crée/Met à jour la table de tickets
    if ( class_exists('\\WinShirt\\Tickets') ) { \WinShirt\Tickets::instance()->install(); }
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function(){ flush_rewrite_rules(); });

/* ===== Bootstrap ===== */
add_action('plugins_loaded', function(){
    \WinShirt\Lottery::instance()->init();
    \WinShirt\Lottery_Product_Link::instance()->init();
    \WinShirt\Tickets::instance()->init();            // endpoints export & outils
    \WinShirt\Lottery_Order::instance()->init();      // paiement → tickets
    \WinShirt\Lottery_Display::instance()->init();    // options d'affichage
});
