<?php
/**
 * WinShirt - Template Modal Customizer (Recovery v1.0)
 * Template unifié sans conflits
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Récupérer données produit/mockup
$product_id = get_the_ID();
$mockup_id = get_post_meta( $product_id, '_winshirt_mockup_id', true );

if ( ! $mockup_id ) {
    return; // Pas de mockup configuré
}

// Récupérer données mockup (nouvelle structure ou fallback)
$mockup_data = get_post_meta( $mockup_id, '_ws_mockup_data', true );
if ( ! $mockup_data ) {
    // Fallback vers anciennes meta keys
    $front = get_post_meta( $mockup_id, '_winshirt_mockup_front', true );
    $back = get_post_meta( $mockup_id, '_winshirt_mockup_back', true );
    $mockup_data = array(
        'images' => array(
            'front' => $front ?: '',
            'back' => $back ?: ''
        ),
        'zones' => array( 'front' => array(), 'back' => array() )
    );
}

// Vérifier qu'on a au moins une image
if ( empty( $mockup_data['images']['front'] ) && empty( $mockup_data['images']['back'] ) ) {
    return; // Pas d'images mockup
}
?>

<div id="winshirt-customizer-modal" class="winshirt-modal" aria-hidden="true">
    
    <!-- Header -->
    <div class="winshirt-dialog" role="dialog" aria-modal="true" aria-labelledby="winshirt-modal-title">
        
        <header class="ws-header">
            <h3 id="winshirt-modal-title"><?php esc_html_e( 'Personnalisez votre produit', 'winshirt' ); ?></h3>
            <button type="button" class="ws-close" aria-label="<?php esc_attr_e( 'Fermer', 'winshirt' ); ?>">
                <span aria-hidden="true">&times;</span>
            </button>
        </header>
        
        <!-- Contrôles côtés -->
        <div class="ws-side-controls">
            <button class="ws-side-btn active" data-side="front">
                <?php esc_html_e( 'Recto', 'winshirt' ); ?>
            </button>
            <?php if ( ! empty( $mockup_data['images']['back'] ) ) : ?>
                <button class="ws-side-btn" data-side="back">
                    <?php esc_html_e( 'Verso', 'winshirt' ); ?>
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Canvas principal -->
        <main class="ws-canvas-container">
            <div id="winshirt-canvas" class="ws-canvas">
                
                <!-- Image mockup recto -->
                <?php if ( ! empty( $mockup_data['images']['front'] ) ) : ?>
                    <img class="winshirt-mockup-img" 
                         data-side="front" 
                         src="<?php echo esc_url( $mockup_data['images']['front'] ); ?>" 
                         alt="<?php esc_attr_e( 'Mockup recto', 'winshirt' ); ?>" />
                <?php endif; ?>
                
                <!-- Image mockup verso -->
                <?php if ( ! empty( $mockup_data['images']['back'] ) ) : ?>
                    <img class="winshirt-mockup-img" 
                         data-side="back" 
                         src="<?php echo esc_url( $mockup_data['images']['back'] ); ?>" 
                         alt="<?php esc_attr_e( 'Mockup verso', 'winshirt' ); ?>" 
                         style="display: none;" />
                <?php endif; ?>
                
                <!-- Zones d'impression (injectées en JavaScript) -->
                
                <!-- Hint si pas de zones -->
                <div class="ws-zone-hint" style="display: none;">
                    <?php esc_html_e( 'Chargement des zones d\'impression...', 'winshirt' ); ?>
                </div>
                
            </div>
        </main>
        
        <!-- Actions footer -->
        <footer class="ws-actions">
            <button type="button" class="button ws-save">
                <?php esc_html_e( 'Sauvegarder', 'winshirt' ); ?>
            </button>
            <button type="button" class="button ws-add-cart">
                <?php esc_html_e( 'Ajouter au panier', 'winshirt' ); ?>
            </button>
        </footer>
        
    </div>
</div>

<?php
// Debug info si WP_DEBUG actif
if ( defined( 'WP_DEBUG' ) && WP_DEBUG && current_user_can( 'manage_options' ) ) :
?>
<!-- Debug Info (visible uniquement en mode debug) -->
<script>
console.group('WinShirt Debug Info');
console.log('Product ID:', <?php echo (int) $product_id; ?>);
console.log('Mockup ID:', <?php echo (int) $mockup_id; ?>);
console.log('Mockup Data:', <?php echo wp_json_encode( $mockup_data ); ?>);
console.log('WinShirtData:', window.WinShirtData || 'Not loaded');
console.groupEnd();
</script>
<?php endif; ?>
