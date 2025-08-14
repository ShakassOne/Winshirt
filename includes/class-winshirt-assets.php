<?php
/**
 * WinShirt - Gestion des Assets (Version Sécurisée)
 * 
 * @package WinShirt
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Assets {
    
    public function __construct() {
        // Chargement conditionnel des assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }
    
    /**
     * Assets front-end (seulement sur nos pages)
     */
    public function enqueue_frontend_assets() {
        // SÉCURITÉ : Ne charger que sur nos pages spécifiques
        if ( ! $this->is_winshirt_page() ) {
            return;
        }
        
        wp_enqueue_style(
            'winshirt-frontend',
            WINSHIRT_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WINSHIRT_VERSION
        );
        
        wp_enqueue_script(
            'winshirt-frontend',
            WINSHIRT_PLUGIN_URL . 'assets/js/frontend.js',
            array( 'jquery' ),
            WINSHIRT_VERSION,
            true
        );
    }
    
    /**
     * Assets admin (seulement sur nos pages)
     */
    public function enqueue_admin_assets( $hook ) {
        // SÉCURITÉ : Ne charger que sur nos pages admin
        if ( strpos( $hook, 'winshirt' ) === false ) {
            return;
        }
        
        wp_enqueue_style(
            'winshirt-admin',
            WINSHIRT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WINSHIRT_VERSION
        );
    }
    
    /**
     * Vérifier si on est sur une page WinShirt
     */
    private function is_winshirt_page() {
        // Vérifier shortcodes
        global $post;
        if ( $post && ( 
            has_shortcode( $post->post_content, 'winshirt_customizer' ) ||
            has_shortcode( $post->post_content, 'winshirt_gallery' )
        )) {
            return true;
        }
        
        // Vérifier pages spécifiques
        if ( is_page( array( 'winshirt-customizer', 'winshirt-gallery' ) ) ) {
            return true;
        }
        
        return false;
    }
}

new WinShirt_Assets();
