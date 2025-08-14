(function($){
  'use strict';

  const Canvas = {
    el: null,
    imgFront: null,
    imgBack: null,
    side: 'front',
    zones: { front: [], back: [] },

    boot(){
      this.el = $('#winshirt-canvas');
      if(!this.el.length || !window.WinShirtData) return;

      this.imgFront = this.el.find('.winshirt-mockup-img[data-side="front"]');
      this.imgBack  = this.el.find('.winshirt-mockup-img[data-side="back"]');

      const mock = WinShirtData.mockups || {};
      if(mock.front) this.imgFront.attr('src', mock.front);
      if(mock.back)  this.imgBack.attr('src',  mock.back);

      const z = WinShirtData.zones || {};
      this.zones.front = Array.isArray(z.front) ? z.front : [];
      this.zones.back  = Array.isArray(z.back)  ? z.back  : [];

      this.setSide('front');
      this.bind();
    },

    bind(){
      $(document).on('winshirt:side', (e, side)=> this.setSide(side));
      // réagir au resize pour recalculer positions en px
      $(window).on('resize.ws', ()=> this.renderZones() );
    },

    setSide(side){
      this.side = (side==='back') ? 'back' : 'front';
      this.imgFront.toggle(this.side==='front');
      this.imgBack.toggle(this.side==='back');
      this.renderZones();
      $(document).trigger('winshirt:canvas:side', [this.side]);
    },

    canvasRect(){
      return this.el[0].getBoundingClientRect();
    },

    pctToPx(p){ // {left,top,width,height} → px rect
      const r = this.canvasRect();
      return {
        x: p.left/100 * r.width,
        y: p.top/100  * r.height,
        w: p.width/100* r.width,
        h: p.height/100* r.height
      };
    },

    renderZones(){
      this.el.find('.ws-print-zone').remove();
      const list = this.zones[this.side] || [];
      list.forEach((z,i)=>{
        const R = this.pctToPx(z);
        const $z = $('<div class="ws-print-zone" />')
          .attr('data-index', i)
          .attr('data-side', this.side)
          .css({ left:R.x, top:R.y, width:R.w, height:R.h });
        this.el.append($z);
      });

      const has = list.length>0;
      $('.ws-zone-hint').text( has ? '' : 'Aucune zone définie pour ce côté.' );
      $(document).trigger('winshirt:zones:rendered', [this.side, list]);
    },

    // util pour layer-manager
    activeZoneRect(){
      // prend la 1ère zone pour MVP (ou celle “sélectionnée” si on ajoute la sélection)
      const z = (this.zones[this.side]||[])[0];
      if(!z) return null;
      return this.pctToPx(z);
    }
  };

  $(function(){ Canvas.boot(); window.WinShirtCanvas = Canvas; });

})(jQuery);
