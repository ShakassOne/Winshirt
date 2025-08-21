<?php
/**
 * Plugin Name: WinShirt (SAFE)
 * Description: Safe bootstrap (affichage loteries uniquement) le temps d’identifier le fatal.
 * Version: 2.0.0-safe
 * Author: Winshirt by Shakass Communication
 */
if ( ! defined('ABSPATH') ) exit;

define('WINSHIRT_DIR', plugin_dir_path(__FILE__));
define('WINSHIRT_URL', plugin_dir_url(__FILE__));

function winshirt_require_safe($rel){
    $p = WINSHIRT_DIR . ltrim($rel,'/');
    if ( file_exists($p) ) { require_once $p; return true; }
    return false;
}

/* On charge UNIQUEMENT le cœur CPT + template (shortcodes/cartes). */
winshirt_require_safe('includes/class-winshirt-lottery.php');
winshirt_require_safe('includes/class-winshirt-lottery-template.php');

/* Activation : on enregistre le CPT s’il existe, puis flush. */
register_activation_hook(__FILE__, function(){
    if ( class_exists('\\WinShirt\\Lottery') ) {
        if ( method_exists('\\WinShirt\\Lottery','register_cpt') ) {
            \WinShirt\Lottery::instance()->register_cpt();
        } elseif ( method_exists('\\WinShirt\\Lottery','register_post_type') ) {
            \WinShirt\Lottery::register_post_type();
        }
    }
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function(){ flush_rewrite_rules(); });

/* Bootstrap min. */
add_action('plugins_loaded', function(){
    if ( class_exists('\\WinShirt\\Lottery') )          \WinShirt\Lottery::instance()->init();
    if ( class_exists('\\WinShirt\\Lottery_Template') ) \WinShirt\Lottery_Template::instance()->init();
});
