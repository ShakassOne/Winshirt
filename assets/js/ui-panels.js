(function($){
  'use strict';

  const UI = {
    panels: ['images','text','layers','qr'],
    booted: false,

    initOnce(){
      if(this.booted) return;
      this.booted = true;

      // Empêche drag natif vignettes
      $(document).on('dragstart', '.ws-gallery img, .ws-gallery .ws-thumb img', e => e.preventDefault())
                 .on('mousedown', '.ws-gallery img, .ws-gallery .ws-thumb img', e => e.preventDefault());

      // Desktop & mobile
      $(document).on('click', '[data-ws-open]', (e)=>{
        e.preventDefault(); this.open($(e.currentTarget).data('ws-open'));
      });
      $(document).on('click', '.ws-panel-head [data-ws-close]', (e)=>{
        e.preventDefault(); this.close();
      });
    },

    mount(){
      this.initOnce();
      // Ouvre Images par défaut si un root est présent
      if($('#winshirt-panel-root').length){ this.open('images'); }
    },

    open(name){
      if(this.panels.indexOf(name) === -1) return;
      $('[data-ws-open]').removeClass('is-active');
      $(`[data-ws-open="${name}"]`).addClass('is-active');

      $('.ws-panel').removeClass('is-open').attr('aria-hidden','true');
      $(`.ws-panel[data-panel="${name}"]`).addClass('is-open').attr('aria-hidden','false');

      $('#winshirt-panel-root').attr('data-active-level','1').attr('data-active', name);
    },
    close(){
      $('.ws-panel').removeClass('is-open').attr('aria-hidden','true');
      $('[data-ws-open]').removeClass('is-active');
      $('#winshirt-panel-root').attr('data-active-level','0').removeAttr('data-active');
    }
  };

  $(function(){ UI.mount(); });
  document.addEventListener('winshirt:mounted', ()=> UI.mount());

  window.WinShirtUI = UI;
})(jQuery);
