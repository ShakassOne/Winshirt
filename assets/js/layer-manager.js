(function($){
  'use strict';

  const Layers = {
    list: [],     // {id,type:'image'|'text', side, x,y,w,h, src|text,font,size,bold,italic, z}
    $root: null,

    boot(){
      this.$root = $('#winshirt-canvas');
      if(!this.$root.length) return;

      this.bindDrag();
      this.refreshListUI();
      $(document).on('winshirt:canvas:side', ()=> this.render() );
    },

    nextId(){ return 'lyr_'+Math.random().toString(36).slice(2,8); },

    addImage(src){
      const side = window.WinShirtCanvas ? WinShirtCanvas.side : 'front';
      const zone = window.WinShirtCanvas ? WinShirtCanvas.activeZoneRect() : null;
      const base = zone ? { x: zone.x+10, y: zone.y+10, w: Math.min( zone.w*0.5, 320), h: 'auto' } : { x:50, y:50, w:240, h:'auto' };
      const item = { id:this.nextId(), type:'image', side, x:base.x, y:base.y, w:base.w, h:base.h, src, z:this.list.length+1 };
      this.list.push(item);
      this.render();
    },

    addText(opts){
      const side = window.WinShirtCanvas ? WinShirtCanvas.side : 'front';
      const zone = window.WinShirtCanvas ? WinShirtCanvas.activeZoneRect() : null;
      const base = zone ? { x: zone.x+20, y: zone.y+20 } : { x:80, y:80 };
      const item = Object.assign({ id:this.nextId(), type:'text', side, x:base.x, y:base.y, text:'Texte', size:32, bold:false, italic:false, z:this.list.length+1 }, opts||{});
      this.list.push(item);
      this.render();
    },

    currentSide(){ return window.WinShirtCanvas ? WinShirtCanvas.side : 'front'; },

    render(){
      const side = this.currentSide();
      // supprimer tout et reposer selon Z ascendant
      this.$root.find('.ws-layer').remove();

      this.list
        .filter(l => l.side===side)
        .sort((a,b)=> a.z-b.z)
        .forEach(l=>{
          const $el = $('<div class="ws-layer" />').attr('data-id', l.id).css({ left:l.x, top:l.y, zIndex:l.z });
          if(l.type==='image'){
            $el.append($('<img/>',{src:l.src, alt:''}).css({ width:l.w, height: l.h==='auto'?'auto':l.h, display:'block' }));
          } else {
            const $t = $('<div class="ws-text"/>').text(l.text).css({
              fontSize: l.size+'px', fontWeight: l.bold?'700':'400', fontStyle: l.italic?'italic':'normal'
            });
            $el.append($t);
          }
          $el.append('<div class="ws-handle ws-h"></div>');
          this.$root.append($el);
        });

      this.refreshListUI();
      $(document).trigger('winshirt:layers:rendered');
    },

    refreshListUI(){
      const $list = $('.ws-layers-list');
      if(!$list.length) return;
      const side = this.currentSide();
      $list.empty();
      this.list
        .filter(l=> l.side===side)
        .sort((a,b)=> a.z-b.z)
        .forEach(l=>{
          const label = (l.type==='image') ? 'Image' : ('Texte: ' + (l.text||'')); 
          const $li = $(`<li data-id="${l.id}"><span>${label}</span> <button class="up">↑</button> <button class="dn">↓</button> <button class="rm">✕</button></li>`);
          $list.append($li);
        });

      $list.off('click.ws');
      $list.on('click.ws','button', (e)=>{
        const $li = $(e.currentTarget).closest('li');
        const id = $li.data('id');
        const L = this.list.find(x=> x.id===id);
        if(!L) return;
        if($(e.currentTarget).hasClass('up')) L.z = Math.max(1, L.z-1);
        if($(e.currentTarget).hasClass('dn')) L.z = L.z+1;
        if($(e.currentTarget).hasClass('rm')) this.list = this.list.filter(x=> x.id!==id);
        this.render();
      });
    },

    bindDrag(){
      let drag = null; // {id, sx,sy, ox,oy, resizing, ow,oh}
      this.$root.on('pointerdown', '.ws-layer', (e)=>{
        if($(e.target).hasClass('ws-h')) {
          const $el = $(e.currentTarget);
          const id  = $el.data('id');
          const L   = this.list.find(x=> x.id===id); if(!L) return;
          drag = { id, resizing:true, sx:e.clientX, sy:e.clientY, ow:$el.outerWidth(), oh:$el.outerHeight() };
          $el[0].setPointerCapture(e.pointerId);
          e.preventDefault(); return;
        }
        const $el = $(e.currentTarget);
        const id  = $el.data('id');
        const off = $el.position();
        drag = { id, sx:e.clientX, sy:e.clientY, ox:off.left, oy:off.top, resizing:false };
        $el[0].setPointerCapture(e.pointerId);
        e.preventDefault();
      });

      this.$root.on('pointermove', (e)=>{
        if(!drag) return;
        const $el = this.$root.find(`.ws-layer[data-id="${drag.id}"]`);
        const L   = this.list.find(x=> x.id===drag.id);
        if(!L || !$el.length) return;

        if(drag.resizing){
          const nw = Math.max(40, drag.ow + (e.clientX - drag.sx));
          const nh = Math.max(40, drag.oh + (e.clientY - drag.sy));
          if(L.type==='image'){
            $el.find('img').css({ width:nw, height:'auto' });
          } else {
            // texte : on adapte la taille de police à la largeur
            const ratio = nw / drag.ow;
            L.size = Math.max(8, Math.round((L.size||32)*ratio));
            $el.find('.ws-text').css({ fontSize: L.size+'px' });
          }
        } else {
          const nx = drag.ox + (e.clientX - drag.sx);
          const ny = drag.oy + (e.clientY - drag.sy);
          L.x = nx; L.y = ny;
          $el.css({ left:nx, top:ny });
        }
      });

      this.$root.on('pointerup pointercancel', '.ws-layer', ()=>{
        drag = null;
      });
    },

    exportJSON(){
      // Minimal : liste + côté + params
      return JSON.stringify({ layers: this.list }, null, 2);
    }
  };

  $(function(){ Layers.boot(); window.WinShirtLayers = Layers; });

})(jQuery);
