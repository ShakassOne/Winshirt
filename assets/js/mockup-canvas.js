(function(){
  'use strict';

  const state = {
    side : 'front',
    productId : 0,
    zones : { front:[], back:[] },
    mockups : { front:'', back:'' }
  };

  function px(n){ return (Math.round(n*100)/100)+'%'; }
  function qs(s,ctx){ return (ctx||document).querySelector(s); }
  function qsa(s,ctx){ return Array.from((ctx||document).querySelectorAll(s)); }

  function readData(){
    if(!window.WinShirtData) return;

    state.productId = (WinShirtData.product && WinShirtData.product.id) ? WinShirtData.product.id : 0;

    // mockups (via filtres PHP)
    if(WinShirtData.mockups){
      state.mockups.front = WinShirtData.mockups.front || '';
      state.mockups.back  = WinShirtData.mockups.back  || '';
    }

    // zones (noms + prix inclus si fournis)
    if(WinShirtData.zones){
      state.zones.front = Array.isArray(WinShirtData.zones.front) ? WinShirtData.zones.front : [];
      state.zones.back  = Array.isArray(WinShirtData.zones.back)  ? WinShirtData.zones.back  : [];
    }
  }

  function setSide(side){
    state.side = (side === 'back') ? 'back' : 'front';

    const front = qs('#ws-mockup-front');
    const back  = qs('#ws-mockup-back');

    if(state.mockups.front) front.src = state.mockups.front;
    if(state.mockups.back)  back.src  = state.mockups.back;

    front.classList.toggle('ws-show', state.side==='front');
    back .classList.toggle('ws-show', state.side==='back');

    renderZones();
    renderZoneButtons();
    // marquer bouton actif
    qsa('.ws-side-btn').forEach(b=>{
      b.classList.toggle('is-active', b.getAttribute('data-side')===state.side);
    });
  }

  function renderZones(){
    const canvas = qs('#winshirt-canvas');
    if(!canvas) return;

    // purge
    qsa('.ws-print-zone', canvas).forEach(n=> n.remove());

    const zones = state.zones[state.side] || [];
    zones.forEach((z, idx)=>{
      const box = document.createElement('div');
      box.className = 'ws-print-zone';
      box.style.left   = px(z.left);
      box.style.top    = px(z.top);
      box.style.width  = px(z.width);
      box.style.height = px(z.height);
      box.dataset.index = String(idx);

      // label nom / prix si fournis
      const label = document.createElement('div');
      label.className = 'ws-zone-label';
      label.textContent = (z.name || ('Zone '+(idx+1))) + (z.price ? ('  —  '+z.price+'€') : '');
      box.appendChild(label);

      box.addEventListener('click', ()=>{
        qsa('.ws-print-zone', canvas).forEach(n=>n.classList.remove('is-active'));
        box.classList.add('is-active');
      });

      canvas.appendChild(box);
    });

    // activer la première zone par défaut
    const first = qs('.ws-print-zone', canvas);
    if(first) first.classList.add('is-active');
  }

  function renderZoneButtons(){
    const wrap = qs('#ws-zone-buttons');
    if(!wrap) return;
    wrap.innerHTML = '';

    const zones = state.zones[state.side] || [];
    zones.forEach((z, idx)=>{
      const b = document.createElement('button');
      b.className = 'ws-zone-btn';
      b.type = 'button';
      b.textContent = (z.name || ('Zone '+(idx+1))) + (z.price ? (' ('+z.price+'€)') : '');
      b.addEventListener('click', ()=>{
        const canvas = qs('#winshirt-canvas');
        const target = qs(`.ws-print-zone[data-index="${idx}"]`, canvas);
        if(!target) return;
        qsa('.ws-print-zone', canvas).forEach(n=>n.classList.remove('is-active'));
        target.classList.add('is-active');
        target.scrollIntoView({block:'center', behavior:'smooth'});
        // marquer bouton actif
        qsa('.ws-zone-btn', wrap).forEach(bb=>bb.classList.remove('is-active'));
        b.classList.add('is-active');
      });
      wrap.appendChild(b);
    });

    const first = qs('.ws-zone-btn', wrap);
    if(first) first.classList.add('is-active');

    // message si aucune zone
    if(!zones.length){
      const m = document.createElement('div');
      m.style.opacity = .7;
      m.textContent = 'Aucune zone définie pour ce côté.';
      wrap.appendChild(m);
    }
  }

  function insertImage(src){
    const canvas = qs('#winshirt-canvas');
    if(!canvas) return;
    const active = qs('.ws-print-zone.is-active', canvas) || qs('.ws-print-zone', canvas);
    if(!active) return;

    // place l’image à ~40% de la zone
    const img = document.createElement('img');
    img.className = 'ws-layer';
    img.src = src;
    img.style.position = 'absolute';
    img.style.left  = `calc(${active.style.left} + 10px)`;
    img.style.top   = `calc(${active.style.top} + 10px)`;
    img.style.width = `calc(${active.style.width} * .4)`;
    img.style.height = 'auto';
    canvas.appendChild(img);
  }

  function wireEvents(){
    // Recto / Verso
    qsa('.ws-side-btn').forEach(b=>{
      b.addEventListener('click', ()=> setSide(b.getAttribute('data-side')) );
    });

    // Galerie → insertion simple (fallback quand WinShirtLayers n’est pas chargé)
    document.addEventListener('click', function(e){
      const item = e.target.closest('.ws-grid-item');
      if(!item) return;
      const src = item.getAttribute('data-src') || item.getAttribute('data-full') || '';
      if(!src) return;

      if(window.WinShirtLayers && typeof WinShirtLayers.addImage === 'function'){
        WinShirtLayers.addImage(src);
      }else{
        insertImage(src);
      }
    });
  }

  function boot(){
    const modal = document.getElementById('winshirt-customizer-modal');
    if(!modal) return;

    readData();
    wireEvents();

    // initialisation au moment de l’ouverture du modal
    document.addEventListener('winshirt:modal:open', ()=>{
      readData();
      setSide('front');
    });
  }

  if(document.readyState !== 'loading') boot(); else document.addEventListener('DOMContentLoaded', boot);
})();
