/* assets/js/mockup-canvas.js */
(function($){
  'use strict';

  // ---- Helpers --------------------------------------------------------------
  function cssPx(n){ return (Math.round(n*100)/100)+'px'; }
  function clamp(n, min, max){ return Math.max(min, Math.min(max, n)); }
  function pct(n){ return (Math.round(n*1000)/1000); }

  function readJsonAttr($el, name, fallback){
    try{
      const raw = $el.attr(name);
      if(!raw) return fallback;
      return JSON.parse(raw);
    }catch(e){ return fallback; }
  }

  function pickData(){
    // Source 1: dataset sur #winshirt-canvas (privilégié)
    const $c = $('#winshirt-canvas');

    const frontUrl = $c.data('front') || (window.WinShirtData && WinShirtData.mockup && WinShirtData.mockup.frontUrl) || '';
    const backUrl  = $c.data('back')  || (window.WinShirtData && WinShirtData.mockup && WinShirtData.mockup.backUrl)  || '';

    let zones = $c.data('zones');
    if(!zones){
      // Source 2: attribut data-zones (string JSON)
      zones = readJsonAttr($c, 'data-zones', null);
    }
    if(!zones && window.WinShirtData){
      // Source 3: WinShirtData.zones (filtrable côté PHP)
      zones = WinShirtData.zones || null;
    }
    zones = zones || { front:[], back:[] };

    return { frontUrl, backUrl, zones };
  }

  // Transforme un rect % => pixels sur base d’un conteneur
  function rectPctToPx($base, z){
    const w = $base.innerWidth(), h = $base.innerHeight();
    return {
      x: z.left/100 * w,
      y: z.top/100 * h,
      w: z.width/100 * w,
      h: z.height/100 * h
    };
  }

  // ---- Module principal -----------------------------------------------------
  const WinShirtCanvas = {
    $root: null,
    $stage: null,
    $mockFront: null,
    $mockBack: null,
    $zonesWrap: null,
    $zoneButtons: null,

    data: { frontUrl:'', backUrl:'', zones:{front:[], back:[]} },
    currentSide: 'front',
    currentZoneIndex: 0,

    boot(){
      // Crée la structure si manquante
      this.$root = $('#winshirt-canvas');
      if(!this.$root.length){
        // On essaie de deviner une zone dans le modal
        const $area = $('.winshirt-mockup-area, .winshirt-customizer-body, body').first();
        this.$root = $('<div id="winshirt-canvas" class="winshirt-mockup-canvas"></div>').appendTo($area);
      }

      this.injectBaseCss();

      // Récup data (urls & zones)
      this.data = pickData();

      // Stage (ratio auto)
      this.$stage = $('<div class="ws-stage"></div>').appendTo(this.$root);
      this.$mockFront = $('<img class="ws-mockup" data-side="front" alt="Mockup Recto">').appendTo(this.$stage);
      this.$mockBack  = $('<img class="ws-mockup" data-side="back"  alt="Mockup Verso" style="display:none">').appendTo(this.$stage);

      // Zones wrapper
      this.$zonesWrap = $('<div class="ws-zones"></div>').appendTo(this.$stage);

      // Boutons (zones)
      this.$zoneButtons = $('<div class="ws-zone-buttons" role="group" aria-label="Zones"></div>')
        .insertAfter(this.$root);

      // Side toggles (si présents dans le template)
      $(document).on('click', '[data-ws-side]', (e)=>{
        e.preventDefault();
        const s = $(e.currentTarget).attr('data-ws-side');
        this.setSide(s);
      });

      // Init images mockup
      if(this.data.frontUrl){ this.$mockFront.attr('src', this.data.frontUrl); }
      if(this.data.backUrl){  this.$mockBack.attr('src',  this.data.backUrl);  }

      // Render initial
      const side = (window.WinShirtState && WinShirtState.currentSide) || 'front';
      this.setSide(side);

      // Reflow sur resize
      $(window).on('resize', ()=> this.renderZones() );
    },

    injectBaseCss(){
      if(document.getElementById('ws-mockup-canvas-css')) return;
      const css = `
      .winshirt-mockup-canvas{display:flex;justify-content:center}
      .ws-stage{position:relative;max-width:min(92vw,900px)}
      .ws-mockup{display:block;max-width:100%;height:auto;user-select:none;pointer-events:none}
      .ws-zones{position:absolute;inset:0;pointer-events:none}
      .ws-print-zone{
        position:absolute;border:2px dashed rgba(0,0,0,.35);
        box-shadow: inset 0 0 0 1px rgba(0,0,0,.06);
        pointer-events:auto;
      }
      .ws-print-zone.ws-active{border-color:#111; box-shadow: inset 0 0 0 2px rgba(0,0,0,.12)}
      .ws-zone-buttons{
        display:flex; flex-wrap:wrap; gap:8px; justify-content:center; margin:12px 0 22px;
      }
      .ws-zone-btn{
        border-radius:999px; padding:8px 14px; line-height:1;
        border:1px solid rgba(0,0,0,.1); background:#fff; cursor:pointer;
      }
      .ws-zone-btn.is-active{background:#111;color:#fff;border-color:#111}
      `;
      const tag = document.createElement('style');
      tag.id = 'ws-mockup-canvas-css';
      tag.textContent = css;
      document.head.appendChild(tag);
    },

    setSide(side){
      this.currentSide = (side === 'back') ? 'back' : 'front';

      if(this.currentSide === 'front'){
        this.$mockFront.show(); this.$mockBack.hide();
      }else{
        this.$mockFront.hide(); this.$mockBack.show();
      }

      // Par défaut : première zone si existante
      const list = this.data.zones[this.currentSide] || [];
      if(list.length){ this.currentZoneIndex = clamp(this.currentZoneIndex, 0, list.length-1); }
      else { this.currentZoneIndex = 0; }

      if(window.WinShirtState){ WinShirtState.currentSide = this.currentSide; }
      this.renderZones();
      this.renderZoneButtons();
    },

    renderZoneButtons(){
      const list = this.data.zones[this.currentSide] || [];
      const $b = this.$zoneButtons.empty();

      if(!list.length){
        $b.append('<div style="opacity:.6;font-size:13px">Aucune zone définie pour ce côté.</div>');
        return;
      }

      list.forEach((z, i)=>{
        const name = z.name || ('Zone '+(i+1));
        const price = z.price ? ` (${z.price}€)` : '';
        const $btn = $(`<button type="button" class="ws-zone-btn" data-index="${i}">${name}${price}</button>`);
        if(i === this.currentZoneIndex) $btn.addClass('is-active');
        $b.append($btn);
      });

      $b.off('click.ws');
      $b.on('click.ws', '.ws-zone-btn', (e)=>{
        const i = parseInt($(e.currentTarget).attr('data-index'), 10) || 0;
        this.setActiveZone(i);
      });
    },

    setActiveZone(index){
      const list = this.data.zones[this.currentSide] || [];
      if(!list.length) return;

      this.currentZoneIndex = clamp(index, 0, list.length-1);
      this.renderZones();
      this.$zoneButtons.find('.ws-zone-btn').removeClass('is-active')
        .filter(`[data-index="${this.currentZoneIndex}"]`).addClass('is-active');
    },

    renderZones(){
      const list = this.data.zones[this.currentSide] || [];
      this.$zonesWrap.empty();

      if(!list.length) return;

      list.forEach((z, i)=>{
        const box = rectPctToPx(this.$stage, z);
        const $zone = $('<div class="ws-print-zone" tabindex="0" aria-label="Zone d\'impression"></div>')
          .attr('data-side', this.currentSide)
          .attr('data-index', i)
          .css({
            left: cssPx(box.x),
            top: cssPx(box.y),
            width: cssPx(box.w),
            height: cssPx(box.h)
          })
          .appendTo(this.$zonesWrap);

        if(i === this.currentZoneIndex) $zone.addClass('ws-active');

        // Cliquer sur la zone = la sélectionner
        $zone.on('click', ()=> this.setActiveZone(i) );
      });
    },

    // API appelée par l’outil Images (ou par toi)
    addImage(src){
      if(!src) return;
      const list = this.data.zones[this.currentSide] || [];
      if(!list.length) return;

      const z = list[this.currentZoneIndex] || list[0];
      const rect = rectPctToPx(this.$stage, z);

      // Création d’un layer simple centré dans la zone (fallback si WinShirtLayers absent)
      const $img = $(`<img class="ws-layer ws-img" alt="">`).attr('src', src).css({
        position:'absolute',
        left: cssPx(rect.x + rect.w*0.1),
        top:  cssPx(rect.y + rect.h*0.1),
        width: cssPx(rect.w*0.8),
        height: 'auto',
        pointerEvents:'auto',
        cursor: 'move',
        zIndex: 10
      });

      this.$stage.append($img);

      // Drag dans la zone (très simple)
      this.makeDraggableWithin($img, rect);
    },

    makeDraggableWithin($el, rect){
      let dragging=false, sx=0, sy=0, ox=0, oy=0;

      $el.on('pointerdown', (e)=>{
        dragging=true; $el[0].setPointerCapture(e.originalEvent.pointerId);
        sx=e.clientX; sy=e.clientY;
        const s=$el.position(); ox=s.left; oy=s.top;
        e.preventDefault();
      });
      $el.on('pointermove', (e)=>{
        if(!dragging) return;
        let nx = ox + (e.clientX - sx);
        let ny = oy + (e.clientY - sy);
        const w = $el.outerWidth(), h = $el.outerHeight();

        nx = clamp(nx, rect.x, rect.x + rect.w - w);
        ny = clamp(ny, rect.y, rect.y + rect.h - h);

        $el.css({ left: cssPx(nx), top: cssPx(ny) });
      });
      $el.on('pointerup pointercancel', ()=> dragging=false );
    }
  };

  // Expose global (utilisé par image-tools.js)
  window.WinShirtCanvas = WinShirtCanvas;

  // Boot au chargement
  if(document.readyState !== 'loading') WinShirtCanvas.boot();
  else document.addEventListener('DOMContentLoaded', ()=> WinShirtCanvas.boot());

  // Bridge optionnel : si WinShirtLayers existe, on peut déléguer
  // (tu pourras l’activer plus tard)
  if(!window.WinShirtLayers){
    window.WinShirtLayers = {
      addImage: function(src){ WinShirtCanvas.addImage(src); }
    };
  }

})(jQuery);
