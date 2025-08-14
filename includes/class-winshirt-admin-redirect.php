<?php
/**
 * WinShirt - Redirection et Désactivation Gutenberg
 * 
 * Force l'utilisation de l'interface admin au lieu de Gutenberg
 * 
 * @package WinShirt
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Admin_Redirect {
    
    public function __construct() {
        // SEULEMENT désactiver Gutenberg pour nos CPT - PAS DE REDIRECTIONS
        add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_gutenberg' ), 10, 2 );
        
        // COMMENTÉ TEMPORAIREMENT - CAUSE DU PROBLÈME PRODUITS
        // add_action( 'admin_init', array( $this, 'redirect_edit_pages' ) );
        // add_filter( 'post_row_actions', array( $this, 'modify_row_actions' ), 10, 2 );
        // add_filter( 'page_row_actions', array( $this, 'modify_row_actions' ), 10, 2 );
        // add_action( 'admin_head', array( $this, 'redirect_add_new_buttons' ) );
        
        add_action( 'add_meta_boxes', array( $this, 'remove_default_meta_boxes' ), 99 );
        add_action( 'admin_head', array( $this, 'hide_editor_completely' ) );
    }
    
    /**
     * Désactiver Gutenberg pour nos types de posts
     */
    public function disable_gutenberg( $use_block_editor, $post_type ) {
        if ( in_array( $post_type, array( 'winshirt_mockup', 'winshirt_visual', 'winshirt_lottery' ) ) ) {
            return false;
        }
        return $use_block_editor;
    }
    
    /**
     * Rediriger les tentatives d'édition vers notre interface
     */
    public function redirect_edit_pages() {
        global $pagenow, $post_type;
        
        // Vérifier si on est sur une page d'édition de nos CPT
        if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
            return;
        }
        
        // Redirection pour mockups
        if ( $post_type === 'winshirt_mockup' || 
             ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'winshirt_mockup' ) ) {
            
            $redirect_url = admin_url( 'admin.php?page=winshirt-edit-mockup' );
            
            // Si on édite un mockup existant, passer l'ID
            if ( $pagenow === 'post.php' && isset( $_GET['post'] ) ) {
                $redirect_url .= '&id=' . intval( $_GET['post'] );
            }
            
            wp_redirect( $redirect_url );
            exit;
        }
        
        // Redirection pour visuels
        if ( $post_type === 'winshirt_visual' || 
             ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'winshirt_visual' ) ) {
            
            $redirect_url = admin_url( 'admin.php?page=winshirt-edit-visual' );
            
            if ( $pagenow === 'post.php' && isset( $_GET['post'] ) ) {
                $redirect_url .= '&id=' . intval( $_GET['post'] );
            }
            
            wp_redirect( $redirect_url );
            exit;
        }
        
        // Redirection pour loteries
        if ( $post_type === 'winshirt_lottery' || 
             ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'winshirt_lottery' ) ) {
            
            $redirect_url = admin_url( 'admin.php?page=winshirt-edit-lottery' );
            
            if ( $pagenow === 'post.php' && isset( $_GET['post'] ) ) {
                $redirect_url .= '&id=' . intval( $_GET['post'] );
            }
            
            wp_redirect( $redirect_url );
            exit;
        }
    }
    
    /**
     * Modifier les liens d'action dans les listes
     */
    public function modify_row_actions( $actions, $post ) {
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
     * Rediriger les boutons "Ajouter nouveau" via JavaScript
     */
    public function redirect_add_new_buttons() {
        global $post_type;
        
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
            
            // Rediriger tous les liens "Ajouter nouveau"
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
     * Supprimer toutes les meta boxes par défaut
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
    
    /**
     * Masquer complètement l'éditeur par défaut
     */
    public function hide_editor_completely() {
        global $post_type;
        
        if ( ! in_array( $post_type, array( 'winshirt_mockup', 'winshirt_visual', 'winshirt_lottery' ) ) ) {
            return;
        }
        
        ?>
        <style>
        /* Masquer tous les éléments de l'éditeur par défaut */
        #post-body-content,
        #titlediv,
        #wp-content-wrap,
        #postdivrich,
        .editor-post-title,
        .block-editor,
        .edit-post-visual-editor,
        .edit-post-layout,
        .interface-interface-skeleton,
        .edit-post-header,
        .edit-post-sidebar,
        .components-notice-list,
        .block-editor-writing-flow,
        .edit-post-text-editor,
        #editor,
        #poststuff #post-body #postdiv,
        #normal-sortables,
        #advanced-sortables,
        #side-sortables,
        .postbox-container {
            display: none !important;
        }
        
        /* Masquer le titre de la page et les boutons par défaut */
        .wrap h1.wp-heading-inline {
            display: none !important;
        }
        
        .page-title-action {
            display: none !important;
        }
        
        /* Styles pour afficher un message de redirection */
        .winshirt-redirect-notice {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-left: 4px solid #0073aa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: center;
        }
        
        .winshirt-redirect-notice h2 {
            margin-top: 0;
            color: #0073aa;
        }
        
        .winshirt-redirect-notice .button-primary {
            margin-top: 15px;
        }
        </style>
        
        <script>
        // Redirection automatique après 2 secondes
        setTimeout(function() {
            <?php if ( $post_type === 'winshirt_mockup' ): ?>
                window.location.href = '<?php echo admin_url('admin.php?page=winshirt-mockup'); ?>';
            <?php elseif ( $post_type === 'winshirt_visual' ): ?>
                window.location.href = '<?php echo admin_url('admin.php?page=winshirt-visual'); ?>';
            <?php elseif ( $post_type === 'winshirt_lottery' ): ?>
                window.location.href = '<?php echo admin_url('admin.php?page=winshirt-lottery'); ?>';
            <?php endif; ?>
        }, 2000);
        </script>
        
        <div class="winshirt-redirect-notice">
            <h2>Redirection en cours...</h2>
            <p>Vous allez être redirigé vers l'interface d'édition WinShirt.</p>
            <p>
                <?php if ( $post_type === 'winshirt_mockup' ): ?>
                    <a href="<?php echo admin_url('admin.php?page=winshirt-mockup'); ?>" class="button button-primary">
                        Aller aux Mockup
                    </a>
                <?php elseif ( $post_type === 'winshirt_visual' ): ?>
                    <a href="<?php echo admin_url('admin.php?page=winshirt-visual'); ?>" class="button button-primary">
                        Aller aux Visuels
                    </a>
                <?php elseif ( $post_type === 'winshirt_lottery' ): ?>
                    <a href="<?php echo admin_url('admin.php?page=winshirt-lottery'); ?>" class="button button-primary">
                        Aller aux Loteries
                    </a>
                <?php endif; ?>
            </p>
        </div>
        <?php
    }
}

// Initialiser la classe
new WinShirt_Admin_Redirect();
