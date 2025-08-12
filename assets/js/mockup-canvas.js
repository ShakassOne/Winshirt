/**
 * WinShirt - Mockup Canvas
 * - Affiche les mockups recto/verso
 * - Calcule la taille affichée et positionne les zones (xPct,yPct,wPct,hPct)
 * - Bascule recto/verso
 * - Expose une API pour Layers: getZoneRect(side)
 */
(function($){
  'use strict';

  const Canvas = {
    $area: null,     // .winshirt-mockup-area
    $canvas: null,   // #winshirt-canvas.winshirt-mockup-canvas
    $imgF: null,     // <img data-side="front">
    $imgB: null,     // <img data-side="back">
    zones: null,     // WinShirtData.zones
    side:  'front',

    init(){
      this.$area   = $('.winshirt-mockup-area');
      this.$canvas = $('#winshirt-canvas.winshirt-mockup-canvas');
      if(!this.$area.length || !this.$canvas.length) return;

      this.zones = (window.WinShirtData && WinShirtData.zones) || { front:[], back:[] };
      const mockups = (window.WinShirtData && WinShirtData.mockups) || { front:'', back:'' };
      this.side = (window.WinShirtData && WinShirtData.state && WinShirtData.state.side) || 'front';

      // Assure positionnement relatif du canvas
      this.$canvas.css({ position:'relative', overflow:'visible' });

      // Installe/Met à jour les deux <img>
      this.ensureImages(mockups);

      // Rendu initial
      this.renderAll();

      // Bascule recto/verso
      $(document).on('click', '[data-ws-side]', (e)=>{
        e.preventDefault();
        const side = $(e.currentTarget).data('ws-side');
        this.switchSide(side === 'back' ? 'back' : 'front');
      });

      // Resize → recalcul
      let tid=null;
      $(window).on('resize', ()=>{
        clearTimeout(tid);
        tid = setTimeout(()=> this.renderAll(), 100);
      });
    },

    ensureImages(mockups){
      // Crée les <img> si absents
      this.$imgF = this.$canvas.find('img.winshirt-mockup-img[data-side="front"]');
      this.$imgB = this.$canvas.find('img.winshirt-mockup-img[data-side="back"]');

      if(!this.$imgF.length){
        this.$imgF = $('<img>',{
          class:'winshirt-mockup-img',
          'data-side':'front',
          alt:'Mockup Recto'
        }).appendTo(this.$canvas);
      }
      if(!this.$imgB.length){
        this.$imgB = $('<img>',{
          class:'winshirt-mockup-img',
          'data-side':'back',
          alt:'Mockup Verso'
        }).appendTo(this.$canvas);
      }

      // Style d’affichage (absolu centré, object-fit contain)
      this.$canvas.find('img.winshirt-mockup-img').css({
        position:'absolute',
        inset:0,
        margin:'auto',
        maxWidth:'100%',
        maxHeight:'100%',
        objectFit:'contain',
        display:'block'
      });

      // Source
      if(mockups.front) this.$imgF.attr('src', mockups.front);
      if(mockups.back)  this.$imgB.attr('src',  mockups.back);

      // Visibilité côté courant
      this.$imgF.toggle(this.side === 'front');
      this.$imgB.toggle(this.side === 'back');

      // Quand une image charge → recalcule
      this.$imgF.on('load', ()=> this.renderAll());
      this.$imgB.on('load', ()=> this.renderAll());
    },

    // Dimensions affichées de l'image active (boîte de content)
    activeImageBox(){
      const $img = (this.side==='back') ? this.$imgB : this.$imgF;
      if(!$img || !$img.length) return null;

      // Taille affichée (après object-fit)
      const cw = this.$canvas.innerWidth();
      const ch = this.$canvas.innerHeight();

      // Si le canvas n’a pas encore de taille, on lui donne une base (responsive)
      if(cw < 50 || ch < 50){
        // Essaie d’occuper 70vh sans dépasser la largeur dispo du conteneur
        const $container = this.$area.length ? this.$area : this.$canvas.parent();
        const maxW = Math.max(300, Math.min($container.innerWidth() - 40, 1000));
        const maxH = Math.max(300, Math.min($(window).height()*0.7, 900));
        this.$canvas.css({ width:maxW+'px', height:maxH+'px' });
      }

      const off = this.$canvas.position();
      const pos = this.$canvas.offset();
      const rect = {
        left: pos.left,
        top:  pos.top,
        width: this.$canvas.innerWidth(),
        height:this.$canvas.innerHeight()
      };
      return rect;
    },

    // Retourne le rect PIXELS d’une zone (dans le repère du canvas)
    getZoneRect(side){
      const box = this.activeImageBox();
      if(!box) return null;
      const arr = (this.zones && this.zones[side]) || [];
      const z   = arr[0]; // une zone par défaut
      if(!z) return null;

      const x = Math.round(box.width  * (z.xPct/100));
      const y = Math.round(box.height * (z.yPct/100));
      const w = Math.round(box.width  * (z.wPct/100));
      const h = Math.round(box.height * (z.hPct/100));
      return { left:x, top:y, width:w, height:h };
    },

    // Installe/positionne les .ws-print-zone
    renderZones(){
      const sides = ['front','back'];
      for(const s of sides){
        const rect = this.getZoneRect(s);
        let $z = this.$canvas.find(`.ws-print-zone[data-side="${s}"]`);
        if(!rect){
          $z.hide();
          continue;
        }
        if(!$z.length){
          $z = $(`<div class="ws-print-zone" data-side="${s}"></div>`).appendTo(this.$canvas);
        }
        $z.css({
          position:'absolute',
          border:'1px dashed rgba(0,0,0,.25)',
          pointerEvents:'none',
          left: rect.left + 'px',
          top:  rect.top  + 'px',
          width: rect.width + 'px',
          height:rect.height+ 'px',
          display: (this.side===s) ? 'block' : 'none'
        });
      }
    },

    switchSide(side){
      if(side!=='back') side='front';
      this.side = side;
      this.$imgF.toggle(side==='front');
      this.$imgB.toggle(side==='back');
      this.renderZones();
      $(document).trigger('winshirt:sideChanged', [side]);
    },

    renderAll(){
      // Ajuste le canvas pour remplir au mieux son conteneur (si le template l’a déjà fait, on respecte)
      if(this.$area.length){
        // occupe la largeur dispo de l’aire centrale, mais garde un max raisonnable
        const availW = this.$area.innerWidth();
        const targetW = Math.max(320, Math.min(availW - 24, 1100));
        // hauteur ~ 110% de la largeur (tee-shirt portrait), bornée
        const targetH = Math.round(Math.max(360, Math.min(targetW * 1.1, $(window).height()*0.75)));
        this.$canvas.css({ width: targetW+'px', height: targetH+'px' });
      }

      this.renderZones();
    }
  };

  window.WinShirtCanvas = {
    getZoneRect: (side)=> Canvas.getZoneRect(side || Canvas.side),
    currentSide: ()=> Canvas.side
  };

  $(function(){ Canvas.init(); });

})(jQuery);
