/**
 * WinShirt – Mockup Canvas
 * - Monte le mockup (front/back) à partir de WinShirtData.mockups
 * - Affiche les zones d'impression (WinShirtData.zones)
 * - Expose une API minimale: addImage(url), addText(opts), setSide('front'|'back')
 * - Fournit un fallback WinShirtLayers si non présent (pour que la galerie fonctionne déjà)
 */
(function(){
  'use strict';

  const WS = {
    el: null,                 // #winshirt-canvas
    side: 'front',            // 'front' | 'back'
    imgs: { front:null, back:null }, // mockup <img>
    zoneEls: [],              // overlays de zones
    items: [],                // éléments posés (images/textes)
    cfg: {
      strictPercent: true
    }
  };

  function ready(fn){ if(document.readyState!=='loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }
  function px(n){ return (Math.round(n*100)/100)+'px'; }

  function getData(){
    const d = (window.WinShirtData||{});
    WS.cfg.strictPercent = !!(d.config && d.config.strictPercent);
    return d;
  }

  function ensureCanvas(){
    WS.el = document.getElementById('winshirt-canvas');
    return !!WS.el;
  }

  function clear(el){ while(el.firstChild) el.removeChild(el.firstChild); }

  function loadMockupImages(){
    const d = getData();
    const mocks = Array.isArray(d.mockups) ? d.mockups : [];
    const m = mocks[0] || {};
    const front = m.front || (m.images && m.images.front) || '';
    const back  = m.back  || (m.images && m.images.back ) || '';

    // Si déjà en place, ne pas dupliquer
    const already = WS.el.querySelector('img.winshirt-mockup-img');
    if(already) return;

    if(front){
      const f = new Image();
      f.src = front; f.alt = 'Mockup Recto';
      f.className = 'winshirt-mockup-img'; f.dataset.side = 'front';
      f.style.cssText = 'position:absolute;inset:0;margin:auto;max-width:100%;max-height:100%;object-fit:contain;display:block;';
      WS.el.appendChild(f);
      WS.imgs.front = f;
    }
    if(back){
      const b = new Image();
      b.src = back; b.alt = 'Mockup Verso';
      b.className = 'winshirt-mockup-img'; b.dataset.side = 'back';
      b.style.cssText = 'position:absolute;inset:0;margin:auto;max-width:100%;max-height:100%;object-fit:contain;display:none;';
      WS.el.appendChild(b);
      WS.imgs.back = b;
    }
  }

  function setSide(side){
    WS.side = (side==='back'?'back':'front');
    const imgs = WS.el.querySelectorAll('img.winshirt-mockup-img');
    imgs.forEach(img=>{
      img.style.display = (img.dataset.side===WS.side ? 'block' : 'none');
    });
    drawZones();
  }

  function canvasRect(){
    return WS.el.getBoundingClientRect();
  }

  function drawZones(){
    // clear zones
    WS.zoneEls.forEach(z=> z.remove());
    WS.zoneEls = [];

    const d = getData();
    const zones = Array.isArray(d.zones) ? d.zones : [];
    const sideZones = zones.filter(z=> (z.side||'front')===WS.side);

    const C = canvasRect();
    sideZones.forEach(z=>{
      // z: {left, top, width, height} en % si strictPercent, sinon pixels
      let left, top, width, height;
      if(WS.cfg.strictPercent){
        left   = (z.left   || 0) * 0.01 * C.width;
        top    = (z.top    || 0) * 0.01 * C.height;
        width  = (z.width  || 0) * 0.01 * C.width;
        height = (z.height || 0) * 0.01 * C.height;
      } else {
        left   = z.left||0;  top = z.top||0;  width = z.width||100; height = z.height||100;
      }
      const div = document.createElement('div');
      div.className = 'ws-print-zone';
      div.dataset.side = WS.side;
      div.style.position = 'absolute';
      div.style.left   = px(left);
      div.style.top    = px(top);
      div.style.width  = px(width);
      div.style.height = px(height);
      div.style.pointerEvents = 'none';
      WS.el.appendChild(div);
      WS.zoneEls.push(div);
    });
  }

  function currentZoneRect(){
    // on prend la première zone visible pour placer par défaut
    const z = WS.zoneEls[0];
    if(!z) return null;
    const r = z.getBoundingClientRect();
    const c = canvasRect();
    return { // coordonnées relatives au canvas
      x: r.left - c.left,
      y: r.top  - c.top,
      w: r.width,
      h: r.height
    };
  }

  function makeDraggable(el){
    let sx=0, sy=0, ox=0, oy=0, moving=false;

    el.addEventListener('pointerdown', (e)=>{
      moving=true; el.setPointerCapture(e.pointerId);
      sx=e.clientX; sy=e.clientY;
      const tr = el.style.transform.match(/translate\(([-0-9.]+)px,\s*([-0-9.]+)px\)/);
      if(tr){ ox=parseFloat(tr[1]||0); oy=parseFloat(tr[2]||0); } else { ox=0; oy=0; el.style.transform='translate(0px,0px)'; }
      e.preventDefault();
    });
    el.addEventListener('pointermove', (e)=>{
      if(!moving) return;
      const dx=e.clientX-sx, dy=e.clientY-sy;
      el.style.transform = `translate(${px(ox+dx)}, ${px(oy+dy)})`;
    });
    el.addEventListener('pointerup', ()=>{ moving=false; });
    el.addEventListener('pointercancel', ()=>{ moving=false; });
  }

  function addImage(url){
    if(!url) return;
    const zone = currentZoneRect();
    const img = new Image();
    img.src = url; img.alt = '';
    const wrap = document.createElement('div');
    wrap.className = 'ws-item ws-item-image';
    wrap.style.position='absolute';
    // taille de base: 50% de la zone
    const baseW = zone ? Math.min(zone.w*0.6, canvasRect().width*0.6) : 240;
    const baseH = baseW;
    // centrer dans zone/canvas
    const cx = zone ? (zone.x + (zone.w-baseW)/2) : ( (canvasRect().width-baseW)/2 );
    const cy = zone ? (zone.y + (zone.h-baseH)/2) : ( (canvasRect().height-baseH)/2 );

    wrap.style.left = px(cx); wrap.style.top = px(cy);
    wrap.style.width = px(baseW); wrap.style.height = px(baseH);
    wrap.style.transform = 'translate(0px,0px)';
    wrap.style.cursor = 'move';
    wrap.style.userSelect='none';

    img.style.position='absolute';
    img.style.inset='0';
    img.style.margin='auto';
    img.style.maxWidth='100%';
    img.style.maxHeight='100%';
    img.style.pointerEvents='none';

    wrap.appendChild(img);
    WS.el.appendChild(wrap);
    makeDraggable(wrap);
    WS.items.push(wrap);
  }

  function addText(opts){
    const o = Object.assign({ text:'Votre texte', size:32, bold:false, italic:false }, opts||{});
    const zone = currentZoneRect();
    const el = document.createElement('div');
    el.className='ws-item ws-item-text';
    el.style.position='absolute';
    el.style.left = px(zone ? (zone.x + zone.w*0.1) : canvasRect().width*0.25);
    el.style.top  = px(zone ? (zone.y + zone.h*0.1) : canvasRect().height*0.25);
    el.style.transform='translate(0px,0px)';
    el.style.cursor='move';
    el.style.userSelect='none';
    el.style.whiteSpace='pre';
    el.style.fontSize = px(o.size);
    el.style.fontWeight = o.bold ? '700' : '400';
    el.style.fontStyle  = o.italic ? 'italic' : 'normal';
    el.style.fontFamily = 'system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif';
    el.textContent = o.text;
    WS.el.appendChild(el);
    makeDraggable(el);
    WS.items.push(el);
  }

  // API publique
  const API = {
    setSide,
    addImage,
    addText,
    getZoneRect: currentZoneRect
  };

  // Boot sequence : on monte au moment où le template est injecté
  function mount(){
    if(!ensureCanvas()) return;
    loadMockupImages();
    setSide(WS.side);
  }

  // Ecoute les signaux venant du template ou d’autres scripts
  document.addEventListener('winshirt:mounted', mount);
  ready(mount);
  document.addEventListener('winshirt:sideChanged', (e)=> setSide((e.detail&&e.detail.side)||'front'));

  // Expose
  window.WinShirtCanvas = API;

  // Fallback WinShirtLayers (si layers.js pas encore chargé)
  if(!window.WinShirtLayers){
    window.WinShirtLayers = {
      addImage: addImage,
      addText: addText
    };
  }
})();
