<?php
/**
 * Template – WinShirt Modal (shell)
 * Structure minimale + CSS critique inline pour verrouiller le layout.
 *
 * @package WinShirt
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

?>
<div id="winshirt-modal" class="winshirt-customizer-modal" aria-hidden="true" role="dialog" aria-modal="true">

  <!-- Backdrop -->
  <div class="ws-backdrop" aria-hidden="true"></div>

  <!-- Dialog -->
  <div class="winshirt-customizer-dialog" role="document">

    <!-- CSS critique pour neutraliser le thème et stabiliser la grille -->
    <style id="ws-critical-modal">
      /* Reset local (pas de reset global pour ne pas polluer le thème) */
      #winshirt-modal, #winshirt-modal * { box-sizing: border-box; }
      body.ws-modal-open { overflow: hidden; }

      /* Overlay & visibilité */
      #winshirt-modal { position: fixed; inset: 0; z-index: 99999; display: none; }
      #winshirt-modal.ws-open { display: block; }
      #winshirt-modal .ws-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.45); }

      /* Dialog plein écran au-dessus du backdrop */
      #winshirt-modal .winshirt-customizer-dialog {
        position: absolute; inset: 0;
        display: flex; flex-direction: column;
        width: 100vw; height: 100vh; /* clé */
      }

      /* Corps = grille stable 220 | 1fr | 320 */
      #winshirt-modal .winshirt-customizer-body{
        position: relative;
        width: 100%; height: 100%; /* clé */
        display: grid;
        grid-template-columns: 220px minmax(0,1fr) 320px; /* minmax(0,1fr) empêche l’effondrement */
        gap: 16px;
        padding: 12px;
        overflow: hidden;
        background: transparent;
      }

      /* Colonne gauche (L1) */
      #winshirt-modal .ws-l1{
        background: #0f172a; color: #fff; border-radius: 12px; padding: 8px;
        display: flex; flex-direction: column; gap: 8px; align-self: start;
      }
      #winshirt-modal .ws-l1-item{
        background: transparent; border: 1px solid rgba(255,255,255,.2); color: #fff;
        border-radius: 10px; padding: 10px 12px; text-align: left; cursor: pointer
      }
      #winshirt-modal .ws-l1-item.is-active, #winshirt-modal .ws-l1-item:hover{
        background:#111827; border-color:#fff;
      }

      /* Panneau de droite (L2) */
      #winshirt-modal .ws-l2{
        background:#fff; border-radius:12px; padding:10px;
        overflow:auto; max-height: calc(100vh - 24px);
      }
      #winshirt-modal .ws-l2-head{ display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:8px }
      #winshirt-modal .ws-l2-title{ margin:0; font-size:16px }
      #winshirt-modal .ws-l2-back{ opacity:.8 }

      /* Zone centrale (mockup) */
      #winshirt-modal .winshirt-mockup-area{
        background:#fff; border-radius:12px; padding:16px;
        display:flex; flex-direction:column; align-items:center; gap:16px;
        overflow:auto;
      }
      #winshirt-modal .winshirt-canvas{
        position:relative; width:min(820px, 78vw); aspect-ratio:3/4; background:transparent;
      }
      #winshirt-modal .winshirt-mockup-img{
        position:absolute; inset:0; margin:auto;
        max-width:100%; max-height:100%; object-fit:contain; display:none;
      }
      #winshirt-modal .winshirt-mockup-img.ws-show{ display:block }

      /* Bouton Fermer */
      #winshirt-modal .ws-close{
        position:absolute; top:12px; right:12px; z-index:2;
        background:#111827; color:#fff; border:none; border-radius:10px; padding:8px 10px; cursor:pointer
      }

      /* Responsive */
      @media (max-width:1024px){
        #winshirt-modal .winshirt-customizer-body{ grid-template-columns: 1fr; gap:12px }
        #winshirt-modal .ws-l1{ flex-direction:row; position:sticky; top:8px; z-index:2 }
        #winshirt-modal .ws-l2{ order:3 }
        #winshirt-modal .winshirt-mockup-area{ order:2 }
      }
    </style>

    <!-- Bouton fermer -->
    <button type="button" class="ws-close" aria-label="<?php esc_attr_e('Fermer', 'winshirt'); ?>">Fermer</button>

    <!-- Corps -->
    <main class="winshirt-customizer-body" role="main" aria-label="<?php esc_attr_e('Personnalisez', 'winshirt'); ?>">

      <!-- Colonne gauche (L1) -->
      <nav class="ws-l1" aria-label="<?php esc_attr_e('Panneaux', 'winshirt'); ?>">
        <button class="ws-l1-item is-active" data-panel="images"><?php esc_html_e('Images', 'winshirt'); ?></button>
        <button class="ws-l1-item" data-panel="text"><?php esc_html_e('Texte', 'winshirt'); ?></button>
        <button class="ws-l1-item" data-panel="layers"><?php esc_html_e('Calques', 'winshirt'); ?></button>
        <button class="ws-l1-item" data-panel="qrcode"><?php esc_html_e('QR Code', 'winshirt'); ?></button>

        <div style="margin-top:auto; display:flex; gap:8px; padding:8px 0;">
          <div class="ws-side-switch">
            <button class="ws-side-btn is-active" data-side="front"><?php esc_html_e('Recto', 'winshirt'); ?></button>
            <button class="ws-side-btn" data-side="back"><?php esc_html_e('Verso', 'winshirt'); ?></button>
          </div>
        </div>

        <div class="ws-cta" style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
          <button class="button button-primary"><?php esc_html_e('Enregistrer le design', 'winshirt'); ?></button>
          <button class="button"><?php esc_html_e('Ajouter au panier', 'winshirt'); ?></button>
        </div>
      </nav>

      <!-- Centre (mockup) -->
      <section class="winshirt-mockup-area">
        <div id="winshirt-canvas" class="winshirt-canvas" aria-live="polite">
          <!-- images mockup ajoutées par JS (front/back) -->
          <img class="winshirt-mockup-img" data-side="front" alt="Mockup Recto">
          <img class="winshirt-mockup-img" data-side="back"  alt="Mockup Verso">
          <!-- zones rendu par JS -->
        </div>
        <div class="ws-zone-buttons" aria-label="<?php esc_attr_e('Zones disponibles', 'winshirt'); ?>"></div>
      </section>

      <!-- Droite (L2) -->
      <aside class="ws-l2" aria-live="polite">
        <div class="ws-l2-head">
          <h3 class="ws-l2-title">Images</h3>
          <button class="ws-l2-back button">&larr; <?php esc_html_e('Retour', 'winshirt'); ?></button>
        </div>
        <div class="ws-l2-body"><!-- contenu outillage injecté par JS --></div>
      </aside>
    </main>
  </div>
</div>
