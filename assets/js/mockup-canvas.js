(function(){
  'use strict';
  const WS = { el:null, side:'front', imgs:{front:null,back:null}, zoneEls:[], items:[], cfg:{ strictPercent:true } };
  function ready(fn){ if(document.readyState!=='loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }
  function px(n){ return (Math.round(n*100)/100)+'px'; }
  function getData(){ const d=(window.WinShirtData||{}); WS.cfg.strictPercent=!!(d.config&&d.config.strictPercent); return d; }
  function ensureCanvas(){ WS.el=document.getElementById('winshirt-canvas'); return !!WS.el; }
  function canvasRect(){ return WS.el.getBoundingClientRect(); }
  function clearZones(){ WS.zoneEls.forEach(z=>z.remove()); WS.zoneEls=[]; }

  function loadMockupImages(){
    // 1) Source WinShirtData.mockups
    let front='', back='';
    const d=getData(), mocks=Array.isArray(d.mockups)?d.mockups:[];
    if(mocks.length){
      const m=mocks[0]||{}; front=m.front||(m.images&&m.images.front)||''; back=m.back||(m.images&&m.images.back)||'';
    }
    // 2) Fallback data-attributes du canvas (posés par le template)
    if(!front || !back){
      const f=WS.el.getAttribute('data-front')||''; const b=WS.el.getAttribute('data-back')||'';
      front = front || f; back = back || b;
    }
    // Ne pas dupliquer
    if(WS.el.querySelector('img.winshirt-mockup-img')) return;

    if(front){
      const f = new Image();
      f.src=front; f.alt='Mockup Recto'; f.className='winshirt-mockup-img'; f.dataset.side='front';
      f.style.cssText='position:absolute;inset:0;margin:auto;max-width:100%;max-height:100%;object-fit:contain;display:block;';
      WS.el.appendChild(f); WS.imgs.front=f;
    }
    if(back){
      const b = new Image();
      b.src=back; b.alt='Mockup Verso'; b.className='winshirt-mockup-img'; b.dataset.side='back';
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
  }

  function drawZones(){
    clearZones();
    const d=getData(), zones=Array.isArray(d.zones)?d.zones:[];
    const sideZones=zones.filter(z=>(z.side||'front')===WS.side);
    const C=canvasRect();
    sideZones.forEach(z=>{
      let left, top, width, height;
      if(WS.cfg.strictPercent){
        left=(z.left||0)*0.01*C.width; top=(z.top||0)*0.01*C.height;
        width=(z.width||0)*0.01*C.width; height=(z.height||0)*0.01*C.height;
      } else { left=z.left||0; top=z.top||0; width=z.width||100; height=z.height||100; }
      const div=document.createElement('div');
      div.className='ws-print-zone'; div.dataset.side=WS.side;
      div.style.cssText=`position:absolute;left:${px(left)};top:${px(top)};width:${px(width)};height:${px(height)};pointer-events:none;`;
      WS.el.appendChild(div); WS.zoneEls.push(div);
    });
  }

  function currentZoneRect(){
    const z=WS.zoneEls[0]; if(!z) return null;
    const r=z.getBoundingClientRect(), c=canvasRect();
    return { x:r.left-c.left, y:r.top-c.top, w:r.width, h:r.height };
  }

  function makeDraggable(el){
    let sx=0, sy=0, ox=0, oy=0, moving=false;
    el.addEventListener('pointerdown', (e)=>{ moving=true; el.setPointerCapture(e.pointerId);
      sx=e.clientX; sy=e.clientY;
      const tr=el.style.transform.match(/translate\(([-0-9.]+)px,\s*([-0-9.]+)px\)/);
      if(tr){ ox=parseFloat(tr[1]||0); oy=parseFloat(tr[2]||0); } else { ox=0; oy=0; el.style.transform='translate(0px,0px)'; }
      e.preventDefault();
    });
    el.addEventListener('pointermove', (e)=>{ if(!moving) return;
      const dx=e.clientX-sx, dy=e.clientY-sy; el.style.transform=`translate(${px(ox+dx)}, ${px(oy+dy)})`;
    });
    el.addEventListener('pointerup', ()=>{ moving=false; });
    el.addEventListener('pointercancel', ()=>{ moving=false; });
  }

  function addImage(url){
    if(!url) return;
    const zone=currentZoneRect(), C=canvasRect();
    const wrap=document.createElement('div'); wrap.className='ws-item ws-item-image';
    wrap.style.position='absolute';
    const baseW=zone?Math.min(zone.w*0.6,C.width*0.6):240, baseH=baseW;
    const cx=zone?(zone.x+(zone.w-baseW)/2):((C.width-baseW)/2);
    const cy=zone?(zone.y+(zone.h-baseH)/2):((C.height-baseH)/2);
    wrap.style.left=px(cx); wrap.style.top=px(cy); wrap.style.width=px(baseW); wrap.style.height=px(baseH);
    wrap.style.transform='translate(0px,0px)'; wrap.style.cursor='move'; wrap.style.userSelect='none';
    const img=new Image(); img.src=url; img.alt='';
    img.style.position='absolute'; img.style.inset='0'; img.style.margin='auto'; img.style.maxWidth='100%'; img.style.maxHeight='100%'; img.style.pointerEvents='none';
    wrap.appendChild(img); WS.el.appendChild(wrap); makeDraggable(wrap); WS.items.push(wrap);
  }

  function addText(opts){
    const o=Object.assign({text:'Votre texte',size:32,bold:false,italic:false},opts||{});
    const zone=currentZoneRect(), C=canvasRect();
    const el=document.createElement('div'); el.className='ws-item ws-item-text'; el.style.position='absolute';
    el.style.left=px(zone?(zone.x+zone.w*0.1):C.width*0.25); el.style.top=px(zone?(zone.y+zone.h*0.1):C.height*0.25);
    el.style.transform='translate(0px,0px)'; el.style.cursor='move'; el.style.userSelect='none'; el.style.whiteSpace='pre';
    el.style.fontSize=px(o.size); el.style.fontWeight=o.bold?'700':'400'; el.style.fontStyle=o.italic?'italic':'normal';
    el.style.fontFamily='system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif'; el.textContent=o.text;
    WS.el.appendChild(el); makeDraggable(el); WS.items.push(el);
  }

  function mount(){ if(!ensureCanvas()) return; loadMockupImages(); setSide(WS.side); }

  document.addEventListener('winshirt:mounted', mount);
  ready(mount);
  document.addEventListener('winshirt:sideChanged', (e)=> setSide((e.detail&&e.detail.side)||'front'));

  window.WinShirtCanvas = { setSide, addImage, addText, getZoneRect: currentZoneRect };
  if(!window.WinShirtLayers){ window.WinShirtLayers = { addImage, addText }; }
})();
