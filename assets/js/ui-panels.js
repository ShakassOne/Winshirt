/**
 * WinShirt - UI Panels (L1/L2/L3)
 * - Construit le menu principal (Images, Texte, Calques, QR, IA)
 * - Gère la navigation entre niveaux
 * - Émet des événements pour que les outils branchent leur UI
 *
 * Events émis :
 *  - winshirt:panel:images (ou :text / :layers / :qr / :ai)  -> {root, l1, l2, l3}
 */

(function($){
  'use strict';

  const Panels = {
    $root: null,
    $l1: null, $l2: null, $l3: null,

    init(){
      this.$root = $('#winshirt-panel-root');
      if(!this.$root.length) return;

      this.$l1 = this.$root.find('.ws-panel-l1');
      this.$l2 = this.$root.find('.ws-panel-l2');
      this.$l3 = this.$root.find('.ws-panel-l3');

      this.renderMainMenu();
      this.activateLevel(1);

      // Back generic
      $(document).on('click', '.ws-back', (e)=>{
        e.preventDefault();
        this.goBack();
      });
    },

    activateLevel(level){
      this.$root.attr('data-active-level', String(level));
      this.$l1.attr('aria-hidden', level!==1);
      this.$l2.attr('aria-hidden', level!==2);
      this.$l3.attr('aria-hidden', level!==3);
    },

    goBack(){
      const lvl = Number(this.$root.attr('data-active-level')||1);
      if(lvl>1) this.activateLevel(lvl-1);
    },

    header(title){
      return `
        <div class="ws-panel-header" style="display:flex;align-items:center;gap:8px;padding:10px;border-bottom:1px solid rgba(0,0,0,.06)">
          <button class="ws-back" aria-label="Retour" style="background:none;border:0;cursor:pointer;font-size:18px">←</button>
          <div style="font-weight:700">${title}</div>
        </div>`;
    },

    renderMainMenu(){
      const html = `
        <div class="ws-mainmenu" style="padding:10px">
          <div class="ws-menu-item" data-section="images" style="padding:10px;cursor:pointer;display:flex;align-items:center;gap:8px">📷 <b>Images</b></div>
          <div class="ws-menu-item" data-section="text"   style="padding:10px;cursor:pointer;display:flex;align-items:center;gap:8px">🔤 <b>Texte</b></div>
          <div class="ws-menu-item" data-section="layers" style="padding:10px;cursor:pointer;display:flex;align-items:center;gap:8px">📚 <b>Calques</b></div>
          <div class="ws-menu-item" data-section="qr"     style="padding:10px;cursor:pointer;display:flex;align-items:center;gap:8px">▦ <b>QR Code</b></div>
          <div class="ws-menu-item" data-section="ai"     style="padding:10px;cursor:pointer;display:flex;align-items:center;gap:8px">🤖 <b>IA</b></div>
        </div>`;
      this.$l1.html(html);

      // Click handlers
      this.$l1.off('click.ws').on('click.ws', '.ws-menu-item', (e)=>{
        const section = $(e.currentTarget).data('section');
        this.openSection(section);
      });

      // signal router ready
      $(document).trigger('winshirt:routerReady', [this]);
    },

    openSection(section){
      const map = {
        images: 'Images',
        text:   'Texte',
        layers: 'Calques',
        qr:     'QR Code',
        ai:     'IA'
      };
      const title = map[section] || 'Outils';

      this.$l2.html(this.header(title) + `<div class="ws-l2-body" style="padding:10px"></div>`);
      this.$l3.html('').attr('aria-hidden', true);

      this.activateLevel(2);

      // évènement pour brancher l’outil correspondant
      $(document).trigger(`winshirt:panel:${section}`, [{
        root: this.$root, l1: this.$l1, l2: this.$l2, l3: this.$l3
      }]);
    }
  };

  window.WinShirtUIPanels = Panels;

  $(function(){ Panels.init(); });

})(jQuery);
