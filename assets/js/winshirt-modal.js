/* ===== WinShirt – Contrôleur de modal (ouverture/fermeture + click traps) ===== */
(function($){
  'use strict';

  const M = {
    $modal: null, $dialog: null,
    open(){
      if(!this.$modal){ this.mount(); }
      $('html,body').addClass('ws-modal-open');
      this.$modal.addClass('is-open');
      $(document).trigger('winshirt:modal:open');
    },
    close(){
      if(!this.$modal) return;
      this.$modal.removeClass('is-open');
      $('html,body').removeClass('ws-modal-open');
      $(document).trigger('winshirt:modal:close');
    },
    mount(){
      // attend que le template HTML soit déjà présent dans la page (templates/modal-customizer.php)
      this.$modal  = $('.winshirt-customizer-modal');
      this.$dialog = this.$modal.find('.winshirt-customizer-dialog');

      if(!this.$modal.length){
        console.warn('WinShirt modal: HTML introuvable (template).');
        return;
      }

      // clic sur overlay => fermer
      this.$modal.on('click', (e)=>{ if(e.target === e.currentTarget){ this.close(); } });
      // bloquer les clics dans la boîte
      this.$dialog.on('click', (e)=> e.stopPropagation());
      // bouton Fermer
      this.$modal.on('click', '.ws-close', ()=> this.close());

      // raccourci clavier ESC
      $(document).on('keydown.wsModal', (e)=>{ if(e.key === 'Escape'){ this.close(); } });
    }
  };

  // Bouton "Personnaliser"
  $(document).on('click', '[data-winshirt-open]', function(e){
    e.preventDefault();
    M.open();
  });

  // exposer pour debug
  window.WinShirtModal = M;

})(jQuery);
