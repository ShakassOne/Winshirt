(function($){
  'use strict';

  const Modal = {
    $m: null,
    open(){
      if(!this.$m) this.$m = $('#winshirt-customizer-modal');
      if(!this.$m.length) return;
      this.$m.attr('aria-hidden','false').addClass('is-open');
      $('body').addClass('ws-modal-open');
      // initial focus
      this.$m.find('.ws-tab[data-panel="images"]').trigger('click');
      // côté par défaut
      $(document).trigger('winshirt:side','front');
    },
    close(){
      if(!this.$m) return;
      this.$m.attr('aria-hidden','true').removeClass('is-open');
      $('body').removeClass('ws-modal-open');
    },
    boot(){
      this.$m = $('#winshirt-customizer-modal');
      if(!this.$m.length) return;

      // prevent overlay closing when interacting inside dialog
      this.$m.on('click', '.winshirt-dialog', function(e){ e.stopPropagation(); });

      this.$m.on('click', '[data-close]', (e)=> { e.preventDefault(); this.close(); });
      this.$m.on('click', (e)=> { if($(e.target).is('.winshirt-overlay')) this.close(); });

      // switch side
      this.$m.on('click','.ws-side', function(){
        const side = $(this).data('side');
        $('.ws-side').removeClass('is-active');
        $(this).addClass('is-active');
        $(document).trigger('winshirt:side', side);
      });

      // save / cart
      this.$m.on('click','.ws-save', ()=>{
        if(!window.WinShirtLayers) return;
        const json = WinShirtLayers.exportJSON();
        console.log('Design JSON', json);
        alert('Design enregistré localement (console). Ajout panier à venir.');
      });

      this.$m.on('click','.ws-cart', ()=>{
        if(!window.WinShirtLayers) return;
        const json = WinShirtLayers.exportJSON();
        // MVP : poster via un input caché et soumettre un mini-form
        const $form = $('<form method="post"></form>').attr('action', window.location.href);
        $form.append(`<input type="hidden" name="add-to-cart" value="${(WinShirtData.product||{}).id||0}">`);
        $form.append(`<input type="hidden" name="winshirt_design" value="${ $('<div>').text(json).html() }">`);
        $('body').append($form); $form.trigger('submit');
      });

      // open event
      $(document).on('winshirt:open', ()=> this.open() );
    }
  };

  $(function(){ Modal.boot(); });

})(jQuery);
