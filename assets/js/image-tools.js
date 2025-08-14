(function($){
  'use strict';

  function apiBase(){
    return (window.WinShirtData && WinShirtData.restUrl) ? WinShirtData.restUrl : '/wp-json/winshirt/v1';
  }

  async function fetchDesigns(cat='all', page=1, per=20){
    const url = `${apiBase()}/designs?category=${encodeURIComponent(cat)}&page=${page}&per_page=${per}`;
    const r = await fetch(url, { credentials:'same-origin' });
    if(!r.ok) return { items:[], total:0, categories:[{slug:'all',name:'Tous'}] };
    return r.json();
  }

  function gridItem(it){
    const src = it.thumb || it.full || '';
    const title = it.title || '';
    return `<div class="ws-grid-item" data-src="${src}" title="${title}">
      ${src? `<img src="${src}" alt="">` : `<div class="ws-ph">—</div>`}
    </div>`;
  }

  async function mount($wrap){
    $wrap.html(`
      <div class="ws-gallery">
        <div class="ws-filters"></div>
        <div class="ws-grid"></div>
        <div class="ws-pager"><button class="prev">◀</button><span class="pi"></span><button class="next">▶</button></div>
        <p class="ws-hint">Astuce : cliquez pour ajouter l’image dans la zone active.</p>
      </div>
    `);

    const state = { cat:'all', page:1, per:12, total:0 };

    async function refresh(){
      const data = await fetchDesigns(state.cat, state.page, state.per);
      state.total = data.total||0;
      // filters
      const $f = $wrap.find('.ws-filters').empty();
      (data.categories||[{slug:'all',name:'Tous'}]).forEach(c=>{
        const $b = $(`<button class="f" data-slug="${c.slug}">${c.name}</button>`);
        if(c.slug===state.cat) $b.addClass('is-active');
        $f.append($b);
      });
      // grid
      const $g = $wrap.find('.ws-grid').empty();
      (data.items||[]).forEach(it=> $g.append(gridItem(it)) );
      const maxp = Math.max(1, Math.ceil(state.total/state.per));
      $wrap.find('.pi').text(`Page ${state.page}/${maxp}`);
      $wrap.find('.prev').prop('disabled', state.page<=1);
      $wrap.find('.next').prop('disabled', state.page>=maxp);
    }

    $wrap.off('click.wsimg');
    $wrap.on('click.wsimg','.ws-grid-item',function(){
      const src = $(this).data('src');
      if(!src || !window.WinShirtLayers) return;
      WinShirtLayers.addImage(src);
    });
    $wrap.on('click.wsimg','.f', function(){ state.cat=$(this).data('slug'); state.page=1; refresh(); });
    $wrap.on('click.wsimg','.prev', function(){ if(state.page>1){state.page--; refresh();} });
    $wrap.on('click.wsimg','.next', function(){ state.page++; refresh(); });

    refresh();
  }

  $(document).on('winshirt:panel:images', function(e, ctx){
    mount(ctx.l2.find('.ws-l2-body'));
  });

})(jQuery);
