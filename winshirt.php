<?php
/**
 * Plugin Name: WinShirt
 * Description: Loteries WinShirt (CPT, tickets en base, commandes WooCommerce, cartes & shortcodes) + liaison Produits ↔ Loteries.
 * Version: 2.0.0
 * Author: Winshirt by Shakass Communication
 * Text Domain: winshirt
 */

if ( ! defined('ABSPATH') ) exit;

/** Constantes */
define('WINSHIRT_VERSION', '2.0.0');
define('WINSHIRT_DIR', plugin_dir_path(__FILE__));
define('WINSHIRT_URL', plugin_dir_url(__FILE__));

/**
 * Charge un fichier relatif au plugin en silence + notice si manquant.
 */
function winshirt_require(string $rel): bool {
    $path = WINSHIRT_DIR . ltrim($rel, '/');
    if ( file_exists($path) ) { require_once $path; return true; }
    add_action('admin_notices', function() use ($rel){
        echo '<div class="notice notice-error"><p><strong>WinShirt</strong> — fichier manquant : <code>'
             . esc_html($rel) . '</code></p></div>';
    });
    return false;
}

/** i18n */
add_action('plugins_loaded', function(){
    load_plugin_textdomain('winshirt', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

/* ===== Inclusions cœur =====
   On inclut tout ce qui peut exister dans ton repo. Chaque inclusion est tolérante :
   si le fichier n’existe pas, on continue sans casser le site. */
winshirt_require('includes/class-winshirt-lottery.php');               // CPT + logique de base
winshirt_require('includes/class-winshirt-lottery-template.php');      // Shortcodes + cartes (overlay/diagonal)
winshirt_require('includes/class-winshirt-lottery-display.php');       // (si présent) options d’affichage admin / front
winshirt_require('includes/class-winshirt-lottery-product-link.php');  // Woo Produits ↔ Loteries
winshirt_require('includes/class-winshirt-tickets.php');               // Tickets en base (table SQL)
winshirt_require('includes/class-winshirt-lottery-order.php');         // Crédit tickets depuis commandes Woo

/* ===== Activation / Désactivation ===== */
register_activation_hook(__FILE__, function(){
    // Registre le CPT pour générer les réécritures même si init() n’a pas encore tourné
    if ( class_exists('\\WinShirt\\Lottery') ) {
        // Compat : certaines versions nomment la méthode différemment
        if ( method_exists('\\WinShirt\\Lottery', 'register_cpt') ) {
            \WinShirt\Lottery::instance()->register_cpt();
        } elseif ( method_exists('\\WinShirt\\Lottery', 'register_post_type') ) {
            \WinShirt\Lottery::register_post_type();
        }
    }
    // Crée/MAJ la table des tickets si dispo
    if ( class_exists('\\WinShirt\\Tickets') && method_exists('\\WinShirt\\Tickets','install') ) {
        \WinShirt\Tickets::instance()->install();
    }
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function(){
    flush_rewrite_rules();
});

/* ===== Bootstrap =====
   On initialise uniquement ce qui est disponible dans l’arborescence actuelle. */
add_action('plugins_loaded', function(){
    if ( class_exists('\\WinShirt\\Lottery') )                 \WinShirt\Lottery::instance()->init();
    if ( class_exists('\\WinShirt\\Lottery_Template') )        \WinShirt\Lottery_Template::instance()->init();
    if ( class_exists('\\WinShirt\\Lottery_Display') )         \WinShirt\Lottery_Display::instance()->init();
    if ( class_exists('\\WinShirt\\Lottery_Product_Link') )    \WinShirt\Lottery_Product_Link::instance()->init();
    if ( class_exists('\\WinShirt\\Tickets') )                 \WinShirt\Tickets::instance()->init();
    if ( class_exists('\\WinShirt\\Lottery_Order') )           \WinShirt\Lottery_Order::instance()->init();
});
