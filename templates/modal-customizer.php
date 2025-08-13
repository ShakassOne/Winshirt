<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div id="winshirt-customizer-modal" class="winshirt-customizer-modal" aria-hidden="true">
  <!-- Place le backdrop AVANT le dialog pour être sûr qu’il reste derrière -->
  <div class="ws-backdrop" data-ws-close></div>

  <div class="winshirt-customizer-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Personnalisez', 'winshirt'); ?>">
    <div class="winshirt-customizer-body">
      <aside class="ws-l1">
        <button type="button" class="ws-l1-item is-active" data-panel="images"><?php echo esc_html__('Images', 'winshirt'); ?></button>
        <button type="button" class="ws-l1-item" data-panel="text"><?php echo esc_html__('Texte', 'winshirt'); ?></button>
        <button type="button" class="ws-l1-item" data-panel="layers"><?php echo esc_html__('Calques', 'winshirt'); ?></button>
        <button type="button" class="ws-l1-item" data-panel="qrcode"><?php echo esc_html__('QR Code', 'winshirt'); ?></button>
      </aside>

      <main class="winshirt-shell">
        <div class="winshirt-mockup-area">
          <div id="winshirt-canvas" class="winshirt-canvas" aria-live="polite">
            <img id="ws-mockup-front" class="winshirt-mockup-img" data-side="front" alt="Mockup Recto">
            <img id="ws-mockup-back"  class="winshirt-mockup-img" data-side="back"  alt="Mockup Verso">
          </div>

          <div class="ws-side-switch">
            <button type="button" class="ws-side-btn is-active" data-side="front"><?php echo esc_html__('Recto', 'winshirt'); ?></button>
            <button type="button" class="ws-side-btn" data-side="back"><?php echo esc_html__('Verso', 'winshirt'); ?></button>
          </div>

          <div id="ws-zone-buttons" class="ws-zone-buttons"></div>

          <div class="ws-cta">
            <button type="button" class="button" id="ws-save"><?php echo esc_html__('Enregistrer le design', 'winshirt'); ?></button>
            <button type="button" class="button button-primary" id="ws-add-to-cart"><?php echo esc_html__('Ajouter au panier', 'winshirt'); ?></button>
          </div>
        </div>
      </main>

      <aside class="ws-l2">
        <header class="ws-l2-head">
          <h3 class="ws-l2-title">Images</h3>
          <button class="button button-small ws-l2-back" type="button">&larr; <?php echo esc_html__('Retour', 'winshirt'); ?></button>
        </header>
        <div class="ws-l2-body"></div>
      </aside>
    </div>

    <button type="button" class="ws-close" data-ws-close aria-label="<?php esc_attr_e('Fermer', 'winshirt'); ?>">Fermer</button>
  </div>
</div>
