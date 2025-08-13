(function(){
  'use strict';

  function px(n){ return (Math.round(n*100)/100)+'px'; }
  function clamp(n,min,max){ return Math.max(min, Math.min(max,n)); }

  function boot(){
    const root = document.querySelector('.ws-zone-editor');
    if(!root) return;
    const canvas = root.querySelector('#ws-ze-canvas');
    const img = root.querySelector('#ws-ze-img');
    const input = root.querySelector('#ws-ze-data');
    let side = 'front';

    // charger les zones existantes
    let zones = {};
    try{ zones = JSON.parse(input.value||'{}')||{}; }catch(e){ zones={}; }
    if(!zones.front) zones.front=[];
    if(!zones.back) zones.back=[];

    function setSide(s){
      side = (s==='back'?'back':'front');
      const url = side==='back' ? root.dataset.back : root.dataset.front;
      if(url) img.src = url;
      render();
    }

    function canvasRect(){ return canvas.getBoundingClientRect(); }

    function toPercentRect(abs){
      const c = canvasRect();
      return {
        left  : clamp(abs.x / c.width * 100, 0, 100),
        top   : clamp(abs.y / c.height* 100, 0, 100),
        width : clamp(abs.w / c.width * 100, 0, 100),
        height: clamp(abs.h / c.height*100, 0, 100),
      };
    }
    function toPixelsRect(pct){
      const c = canvasRect();
      return {
        x: pct.left/100*c.width,
        y: pct.top/100*c.height,
        w: pct.width/100*c.width,
        h: pct.height/100*c.height
      };
    }

    function save(){
      input.value = JSON.stringify(zones);
    }

    function render(){
      canvas.querySelectorAll('.ws-ze-rect').forEach(n=> n.remove());
      (zones[side]||[]).forEach((z,i)=>{
        const r = toPixelsRect(z);
        const el = document.createElement('div');
        el.className='ws-ze-rect';
        el.style.left=px(r.x); el.style.top=px(r.y); el.style.width=px(r.w); el.style.height=px(r.h);
        el.dataset.index=i;

        const h = document.createElement('div'); h.className='ws-h'; el.appendChild(h);
        makeDraggable(el); makeResizable(el,h);
        canvas.appendChild(el);
      });
    }

    function makeDraggable(el){
      let sx=0, sy=0, ox=0, oy=0, moving=false;
      el.addEventListener('pointerdown', e=>{
        if(e.target.classList.contains('ws-h')) return; // resize handle
        moving=true; el.setPointerCapture(e.pointerId);
        sx=e.clientX; sy=e.clientY;
        const s=el.style; ox=parseFloat(s.left)||0; oy=parseFloat(s.top)||0;
        e.preventDefault();
      });
      el.addEventListener('pointermove', e=>{
        if(!moving) return;
        const c=canvasRect();
        let nx=ox + (e.clientX-sx);
        let ny=oy + (e.clientY-sy);
        const w=el.offsetWidth, h=el.offsetHeight;
        nx = clamp(nx, 0, c.width - w);
        ny = clamp(ny, 0, c.height- h);
        el.style.left=px(nx); el.style.top=px(ny);
      });
      el.addEventListener('pointerup', e=>{ if(!moving) return; moving=false; commit(el); });
      el.addEventListener('pointercancel', ()=>{ moving=false; });
    }

    function makeResizable(el, handle){
      let sx=0, sy=0, ow=0, oh=0, resizing=false;
      handle.addEventListener('pointerdown', e=>{
        resizing=true; handle.setPointerCapture(e.pointerId);
        sx=e.clientX; sy=e.clientY; ow=el.offsetWidth; oh=el.offsetHeight;
        e.preventDefault();
      });
      handle.addEventListener('pointermove', e=>{
        if(!resizing) return;
        const c=canvasRect();
        let nw = clamp(ow + (e.clientX-sx), 20, c.width - el.offsetLeft);
        let nh = clamp(oh + (e.clientY-sy), 20, c.height - el.offsetTop);
        el.style.width=px(nw); el.style.height=px(nh);
      });
      handle.addEventListener('pointerup', e=>{ if(!resizing) return; resizing=false; commit(el); });
      handle.addEventListener('pointercancel', ()=>{ resizing=false; });
    }

    function commit(el){
      const i=parseInt(el.dataset.index,10);
      const rect = { x: el.offsetLeft, y: el.offsetTop, w: el.offsetWidth, h: el.offsetHeight };
      zones[side][i] = toPercentRect(rect);
      save();
    }

    // UI
    root.querySelectorAll('.ws-ze-side').forEach(b=>{
      b.addEventListener('click', ()=> setSide(b.getAttribute('data-side')));
    });
    const addBtn = root.querySelector('.ws-ze-add');
    if(addBtn){
      addBtn.addEventListener('click', ()=>{
        const c=canvasRect();
        const w=Math.min( c.width*0.5, 420 ), h=Math.min( c.height*0.5, 420 );
        const rectPct = toPercentRect({ x:(c.width-w)/2, y:(c.height-h)/2, w, h });
        zones[side].push(rectPct); save(); render();
      });
    }
    const clearBtn = root.querySelector('.ws-ze-clear');
    if(clearBtn){
      clearBtn.addEventListener('click', ()=>{
        if(!confirm('Supprimer toutes les zones de ce côté ?')) return;
        zones[side] = []; save(); render();
      });
    }

    // init
    setSide('front');
    window.addEventListener('resize', ()=> render(), { passive:true });
  }

  if(document.readyState!=='loading') boot(); else document.addEventListener('DOMContentLoaded', boot);
})();
