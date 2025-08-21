<?php
/**
 * Plugin Name: WinShirt
 * Description: Loteries WinShirt (CPT + shortcodes + tickets) avec bootstrap tolérant (charge uniquement ce qui existe).
 * Version: 2.0.0-tolerant
 * Author: WinShirt by Shakass Communication
 * Text Domain: winshirt
 */

if (!defined('ABSPATH')) exit;

/** Constantes basiques */
define('WINSHIRT_VERSION', '2.0.0-tolerant');
define('WINSHIRT_DIR', plugin_dir_path(__FILE__));
define('WINSHIRT_URL', plugin_dir_url(__FILE__));

/**
 * Require tolérant : n’émet pas de fatal si le fichier est absent.
 * Retourne true si require a eu lieu, false sinon.
 */
function winshirt_require(string $rel): bool {
    $p = WINSHIRT_DIR . ltrim($rel, '/');
    if (file_exists($p)) {
        require_once $p;
        return true;
    }
    return false;
}

/**
 * Résolution “intelligente” des noms de classes :
 * - Essaie namespace \WinShirt\*, puis sans namespace, puis variantes courantes.
 * - Retourne le nom de classe trouvé ou null.
 */
function winshirt_resolve_class(array $candidates): ?string {
    foreach ($candidates as $c) {
        if (class_exists($c)) return $c;
    }
    return null;
}

/**
 * Initialisation souple d’un module :
 * - Patterns couverts : ::instance()->init(), ::instance(), new Class(), ::init(), objet->init()
 */
function winshirt_boot_module(array $classCandidates, array $initMethods = ['init']) {
    $class = winshirt_resolve_class($classCandidates);
    if (!$class) return;

    // 1) Pattern singleton :instance()
    if (method_exists($class, 'instance')) {
        $obj = call_user_func([$class, 'instance']);
        if (is_object($obj)) {
            foreach ($initMethods as $m) {
                if (method_exists($obj, $m)) { $obj->{$m}(); return; }
            }
        }
    }

    // 2) Méthode statique ::init()
    foreach ($initMethods as $m) {
        if (method_exists($class, $m)) { call_user_func([$class, $m]); return; }
    }

    // 3) Fallback : new Class() puis ->init()
    if (class_exists($class)) {
        $obj = new $class();
        foreach ($initMethods as $m) {
            if (method_exists($obj, $m)) { $obj->{$m}(); return; }
        }
    }
}

/** Chargements “silencieux” (uniquement si présents) */
add_action('plugins_loaded', function () {
    // Core files
    winshirt_require('includes/class-winshirt-lottery.php');
    winshirt_require('includes/class-winshirt-lottery-template.php');
    winshirt_require('includes/class-winshirt-tickets.php');
    winshirt_require('includes/class-winshirt-lottery-order.php');
    winshirt_require('includes/class-winshirt-lottery-display.php');
    winshirt_require('includes/class-winshirt-lottery-product-link.php');

    // Détection Woo : on ne branche la partie commandes que si Woo est là
    $hasWoo = defined('WC_VERSION');

    // Boot des modules (on liste plusieurs alias possibles)
    // Lottery (CPT + cœur)
    winshirt_boot_module([
        '\\WinShirt\\Lottery',
        'WinShirt\\Lottery',
        'Winshirt\\Lottery',
        'Lottery', // au cas où pas de namespace
    ], ['init', 'bootstrap', 'run']);

    // Shortcodes + templates
    winshirt_boot_module([
        '\\WinShirt\\Lottery_Template',
        'WinShirt\\Lottery_Template',
        'Lottery_Template',
    ], ['init', 'register']);

    // Tickets (table SQL + API)
    winshirt_boot_module([
        '\\WinShirt\\Tickets',
        'WinShirt\\Tickets',
        'Tickets',
    ], ['init', 'register']);

    // Woo → Orders → Tickets (seulement si Woo)
    if ($hasWoo) {
        winshirt_boot_module([
            '\\WinShirt\\Lottery_Order',
            'WinShirt\\Lottery_Order',
            'Lottery_Order',
        ], ['init', 'hook']);
    }

    // Réglages d’affichage (optionnel)
    winshirt_boot_module([
        '\\WinShirt\\Lottery_Display',
        'WinShirt\\Lottery_Display',
        'Lottery_Display',
    ], ['init', 'register']);

    // Lien Produits ↔ Loteries (Woo)
    if ($hasWoo) {
        winshirt_boot_module([
            '\\WinShirt\\Lottery_Product_Link',
            'WinShirt\\Lottery_Product_Link',
            'Lottery_Product_Link',
        ], ['init', 'register']);
    }
});

/** Activation : installe la table tickets si dispo + flush rewrite */
register_activation_hook(__FILE__, function () {
    // Charger fichiers nécessaires si pas déjà chargés
    winshirt_require('includes/class-winshirt-lottery.php');
    winshirt_require('includes/class-winshirt-tickets.php');

    // Enregistrement CPT (couverture deux méthodes possibles)
    $lotteryClass = winshirt_resolve_class([
        '\\WinShirt\\Lottery',
        'WinShirt\\Lottery',
        'Winshirt\\Lottery',
        'Lottery'
    ]);

    if ($lotteryClass) {
        if (method_exists($lotteryClass, 'register_cpt')) {
            // instance()->register_cpt()    ou   ::register_cpt()
            if (method_exists($lotteryClass, 'instance')) {
                $obj = call_user_func([$lotteryClass, 'instance']);
                if (is_object($obj) && method_exists($obj, 'register_cpt')) $obj->register_cpt();
            } else {
                call_user_func([$lotteryClass, 'register_cpt']);
            }
        } elseif (method_exists($lotteryClass, 'register_post_type')) {
            call_user_func([$lotteryClass, 'register_post_type']);
        }
    }

    // Installation Tickets si présent (instance()->install() ou ::install())
    $ticketsClass = winshirt_resolve_class([
        '\\WinShirt\\Tickets',
        'WinShirt\\Tickets',
        'Tickets'
    ]);
    if ($ticketsClass && method_exists($ticketsClass, 'install')) {
        if (method_exists($ticketsClass, 'instance')) {
            $obj = call_user_func([$ticketsClass, 'instance']);
            if (is_object($obj) && method_exists($obj, 'install')) $obj->install();
        } else {
            call_user_func([$ticketsClass, 'install']);
        }
    }

    flush_rewrite_rules();
});

/** i18n facultatif (si des .mo/.po existent plus tard) */
add_action('init', function () {
    load_plugin_textdomain('winshirt', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

/** Enqueue minimal des assets front si présents (laisse passer si absents) */
add_action('wp_enqueue_scripts', function () {
    $css = 'assets/css/winshirt-lottery.css';
    $js  = 'assets/js/winshirt-lottery.js';

    if (file_exists(WINSHIRT_DIR . $css)) {
        wp_enqueue_style('winshirt-lottery', WINSHIRT_URL . $css, [], WINSHIRT_VERSION);
    }
    if (file_exists(WINSHIRT_DIR . $js)) {
        wp_enqueue_script('winshirt-lottery', WINSHIRT_URL . $js, ['jquery'], WINSHIRT_VERSION, true);
    }
});
