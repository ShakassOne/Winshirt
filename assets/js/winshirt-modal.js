/**
 * WinShirt - Modal (glue légère)
 * - Ouvre/ferme le customizer
 * - Initialise Layers sur le canvas
 * - Déclenche l’ouverture du menu principal (router déjà prêt)
 * - Évite doublons de roots / écouteurs
 *
 * Dépendances : jQuery, WinShirtState, WinShirtUIRouter, WinShirtLayers
 */
(function($){
  'use strict';

  const Modal = {
    booted: false,

    open(){
      const $modal = $('#winshirt-customizer-modal');
      if(!$modal.length){ console.warn('WinShirt modal introuvable.'); return; }

      // Affiche le modal (adapte si tu as déjà un système d’overlay)
      $modal.addClass('is-open').show();

      // Init unique
      if(!this.booted){
        this.boot();
      }

      // Ouvre le menu principal (router) proprement
      $(document).trigger('winshirt:openMainMenu');
    },

    close(){
      $('#winshirt-customizer-modal').removeClass('is-open').hide();
    },

    boot(){
      if(this.booted) return;
      this.booted = true;

      // Init Layers sur le canvas
      const $canvas = $('.winshirt-mockup-canvas');
      if($canvas.length && window.WinShirtLayers){
        WinShirtLayers.init($canvas);
      }

      // Sélecteurs par défaut (tu peux binder tes propres boutons avec data-ws-open/close)
      $(document).on('click', '[data-ws-open-customizer]', (e)=>{
        e.preventDefault(); this.open();
      });
      $(document).on('click', '[data-ws-close-customizer]', (e)=>{
        e.preventDefault(); this.close();
      });

      // Sécurité : empêcher double-root
      const $roots = $('#winshirt-panel-root');
      if($roots.length > 1){
        // on garde le premier, on supprime les suivants
        $roots.slice(1).remove();
      }

      // À l’ouverture du template, recalcul des zones (au cas où)
      $(document).on('winshirt:templateReady', ()=>{
        $(window).trigger('resize');
      });
    }
  };

  // Expose (si besoin)
  window.WinShirtModal = Modal;

  // Auto-bind : si un bouton “Personnaliser” existe déjà
  $(function(){
    // Par convention : un bouton avec data-ws-open-customizer
    // Sinon, laisse l’intégrateur binder manuellement.
    // Exemple : $('body').on('click', '.btn-personnaliser', ()=>WinShirtModal.open());
  });

})(jQuery);
