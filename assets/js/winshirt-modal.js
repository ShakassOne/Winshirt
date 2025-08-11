/**
 * WinShirt - Modal (glue légère)
 * - Ouvre/ferme le customizer
 * - Initialise Layers sur le canvas
 * - Déclenche l’ouverture du menu principal
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

      // Affiche le modal (overlay)
      $modal.addClass('is-open').show();

      if(!this.booted){
        this.boot();
      }

      // Ouvre le menu principal
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

      // Sécurité : un seul root panels
      const $roots = $('#winshirt-panel-root');
      if($roots.length > 1){
        $roots.slice(1).remove();
      }

      // Recalcul zones après rendu template
      $(document).on('winshirt:templateReady', ()=>{
        $(window).trigger('resize');
      });
    }
  };

  // Expose si besoin
  window.WinShirtModal = Modal;

  // ❗️Bind du bouton dès le ready (corrige le bug)
  $(function(){
    $(document).on('click', '[data-ws-open-customizer]', function(e){
      e.preventDefault();
      Modal.open();
    });
    $(document).on('click', '[data-ws-close-customizer]', function(e){
      e.preventDefault();
      Modal.close();
    });
  });

})(jQuery);
