/**
 * WinShirt - Layers
 * - Calques image/texte : sélection, déplacement, redimensionnement (souris + tactile)
 * - Contrainte à la zone d’impression du côté courant
 * - Empêche le drag natif des images (ghost)
 */
(function($){
  'use strict';

  const Layers = {
    $canvas: null,
    $active: null,
    side() { return (window.WinShirtCanvas && window.WinShirtCanvas.currentSide && window.WinShirtCanvas.currentSide()) || (window.WinShirtState && WinShirtState.currentSide) || 'front'; },

    init($canvas){
      if(!$canvas || !$canvas.length) return;
      this.$canvas = $canvas;

      // Empêche le drag natif des images de calque (ghost)
      this.$canvas.on('dragstart', '.ws-layer img', e => e.preventDefault());
      this.$canvas.on('mousedown', '.ws-layer img', e => e.preventDefault());

      // Sélection / déselection
      $canvas.on('mousedown touchstart', '.ws-layer', (e)=>{ this.select($(e.currentTarget)); });
      $(document).on('mousedown touchstart', (e)=>{
        if(!this.$canvas) return;
        const $t = $(e.target);
        if(!$t.closest('.ws-layer, #winshirt-panel-root, .ws-mobile-bar').length){
          this.deselect();
        }
      });

      this.bindDrag();
      this.bindResize();

      $(document).on('winshirt:sideChanged', (e, side)=>{ this.renderSide(side); });

      this.renderSide(this.side());
    },

    addImage(url){
      if(!this.$canvas || !url) return;
      const side = this.side();
      const zr = (window.WinShirtCanvas && window.WinShirtCanvas.getZoneRect) ? window.WinShirtCanvas.getZoneRect(side) : null;

      let left = 20, top = 20, w = 200;
      if(zr){
        w = Math.max(80, Math.round(zr.width * 0.6));
        left = Math.round(zr.left + (zr.width - w)/2);
        top  = Math.round(zr.top  + (zr.height - (w*0.8))/2);
      }

      const $el = $(`
        <div class="ws-layer ws-type-image" data-side="${side}" data-locked="0" style="left:${left}px; top:${top}px; width:${w}px; height:auto;">
          <img src="${url}" alt="" draggable="false" style="display:block; max-width:100%; height:auto; user-select:none;">
          ${this.handlesHTML()}
        </div>
      `);
      this.$canvas.append($el);
      this.select($el);
      this.clampInside($el);
    },

    addText(opts = {}){
      const side = this.side();
      const zr = (window.WinShirtCanvas && window.WinShirtCanvas.getZoneRect) ? window.WinShirtCanvas.getZoneRect(side) : null;

      const text = (opts.text || 'Votre texte').replace(/</g,'&lt;');
      const size = parseInt(opts.size||32,10);
      const weight = opts.bold ? '700' : '400';
      const fontStyle = opts.italic ? 'italic' : 'normal';

      let left = 20, top = 20;
      if(zr){
        left = Math.round(zr.left + zr.width*0.2);
        top  = Math.round(zr.top  + zr.height*0.2);
      }

      const $el = $(`
        <div class="ws-layer ws-type-text" data-side="${side}" data-locked="0" style="left:${left}px; top:${top}px; min-width:60px;">
          <div class="ws-text" contenteditable="true"
               style="font-size:${size}px; font-weight:${weight}; font-style:${fontStyle}; line-height:1.1; white-space:pre;">
            ${text}
          </div>
          ${this.handlesHTML()}
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
    },

    select($el){
      if(this.$active && this.$active.is($el)) return;
      this.$canvas.find('.ws-layer').removeClass('selected');
      this.$active = $el.addClass('selected');
    },
    deselect(){
      this.$canvas.find('.ws-layer').removeClass('selected');
      this.$active = null;
    },

    zoneRect(side){
      return (window.WinShirtCanvas && window.WinShirtCanvas.getZoneRect) ? window.WinShirtCanvas.getZoneRect(side) : null;
    },

    clampInside($el){
      const side = $el.data('side') || this.side();
      const zr = this.zoneRect(side);
      if(!zr){
        const maxW = this.$canvas.innerWidth();
        const maxH = this.$canvas.innerHeight();
        let left = parseFloat($el.css('left'))||0;
        let top  = parseFloat($el.css('top'))||0;
        let w = $el.outerWidth(), h=$el.outerHeight();
        left = Math.max(0, Math.min(left, maxW - w));
        top  = Math.max(0, Math.min(top,  maxH - h));
        $el.css({ left, top });
        return;
      }

      let left = parseFloat($el.css('left'))||0;
      let top  = parseFloat($el.css('top'))||0;
      let w = $el.outerWidth(), h=$el.outerHeight();

      left = Math.max(zr.left, Math.min(left, zr.left + zr.width  - w));
      top  = Math.max(zr.top,  Math.min(top,  zr.top  + zr.height - h));

      const minW = 30, minH = 24;
      w = Math.max(minW, Math.min(w, zr.width));
      h = Math.max(minH, Math.min(h, zr.height));

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

    bindDrag(){
      const self = this;
      let dragging = false, start = null, $el = null;

      function getPoint(ev){
        if(ev.originalEvent && ev.originalEvent.touches && ev.originalEvent.touches[0]){
          const t = ev.originalEvent.touches[0]; return { x:t.pageX, y:t.pageY };
        }
        return { x: ev.pageX, y: ev.pageY };
      }
      function onMove(ev){
        if(!dragging || !$el) return;
        const p = getPoint(ev);
        const dx = p.x - start.x, dy = p.y - start.y;
        $el.css({ left: start.left + dx, top: start.top + dy });
        self.clampInside($el);
      }
      function onUp(){ dragging=false; $el=null; $(document).off('.wsdrag'); }

      this.$canvas.on('mousedown touchstart', '.ws-layer', function(ev){
        if($(ev.target).closest('.ws-h').length) return; // ignore handles
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
          case 'se': w = start.w + dx; h = isImage ? Math.round(w/aspect) : start.h + dy; break;
          case 'ne': w = start.w + dx; h = isImage ? Math.round(w/aspect) : start.h - dy; top = start.top + (start.h - h); break;
          case 'sw': w = start.w - dx; h = isImage ? Math.round(w/aspect) : start.h + dy; left = start.left + (start.w - w); break;
          case 'nw': w = start.w - dx; h = isImage ? Math.round(w/aspect) : start.h - dy; left = start.left + (start.w - w); top = start.top + (start.h - h); break;
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

  $(function(){
    const $canvas = $('#winshirt-canvas.winshirt-mockup-canvas');
    Layers.init($canvas);
  });

})(jQuery);
