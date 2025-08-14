<?php
/**
 * WinShirt - Redirection Corrigée (Ne casse plus les produits)
 * 
 * @package WinShirt
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Admin_Redirect {
    
    public function __construct() {
        // Désactiver Gutenberg SEULEMENT pour nos CPT
        add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_gutenberg' ), 10, 2 );
        
        // Rediriger SEULEMENT nos pages d'édition
        add_action( 'admin_init', array( $this, 'redirect_edit_pages' ) );
        
        // Modifier les liens d'action SEULEMENT pour nos CPT
        add_filter( 'post_row_actions', array( $this, 'modify_row_actions' ), 10, 2 );
        add_filter( 'page_row_actions', array( $this, 'modify_row_actions' ), 10, 2 );
        
        // Rediriger les boutons "Ajouter nouveau" SEULEMENT pour nos CPT
        add_action( 'admin_head', array( $this, 'redirect_add_new_buttons' ) );
        
        // Supprimer les meta boxes SEULEMENT pour nos CPT
        add_action( 'add_meta_boxes', array( $this, 'remove_default_meta_boxes' ), 99 );
    }
    
    /**
     * Désactiver Gutenberg SEULEMENT pour nos types de posts
     */
    public function disable_gutenberg( $use_block_editor, $post_type ) {
        // IMPORTANT : Ne désactiver que pour NOS CPT, pas pour les produits WooCommerce
        if ( in_array( $post_type, array( 'winshirt_mockup', 'winshirt_visual', 'winshirt_lottery' ) ) ) {
            return false;
        }
        return $use_block_editor;
    }
    
    /**
     * Rediriger SEULEMENT nos tentatives d'édition
     */
    public function redirect_edit_pages() {
        global $pagenow, $post_type;
        
        // SÉCURITÉ : Ne rediriger que nos pages d'édition
        if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
            return;
        }
        
        // SÉCURITÉ : Ne pas toucher aux produits WooCommerce ou autres CPT
        $winshirt_post_types = array( 'winshirt_mockup', 'winshirt_visual', 'winshirt_lottery' );
        
        // Vérifier le post_type de plusieurs façons
        $current_post_type = '';
        
        if ( $post_type ) {
            $current_post_type = $post_type;
        } elseif ( isset( $_GET['post_type'] ) ) {
            $current_post_type = sanitize_text_field( $_GET['post_type'] );
        } elseif ( isset( $_GET['post'] ) && $pagenow === 'post.php' ) {
            $post_id = intval( $_GET['post'] );
            $post = get_post( $post_id );
            if ( $post ) {
                $current_post_type = $post->post_type;
            }
        }
        
        // SÉCURITÉ : Ne rediriger QUE nos CPT
        if ( ! in_array( $current_post_type, $winshirt_post_types ) ) {
            return; // Laisser les autres types (produits, pages, etc.) tranquilles
        }
        
        // Redirection pour nos mockups seulement
        if ( $current_post_type === 'winshirt_mockup' ) {
            $redirect_url = admin_url( 'admin.php?page=winshirt-edit-mockup' );
            
            if ( $pagenow === 'post.php' && isset( $_GET['post'] ) ) {
                $redirect_url .= '&id=' . intval( $_GET['post'] );
            }
            
            wp_redirect( $redirect_url );
            exit;
        }
        
        // Redirection pour nos visuels seulement
        if ( $current_post_type === 'winshirt_visual' ) {
            $redirect_url = admin_url( 'admin.php?page=winshirt-edit-visual' );
            
            if ( $pagenow === 'post.php' && isset( $_GET['post'] ) ) {
                $redirect_url .= '&id=' . intval( $_GET['post'] );
            }
            
            wp_redirect( $redirect_url );
            exit;
        }
        
        // Redirection pour nos loteries seulement
        if ( $current_post_type === 'winshirt_lottery' ) {
            $redirect_url = admin_url( 'admin.php?page=winshirt-edit-lottery' );
            
            if ( $pagenow === 'post.php' && isset( $_GET['post'] ) ) {
                $redirect_url .= '&id=' . intval( $_GET['post'] );
            }
            
            wp_redirect( $redirect_url );
            exit;
        }
    }
    
    /**
     * Modifier les liens d'action SEULEMENT pour nos CPT
     */
    public function modify_row_actions( $actions, $post ) {
        // SÉCURITÉ : Ne modifier que nos CPT
        if ( ! in_array( $post->post_type, array( 'winshirt_mockup', 'winshirt_visual', 'winshirt_lottery' ) ) ) {
            return $actions;
        }
        
        // Supprimer les actions par défaut
        unset( $actions['edit'] );
        unset( $actions['quick_edit'] );
        unset( $actions['inline hide-if-no-js'] );
        
        // Ajouter nos actions personnalisées
        switch ( $post->post_type ) {
            case 'winshirt_mockup':
                $actions['winshirt_edit'] = sprintf(
                    '<a href="%s">Éditer</a>',
                    admin_url( 'admin.php?page=winshirt-edit-mockup&id=' . $post->ID )
                );
                break;
                
            case 'winshirt_visual':
                $actions['winshirt_edit'] = sprintf(
                    '<a href="%s">Éditer</a>',
                    admin_url( 'admin.php?page=winshirt-edit-visual&id=' . $post->ID )
                );
                break;
                
            case 'winshirt_lottery':
                $actions['winshirt_edit'] = sprintf(
                    '<a href="%s">Éditer</a>',
                    admin_url( 'admin.php?page=winshirt-edit-lottery&id=' . $post->ID )
                );
                break;
        }
        
        return $actions;
    }
    
    /**
     * Rediriger les boutons "Ajouter nouveau" SEULEMENT pour nos CPT
     */
    public function redirect_add_new_buttons() {
        global $post_type;
        
        // SÉCURITÉ : Ne s'exécuter que sur nos pages d'admin
        if ( ! in_array( $post_type, array( 'winshirt_mockup', 'winshirt_visual', 'winshirt_lottery' ) ) ) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Rediriger le bouton "Ajouter nouveau" principal
            $('.page-title-action').each(function() {
                var $btn = $(this);
                var href = $btn.attr('href');
                
                if (href && href.indexOf('post-new.php') !== -1) {
                    <?php if ( $post_type === 'winshirt_mockup' ): ?>
                        $btn.attr('href', '<?php echo admin_url('admin.php?page=winshirt-edit-mockup'); ?>');
                    <?php elseif ( $post_type === 'winshirt_visual' ): ?>
                        $btn.attr('href', '<?php echo admin_url('admin.php?page=winshirt-edit-visual'); ?>');
                    <?php elseif ( $post_type === 'winshirt_lottery' ): ?>
                        $btn.attr('href', '<?php echo admin_url('admin.php?page=winshirt-edit-lottery'); ?>');
                    <?php endif; ?>
                }
            });
            
            // Rediriger tous les liens "Ajouter nouveau" SEULEMENT pour nos CPT
            $('a[href*="post-new.php"]').each(function() {
                var $link = $(this);
                var href = $link.attr('href');
                
                <?php if ( $post_type === 'winshirt_mockup' ): ?>
                    if (href.indexOf('post_type=winshirt_mockup') !== -1) {
                        $link.attr('href', '<?php echo admin_url('admin.php?page=winshirt-edit-mockup'); ?>');
                    }
                <?php elseif ( $post_type === 'winshirt_visual' ): ?>
                    if (href.indexOf('post_type=winshirt_visual') !== -1) {
                        $link.attr('href', '<?php echo admin_url('admin.php?page=winshirt-edit-visual'); ?>');
                    }
                <?php elseif ( $post_type === 'winshirt_lottery' ): ?>
                    if (href.indexOf('post_type=winshirt_lottery') !== -1) {
                        $link.attr('href', '<?php echo admin_url('admin.php?page=winshirt-edit-lottery'); ?>');
                    }
                <?php endif; ?>
            });
        });
        </script>
        <?php
    }
    
    /**
     * Supprimer les meta boxes SEULEMENT pour nos CPT
     */
    public function remove_default_meta_boxes() {
        $post_types = array( 'winshirt_mockup', 'winshirt_visual', 'winshirt_lottery' );
        
        foreach ( $post_types as $post_type ) {
            // Supprimer l'éditeur principal
            remove_post_type_support( $post_type, 'editor' );
            remove_post_type_support( $post_type, 'title' );
            remove_post_type_support( $post_type, 'thumbnail' );
            remove_post_type_support( $post_type, 'excerpt' );
            remove_post_type_support( $post_type, 'comments' );
            remove_post_type_support( $post_type, 'revisions' );
            remove_post_type_support( $post_type, 'custom-fields' );
            
            // Supprimer toutes les meta boxes
            remove_meta_box( 'submitdiv', $post_type, 'side' );
            remove_meta_box( 'slugdiv', $post_type, 'normal' );
            remove_meta_box( 'postcustom', $post_type, 'normal' );
            remove_meta_box( 'postexcerpt', $post_type, 'normal' );
            remove_meta_box( 'commentstatusdiv', $post_type, 'normal' );
            remove_meta_box( 'commentsdiv', $post_type, 'normal' );
            remove_meta_box( 'revisionsdiv', $post_type, 'normal' );
        }
    }
}

// Initialiser la classe
new WinShirt_Admin_Redirect();
