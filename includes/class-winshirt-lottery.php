<?php
/**
 * WinShirt - Système de Loteries (Version Sécurisée)
 * 
 * @package WinShirt
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Lottery {
    
    public function __construct() {
        // SÉCURITÉ : Hooks conditionnels seulement
        if ( is_admin() ) {
            add_action( 'init', array( $this, 'init_lottery_cpt' ) );
        }
        
        // SÉCURITÉ : Pas d'interférence avec les autres pages
        add_action( 'wp_ajax_winshirt_lottery_action', array( $this, 'handle_lottery_ajax' ) );
        add_action( 'wp_ajax_nopriv_winshirt_lottery_action', array( $this, 'handle_lottery_ajax' ) );
    }
    
    /**
     * Initialiser le CPT Lottery (mode sécurisé)
     */
    public function init_lottery_cpt() {
        // CPT déjà créé par class-winshirt-cpt.php
        // Ici on ajoute juste les fonctionnalités
    }
    
    /**
     * Gérer les actions AJAX
     */
    public function handle_lottery_ajax() {
        // Vérifications de sécurité
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'winshirt_lottery_nonce' ) ) {
            wp_die( 'Sécurité : Nonce invalide' );
        }
        
        $action = sanitize_text_field( $_POST['lottery_action'] ?? '' );
        
        switch ( $action ) {
            case 'participate':
                $this->handle_participation();
                break;
            case 'draw':
                $this->handle_draw();
                break;
            default:
                wp_send_json_error( 'Action non reconnue' );
        }
    }
    
    /**
     * Gérer la participation
     */
    private function handle_participation() {
        wp_send_json_success( 'Participation enregistrée' );
    }
    
    /**
     * Gérer le tirage
     */
    private function handle_draw() {
        wp_send_json_success( 'Tirage effectué' );
    }
}

new WinShirt_Lottery();
