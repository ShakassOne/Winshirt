<?php
/**
 * Plugin Name: WinShirt (Quarantine)
 * Description: Bootstrap neutre (aucune inclusion) pour rétablir le site sans supprimer l’extension. À remplacer par la version complète après diagnostic.
 * Version: 0.0.1
 * Author: WinShirt by Shakass
 */

if ( ! defined('ABSPATH') ) exit;

/**
 * IMPORTANT :
 * - Ce bootstrap ne charge AUCUN fichier.
 * - Il ne déclare AUCUNE classe.
 * - Il ne touche PAS à WooCommerce.
 * => Impossible qu’il provoque un fatal au front.
 */

/** Petite bannière en admin pour rappeler l’état “quarantaine”. */
add_action('admin_notices', function () {
    if ( ! current_user_can('activate_plugins') ) return;
    $url = add_query_arg('winshirt_diag', '1', home_url('/'));
    echo '<div class="notice notice-warning"><p><strong>WinShirt</strong> est en <em>mode quarantaine</em> (aucune fonctionnalité chargée). '
       . 'Utilise la doc/diag fournis pour réinstaller le bootstrap complet en toute sécurité. '
       . '<a href="' . esc_url($url) . '">Ouvrir le mini diagnostic</a></p></div>';
});

/** Lien rapide vers le mini diagnostic (optionnel). */
add_action('plugins_loaded', function () {
    if ( isset($_GET['winshirt_diag']) && current_user_can('manage_options') ) {
        require_once __DIR__ . '/DEV_DIAG/winshirt_diag.php';
        exit;
    }
});
