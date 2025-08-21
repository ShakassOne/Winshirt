<?php
/**
 * Plugin Name: WinShirt
 * Description: Loteries WinShirt (CPT + shortcodes + tickets) avec tous les modules en commentaires.
 * Version: 2.0.0-debug
 * Author: WinShirt by Shakass Communication
 * Text Domain: winshirt
 */

if (!defined('ABSPATH')) exit;

define('WINSHIRT_VERSION', '2.0.0-debug');
define('WINSHIRT_DIR', plugin_dir_path(__FILE__));
define('WINSHIRT_URL', plugin_dir_url(__FILE__));

// Inclure les fichiers de classes (tous commentés pour débug)
// winshirt_require('includes/class-winshirt-lottery.php');
// winshirt_require('includes/class-winshirt-lottery-template.php');
// winshirt_require('includes/class-winshirt-tickets.php');
// winshirt_require('includes/class-winshirt-lottery-order.php');
// winshirt_require('includes/class-winshirt-lottery-display.php');
// winshirt_require('includes/class-winshirt-lottery-product-link.php');

add_action('plugins_loaded', function () {
    // Ici, tout est commenté au départ pour débug
    // Tu décommenteras une ligne à la fois pour identifier l'origine du problème.

    // Exemple (à décommenter un par un) :
    // winshirt_boot_module(['\\WinShirt\\Lottery', 'WinShirt\\Lottery', 'Lottery'], ['init']);
    // winshirt_boot_module(['\\WinShirt\\Lottery_Template', 'Lottery_Template'], ['init']);
    // ... et ainsi de suite pour chaque module.
});

// Activation : tout commenté aussi pour que tu puisses tester
// register_activation_hook(__FILE__, function () {
    // Idem, tu décommenteras après avoir identifié l’élément problématique.
    // ...
});

// Chargement conditionnel des assets aussi commenté pour le moment
// add_action('wp_enqueue_scripts', function () {
    // wp_enqueue_style('winshirt-lottery', WINSHIRT_URL . 'assets/css/winshirt-lottery.css', [], WINSHIRT_VERSION);
    // wp_enqueue_script('winshirt-lottery', WINSHIRT_URL . 'assets/js/winshirt-lottery.js', ['jquery'], WINSHIRT_VERSION, true);
// });
