<?php
/**
 * Éditeur de Mockup - Interface Admin Complète
 * 
 * @package WinShirt
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Mockup_Admin {
    
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_winshirt_save_mockup', array( $this, 'ajax_save_mockup' ) );
        add_action( 'wp_ajax_winshirt_delete_mockup', array( $this, 'ajax_delete_mockup' ) );
        add_action( 'wp_ajax_winshirt_get_mockup', array( $this, 'ajax_get_mockup' ) );
    }
    
    /**
     * Charger les assets
     */
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'winshirt' ) === false ) return;
        
        wp_enqueue_media();
        wp_enqueue_script( 'jquery-ui-draggable' );
        wp_enqueue_script( 'jquery-ui-resizable' );
        
        wp_enqueue_style( 
            'winshirt-mockup-admin', 
            WINSHIRT_PLUGIN_URL . 'assets/css/mockup-admin.css',
            array(),
            WINSHIRT_VERSION
        );
        
        wp_enqueue_script( 
            'winshirt-mockup-admin', 
            WINSHIRT_PLUGIN_URL . 'assets/js/mockup-admin.js',
            array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-resizable' ),
            WINSHIRT_VERSION,
            true
        );
        
        wp_localize_script( 'winshirt-mockup-admin', 'winshirtAdmin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'winshirt_admin_nonce' ),
            'strings' => array(
                'save_success' => 'Mockup sauvegardé !',
                'save_error' => 'Erreur lors de la sauvegarde',
                'delete_confirm' => 'Êtes-vous sûr de vouloir supprimer ce mockup ?',
            )
        ));
    }
    
    /**
     * Page liste des mockups
     */
    public function render_mockups_list() {
        $mockups = get_posts(array(
            'post_type' => 'winshirt_mockup',
            'post_status' => 'any',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        ?>
        <div class="wrap winshirt-admin">
            <h1>
                Gestion des Mockups
                <a href="<?php echo admin_url('admin.php?page=winshirt-edit-mockup'); ?>" class="page-title-action">
                    Ajouter un Mockup
                </a>
            </h1>
            
            <div class="winshirt-mockups-grid">
                <?php if ( empty( $mockups ) ): ?>
                    <div class="winshirt-empty-state">
                        <h3>Aucun mockup créé</h3>
                        <p>Commencez par créer votre premier mockup</p>
                        <a href="<?php echo admin_url('admin.php?page=winshirt-edit-mockup'); ?>" class="button-primary">
                            Créer un Mockup
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ( $mockups as $mockup ): 
                        $colors = get_post_meta( $mockup->ID, '_mockup_colors', true ) ?: array();
                        $zones = get_post_meta( $mockup->ID, '_zones', true ) ?: array();
                        $default_color = get_post_meta( $mockup->ID, '_default_color', true );
                        $preview_image = '';
                        
                        // Image de prévisualisation
                        if ( !empty( $colors ) && $default_color && isset( $colors[$default_color] ) ) {
                            $preview_image = $colors[$default_color]['front'] ?: '';
                        }
                    ?>
                        <div class="winshirt-mockup-card">
                            <div class="mockup-preview">
                                <?php if ( $preview_image ): ?>
                                    <img src="<?php echo esc_url( $preview_image ); ?>" alt="<?php echo esc_attr( $mockup->post_title ); ?>">
                                <?php else: ?>
                                    <div class="mockup-placeholder">
                                        <span>Aucune image</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mockup-info">
                                <h3><?php echo esc_html( $mockup->post_title ?: 'Sans titre' ); ?></h3>
                                <div class="mockup-stats">
                                    <span><?php echo count( $colors ); ?> couleur(s)</span>
                                    <span><?php echo count( $zones ); ?> zone(s)</span>
                                </div>
                                
                                <div class="mockup-actions">
                                    <a href="<?php echo admin_url('admin.php?page=winshirt-edit-mockup&id=' . $mockup->ID); ?>" 
                                       class="button button-primary">
                                        Éditer
                                    </a>
                                    <button type="button" 
                                            class="button button-secondary winshirt-delete-mockup" 
                                            data-id="<?php echo $mockup->ID; ?>">
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Page éditeur de mockup
     */
    public function render_mockup_editor() {
        $mockup_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $mockup = null;
        $colors = array();
        $zones = array();
        $default_color = '';
        $title = '';
        
        if ( $mockup_id ) {
            $mockup = get_post( $mockup_id );
            if ( $mockup && $mockup->post_type === 'winshirt_mockup' ) {
                $colors = get_post_meta( $mockup_id, '_mockup_colors', true ) ?: array();
                $zones = get_post_meta( $mockup_id, '_zones', true ) ?: array();
                $default_color = get_post_meta( $mockup_id, '_default_color', true );
                $title = $mockup->post_title;
            } else {
                $mockup_id = 0;
            }
        }
        
        ?>
        <div class="wrap winshirt-admin">
            <h1>
                <?php echo $mockup_id ? 'Éditer le Mockup' : 'Nouveau Mockup'; ?>
                <button type="button" id="winshirt-save-mockup" class="page-title-action">
                    Sauvegarder
                </button>
            </h1>
            
            <form id="winshirt-mockup-form" data-mockup-id="<?php echo $mockup_id; ?>">
                
                <!-- Informations générales -->
                <div class="winshirt-editor-section">
                    <h2>Informations Générales</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="mockup-title">Nom du Mockup</label></th>
                            <td>
                                <input type="text" 
                                       id="mockup-title" 
                                       name="title" 
                                       value="<?php echo esc_attr( $title ); ?>" 
                                       class="regular-text" 
                                       placeholder="Ex: T-shirt Premium Homme">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Gestion des couleurs -->
                <div class="winshirt-editor-section">
                    <h2>
                        Couleurs et Images
                        <button type="button" id="add-color" class="button button-secondary">
                            Ajouter une Couleur
                        </button>
                    </h2>
                    
                    <div id="colors-container">
                        <?php if ( !empty( $colors ) ): ?>
                            <?php foreach ( $colors as $color_id => $color_data ): ?>
                                <div class="color-item" data-color-id="<?php echo esc_attr( $color_id ); ?>">
                                    <div class="color-header">
                                        <div class="color-preview" style="background-color: <?php echo esc_attr( $color_data['hex'] ); ?>"></div>
                                        <input type="text" 
                                               class="color-name" 
                                               value="<?php echo esc_attr( $color_data['name'] ); ?>" 
                                               placeholder="Nom de la couleur">
                                        <input type="color" 
                                               class="color-hex" 
                                               value="<?php echo esc_attr( $color_data['hex'] ); ?>">
                                        <label>
                                            <input type="radio" 
                                                   name="default_color" 
                                                   value="<?php echo esc_attr( $color_id ); ?>"
                                                   <?php checked( $default_color, $color_id ); ?>>
                                            Par défaut
                                        </label>
                                        <button type="button" class="remove-color">×</button>
                                    </div>
                                    
                                    <div class="color-images">
                                        <div class="image-upload">
                                            <label>Image Recto:</label>
                                            <div class="image-preview">
                                                <?php if ( !empty( $color_data['front'] ) ): ?>
                                                    <img src="<?php echo esc_url( $color_data['front'] ); ?>" alt="Recto">
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="upload-image button" data-side="front">
                                                Choisir l'image Recto
                                            </button>
                                            <input type="hidden" class="image-url" data-side="front" value="<?php echo esc_url( $color_data['front'] ?? '' ); ?>">
                                        </div>
                                        
                                        <div class="image-upload">
                                            <label>Image Verso:</label>
                                            <div class="image-preview">
                                                <?php if ( !empty( $color_data['back'] ) ): ?>
                                                    <img src="<?php echo esc_url( $color_data['back'] ); ?>" alt="Verso">
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="upload-image button" data-side="back">
                                                Choisir l'image Verso
                                            </button>
                                            <input type="hidden" class="image-url" data-side="back" value="<?php echo esc_url( $color_data['back'] ?? '' ); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Éditeur de zones -->
                <div class="winshirt-editor-section">
                    <h2>
                        Zones d'Impression
                        <button type="button" id="add-zone" class="button button-secondary">
                            Ajouter une Zone
                        </button>
                        <div class="side-switcher">
                            <button type="button" class="side-btn active" data-side="front">Recto</button>
                            <button type="button" class="side-btn" data-side="back">Verso</button>
                        </div>
                    </h2>
                    
                    <div class="zones-editor">
                        <div class="canvas-container">
                            <canvas id="zones-canvas" width="400" height="500"></canvas>
                            <div id="zones-overlay"></div>
                        </div>
                        
                        <div class="zones-list">
                            <h3>Zones Actives</h3>
                            <div id="zones-container">
                                <?php if ( !empty( $zones ) ): ?>
                                    <?php foreach ( $zones as $zone_id => $zone_data ): ?>
                                        <div class="zone-item" data-zone-id="<?php echo esc_attr( $zone_id ); ?>">
                                            <input type="text" 
                                                   class="zone-name" 
                                                   value="<?php echo esc_attr( $zone_data['name'] ); ?>" 
                                                   placeholder="Nom de la zone">
                                            <input type="number" 
                                                   class="zone-price" 
                                                   value="<?php echo esc_attr( $zone_data['price'] ); ?>" 
                                                   step="0.01" 
                                                   placeholder="Prix">
                                            <select class="zone-side">
                                                <option value="front" <?php selected( $zone_data['side'], 'front' ); ?>>Recto</option>
                                                <option value="back" <?php selected( $zone_data['side'], 'back' ); ?>>Verso</option>
                                            </select>
                                            <button type="button" class="remove-zone">Supprimer</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
            </form>
            
            <div id="winshirt-save-status"></div>
        </div>
        
        <script type="text/template" id="color-template">
            <div class="color-item" data-color-id="{{COLOR_ID}}">
                <div class="color-header">
                    <div class="color-preview" style="background-color: #000000"></div>
                    <input type="text" class="color-name" placeholder="Nom de la couleur">
                    <input type="color" class="color-hex" value="#000000">
                    <label>
                        <input type="radio" name="default_color" value="{{COLOR_ID}}">
                        Par défaut
                    </label>
                    <button type="button" class="remove-color">×</button>
                </div>
                <div class="color-images">
                    <div class="image-upload">
                        <label>Image Recto:</label>
                        <div class="image-preview"></div>
                        <button type="button" class="upload-image button" data-side="front">
                            Choisir l'image Recto
                        </button>
                        <input type="hidden" class="image-url" data-side="front">
                    </div>
                    <div class="image-upload">
                        <label>Image Verso:</label>
                        <div class="image-preview"></div>
                        <button type="button" class="upload-image button" data-side="back">
                            Choisir l'image Verso
                        </button>
                        <input type="hidden" class="image-url" data-side="back">
                    </div>
                </div>
            </div>
        </script>
        
        <script type="text/template" id="zone-template">
            <div class="zone-item" data-zone-id="{{ZONE_ID}}">
                <input type="text" class="zone-name" placeholder="Nom de la zone">
                <input type="number" class="zone-price" step="0.01" placeholder="Prix">
                <select class="zone-side">
                    <option value="front">Recto</option>
                    <option value="back">Verso</option>
                </select>
                <button type="button" class="remove-zone">Supprimer</button>
            </div>
        </script>
        <?php
    }
    
    /**
     * AJAX - Sauvegarder le mockup
     */
    public function ajax_save_mockup() {
        check_ajax_referer( 'winshirt_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Permissions insuffisantes' );
        }
        
        $mockup_id = intval( $_POST['mockup_id'] ?? 0 );
        $title = sanitize_text_field( $_POST['title'] ?? '' );
        $colors = $_POST['colors'] ?? array();
        $zones = $_POST['zones'] ?? array();
        $default_color = sanitize_text_field( $_POST['default_color'] ?? '' );
        
        // Créer ou mettre à jour le post
        $post_data = array(
            'post_title' => $title,
            'post_type' => 'winshirt_mockup',
            'post_status' => 'publish'
        );
        
        if ( $mockup_id ) {
            $post_data['ID'] = $mockup_id;
            $result = wp_update_post( $post_data );
        } else {
            $result = wp_insert_post( $post_data );
            $mockup_id = $result;
        }
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( 'Erreur lors de la sauvegarde' );
        }
        
        // Sauvegarder les meta données
        update_post_meta( $mockup_id, '_mockup_colors', $colors );
        update_post_meta( $mockup_id, '_zones', $zones );
        update_post_meta( $mockup_id, '_default_color', $default_color );
        
        wp_send_json_success( array(
            'message' => 'Mockup sauvegardé avec succès',
            'mockup_id' => $mockup_id
        ));
    }
    
    /**
     * AJAX - Supprimer le mockup
     */
    public function ajax_delete_mockup() {
        check_ajax_referer( 'winshirt_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Permissions insuffisantes' );
        }
        
        $mockup_id = intval( $_POST['mockup_id'] ?? 0 );
        
        if ( ! $mockup_id ) {
            wp_send_json_error( 'ID invalide' );
        }
        
        $result = wp_delete_post( $mockup_id, true );
        
        if ( $result ) {
            wp_send_json_success( 'Mockup supprimé' );
        } else {
            wp_send_json_error( 'Erreur lors de la suppression' );
        }
    }
    
    /**
     * AJAX - Récupérer les données du mockup
     */
    public function ajax_get_mockup() {
        check_ajax_referer( 'winshirt_admin_nonce', 'nonce' );
        
        $mockup_id = intval( $_GET['mockup_id'] ?? 0 );
        
        if ( ! $mockup_id ) {
            wp_send_json_error( 'ID invalide' );
        }
        
        $mockup = get_post( $mockup_id );
        if ( ! $mockup || $mockup->post_type !== 'winshirt_mockup' ) {
            wp_send_json_error( 'Mockup non trouvé' );
        }
        
        $data = array(
            'title' => $mockup->post_title,
            'colors' => get_post_meta( $mockup_id, '_mockup_colors', true ) ?: array(),
            'zones' => get_post_meta( $mockup_id, '_zones', true ) ?: array(),
            'default_color' => get_post_meta( $mockup_id, '_default_color', true )
        );
        
        wp_send_json_success( $data );
    }
}

// Initialiser la classe
new WinShirt_Mockup_Admin();
