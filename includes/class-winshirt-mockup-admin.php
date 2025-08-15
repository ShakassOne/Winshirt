<?php
/**
 * WinShirt Mockup Admin
 * Interface d'administration des mockups - VERSION STABLE
 * 
 * @package WinShirt
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class WinShirt_Mockup_Admin {

    /**
     * Constructeur
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_mockup_data'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_mockup_zones', array($this, 'ajax_save_zones'));
    }

    /**
     * Ajouter les métaboxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'winshirt_mockup_editor',
            'Éditeur de Mockup',
            array($this, 'render_mockup_editor'),
            'winshirt_mockup',
            'normal',
            'high'
        );
    }

    /**
     * Afficher l'éditeur de mockup
     */
    public function render_mockup_editor($post) {
        // Nonce pour la sécurité
        wp_nonce_field('winshirt_mockup_save', 'winshirt_mockup_nonce');

        // Récupérer les données existantes
        $colors = get_post_meta($post->ID, '_mockup_colors', true) ?: array();
        $zones = get_post_meta($post->ID, '_zones', true) ?: array();
        $default_color = get_post_meta($post->ID, '_default_color', true) ?: '';

        ?>
        <div class="winshirt-admin-container">
            
            <!-- Section 1: Informations Générales -->
            <div class="admin-section">
                <h3 class="section-header">Informations Générales</h3>
                <div class="section-content">
                    <div class="info-field">
                        <label for="mockup_title">Titre du Mockup</label>
                        <input type="text" id="mockup_title" name="post_title" value="<?php echo esc_attr($post->post_title); ?>" />
                    </div>
                </div>
            </div>

            <!-- Section 2: Couleurs et Images -->
            <div class="admin-section">
                <h3 class="section-header">Couleurs et Images</h3>
                <div class="section-content">
                    <button type="button" id="add-color" class="add-color-btn">Ajouter une Couleur</button>
                    
                    <div class="colors-container" id="colors-container">
                        <?php
                        if (!empty($colors)) {
                            foreach ($colors as $color_id => $color_data) {
                                $this->render_color_row($color_id, $color_data, $default_color);
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Section 3: Zones d'Impression -->
            <div class="admin-section">
                <h3 class="section-header">Zones d'Impression</h3>
                <div class="section-content">
                    <div class="canvas-instructions">
                        <strong>Instructions :</strong> Double-cliquez sur le canvas pour créer une zone. Glissez pour déplacer.
                    </div>
                    
                    <div class="zones-editor">
                        <div class="canvas-container">
                            <div class="side-switch">
                                <button type="button" class="btn active" data-side="front">Recto</button>
                                <button type="button" class="btn" data-side="back">Verso</button>
                            </div>
                            
                            <div id="zone-canvas"></div>
                        </div>
                        
                        <div class="zones-panel">
                            <h4>Zones Actives</h4>
                            <button type="button" id="add-zone-btn">Ajouter une Zone</button>
                            <div class="zones-list" id="zones-list"></div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="_zones" id="zones-data" value="<?php echo esc_attr(json_encode($zones)); ?>" />
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendre une ligne de couleur
     */
    private function render_color_row($color_id, $color_data, $default_color) {
        $name = isset($color_data['name']) ? $color_data['name'] : 'Couleur';
        $hex = isset($color_data['hex']) ? $color_data['hex'] : '#FFFFFF';
        $front = isset($color_data['front']) ? $color_data['front'] : '';
        $back = isset($color_data['back']) ? $color_data['back'] : '';
        $is_default = ($color_id === $default_color);
        ?>
        <div class="color-row" data-color-id="<?php echo esc_attr($color_id); ?>">
            <div class="color-basic">
                <input type="color" class="color-picker" data-color-id="<?php echo esc_attr($color_id); ?>" value="<?php echo esc_attr($hex); ?>" />
                <input type="text" class="color-hex" value="<?php echo esc_attr($hex); ?>" readonly />
                <input type="radio" name="_default_color" value="<?php echo esc_attr($color_id); ?>" <?php checked($is_default); ?> />
                <span>Par défaut</span>
                <button type="button" class="remove-color btn btn-danger" data-color-id="<?php echo esc_attr($color_id); ?>">Supprimer</button>
            </div>
            <div class="color-images">
                <div class="image-upload">
                    <label>Image Recto:</label>
                    <button type="button" class="upload-image btn btn-secondary" data-target="front-<?php echo esc_attr($color_id); ?>">Choisir Image Recto</button>
                    <input type="hidden" class="front-image-url" name="colors[<?php echo esc_attr($color_id); ?>][front]" value="<?php echo esc_attr($front); ?>" />
                    <?php if ($front): ?>
                        <div class="image-preview"><img src="<?php echo esc_url($front); ?>" style="max-width: 100px;" /></div>
                    <?php endif; ?>
                </div>
                <div class="image-upload">
                    <label>Image Verso:</label>
                    <button type="button" class="upload-image btn btn-secondary" data-target="back-<?php echo esc_attr($color_id); ?>">Choisir Image Verso</button>
                    <input type="hidden" class="back-image-url" name="colors[<?php echo esc_attr($color_id); ?>][back]" value="<?php echo esc_attr($back); ?>" />
                    <?php if ($back): ?>
                        <div class="image-preview"><img src="<?php echo esc_url($back); ?>" style="max-width: 100px;" /></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Sauvegarder les données du mockup
     */
    public function save_mockup_data($post_id) {
        // Vérifications de sécurité
        if (!isset($_POST['winshirt_mockup_nonce']) || !wp_verify_nonce($_POST['winshirt_mockup_nonce'], 'winshirt_mockup_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $post = get_post($post_id);
        if ($post->post_type !== 'winshirt_mockup') {
            return;
        }

        // Sauvegarder les zones
        if (isset($_POST['_zones'])) {
            update_post_meta($post_id, '_zones', $_POST['_zones']);
        }

        // Sauvegarder les couleurs
        if (isset($_POST['colors'])) {
            update_post_meta($post_id, '_mockup_colors', $_POST['colors']);
        }

        // Sauvegarder la couleur par défaut
        if (isset($_POST['_default_color'])) {
            update_post_meta($post_id, '_default_color', sanitize_text_field($_POST['_default_color']));
        }
    }

    /**
     * Sauvegarder les zones via AJAX
     */
    public function ajax_save_zones() {
        // Vérification de sécurité
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'winshirt_ajax')) {
            wp_send_json_error('Nonce invalide');
        }

        $post_id = intval($_POST['post_id']);
        $zones = $_POST['zones'];

        if ($post_id && current_user_can('edit_post', $post_id)) {
            update_post_meta($post_id, '_zones', $zones);
            wp_send_json_success('Zones sauvegardées');
        } else {
            wp_send_json_error('Erreur de sauvegarde');
        }
    }

    /**
     * Charger les scripts et styles
     */
    public function enqueue_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'winshirt_mockup') {
            // WordPress Media Uploader
            wp_enqueue_media();
            
            // Scripts et styles
            wp_enqueue_script(
                'winshirt-mockup-admin',
                WINSHIRT_PLUGIN_URL . 'assets/js/mockup-admin.js',
                array('jquery'),
                WINSHIRT_VERSION,
                true
            );
            
            wp_enqueue_style(
                'winshirt-mockup-admin',
                WINSHIRT_PLUGIN_URL . 'assets/css/mockup-admin.css',
                array(),
                WINSHIRT_VERSION
            );

            // Variables AJAX
            wp_localize_script('winshirt-mockup-admin', 'winshirtAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('winshirt_ajax')
            ));
        }
    }
}
