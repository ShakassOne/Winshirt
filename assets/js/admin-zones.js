(function(){
  'use strict';

  const WS = {
    el: null,
    side: 'front',
    imgs: { front: null, back: null },
    zoneEls: [],
    items: [],
    cfg: { strictPercent: true }
  };

  /* ---------- utils ---------- */
  function ready(fn){ if(document.readyState!=='loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }
  function px(n){ return (Math.round(n*100)/100)+'px'; }
  function clamp(n,min,max){ return Math.max(min, Math.min(max,n)); }
  function qs(s,c){ return (c||document).querySelector(s); }
  function qsa(s,c){ return Array.from((c||document).querySelectorAll(s)); }

  function getData(){
    const d = (window.WinShirtData||{});
    WS.cfg.strictPercent = !!(d.config && d.config.strictPercent);
    return d;
  }
  function ensureCanvas(){ WS.el = document.getElementById('winshirt-canvas'); return !!WS.el; }
  function canvasRect(){ return WS.el.getBoundingClientRect(); }
  function clearZones(){ WS.zoneEls.forEach(z=>z.remove()); WS.zoneEls.length=0; }

  /* ---------- mockup load (WinShirtData OR data-attributes fallback) ---------- */
  function loadMockupImages(){
    let front='', back='';
    const d = getData();
    const mocks = Array.isArray(d.mockups) ? d.mockups : [];
    if(mocks.length){
      const m = mocks[0]||{};
      front = m.front || (m.images && m.images.front) || '';
      back  = m.back  || (m.images && m.images.back)  || '';
    }
    if(!front || !back){
      const f = WS.el.getAttribute('data-front')||'';
      const b = WS.el.getAttribute('data-back')||'';
      front = front || f; back = back || b;
    }

    // éviter doublons
    if(WS.el.querySelector('img.winshirt-mockup-img')) return;

    if(front){
      const f = new Image();
      f.src=front; f.alt='Mockup Recto'; f.dataset.side='front';
      f.className='winshirt-mockup-img';
      f.style.cssText='position:absolute;inset:0;margin:auto;max-width:100%;max-height:100%;object-fit:contain;display:block;';
      WS.el.appendChild(f); WS.imgs.front=f;
    }
    if(back){
      const b = new Image();
      b.src=back; b.alt='Mockup Verso'; b.dataset.side='back';
      b.className='winshirt-mockup-img';
      b.style.cssText='position:absolute;inset:0;margin:auto;max-width:100%;max-height:100%;object-fit:contain;display:none;';
      WS.el.appendChild(b); WS.imgs.back=b;
    }
  }

  function setSide(side){
    WS.side = (side==='back'?'back':'front');
    WS.el.querySelectorAll('img.winshirt-mockup-img').forEach(img=>{
      img.style.display = (img.dataset.side===WS.side ? 'block' : 'none');
    });
    drawZones();
    // on pourrait masquer les items “de l’autre côté” si tu gères des items par side
  }

  /* ---------- zones ---------- */
  function drawZones(){
    clearZones();
    const d = getData();
    const zones = Array.isArray(d.zones) ? d.zones : [];
    const sideZones = zones.filter(z => (z.side||'front')===WS.side);
    const C = canvasRect();

    sideZones.forEach(z=>{
      let left, top, width, height;
      if(WS.cfg.strictPercent){
        left=(z.left||0)*0.01*C.width;  top=(z.top||0)*0.01*C.height;
        width=(z.width||0)*0.01*C.width;height=(z.height||0)*0.01*C.height;
      } else {
        left=z.left||0; top=z.top||0; width=z.width||200; height=z.height||200;
      }
      const div=document.createElement('div');
      div.className='ws-print-zone'; div.dataset.side=WS.side;
      div.style.cssText=`position:absolute;left:${px(left)};top:${px(top)};width:${px(width)};height:${px(height)};border:1px dashed rgba(0,0,0,.25);pointer-events:none;`;
      WS.el.appendChild(div); WS.zoneEls.push(div);
    });
  }

  function currentZoneRect(){
    const z = WS.zoneEls[0]; if(!z) return null;
    const r = z.getBoundingClientRect(), c = canvasRect();
    return { x:r.left-c.left, y:r.top-c.top, w:r.width, h:r.height };
  }

  /* ---------- items (drag + resize, confinement zone si présente) ---------- */
  function makeDraggable(el){
    let sx=0, sy=0, ox=0, oy=0, moving=false;

    el.addEventListener('pointerdown', (e)=>{
      if(e.target.classList.contains('ws-rh')) return; // handle → resize
      moving=true; el.setPointerCapture(e.pointerId);
      sx=e.clientX; sy=e.clientY;
      const s=el.style; ox=parseFloat(s.left)||0; oy=parseFloat(s.top)||0;
      e.preventDefault();
    });

    el.addEventListener('pointermove', (e)=>{
      if(!moving) return;
      const zone = currentZoneRect(); const c = canvasRect();
      const w = el.offsetWidth, h = el.offsetHeight;

      let nx = ox + (e.clientX - sx);
      let ny = oy + (e.clientY - sy);

      if(zone){
        nx = clamp(nx, zone.x, zone.x + zone.w - w);
        ny = clamp(ny, zone.y, zone.y + zone.h - h);
      }else{
        nx = clamp(nx, 0, c.width - w);
        ny = clamp(ny, 0, c.height - h);
      }

      el.style.left = px(nx);
      el.style.top  = px(ny);
    });

    const stop=()=>{ moving=false; };
    el.addEventListener('pointerup', stop);
    el.addEventListener('pointercancel', stop);
  }

  function makeResizable(el, handle){
    let sx=0, sy=0, ow=0, oh=0, resizing=false;

    handle.addEventListener('pointerdown', (e)=>{
      resizing=true; handle.setPointerCapture(e.pointerId);
      sx=e.clientX; sy=e.clientY; ow=el.offsetWidth; oh=el.offsetHeight;
      e.preventDefault();
    });

    handle.addEventListener('pointermove', (e)=>{
      if(!resizing) return;
      const zone=currentZoneRect(); const c=canvasRect();

      let nw = ow + (e.clientX - sx);
      let nh = oh + (e.clientY - sy);
      nw = Math.max(24, nw); nh = Math.max(24, nh);

      // confinement dans zone/canvas
      if(zone){
        const maxW = zone.x + zone.w - el.offsetLeft;
        const maxH = zone.y + zone.h - el.offsetTop;
        nw = Math.min(nw, maxW); nh = Math.min(nh, maxH);
      }else{
        const maxW = c.width  - el.offsetLeft;
        const maxH = c.height - el.offsetTop;
        nw = Math.min(nw, maxW); nh = Math.min(nh, maxH);
      }

      el.style.width  = px(nw);
      el.style.height = px(nh);
    });

    const stop=()=>{ resizing=false; };
    handle.addEventListener('pointerup', stop);
    handle.addEventListener('pointercancel', stop);
  }

  function addImage(url){
    if(!url) return;
    const zone = currentZoneRect(), C = canvasRect();

    const wrap=document.createElement('div');
    wrap.className='ws-item ws-item-image';
    wrap.style.position='absolute';
    wrap.style.cursor='move';
    wrap.style.userSelect='none';

    const baseW = zone ? Math.min(zone.w*0.6, C.width*0.6) : Math.min(320, C.width*0.6);
    const baseH = baseW;

    const cx = zone ? (zone.x + (zone.w - baseW)/2) : ( (C.width - baseW)/2 );
    const cy = zone ? (zone.y + (zone.h - baseH)/2) : ( (C.height- baseH)/2 );

    wrap.style.left = px(cx);
    wrap.style.top  = px(cy);
    wrap.style.width  = px(baseW);
    wrap.style.height = px(baseH);

    const img = new Image();
    img.src = url; img.alt = '';
    img.style.cssText='position:absolute;inset:0;margin:auto;max-width:100%;max-height:100%;pointer-events:none;';
    wrap.appendChild(img);

    const rh = document.createElement('div');
    rh.className='ws-rh';
    rh.style.cssText='position:absolute;width:12px;height:12px;right:-6px;bottom:-6px;background:#111827;border-radius:6px;cursor:nwse-resize;';
    wrap.appendChild(rh);

    WS.el.appendChild(wrap);
    makeDraggable(wrap);
    makeResizable(wrap, rh);
    WS.items.push(wrap);
  }

  function addText(opts){
    const o = Object.assign({ text:'Votre texte', size:32, bold:false, italic:false }, opts||{});
    const zone = currentZoneRect(), C = canvasRect();

    const el = document.createElement('div');
    el.className='ws-item ws-item-text';
    el.style.position='absolute';
    el.style.cursor='move';
    el.style.userSelect='none';
    el.style.whiteSpace='pre';
    el.style.fontFamily='system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif';
    el.style.fontSize  = px(o.size);
    el.style.fontWeight= o.bold ? '700':'400';
    el.style.fontStyle = o.italic ? 'italic':'normal';
    el.textContent = o.text;

    const baseW = Math.min( Math.max(o.size*6, 140), C.width*0.8 );
    const baseH = o.size*1.6;

    const cx = zone ? (zone.x + (zone.w - baseW)/2) : ( (C.width - baseW)/2 );
    const cy = zone ? (zone.y + (zone.h - baseH)/2) : ( (C.height- baseH)/2 );

    el.style.left = px(cx);
    el.style.top  = px(cy);
    el.style.width  = px(baseW);
    el.style.height = px(baseH);
    el.style.display='flex'; el.style.alignItems='center'; el.style.justifyContent='center';

    const rh = document.createElement('div');
    rh.className='ws-rh';
    rh.style.cssText='position:absolute;width:12px;height:12px;right:-6px;bottom:-6px;background:#111827;border-radius:6px;cursor:nwse-resize;';
    el.appendChild(rh);

    WS.el.appendChild(el);
    makeDraggable(el);
    makeResizable(el, rh);
    WS.items.push(el);
  }

  /* ---------- boot & public API ---------- */
  function mount(){
    if(!ensureCanvas()) return;
    loadMockupImages();
    setSide(WS.side);
  }

  document.addEventListener('winshirt:mounted', mount);
  ready(mount);

  document.addEventListener('winshirt:sideChanged', (e)=>{
    const side = (e.detail && e.detail.side) || 'front';
    setSide(side);
  });

  // API utilisée par les panneaux (galerie, texte)
  window.WinShirtCanvas = { setSide, addImage, addText, getZoneRect: currentZoneRect };
  if(!window.WinShirtLayers){ window.WinShirtLayers = { addImage, addText }; }
})();
