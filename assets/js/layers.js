/**
 * WinShirt - Layers
 * - Gestion des calques : image/texte
 * - Sélection, déplacement, redimensionnement (souris + tactile)
 * - Contrainte à la zone d’impression du côté courant
 * - API publique: init($canvas), addImage(url), addText(opts), renderSide(side)
 */
(function($){
  'use strict';

  const Layers = {
    $canvas: null,
    $active: null,
    side() { return (window.WinShirtState && WinShirtState.currentSide) || 'front'; },

    init($canvas){
      if(!$canvas || !$canvas.length) return;
      this.$canvas = $canvas;

      // Sélection / déselection
      $canvas.on('mousedown touchstart', '.ws-layer', (e)=>{ this.select($(e.currentTarget)); });
      $(document).on('mousedown touchstart', (e)=>{
        // clic en dehors du canvas => deselect
        if(!this.$canvas) return;
        const $t = $(e.target);
        if(!$t.closest('.ws-layer, .ws-panel-l2, .ws-panel-l3').length && !$t.closest('#winshirt-panel-root').length){
          this.deselect();
        }
      });

      // Drag
      this.bindDrag();

      // Resize
      this.bindResize();

      // Switch côté
      $(document).on('winshirt:sideChanged', (e, side)=>{ this.renderSide(side); });

      // Rendre le côté courant
      this.renderSide(this.side());
    },

    // ---------- API publique ----------

    addImage(url){
      if(!this.$canvas || !url) return;
      const side = this.side();
      const $zone = this.zone(side);
      const off = $zone.position();
      const w = Math.max(80, $zone.width()*0.45);

      const $el = $(`
        <div class="ws-layer ws-type-image" data-side="${side}" data-locked="0" style="left:${off.left+10}px; top:${off.top+10}px; width:${w}px; height:auto;">
          <img src="${url}" alt="" style="display:block; max-width:100%; height:auto;">
          ${this.handlesHTML()}
        </div>
      `);
      this.$canvas.append($el);
      this.select($el);
      this.clampInside($el);
    },

    addText(opts = {}){
      if(!this.$canvas) return;
      const side = this.side();
      const $zone = this.zone(side);
      const off = $zone.position();

      const text = (opts.text || 'Votre texte').replace(/</g,'&lt;');
      const size = parseInt(opts.size||32,10);
      const weight = opts.bold ? '700' : '400';
      const fontStyle = opts.italic ? 'italic' : 'normal';

      const $el = $(`
        <div class="ws-layer ws-type-text" data-side="${side}" data-locked="0"
             style="left:${off.left+15}px; top:${off.top+15}px; min-width:60px;">
          <div class="ws-text" contenteditable="true"
               style="font-size:${size}px; font-weight:${weight}; font-style:${fontStyle}; line-height:1.1; white-space:pre;">
            ${text}
          </div>
          ${this.handlesHTML(/* no aspect lock for text */)}
        </div>
      `);
      this.$canvas.append($el);
      this.select($el);
      this.clampInside($el);
    },

    renderSide(side){
      this.$canvas.find('.ws-layer').each(function(){
        const show = $(this).data('side') === side;
        $(this).toggle(show);
      });
      // afficher la bonne zone (mockup-canvas.js s’en occupe déjà, ici on s’aligne)
    },

    // ---------- Internes ----------

    select($el){
      if(this.$active && this.$active.is($el)) return;
      this.$canvas.find('.ws-layer').removeClass('selected');
      this.$active = $el.addClass('selected');
    },
    deselect(){
      if(!this.$canvas) return;
      this.$canvas.find('.ws-layer').removeClass('selected');
      this.$active = null;
    },

    zone(side){
      return this.$canvas.find(`.ws-print-zone[data-side="${side}"]`);
    },

    rects($el){
      const c = this.$canvas.offset();
      const e = $el.offset();
      const z = this.zone($el.data('side') || this.side()).offset();
      return {
        canvas: { left:c.left, top:c.top, width:this.$canvas.innerWidth(), height:this.$canvas.innerHeight() },
        zone:   { left:z.left, top:z.top, width:this.zone(this.side()).outerWidth(), height:this.zone(this.side()).outerHeight() },
        el:     { left:e.left, top:e.top, width:$el.outerWidth(), height:$el.outerHeight() }
      };
    },

    clampInside($el){
      const side = $el.data('side') || this.side();
      const $zone = this.zone(side);
      const zOff = $zone.position();
      const zW = $zone.width(), zH = $zone.height();
      let left = parseFloat($el.css('left')) || 0;
      let top  = parseFloat($el.css('top')) || 0;
      let w    = $el.outerWidth();
      let h    = $el.outerHeight();

      // clamp position
      left = Math.max(zOff.left, Math.min(left, zOff.left + zW - w));
      top  = Math.max(zOff.top,  Math.min(top,  zOff.top  + zH - h));

      // clamp taille (min)
      const minW = 30, minH = 24;
      w = Math.max(minW, Math.min(w, zW));
      h = Math.max(minH, Math.min(h, zH));

      $el.css({ left, top, width:w, height:h });
    },

    handlesHTML(){
      return `
        <div class="ws-handles">
          <i class="ws-h ws-h-nw" data-dir="nw"></i>
          <i class="ws-h ws-h-ne" data-dir="ne"></i>
          <i class="ws-h ws-h-sw" data-dir="sw"></i>
          <i class="ws-h ws-h-se" data-dir="se"></i>
        </div>
      `;
    },

    // ---- Drag logic ----
    bindDrag(){
      const self = this;
      let dragging = false, start = null, $el = null;

      function onMove(ev){
        if(!dragging || !$el) return;
        const p = getPoint(ev);
        const dx = p.x - start.x, dy = p.y - start.y;
        const left = start.left + dx, top = start.top + dy;
        $el.css({ left, top });
        self.clampInside($el);
      }
      function onUp(){ dragging=false; $el=null; $(document).off('.wsdrag'); }

      function getPoint(ev){
        if(ev.originalEvent && ev.originalEvent.touches && ev.originalEvent.touches[0]){
          const t = ev.originalEvent.touches[0]; return { x:t.pageX, y:t.pageY };
        }
        return { x: ev.pageX, y: ev.pageY };
      }

      this.$canvas.on('mousedown touchstart', '.ws-layer', function(ev){
        // ignore si on clique une poignée
        if($(ev.target).closest('.ws-h').length) return;

        dragging = true;
        $el = $(this);
        self.select($el);

        const p = getPoint(ev);
        start = {
          x: p.x, y: p.y,
          left: parseFloat($el.css('left'))||0,
          top:  parseFloat($el.css('top'))||0
        };

        $(document).on('mousemove.wsdrag touchmove.wsdrag', onMove);
        $(document).on('mouseup.wsdrag touchend.wsdrag touchcancel.wsdrag', onUp);
      });
    },

    // ---- Resize logic ----
    bindResize(){
      const self = this;
      let resizing = false, dir=null, start=null, $el=null, aspect=1, isImage=false;

      function getPoint(ev){
        if(ev.originalEvent && ev.originalEvent.touches && ev.originalEvent.touches[0]){
          const t = ev.originalEvent.touches[0]; return { x:t.pageX, y:t.pageY };
        }
        return { x: ev.pageX, y: ev.pageY };
      }

      function onMove(ev){
        if(!resizing || !$el) return;
        const p = getPoint(ev);
        const dx = p.x - start.x;
        const dy = p.y - start.y;

        let left=start.left, top=start.top, w=start.w, h=start.h;

        switch(dir){
          case 'se': w = start.w + dx; h = isImage ? w/aspect : start.h + dy; break;
          case 'ne': w = start.w + dx; h = isImage ? w/aspect : start.h - dy; top = start.top + (start.h - h); break;
          case 'sw': w = start.w - dx; h = isImage ? w/aspect : start.h + dy; left = start.left + (start.w - w); break;
          case 'nw': w = start.w - dx; h = isImage ? w/aspect : start.h - dy; left = start.left + (start.w - w); top = start.top + (start.h - h); break;
        }

        $el.css({ left, top, width:w, height:h });
        self.clampInside($el);
      }
      function onUp(){ resizing=false; $el=null; $(document).off('.wsresize'); }

      this.$canvas.on('mousedown touchstart', '.ws-h', function(ev){
        ev.stopPropagation();
        resizing = true;
        dir = $(this).data('dir');
        $el = $(this).closest('.ws-layer');

        const p = getPoint(ev);
        const w = $el.outerWidth(), h = $el.outerHeight();
        start = {
          x:p.x, y:p.y,
          left: parseFloat($el.css('left'))||0,
          top:  parseFloat($el.css('top'))||0,
          w, h
        };
        isImage = $el.hasClass('ws-type-image');
        aspect  = isImage ? (w/h || 1) : 1;

        $(document).on('mousemove.wsresize touchmove.wsresize', onMove);
        $(document).on('mouseup.wsresize touchend.wsresize touchcancel.wsresize', onUp);
      });
    }
  };

  window.WinShirtLayers = Layers;

})(jQuery);
