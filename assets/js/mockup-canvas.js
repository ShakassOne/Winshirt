/* ===== WinShirt – Rendu du mockup + zones ===== */
(function($){
  'use strict';

  const C = {
    side: 'front',
    $area: null, $canvas: null,
    data(){ return window.WinShirtData || {}; },

    mount(){
      this.$area   = $('#winshirt-mockup-area');
      this.$canvas = $('#winshirt-canvas');
      if(!this.$area.length || !this.$canvas.length) return;

      // boutons Recto / Verso
      $(document).on('click', '.js-ws-side', (e)=>{
        this.side = $(e.currentTarget).data('side') === 'back' ? 'back' : 'front';
        $('.js-ws-side').removeClass('is-active');
        $(e.currentTarget).addClass('is-active');
        $(document).trigger('winshirt:sideChanged', [this.side]);
        this.render();
      });

      this.render();
    },

    render(){
      const d = this.data();
      const front = (d.mockups && d.mockups.front) || '';
      const back  = (d.mockups && d.mockups.back)  || '';

      // image mockup
      let $imgFront = this.$canvas.find('img[data-side="front"]');
      let $imgBack  = this.$canvas.find('img[data-side="back"]');
      if(!$imgFront.length){
        $imgFront = $('<img class="winshirt-mockup-img" data-side="front" alt="Mockup Recto">').appendTo(this.$canvas);
        $imgBack  = $('<img class="winshirt-mockup-img" data-side="back"  alt="Mockup Verso">').appendTo(this.$canvas);
      }
      $imgFront.attr('src', front || d.assetsUrl+'img/mockup-front.png');
      $imgBack.attr('src',  back  || d.assetsUrl+'img/mockup-back.png');
      $imgFront.toggleClass('is-visible', this.side==='front');
      $imgBack.toggleClass('is-visible',  this.side==='back');

      // zones : reset
      this.$canvas.find('.ws-print-zone').remove();

      const zones = (d.zones && d.zones[this.side]) || [];
      const w = this.$canvas.width();
      const h = this.$canvas.height();

      if(!zones.length){
        $('.ws-zone-empty').remove();
        $('<div class="ws-zone-empty" style="text-align:center;opacity:.6;margin-top:8px">Aucune zone définie pour ce côté.</div>')
          .appendTo(this.$area);
        return;
      } else {
        $('.ws-zone-empty').remove();
      }

      zones.forEach((z, i)=>{
        const $z = $('<div class="ws-print-zone">').attr('data-idx', i);
        // z left/top/width/height sont en %
        const left = (z.left||0)/100 * w;
        const top  = (z.top||0)/100 * h;
        const zw   = (z.width||0)/100 * w;
        const zh   = (z.height||0)/100 * h;

        $z.css({ left, top, width: zw, height: zh });
        if(i===0) $z.addClass('is-active');
        this.$canvas.append($z);
      });
    }
  };

  $(function(){ C.mount(); });
  window.WinShirtCanvas = C;

})(jQuery);
