<?php
/**
 * WinShirt - Paramètres (Version Sécurisée)
 * 
 * @package WinShirt
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Settings {
    
    public function __construct() {
        // SÉCURITÉ : Hooks conditionnels
        if ( is_admin() ) {
            add_action( 'admin_init', array( $this, 'init_settings' ) );
        }
        
        // SÉCURITÉ : Hooks WooCommerce uniquement si WooCommerce existe
        if ( class_exists( 'WooCommerce' ) ) {
            add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
            add_action( 'woocommerce_settings_tabs_winshirt', array( $this, 'settings_tab' ) );
            add_action( 'woocommerce_update_options_winshirt', array( $this, 'update_settings' ) );
        }
    }
    
    /**
     * Initialiser les paramètres
     */
    public function init_settings() {
        register_setting( 'winshirt_settings', 'winshirt_options' );
    }
    
    /**
     * Ajouter onglet WooCommerce (seulement si WooCommerce actif)
     */
    public function add_settings_tab( $settings_tabs ) {
        $settings_tabs['winshirt'] = 'WinShirt';
        return $settings_tabs;
    }
    
    /**
     * Contenu de l'onglet
     */
    public function settings_tab() {
        woocommerce_admin_fields( $this->get_settings() );
    }
    
    /**
     * Sauvegarder les paramètres
     */
    public function update_settings() {
        woocommerce_update_options( $this->get_settings() );
    }
    
    /**
     * Obtenir les paramètres
     */
    public function get_settings() {
        return array(
            array(
                'name' => 'Paramètres WinShirt',
                'type' => 'title',
                'desc' => 'Configuration du plugin WinShirt',
                'id'   => 'winshirt_settings'
            ),
            array(
                'name'    => 'Activer le customizer',
                'desc'    => 'Activer l\'interface de personnalisation',
                'id'      => 'winshirt_enable_customizer',
                'type'    => 'checkbox',
                'default' => 'yes'
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'winshirt_settings'
            )
        );
    }
}

new WinShirt_Settings();
