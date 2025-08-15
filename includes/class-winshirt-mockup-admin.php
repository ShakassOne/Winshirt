<?php
/**
 * WinShirt Mockup Admin - Version qui MARCHE
 */

if (!defined('ABSPATH')) {
    exit;
}

class WinShirt_Mockup_Admin {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_mockup_data'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // FORCER la sauvegarde via AJAX
        add_action('wp_ajax_save_mockup_zones', array($this, 'ajax_save_zones'));
        add_action('wp_ajax_nopriv_save_mockup_zones', array($this, 'ajax_save_zones'));
    }

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

    public function render_mockup_editor($post) {
        wp_nonce_field('winshirt_mockup_save', 'winshirt_mockup_nonce');
        
        // Récupérer les données existantes
        $colors = get_post_meta($post->ID, '_mockup_colors', true) ?: array();
        $zones = get_post_meta($post->ID, '_zones', true) ?: array();
        $default_color = get_post_meta($post->ID, '_default_color', true) ?: '';
        
        ?>
        <div class="winshirt-admin-container">
            
            <!-- Informations Générales -->
            <div class="admin-section">
                <div class="section-header">Informations Générales</div>
                <div class="section-content">
                    <div class="info-field">
                        <label for="post_title">Nom du Mockup</label>
                        <input type="text" id="post_title" name="post_title" value="<?php echo esc_attr($post->post_title); ?>" />
                    </div>
                </div>
            </div>

            <!-- Couleurs et Images -->
            <div class="admin-section">
                <div class="section-header">Couleurs et Images</div>
                <div class="section-content">
                    <button type="button" id="add-color" class="button button-primary">Ajouter une Couleur</button>
                    
                    <div id="colors-container" style="margin-top: 15px;">
                        <?php
                        if (!empty($colors)) {
                            foreach ($colors as $color_id => $color_data) {
                                $this->render_color_row($color_id, $color_data, $default_color);
                            }
                        } else {
                            // Ajouter une couleur par défaut si aucune
                            $this->render_color_row('color_default', array(
                                'name' => 'Blanc',
                                'hex' => '#FFFFFF',
                                'front' => '',
                                'back' => ''
                            ), 'color_default');
                        }
                        ?>
                    </div>
                    
                    <input type="hidden" name="_default_color" value="<?php echo esc_attr($default_color); ?>" />
                </div>
            </div>

            <!-- Zones d'Impression -->
            <div class="admin-section">
                <div class="section-header">Zones d'Impression</div>
                <div class="section-content">
                    <div class="canvas-instructions">
                        <strong>Instructions:</strong> Double-cliquez sur l'image pour ajouter une zone. Glissez pour déplacer.
                    </div>
                    
                    <div class="zones-editor">
                        <div class="canvas-section">
                            <div class="side-switch">
                                <button type="button" class="btn active" data-side="front">Recto</button>
                                <button type="button" class="btn" data-side="back">Verso</button>
                            </div>
                            
                            <!-- CANVAS PLUS GRAND -->
                            <div id="zone-canvas" style="width: 100%; height: 600px; position: relative; 
                                 background: white; border: 2px dashed #ddd; border-radius: 4px;
                                 background-size: contain; background-repeat: no-repeat; background-position: center;">
                                <!-- Les zones seront ajoutées ici dynamiquement -->
                            </div>
                        </div>
                        
                        <div class="zones-panel">
                            <h4>Zones Actives</h4>
                            <button type="button" id="add-zone-btn" class="button button-secondary" style="width: 100%; margin-bottom: 15px;">
                                Ajouter une Zone
                            </button>
                            
                            <div id="zones-list" style="border: 1px solid #ddd; border-radius: 4px; max-height: 400px; overflow-y: auto;">
                                <!-- Les zones seront listées ici -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Champ caché pour stocker les zones -->
                    <input type="hidden" name="_zones" value="<?php echo esc_attr(json_encode($zones)); ?>" />
                </div>
            </div>
            
            <!-- BOUTON DE SAUVEGARDE FORCÉE -->
            <div class="admin-section">
                <div class="section-content">
                    <button type="button" id="force-save" class="button button-primary button-large" style="width: 100%;">
                        💾 SAUVEGARDER MAINTENANT
                    </button>
                    <div id="save-status" style="margin-top: 10px; text-align: center;"></div>
                </div>
            </div>
        </div>

        <script>
        // SAUVEGARDE FORCÉE
        jQuery(document).ready(function($) {
            $('#force-save').on('click', function() {
                const button = $(this);
                const status = $('#save-status');
                
                button.prop('disabled', true).text('💾 Sauvegarde...');
                status.html('<span style="color: orange;">Sauvegarde en cours...</span>');
                
                // Récupérer toutes les données
                const mockupData = {
                    action: 'save_mockup_zones',
                    post_id: <?php echo $post->ID; ?>,
                    post_title: $('#post_title').val(),
                    zones: window.WinShirtDebug ? window.WinShirtDebug.zones : {},
                    colors: getColorsData(),
                    default_color: $('input[name="_default_color"]').val(),
                    nonce: '<?php echo wp_create_nonce("winshirt_save"); ?>'
                };
                
                $.post(ajaxurl, mockupData)
                .done(function(response) {
                    console.log('Réponse:', response);
                    button.prop('disabled', false).text('💾 SAUVEGARDER MAINTENANT');
                    status.html('<span style="color: green;">✅ Sauvegardé avec succès!</span>');
                    
                    // Vider le message après 3 secondes
                    setTimeout(() => status.empty(), 3000);
                })
                .fail(function() {
                    button.prop('disabled', false).text('💾 SAUVEGARDER MAINTENANT');
                    status.html('<span style="color: red;">❌ Erreur de sauvegarde</span>');
                });
            });
            
            function getColorsData() {
                const colors = {};
                $('.color-row').each(function() {
                    const colorId = $(this).data('color-id');
                    colors[colorId] = {
                        name: $(this).find('.color-name').val() || 'Couleur',
                        hex: $(this).find('input[type="color"]').val() || '#FFFFFF',
                        front: $(this).find('input[name*="[front]"]').val() || '',
                        back: $(this).find('input[name*="[back]"]').val() || ''
                    };
                });
                return colors;
            }
        });
        </script>
        <?php
    }

    private function render_color_row($color_id, $color_data, $default_color) {
        $name = $color_data['name'] ?? 'Couleur';
        $hex = $color_data['hex'] ?? '#FFFFFF';
        $front = $color_data['front'] ?? '';
        $back = $color_data['back'] ?? '';
        $is_default = ($color_id === $default_color);
        ?>
        <div class="color-row" data-color-id="<?php echo esc_attr($color_id); ?>" style="border-bottom: 1px solid #eee; padding: 15px; background: #fafafa; margin-bottom: 10px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                <input type="color" value="<?php echo esc_attr($hex); ?>" style="width: 50px; height: 35px;" />
                <input type="text" class="color-name" placeholder="Nom couleur" value="<?php echo esc_attr($name); ?>" style="flex: 1; padding: 8px;" />
                <label style="display: flex; align-items: center; gap: 5px;">
                    <input type="radio" name="default_color_radio" value="<?php echo esc_attr($color_id); ?>" <?php checked($is_default); ?> />
                    Par défaut
                </label>
                <button type="button" class="remove-color button" data-color-id="<?php echo esc_attr($color_id); ?>">Supprimer</button>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="text-align: center;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">Image Recto:</label>
                    <button type="button" class="upload-image button" data-side="front" data-color="<?php echo esc_attr($color_id); ?>">
                        Choisir Image Recto
                    </button>
                    <input type="hidden" name="colors[<?php echo esc_attr($color_id); ?>][front]" value="<?php echo esc_attr($front); ?>" />
                    <div class="image-preview">
                        <?php if ($front): ?>
                            <img src="<?php echo esc_url($front); ?>" style="max-width: 120px; height: auto; margin-top: 10px; border: 1px solid #ddd;" />
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">Image Verso:</label>
                    <button type="button" class="upload-image button" data-side="back" data-color="<?php echo esc_attr($color_id); ?>">
                        Choisir Image Verso
                    </button>
                    <input type="hidden" name="colors[<?php echo esc_attr($color_id); ?>][back]" value="<?php echo esc_attr($back); ?>" />
                    <div class="image-preview">
                        <?php if ($back): ?>
                            <img src="<?php echo esc_url($back); ?>" style="max-width: 120px; height: auto; margin-top: 10px; border: 1px solid #ddd;" />
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_save_zones() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'winshirt_save')) {
            wp_send_json_error('Nonce invalide');
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error('ID post manquant');
        }

        // Sauvegarder le titre
        if (isset($_POST['post_title'])) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => sanitize_text_field($_POST['post_title'])
            ));
        }

        // Sauvegarder les zones
        if (isset($_POST['zones'])) {
            update_post_meta($post_id, '_zones', $_POST['zones']);
        }

        // Sauvegarder les couleurs
        if (isset($_POST['colors'])) {
            update_post_meta($post_id, '_mockup_colors', $_POST['colors']);
        }

        // Sauvegarder la couleur par défaut
        if (isset($_POST['default_color'])) {
            update_post_meta($post_id, '_default_color', sanitize_text_field($_POST['default_color']));
        }

        wp_send_json_success('Données sauvegardées');
    }

    public function save_mockup_data($post_id, $post) {
        if ($post->post_type !== 'winshirt_mockup') return;
        if (!isset($_POST['winshirt_mockup_nonce']) || !wp_verify_nonce($_POST['winshirt_mockup_nonce'], 'winshirt_mockup_save')) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Sauvegarder les zones
        if (isset($_POST['_zones'])) {
            update_post_meta($post_id, '_zones', $_POST['_zones']);
        }

        // Sauvegarder les couleurs
        if (isset($_POST['colors']) && is_array($_POST['colors'])) {
            update_post_meta($post_id, '_mockup_colors', $_POST['colors']);
        }

        // Sauvegarder la couleur par défaut
        if (isset($_POST['_default_color'])) {
            update_post_meta($post_id, '_default_color', sanitize_text_field($_POST['_default_color']));
        }
    }

    public function enqueue_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'winshirt_mockup') {
            wp_enqueue_media();
            wp_enqueue_script('winshirt-mockup-admin', WINSHIRT_PLUGIN_URL . 'assets/js/mockup-admin.js', array('jquery'), WINSHIRT_VERSION, true);
            wp_enqueue_style('winshirt-mockup-admin', WINSHIRT_PLUGIN_URL . 'assets/css/mockup-admin.css', array(), WINSHIRT_VERSION);
            
            wp_localize_script('winshirt-mockup-admin', 'winshirtAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('winshirt_save')
            ));
        }
    }
}
