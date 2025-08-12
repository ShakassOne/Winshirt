/**
 * WinShirt - UI Panels
 * - Gère la navigation latérale (desktop) et la barre bas (mobile)
 * - Ouvre/ferme les panneaux (Images, Texte, Calques, QR)
 * - Empêche le drag natif des vignettes
 */
(function($){
  'use strict';

  const UI = {
    panels: ['images','text','layers','qr'],
    current: null,

    init(){
      // Empêche le drag natif sur toutes vignettes galerie
      $(document).on('dragstart', '.ws-gallery img, .ws-gallery .ws-thumb img', e => e.preventDefault())
                 .on('mousedown', '.ws-gallery img, .ws-gallery .ws-thumb img', e => e.preventDefault());

      // Desktop: clic sur la nav de gauche
      $(document).on('click', '[data-ws-open]', (e)=>{
        e.preventDefault();
        this.open($(e.currentTarget).data('ws-open'));
      });

      // Mobile: barre bas
      $(document).on('click', '.ws-mobile-bar [data-ws-open]', (e)=>{
        e.preventDefault();
        this.open($(e.currentTarget).data('ws-open'));
        $('html,body').animate({ scrollTop: $(document).height() }, 150);
      });

      // Bouton retour (mobile)
      $(document).on('click', '.ws-panel-head [data-ws-close]', (e)=>{
        e.preventDefault();
        this.close();
      });

      // Boot: ouvre le premier panneau dispo (images)
      this.open('images');
    },

    open(name){
      if(this.panels.indexOf(name) === -1) return;
      this.current = name;

      // Active la nav
      $('[data-ws-open]').removeClass('is-active');
      $(`[data-ws-open="${name}"]`).addClass('is-active');

      // Panneaux
      $('.ws-panel').removeClass('is-open').attr('aria-hidden','true');
      $(`.ws-panel[data-panel="${name}"]`).addClass('is-open').attr('aria-hidden','false');

      // Marqueur de l’item ouvert (utile pour CSS)
      $('#winshirt-panel-root').attr('data-active-level', '1').attr('data-active', name);
    },

    close(){
      this.current = null;
      $('.ws-panel').removeClass('is-open').attr('aria-hidden','true');
      $('[data-ws-open]').removeClass('is-active');
      $('#winshirt-panel-root').attr('data-active-level', '0').removeAttr('data-active');
    }
  };

  $(function(){ UI.init(); });

})(jQuery);
