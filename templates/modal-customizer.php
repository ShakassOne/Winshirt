<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<style>
/* === CRITICAL CSS === */
.winshirt-shell{
  box-sizing:border-box;
  display:grid;
  grid-template-columns:260px minmax(0,1fr) 360px;
  grid-template-areas:"nav canvas panel";
  gap:16px; align-items:start;
  font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif
}
.ws-nav-left{ grid-area:nav; position:sticky; top:72px }
.winshirt-mockup-area{ grid-area:canvas; display:flex; flex-direction:column; align-items:center; min-height:520px }
#winshirt-canvas.winshirt-mockup-canvas{ position:relative; width:640px; height:720px; max-width:100% }
#winshirt-panel-root{ grid-area:panel; min-height:520px }

.ws-nav{ display:flex; flex-direction:column; gap:10px }
.ws-nav .ws-btn{ appearance:none; border:1px solid #e5e7eb; background:#fff; border-radius:10px; padding:10px 12px; text-align:left; cursor:pointer }
.ws-nav .ws-btn.is-active{ border-color:#111827; background:#111827; color:#fff }

.ws-side-switch{ display:flex; justify-content:center; gap:8px; margin-top:12px }
.ws-side-switch .button{ appearance:none; border:1px solid #e5e7eb; background:#f9fafb; padding:6px 12px; border-radius:8px; cursor:pointer }
.ws-actions{ display:flex; gap:10px; justify-content:center; margin-top:12px }
.ws-actions .button{ appearance:none; border:1px solid #111827; background:#111827; color:#fff; border-radius:8px; padding:8px 12px; cursor:pointer }

.ws-panels{ display:block }
.ws-panel{
  display:none; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px;
  max-height:720px; overflow:auto;
}
.ws-panel.is-open{ display:block }
.ws-panel-head{ display:flex; align-items:center; justify-content:space-between; margin-bottom:10px }
.ws-panel-head .title{ font-weight:700 }
.ws-panel-head .ws-back{ background:none; border:1px solid #e5e7eb; border-radius:8px; cursor:pointer; padding:6px 10px }

.ws-gallery{ display:grid; grid-template-columns:repeat(2,1fr); gap:12px }
.ws-thumb{ background:#f7f7f7; border-radius:10px; overflow:hidden; aspect-ratio:1/1; display:flex; align-items:center; justify-content:center }
.ws-thumb img{ max-width:100%; max-height:100%; pointer-events:none; user-select:none }

.ws-layers-list{ display:flex; flex-direction:column; gap:8px }
.ws-print-zone{ border:1px dashed rgba(0,0,0,.25); pointer-events:none }

/* Barre mobile : masquée par défaut (desktop) */
.ws-mobile-bar{ display:none; }

@media(max-width:1024px){
  .winshirt-shell{ display:block }
  .ws-nav-left{ display:none }
  #winshirt-canvas.winshirt-mockup-canvas{ width:92vw; height:105vw; margin:0 auto }
  #winshirt-panel-root{ margin-top:8px }
  .ws-panel{ display:none; border:none; border-top:1px solid #e5e7eb; border-radius:0; padding:12px; max-height:none; }
  .ws-panel.is-open{ display:block }
  .ws-mobile-bar{
    display:flex;
    position:sticky; bottom:0; background:#fff; border-top:1px solid #e5e7eb;
    padding:10px 12px; gap:10px; justify-content:space-between; z-index:20
  }
  .ws-mobile-bar .ws-btn{ flex:1 1 auto; appearance:none; border:1px solid #e5e7eb; background:#f9fafb; padding:10px; border-radius:10px; text-align:center }
  .ws-mobile-bar .ws-btn.is-active{ background:#111827; color:#fff; border-color:#111827 }
}
</style>

<div class="winshirt-shell">

  <!-- NAV GAUCHE -->
  <aside class="ws-nav-left">
    <nav class="ws-nav" aria-label="<?php esc_attr_e('Personnalisation', 'winshirt'); ?>">
      <button class="ws-btn" data-ws-open="images"><?php esc_html_e('Images', 'winshirt'); ?></button>
      <button class="ws-btn" data-ws-open="text"><?php esc_html_e('Texte', 'winshirt'); ?></button>
      <button class="ws-btn" data-ws-open="layers"><?php esc_html_e('Calques', 'winshirt'); ?></button>
      <button class="ws-btn" data-ws-open="qr"><?php esc_html_e('QR Code', 'winshirt'); ?></button>
    </nav>
  </aside>

  <!-- CANVAS CENTRE -->
  <main class="winshirt-mockup-area" aria-live="polite">
    <div id="winshirt-canvas" class="winshirt-mockup-canvas" role="img" aria-label="<?php esc_attr_e('Aperçu du mockup', 'winshirt'); ?>"></div>

    <div class="ws-side-switch" aria-label="<?php esc_attr_e('Côté du produit', 'winshirt'); ?>">
      <button class="button" data-ws-side="front"><?php esc_html_e('Recto', 'winshirt'); ?></button>
      <button class="button" data-ws-side="back"><?php esc_html_e('Verso', 'winshirt'); ?></button>
    </div>

    <div class="ws-actions">
      <button class="button" data-ws-save><?php esc_html_e('Enregistrer le design', 'winshirt'); ?></button>
      <button class="button" data-ws-add-to-cart><?php esc_html_e('Ajouter au panier', 'winshirt'); ?></button>
    </div>
  </main>

  <!-- PANNEAUX DROITE -->
  <aside id="winshirt-panel-root">
    <div class="ws-panels">

      <!-- IMAGES -->
      <section class="ws-panel" data-panel="images" aria-hidden="true">
        <header class="ws-panel-head">
          <span class="title"><?php esc_html_e('Images', 'winshirt'); ?></span>
          <button class="ws-back" data-ws-close>&larr; <?php esc_html_e('Retour', 'winshirt'); ?></button>
        </header>

        <div class="ws-gallery">
          <?php
          /**
           * Priorité aux hooks (ex: ta classe Images).
           */
          do_action( 'winshirt_panel_images_content' );

          /**
           * Fallback : si aucun hook n’a produit de contenu, on liste les CPT ws-design.
           */
          global $wp_filter;
          $had_hook = ! empty( $wp_filter['winshirt_panel_images_content'] );
          if ( ! $had_hook ) :
            $q = new WP_Query([
              'post_type'      => 'ws-design',
              'post_status'    => 'publish',
              'posts_per_page' => 12,
              'no_found_rows'  => true,
            ]);
            if ( $q->have_posts() ) :
              while ( $q->have_posts() ) : $q->the_post();
                $src = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
                if ( ! $src ) continue;
                ?>
                <div class="ws-thumb"><img src="<?php echo esc_url( $src ); ?>" alt=""></div>
                <?php
              endwhile;
              wp_reset_postdata();
            else :
              echo '<p style="opacity:.6">'.esc_html__('Aucun visuel pour le moment.','winshirt').'</p>';
            endif;
          endif;
          ?>
        </div>
      </section>

      <!-- TEXTE -->
      <section class="ws-panel" data-panel="text" aria-hidden="true">
        <header class="ws-panel-head">
          <span class="title"><?php esc_html_e('Texte', 'winshirt'); ?></span>
          <button class="ws-back" data-ws-close>&larr; <?php esc_html_e('Retour', 'winshirt'); ?></button>
        </header>
        <form class="ws-text-form" onsubmit="return false" style="display:grid;gap:8px;">
          <label><?php esc_html_e('Texte', 'winshirt'); ?>
            <input type="text" class="ws-input-text" value="Winshirt"></label>
          <label><?php esc_html_e('Taille', 'winshirt'); ?>
            <input type="number" class="ws-input-size" value="32" min="10" max="160"></label>
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
        <div class="ws-layers-list"><?php do_action( 'winshirt_panel_layers_content' ); ?></div>
      </section>

      <!-- QR -->
      <section class="ws-panel" data-panel="qr" aria-hidden="true">
        <header class="ws-panel-head">
          <span class="title"><?php esc_html_e('QR Code', 'winshirt'); ?></span>
          <button class="ws-back" data-ws-close>&larr; <?php esc_html_e('Retour', 'winshirt'); ?></button>
        </header>
        <div class="ws-qr-form" style="display:grid;gap:8px;">
          <label>URL <input type="url" class="ws-qr-url" placeholder="https://…"></label>
          <button class="button" data-ws-add-qr><?php esc_html_e('Ajouter un QR', 'winshirt'); ?></button>
        </div>
      </section>

    </div>
  </aside>

  <!-- BARRE MOBILE -->
  <div class="ws-mobile-bar" role="tablist" aria-label="<?php esc_attr_e('Outils', 'winshirt'); ?>">
    <button class="ws-btn" data-ws-open="images"><?php esc_html_e('Images', 'winshirt'); ?></button>
    <button class="ws-btn" data-ws-open="text"><?php esc_html_e('Texte', 'winshirt'); ?></button>
    <button class="ws-btn" data-ws-open="layers"><?php esc_html_e('Calques', 'winshirt'); ?></button>
    <button class="ws-btn" data-ws-open="qr"><?php esc_html_e('QR Code', 'winshirt'); ?></button>
  </div>

</div>

<script>
(function(){
  function qsa(s,c){return Array.from((c||document).querySelectorAll(s))}
  function qs(s,c){return (c||document).querySelector(s)}
  function openPanel(name){
    qsa('[data-ws-open]').forEach(b=> b.classList.toggle('is-active', b.getAttribute('data-ws-open')===name));
    qsa('.ws-panel').forEach(p=>{
      const ok = p.getAttribute('data-panel')===name;
      p.classList.toggle('is-open', ok);
      p.setAttribute('aria-hidden', ok ? 'false' : 'true');
    });
    const root = qs('#winshirt-panel-root'); if(root){ root.setAttribute('data-active-level','1'); root.setAttribute('data-active', name); }
  }

  // Nav desktop & mobile
  qsa('[data-ws-open]').forEach(b=> b.addEventListener('click', e=>{e.preventDefault(); openPanel(b.getAttribute('data-ws-open'));}));
  // Retour => revient sur Images
  qsa('[data-ws-close]').forEach(b=> b.addEventListener('click', e=>{e.preventDefault(); openPanel('images');}));
  // Par défaut : Images
  openPanel('images');

  // Fallback MOCKUP : si WinShirtCanvas pas prêt, on affiche les images front/back depuis WinShirtData
  try{
    const data = window.WinShirtData || {};
    const mocks = (data.mockups && data.mockups.length ? data.mockups : []);
    if(mocks.length && !qs('#winshirt-canvas img.winshirt-mockup-img')){
      const m = mocks[0]; // fallback: premier mockup dispo
      const front = m.front || (m.images && m.images.front) || '';
      const back  = m.back  || (m.images && m.images.back ) || '';
      const wrap = qs('#winshirt-canvas');
      if(wrap && front){
        const f = new Image(); f.src = front; f.alt = 'Mockup Recto';
        f.className = 'winshirt-mockup-img'; f.setAttribute('data-side','front');
        f.style.cssText = 'position:absolute;inset:0;margin:auto;max-width:100%;max-height:100%;object-fit:contain;display:block;';
        wrap.appendChild(f);
      }
      if(wrap && back){
        const b = new Image(); b.src = back; b.alt = 'Mockup Verso';
        b.className = 'winshirt-mockup-img'; b.setAttribute('data-side','back');
        b.style.cssText = 'position:absolute;inset:0;margin:auto;max-width:100%;max-height:100%;object-fit:contain;display:none;';
        wrap.appendChild(b);
      }
      // Bascule recto/verso
      qsa('[data-ws-side]').forEach(btn=>{
        btn.addEventListener('click', function(e){
          e.preventDefault();
          const side = this.getAttribute('data-ws-side')==='back' ? 'back' : 'front';
          qsa('#winshirt-canvas img.winshirt-mockup-img').forEach(img=>{
            img.style.display = (img.getAttribute('data-side')===side ? 'block' : 'none');
          });
          document.dispatchEvent(new CustomEvent('winshirt:sideChanged',{detail:{side}}));
        });
      });
    }
  }catch(e){}

  // Galerie : clic → ajoute image si API présente
  qsa('.ws-gallery img').forEach(img=>{
    img.addEventListener('dragstart', e=> e.preventDefault());
    img.addEventListener('mousedown', e=> e.preventDefault());
    img.addEventListener('click', function(e){
      e.preventDefault();
      const url = this.getAttribute('src');
      if(window.WinShirtLayers && typeof WinShirtLayers.addImage==='function'){
        WinShirtLayers.addImage(url);
      }
    });
  });

  // Ajout texte
  const addTextBtn = qs('[data-ws-add-text]');
  if(addTextBtn){
    addTextBtn.addEventListener('click', function(e){
      e.preventDefault();
      const root = this.closest('.ws-panel');
      const txt  = qs('.ws-input-text',root)?.value || 'Votre texte';
      const size = parseInt(qs('.ws-input-size',root)?.value||'32',10);
      const bold = !!qs('.ws-input-bold',root)?.checked;
      const italic = !!qs('.ws-input-italic',root)?.checked;
      if(window.WinShirtLayers && typeof WinShirtLayers.addText==='function'){
        WinShirtLayers.addText({text:txt,size:size,bold:bold,italic:italic});
      }
    });
  }
})();
</script>
