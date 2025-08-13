/* ===== WinShirt – Contrôleur de modal (ouverture/fermeture + auto-squelette) ===== */
(function($){
  'use strict';

  // Tous les sélecteurs possibles du bouton "Personnaliser"
  const OPENERS = [
    '[data-winshirt-open]',
    '#winshirt-open',
    '.ws-open-customizer',
    '.winshirt-open',
    '.button-personnaliser',
    '.button.winshirt-open',
    '.winshirt-btn-open'
  ];

  const M = {
    $modal: null, $dialog: null,

    ensureSkeleton(){
      // Si le template n'est pas présent, on injecte un squelette minimal fonctionnel
      if ($('.winshirt-customizer-modal').length) return;

      const html = `
      <div class="winshirt-customizer-modal" aria-hidden="true" style="display:none">
        <div class="winshirt-customizer-dialog" role="dialog" aria-modal="true">
          <div class="ws-head">
            <button type="button" class="ws-close" aria-label="Fermer">Fermer</button>
          </div>
          <div class="winshirt-customizer-body">
            <aside class="ws-l1">
              <button class="ws-nav-btn is-active" data-panel="images">Images</button>
              <button class="ws-nav-btn" data-panel="text">Texte</button>
              <button class="ws-nav-btn" data-panel="layers">Calques</button>
              <button class="ws-nav-btn" data-panel="qr">QR Code</button>
            </aside>
            <main id="winshirt-mockup-area">
              <div id="winshirt-canvas" style="position:relative; width:min(800px,90%); aspect-ratio:1/1; margin:24px auto"></div>
            </main>
            <aside class="ws-l2">
              <div class="ws-l2-title">Images</div>
              <button class="ws-back" type="button">← Retour</button>
              <div class="ws-l2-body"></div>
            </aside>
          </div>
          <div class="ws-footer">
            <button class="ws-pill js-ws-side is-active" data-side="front">Recto</button>
            <button class="ws-pill js-ws-side" data-side="back">Verso</button>
            <span style="flex:1"></span>
            <button class="ws-cta js-ws-save">Enregistrer le design</button>
            <button class="ws-cta js-ws-addcart">Ajouter au panier</button>
          </div>
        </div>
      </div>`;
      document.body.insertAdjacentHTML('beforeend', html);
    },

    mount(){
      this.ensureSkeleton();
      this.$modal  = $('.winshirt-customizer-modal');
      this.$dialog = this.$modal.find('.winshirt-customizer-dialog');

      if(!this.$modal.length){ console.warn('WinShirt modal: HTML introuvable'); return; }

      // Overlay → fermer
      this.$modal.off('click.ws').on('click.ws', (e)=>{ if(e.target === e.currentTarget){ this.close(); } });
      // Bloquer la propagation dans la boîte
      this.$dialog.off('click.ws').on('click.ws', (e)=> e.stopPropagation());
      // Bouton fermer
      this.$modal.off('click.wsClose').on('click.wsClose', '.ws-close', ()=> this.close());
      // ESC
      $(document).off('keydown.wsModal').on('keydown.wsModal', (e)=>{ if(e.key === 'Escape') this.close(); });
    },

    open(){
      if(!this.$modal){ this.mount(); }
      $('html,body').addClass('ws-modal-open');
      this.$modal.addClass('is-open').show().attr('aria-hidden','false');
      $(document).trigger('winshirt:modal:open');
      // Première peinture du mockup/zones
      if (window.WinShirtCanvas && typeof WinShirtCanvas.render === 'function') {
        WinShirtCanvas.render();
      }
    },

    close(){
      if(!this.$modal) return;
      this.$modal.removeClass('is-open').hide().attr('aria-hidden','true');
      $('html,body').removeClass('ws-modal-open');
      $(document).trigger('winshirt:modal:close');
    }
  };

  // Délégation sur tous les sélecteurs openers
  const openerSelector = OPENERS.join(',');
  $(document).off('click.wsOpen').on('click.wsOpen', openerSelector, function(e){
    e.preventDefault();
    e.stopPropagation();
    M.open();
  });

  // Exposer pour debug éventuel
  window.WinShirtModal = M;

  // Auto-mount au chargement
  if (document.readyState !== 'loading') M.mount();
  else document.addEventListener('DOMContentLoaded', ()=> M.mount());

})(jQuery);
