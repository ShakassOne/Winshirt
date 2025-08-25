<?php
/**
 * Plugin Name: WinShirt
 * Description: Galerie des loteries (grille, masonry, diagonale) + menu admin minimal. Pack de récupération v1.
 * Version: 2.1.0
 * Author: WinShirt by Shakass Communication
 * Text Domain: winshirt
 */
if ( ! defined('ABSPATH') ) exit;

define('WINSHIRT_VERSION', '2.1.0');
define('WINSHIRT_DIR', plugin_dir_path(__FILE__));
define('WINSHIRT_URL', plugin_dir_url(__FILE__));

// Chargement des classes cœur
require_once WINSHIRT_DIR . 'includes/class-winshirt-assets.php';
require_once WINSHIRT_DIR . 'includes/class-winshirt-admin.php';
require_once WINSHIRT_DIR . 'includes/diagonal-layout.php';
require_once WINSHIRT_DIR . 'includes/shortcode-winshirt-lotteries.php';
require_once WINSHIRT_DIR . 'includes/class-winshirt-slugs.php';
require_once WINSHIRT_DIR . 'includes/class-winshirt-archive-overlay.php';
WinShirt_Slugs::init();
WinShirt_Archive_Overlay::init();

add_action('plugins_loaded', function () {
    WinShirt_Assets::init();
    WinShirt_Admin::init();
});

/**
 * À l'activation : garantir l'existence de la catégorie "loterie"
 */
register_activation_hook(__FILE__, function () {
    if ( ! term_exists('loterie', 'category') ) {
        wp_insert_term('loterie', 'category', [
            'description' => 'Catégorie utilisée par le shortcode [winshirt_lotteries]',
            'slug'        => 'loterie',
        ]);
    }
});
