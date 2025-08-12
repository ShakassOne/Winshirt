/**
 * WinShirt - Image Tools
 * - Galerie de visuels (ws-design) via REST /winshirt/v1/designs
 * - Filtres catégories, pagination
 * - Insertion du visuel dans la zone d'impression (via WinShirtLayers si dispo)
 */

(function($){
  'use strict';

  const API = {
    base(){ return (window.WinShirtData && WinShirtData.restUrl) ? WinShirtData.restUrl : '/wp-json/winshirt/v1'; },
    async list(category='all', page=1, per=24){
      const url = `${this.base()}/designs?category=${encodeURIComponent(category)}&page=${page}&per_page=${per}`;
      const r = await fetch(url, { credentials: 'same-origin' });
      if(!r.ok) throw new Error('HTTP '+r.status);
      return r.json();
    }
  };

  function gridItemTpl(item){
    const title = item.title ? item.title.replace(/"/g,'&quot;') : '';
    const src = item.thumb || item.full || '';
    return `
      <div class="ws-grid-item" data-id="${item.id}" data-src="${src}" title="${title}"
           style="border:1px solid rgba(0,0,0,.08);border-radius:10px;overflow:hidden;cursor:pointer;background:#fff">
        <div style="aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;background:#f7f7f7">
          ${src ? `<img src="${src}" style="max-width:100%;max-height:100%;object-fit:contain;display:block">` : '<div style="opacity:.5">—</div>'}
        </div>
        <div style="font-size:12px;padding:6px 8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${title}</div>
      </div>`;
  }

  function mountGallery($container){
    $container.html(`
      <div class="ws-gallery" style="display:flex;flex-direction:column;gap:10px">
        <div class="ws-filters" style="display:flex;gap:8px;flex-wrap:wrap"></div>
        <div class="ws-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px"></div>
        <div class="ws-pager" style="display:flex;gap:8px;justify-content:center">
          <button class="button ws-prev" disabled>◀</button>
          <span class="ws-pageinfo" style="align-self:center"></span>
          <button class="button ws-next" disabled>▶</button>
        </div>
        <div class="ws-upload-hint" style="font-size:12px;opacity:.7">Ou glissez-déposez votre image sur le mockup pour l’ajouter.</div>
      </div>
    `);
  }

  async function loadAndRender($l2){
    const $body = $l2.find('.ws-l2-body');
    mountGallery($body);

    const state = { cat:'all', page:1, per:12, total:0 };

    async function refresh(){
      $body.find('.ws-grid').html('<div style="opacity:.6">Chargement…</div>');
      try{
        const data = await API.list(state.cat, state.page, state.per);

        // Filters
        const $filters = $body.find('.ws-filters').empty();
        data.categories.forEach(c=>{
          const btn = $(`<button class="button ws-cat" data-slug="${c.slug}">${c.name}</button>`);
          if(c.slug === state.cat) btn.addClass('active').css({fontWeight:'700'});
          $filters.append(btn);
        });

        // Grid
        const $grid = $body.find('.ws-grid').empty();
        data.items.forEach(it=> $grid.append(gridItemTpl(it)) );

        state.total = data.total;
        const maxPage = Math.max(1, Math.ceil(state.total / state.per));

        $body.find('.ws-pageinfo').text(`Page ${state.page} / ${maxPage}`);
        $body.find('.ws-prev').prop('disabled', state.page<=1);
        $body.find('.ws-next').prop('disabled', state.page>=maxPage);

      }catch(e){
        console.error(e);
        $body.find('.ws-grid').html('<div style="color:#c00">Erreur chargement.</div>');
      }
    }

    // Interactions
    $body.off('click.wsimg');
    $body.on('click.wsimg', '.ws-cat', function(){
      state.cat = $(this).data('slug'); state.page = 1; refresh();
    });
    $body.on('click.wsimg', '.ws-prev', ()=>{ if(state.page>1){ state.page--; refresh(); } });
    $body.on('click.wsimg', '.ws-next', ()=>{ state.page++; refresh(); });

    // Insertion d’un visuel
    $body.on('click.wsimg', '.ws-grid-item', function(){
      const src = $(this).data('src');
      if(!src) return;

      if(window.WinShirtLayers && typeof WinShirtLayers.addImage==='function'){
        WinShirtLayers.addImage(src);
      } else {
        // Fallback simple : image centrée dans la zone
        const side = (window.WinShirtState && WinShirtState.currentSide) || 'front';
        const $zone = $(`#winshirt-canvas .ws-print-zone[data-side="${side}"]`);
        if(!$zone.length) return;

        const zOff = $zone.position();
        const $img = $(`<img class="ws-layer" src="${src}" alt="">`).css({
          left: zOff.left + 10, top: zOff.top + 10, width: Math.max(60, $zone.width()*0.4), height:'auto'
        });
        $('#winshirt-canvas').append($img);
      }
    });

    await refresh();
  }

  // Branche l’outil Images quand L2 s’ouvre
  $(document).on('winshirt:panel:images', function(e, ctx){
    loadAndRender(ctx.l2);
  });

})(jQuery);
// --- WinShirt bridge: clic vignette -> addImage sur canvas ---
(function(){
  'use strict';

  function onClickThumbnail(e){
    const t = e.target.closest('[data-ws-add-image], .ws-grid-item img, .ws-gallery img, .ws-panel--images img, .ws-design-thumb img');
    if(!t) return;
    e.preventDefault();

    const card = t.closest('[data-src]');
    const url = (card && card.getAttribute('data-src')) || t.getAttribute('data-ws-add-image') || t.getAttribute('src');
    if(!url) return;

    if(window.WinShirtLayers && typeof WinShirtLayers.addImage==='function'){
      WinShirtLayers.addImage(url);
    } else if (window.WinShirtCanvas && typeof WinShirtCanvas.addImage==='function'){
      WinShirtCanvas.addImage(url);
    }
  }

  document.addEventListener('click', onClickThumbnail);

  // évite le drag natif (ghost) sur les thumbs
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.ws-gallery img, .ws-grid-item img, .ws-panel--images img, [data-ws-add-image]').forEach(function(img){
      img.addEventListener('dragstart', function(e){ e.preventDefault(); return false; });
    });
  });
})();
