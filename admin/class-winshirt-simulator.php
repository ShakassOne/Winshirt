<?php
// admin/class-winshirt-simulator.php — STUB SAFE
if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('WinShirt_Simulator') ) {
class WinShirt_Simulator {
    const MENU_SLUG = 'winshirt-simulator-stub';

    public function __construct() {
        add_action('admin_menu', array($this,'add_menu'));
    }

    public function add_menu() {
        // Si le menu parent 'winshirt' n'existe pas, crée un top-level minimal
        if ( ! has_action('admin_menu', array($this,'_dummy')) ) {
            add_menu_page('WinShirt','WinShirt','manage_options','winshirt', function(){
                echo '<div class="wrap"><h1>WinShirt</h1><p>Dashboard.</p></div>';
            }, 'dashicons-tickets', 56);
        }

        add_submenu_page(
            'winshirt',
            'Simulateur (STUB)',
            'Simulateur',
            'manage_options',
            self::MENU_SLUG,
            array($this,'render_admin_page')
        );
    }

    public function render_admin_page() {
        echo '<div class="wrap"><h1>Simulateur (Mode STUB)</h1>';
        echo '<p>Le module complet est temporairement désactivé pour éviter une erreur critique.</p>';
        echo '<p>Confirme-moi ta version PHP (idéal ≥ 7.4). Je te renvoie le fichier complet ajusté à ta version.</p>';
        echo '</div>';
    }

    // Dummy pour has_action
    public function _dummy() {}
}
}
add_action('plugins_loaded', function(){ new WinShirt_Simulator(); });
