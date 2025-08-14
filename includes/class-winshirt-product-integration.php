<?php
/**
 * WinShirt Product Integration
 * Intégration WooCommerce pour les produits personnalisables
 * 
 * @package WinShirt
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class WinShirt_Product_Integration {

    /**
     * Initialisation
     */
    public function __construct() {
        // Vérifier que WooCommerce est actif
        if (!class_exists('WooCommerce')) {
            return;
        }

        $this->init_hooks();
    }

    /**
     * Initialiser les hooks
     */
    private function init_hooks() {
        // Métabox dans l'admin produit
        add_action('add_meta_boxes', array($this, 'add_product_metabox'));
        
        // Sauvegarde des données produit
        add_action('woocommerce_process_product_meta', array($this, 'save_product_meta'));
        
        // Affichage front-end
        add_action('woocommerce_single_product_summary', array($this, 'display_customizer_button'), 25);
    }

    /**
     * Ajouter la métabox aux produits
     */
    public function add_product_metabox() {
        add_meta_box(
            'winshirt_product_options',
            'WinShirt - Personnalisation',
            array($this, 'render_product_metabox'),
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Afficher le contenu de la métabox
     */
    public function render_product_metabox($post) {
        // Sécurité nonce
        wp_nonce_field('winshirt_product_meta', 'winshirt_product_nonce');

        // Récupérer les valeurs actuelles
        $is_customizable = get_post_meta($post->ID, '_winshirt_customizable', true);
        $selected_mockup = get_post_meta($post->ID, '_winshirt_mockup_id', true);

        // Récupérer la liste des mockups disponibles
        $mockups = get_posts(array(
            'post_type' => 'winshirt_mockup',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        ?>
        <div class="winshirt-product-options">
            <style>
                .winshirt-product-options { padding: 15px; }
                .winshirt-option-row { margin-bottom: 15px; }
                .winshirt-option-row label { font-weight: bold; margin-right: 10px; }
                .winshirt-mockup-preview { 
                    max-width: 100px; 
                    height: auto; 
                    margin-left: 10px; 
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                .winshirt-description { 
                    color: #666; 
                    font-size: 13px; 
                    margin-top: 5px; 
                }
            </style>

            <div class="winshirt-option-row">
                <label>
                    <input type="checkbox" 
                           name="_winshirt_customizable" 
                           value="1" 
                           <?php checked($is_customizable, '1'); ?> />
                    Activer la personnalisation pour ce produit
                </label>
                <div class="winshirt-description">
                    Cochez cette case pour permettre aux clients de personnaliser ce produit.
                </div>
            </div>

            <div class="winshirt-option-row" id="mockup-selection" 
                 style="<?php echo $is_customizable ? '' : 'display:none;'; ?>">
                <label for="winshirt_mockup_select">Mockup à utiliser :</label>
                <select name="_winshirt_mockup_id" id="winshirt_mockup_select">
                    <option value="">-- Sélectionner un mockup --</option>
                    <?php foreach ($mockups as $mockup): ?>
                        <option value="<?php echo $mockup->ID; ?>" 
                                <?php selected($selected_mockup, $mockup->ID); ?>>
                            <?php echo esc_html($mockup->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <?php if ($selected_mockup && is_numeric($selected_mockup)): ?>
                    <?php 
                    // Afficher un aperçu du mockup sélectionné
                    $mockup_colors = get_post_meta($selected_mockup, '_mockup_colors', true);
                    $default_color = get_post_meta($selected_mockup, '_default_color', true);
                    
                    if ($mockup_colors && isset($mockup_colors[$default_color])) {
                        $preview_image = $mockup_colors[$default_color]['front'];
                        if ($preview_image) {
                            echo '<img src="' . esc_url($preview_image) . '" class="winshirt-mockup-preview" alt="Aperçu mockup" />';
                        }
                    }
                    ?>
                <?php endif; ?>

                <div class="winshirt-description">
                    Le mockup définit les zones de personnalisation et les couleurs disponibles.
                    <br><a href="<?php echo admin_url('edit.php?post_type=winshirt_mockup'); ?>" target="_blank">
                        Gérer les mockups →
                    </a>
                </div>
            </div>

            <?php if (!empty($mockups) && $selected_mockup): ?>
                <div class="winshirt-option-row" id="mockup-info">
                    <?php
                    $zones = get_post_meta($selected_mockup, '_zones', true);
                    $zones_count = is_array($zones) ? count($zones) : 0;
                    $colors_count = is_array($mockup_colors) ? count($mockup_colors) : 0;
                    ?>
                    <div class="winshirt-description">
                        <strong>Informations du mockup sélectionné :</strong><br>
                        • <?php echo $zones_count; ?> zone(s) de personnalisation<br>
                        • <?php echo $colors_count; ?> couleur(s) disponible(s)
                    </div>
                </div>
            <?php elseif (empty($mockups)): ?>
                <div class="winshirt-option-row">
                    <div class="winshirt-description" style="color: #d54e21;">
                        <strong>Aucun mockup disponible.</strong><br>
                        <a href="<?php echo admin_url('post-new.php?post_type=winshirt_mockup'); ?>">
                            Créer votre premier mockup →
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Afficher/masquer la sélection de mockup
            $('input[name="_winshirt_customizable"]').change(function() {
                if ($(this).is(':checked')) {
                    $('#mockup-selection').show();
                } else {
                    $('#mockup-selection').hide();
                    $('#winshirt_mockup_select').val('');
                }
            });

            // Mise à jour de l'aperçu lors du changement de mockup
            $('#winshirt_mockup_select').change(function() {
                var mockupId = $(this).val();
                if (mockupId) {
                    // Ici on pourrait faire un appel AJAX pour récupérer l'aperçu
                    // Pour l'instant, on recharge la page pour voir l'aperçu
                    // location.reload(); // Commenté pour éviter de perdre les autres modifications
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Sauvegarder les métadonnées du produit
     */
    public function save_product_meta($product_id) {
        // Vérifier la sécurité
        if (!isset($_POST['winshirt_product_nonce']) || 
            !wp_verify_nonce($_POST['winshirt_product_nonce'], 'winshirt_product_meta')) {
            return;
        }

        // Vérifier les permissions
        if (!current_user_can('edit_post', $product_id)) {
            return;
        }

        // Sauvegarder l'option de personnalisation
        $is_customizable = isset($_POST['_winshirt_customizable']) ? '1' : '0';
        update_post_meta($product_id, '_winshirt_customizable', $is_customizable);

        // Sauvegarder le mockup sélectionné
        if (isset($_POST['_winshirt_mockup_id']) && !empty($_POST['_winshirt_mockup_id'])) {
            $mockup_id = intval($_POST['_winshirt_mockup_id']);
            update_post_meta($product_id, '_winshirt_mockup_id', $mockup_id);
        } else {
            delete_post_meta($product_id, '_winshirt_mockup_id');
        }

        // Log pour debug (optionnel)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WinShirt: Product ' . $product_id . ' customization saved - Customizable: ' . $is_customizable);
        }
    }

    /**
     * Vérifier si un produit est personnalisable
     * Fonction utilitaire pour les autres parties du plugin
     */
    public static function is_product_customizable($product_id) {
        return get_post_meta($product_id, '_winshirt_customizable', true) === '1';
    }

    /**
     * Récupérer le mockup associé à un produit
     */
    public static function get_product_mockup($product_id) {
        $mockup_id = get_post_meta($product_id, '_winshirt_mockup_id', true);
        return $mockup_id ? intval($mockup_id) : null;
    }

    /**
     * Afficher le bouton de personnalisation sur la page produit
     */
    public function display_customizer_button() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $product_id = $product->get_id();
        
        // Vérifier si le produit est personnalisable
        if (!self::is_product_customizable($product_id)) {
            return;
        }
        
        $mockup_id = self::get_product_mockup($product_id);
        if (!$mockup_id) {
            return;
        }
        
        // Récupérer les infos du mockup
        $mockup = get_post($mockup_id);
        if (!$mockup) {
            return;
        }
        
        ?>
        <div class="winshirt-customizer-section" style="margin: 20px 0;">
            <style>
                .winshirt-customize-btn {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 15px 30px;
                    border: none;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: bold;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-block;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
                }
                .winshirt-customize-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
                    color: white;
                    text-decoration: none;
                }
                .winshirt-mockup-info {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 8px;
                    margin-top: 15px;
                    border-left: 4px solid #667eea;
                }
                .winshirt-mockup-info h4 {
                    margin: 0 0 10px 0;
                    color: #333;
                }
                .winshirt-mockup-features {
                    list-style: none;
                    padding: 0;
                    margin: 0;
                }
                .winshirt-mockup-features li {
                    padding: 5px 0;
                    color: #666;
                }
                .winshirt-mockup-features li:before {
                    content: "✓ ";
                    color: #28a745;
                    font-weight: bold;
                    margin-right: 5px;
                }
            </style>
            
            <a href="#" class="winshirt-customize-btn" onclick="openWinShirtCustomizer(<?php echo $product_id; ?>, <?php echo $mockup_id; ?>); return false;">
                🎨 Personnaliser ce produit
            </a>
            
            <div class="winshirt-mockup-info">
                <h4>Personnalisation disponible</h4>
                <ul class="winshirt-mockup-features">
                    <?php
                    // Récupérer les infos du mockup
                    $zones = get_post_meta($mockup_id, '_zones', true);
                    $colors = get_post_meta($mockup_id, '_mockup_colors', true);
                    $zones_count = is_array($zones) ? count($zones) : 0;
                    $colors_count = is_array($colors) ? count($colors) : 0;
                    ?>
                    <li><?php echo $zones_count; ?> zone(s) de personnalisation</li>
                    <li><?php echo $colors_count; ?> couleur(s) disponible(s)</li>
                    <li>Aperçu temps réel</li>
                    <li>Sauvegarde de vos créations</li>
                </ul>
            </div>
            
            <script>
            function openWinShirtCustomizer(productId, mockupId) {
                // Pour l'instant, afficher une alerte - à remplacer par l'ouverture du customizer
                alert('Ouverture du customizer pour le produit ' + productId + ' avec le mockup ' + mockupId + '\n\nCustomizer en cours de développement...');
                
                // TODO: Ici on ouvrira le customizer dans une modale ou une nouvelle page
                // window.open('/winshirt-customizer/?product=' + productId + '&mockup=' + mockupId, '_blank');
            }
            </script>
        </div>
        <?php
    }

    /**
     * Récupérer tous les produits personnalisables
     */
    public static function get_customizable_products() {
        return get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_winshirt_customizable',
                    'value' => '1',
                    'compare' => '='
                )
            )
        ));
    }
}

// Initialiser seulement si nous sommes dans l'admin
if (is_admin()) {
    new WinShirt_Product_Integration();
}
