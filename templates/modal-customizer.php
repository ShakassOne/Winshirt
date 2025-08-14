<?php
/**
 * WinShirt - Modale du customizer (structure simple, stable)
 * Inclus par le thème via hook, ou rendu par shortcode.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div id="winshirt-customizer-modal" class="winshirt-modal" aria-hidden="true">
  <div class="winshirt-overlay" data-close="1"></div>

  <div class="winshirt-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Personnalisez','winshirt'); ?>">
    <header class="ws-head">
      <h3><?php esc_html_e('Personnalisez', 'winshirt'); ?></h3>
      <button type="button" class="ws-close" data-close="1" aria-label="<?php esc_attr_e('Fermer','winshirt');?>">×</button>
    </header>

    <main class="ws-body">
      <aside class="ws-left">
        <nav class="ws-tabs">
          <button class="ws-tab is-active" data-panel="images"><?php esc_html_e('Images','winshirt'); ?></button>
          <button class="ws-tab" data-panel="text"><?php esc_html_e('Texte','winshirt'); ?></button>
          <button class="ws-tab" data-panel="layers"><?php esc_html_e('Calques','winshirt'); ?></button>
          <button class="ws-tab" data-panel="qr"><?php esc_html_e('QR Code','winshirt'); ?></button>
        </nav>

        <div class="ws-actions">
          <div class="ws-side-switch">
            <button class="ws-side ws-side-front is-active" data-side="front"><?php esc_html_e('Recto','winshirt'); ?></button>
            <button class="ws-side ws-side-back" data-side="back"><?php esc_html_e('Verso','winshirt'); ?></button>
          </div>
          <button class="ws-save"><?php esc_html_e('Enregistrer le design','winshirt'); ?></button>
          <button class="ws-cart"><?php esc_html_e('Ajouter au panier','winshirt'); ?></button>
        </div>
      </aside>

      <section class="ws-canvas-wrap">
        <div id="winshirt-canvas" class="ws-canvas" aria-live="polite">
          <img class="winshirt-mockup-img" data-side="front" alt="Mockup Recto" />
          <img class="winshirt-mockup-img" data-side="back"  alt="Mockup Verso" />
          <!-- zones d’impression -->
        </div>
        <div class="ws-zone-hint"></div>
      </section>

      <aside class="ws-right">
        <div class="ws-panel is-active" data-panel="images">
          <div class="ws-l2-head">
            <strong><?php esc_html_e('Images','winshirt'); ?></strong>
            <button class="button ws-l2-back" type="button"><?php esc_html_e('← Retour','winshirt'); ?></button>
          </div>
          <div class="ws-l2-body"><!-- rempli par image-tools.js --></div>
        </div>

        <div class="ws-panel" data-panel="text">
          <div class="ws-l2-body"><!-- rempli par text-tools.js --></div>
        </div>

        <div class="ws-panel" data-panel="layers">
          <div class="ws-l2-body">
            <ul class="ws-layers-list"><!-- rempli par layer-manager.js --></ul>
          </div>
        </div>

        <div class="ws-panel" data-panel="qr">
          <div class="ws-l2-body">
            <p><?php esc_html_e('Bientôt : génération de QR Code.','winshirt'); ?></p>
          </div>
        </div>
      </aside>
    </main>
  </div>
</div>
