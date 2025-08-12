<?php
/**
 * WinShirt – Template Customizer (front)
 * Layout :
 *  - Desktop : nav gauche / canvas centre / panneaux à droite
 *  - Mobile  : canvas en haut / barre de boutons en bas / panneaux en dessous
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="winshirt-shell">

  <!-- NAV GAUCHE (desktop) -->
  <aside class="ws-nav-left">
    <nav class="ws-nav" aria-label="<?php esc_attr_e('Personnalisation', 'winshirt'); ?>">
      <button class="ws-btn" data-ws-open="images"><?php esc_html_e('Images', 'winshirt'); ?></button>
      <button class="ws-btn" data-ws-open="text"><?php esc_html_e('Texte', 'winshirt'); ?></button>
      <button class="ws-btn" data-ws-open="layers"><?php esc_html_e('Calques', 'winshirt'); ?></button>
      <button class="ws-btn" data-ws-open="qr"><?php esc_html_e('QR Code', 'winshirt'); ?></button>
    </nav>
  </aside>

  <!-- CANVAS / MOCKUP AU CENTRE -->
  <main class="winshirt-mockup-area" aria-live="polite">
    <div id="winshirt-canvas" class="winshirt-mockup-canvas" role="img" aria-label="<?php esc_attr_e('Aperçu du mockup', 'winshirt'); ?>"></div>

    <div class="ws-side-switch" aria-label="<?php esc_attr_e('Côté du produit', 'winshirt'); ?>">
      <button class="button" data-ws-side="front"><?php esc_html_e('Recto', 'winshirt'); ?></button>
      <button class="button" data-ws-side="back"><?php esc_html_e('Verso', 'winshirt'); ?></button>
    </div>

    <div class="ws-actions" style="display:flex; gap:10px; justify-content:center; margin-top:12px;">
      <button class="button button-primary" data-ws-save><?php esc_html_e('Enregistrer le design', 'winshirt'); ?></button>
      <button class="button button-primary" data-ws-add-to-cart><?php esc_html_e('Ajouter au panier', 'winshirt'); ?></button>
    </div>
  </main>

  <!-- PANNEAUX A DROITE -->
  <aside id="winshirt-panel-root">
    <div class="ws-panels">
      <!-- IMAGES -->
      <section class="ws-panel" data-panel="images" aria-hidden="true">
        <header class="ws-panel-head">
          <span class="title"><?php esc_html_e('Images', 'winshirt'); ?></span>
          <button class="ws-back" data-ws-close>&larr; <?php esc_html_e('Retour', 'winshirt'); ?></button>
        </header>
        <div class="ws-gallery"><?php
          /**
           * Contenu de la galerie.
           * Laisse la main aux hooks/classes existantes si elles peuplent la galerie.
           * Sinon, on tente un fallback simple via une action dédiée.
           */
          do_action( 'winshirt_panel_images_content' );
        ?></div>
      </section>

      <!-- TEXTE -->
      <section class="ws-panel" data-panel="text" aria-hidden="true">
        <header class="ws-panel-head">
          <span class="title"><?php esc_html_e('Texte', 'winshirt'); ?></span>
          <button class="ws-back" data-ws-close>&larr; <?php esc_html_e('Retour', 'winshirt'); ?></button>
        </header>
        <form class="ws-text-form" onsubmit="return false" style="display:grid; gap:8px;">
          <label>
            <?php esc_html_e('Texte', 'winshirt'); ?>
            <input type="text" class="ws-input-text" value="Winshirt">
          </label>
          <label>
            <?php esc_html_e('Taille', 'winshirt'); ?>
            <input type="number" class="ws-input-size" value="32" min="10" max="160">
          </label>
          <label><input type="checkbox" class="ws-input-bold"> <?php esc_html_e('Gras', 'winshirt'); ?></label>
          <label><input type="checkbox" class="ws-input-italic"> <?php esc_html_e('Italique', 'winshirt'); ?></label>
          <button class="button" data-ws-add-text><?php esc_html_e('Ajouter', 'winshirt'); ?></button>
        </form>
      </section>

      <!-- CALQUES -->
      <section class="ws-panel" data-panel="layers" aria-hidden="true">
        <header class="ws-panel-head">
          <span class="title"><?php esc_html_e('Calques', 'winshirt'); ?></span>
          <button class="ws-back" data-ws-close>&larr; <?php esc_html_e('Retour', 'winshirt'); ?></button>
        </header>
        <div class="ws-layers-list">
          <?php do_action( 'winshirt_panel_layers_content' ); ?>
          <!-- La liste sera peuplée côté JS dans une prochaine étape -->
        </div>
      </section>

      <!-- QR CODE -->
      <section class="ws-panel" data-panel="qr" aria-hidden="true">
        <header class="ws-panel-head">
          <span class="title"><?php esc_html_e('QR Code', 'winshirt'); ?></span>
          <button class="ws-back" data-ws-close>&larr; <?php esc_html_e('Retour', 'winshirt'); ?></button>
        </header>
        <div class="ws-qr-form" style="display:grid; gap:8px;">
          <label>
            URL
            <input type="url" class="ws-qr-url" placeholder="https://…">
          </label>
          <button class="button" data-ws-add-qr><?php esc_html_e('Ajouter un QR', 'winshirt'); ?></button>
        </div>
      </section>
    </div>
  </aside>

  <!-- BARRE MOBILE EN BAS -->
  <div class="ws-mobile-bar" role="tablist" aria-label="<?php esc_attr_e('Outils', 'winshirt'); ?>">
    <button class="ws-btn" data-ws-open="images"><?php esc_html_e('Images', 'winshirt'); ?></button>
    <button class="ws-btn" data-ws-open="text"><?php esc_html_e('Texte', 'winshirt'); ?></button>
    <button class="ws-btn" data-ws-open="layers"><?php esc_html_e('Calques', 'winshirt'); ?></button>
    <button class="ws-btn" data-ws-open="qr"><?php esc_html_e('QR Code', 'winshirt'); ?></button>
  </div>

</div>

<script>
/* Glue minimale pour les boutons Ajouter/Save/Text, en attendant les fichiers dédiés */
document.addEventListener('click', function(e){
  // Ajouter un visuel depuis la galerie (clic sur image)
  if(e.target.closest('.ws-gallery img')){
    e.preventDefault();
    var url = e.target.getAttribute('src');
    if(window.WinShirtLayers && url){ window.WinShirtLayers.addImage(url); }
  }
  // Ajouter texte
  if(e.target.matches('[data-ws-add-text]')){
    e.preventDefault();
    var root = e.target.closest('.ws-panel');
    var txt  = root.querySelector('.ws-input-text')?.value || 'Votre texte';
    var size = parseInt(root.querySelector('.ws-input-size')?.value || '32', 10);
    var bold = root.querySelector('.ws-input-bold')?.checked || false;
    var italic = root.querySelector('.ws-input-italic')?.checked || false;
    if(window.WinShirtLayers){
      window.WinShirtLayers.addText({ text: txt, size: size, bold: bold, italic: italic });
    }
  }
  // Enregistrer / Ajouter au panier (hooks JS à venir)
  if(e.target.matches('[data-ws-save]')){ e.preventDefault(); console.log('TODO: save design'); }
  if(e.target.matches('[data-ws-add-to-cart]')){ e.preventDefault(); console.log('TODO: add to cart'); }
}, {passive:false});
</script>
