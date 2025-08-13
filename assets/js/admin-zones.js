(function(){
  'use strict';

  function px(n){ return (Math.round(n*100)/100)+'px'; }
  function clamp(n,min,max){ return Math.max(min, Math.min(max,n)); }

  function boot(){
    const root = document.querySelector('.ws-zone-editor');
    if(!root) return;

    // ===== Colors repeater (in the other metabox) =====
    const colorsWrap = document.querySelector('.ws-colors-table');
    if (colorsWrap){
      const tbody = document.getElementById('ws-colors-rows');
      const addBtn = document.querySelector('.ws-colors-add');
      colorsWrap.addEventListener('click', e=>{
        if(e.target && e.target.classList.contains('ws-colors-del')){
          const tr = e.target.closest('tr');
          if(tr && tbody.children.length > 1) tr.remove();
        }
      });
      if(addBtn){
        addBtn.addEventListener('click', ()=>{
          const tr = document.createElement('tr');
          tr.className = 'ws-color-row';
          tr.innerHTML = `
            <td><input type="text" class="widefat" name="ws_color_label[]" placeholder="Noir"></td>
            <td><input type="text" class="widefat" name="ws_color_hex[]"   placeholder="#000000"></td>
            <td><input type="text" class="widefat" name="ws_color_front[]" placeholder="https://.../recto.png"></td>
            <td><input type="text" class="widefat" name="ws_color_back[]"  placeholder="https://.../verso.png"></td>
            <td><button type="button" class="button ws-colors-del">–</button></td>`;
          tbody.appendChild(tr);
        });
      }
    }

    // ===== Zones editor =====
    const canvas = root.querySelector('#ws-ze-canvas');
    const img = root.querySelector('#ws-ze-img');
    const input = root.querySelector('#ws-ze-data');
    const list = root.querySelector('#ws-ze-list');

    let side = 'front';
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
      // rectangles
      canvas.querySelectorAll('.ws-ze-rect').forEach(n=> n.remove());
      (zones[side]||[]).forEach((z,i)=>{
        const r = toPixelsRect(z);
        const el = document.createElement('div');
        el.className='ws-ze-rect';
        el.style.left=px(r.x); el.style.top=px(r.y); el.style.width=px(r.w); el.style.height=px(r.h);
        el.dataset.index=i;

        // badge with index
        const badge = document.createElement('div');
        badge.className='ws-badge';
        badge.textContent = (z.name||('Zone '+(i+1)));
        el.appendChild(badge);

        const h = document.createElement('div'); h.className='ws-h'; el.appendChild(h);
        makeDraggable(el); makeResizable(el,h);
        canvas.appendChild(el);
      });

      // sidebar list
      list.innerHTML = '';
      (zones[side]||[]).forEach((z,i)=>{
        const row = document.createElement('div');
        row.className = 'ws-ze-row';
        row.innerHTML = `
          <div class="ws-ze-row-title">#${i+1}</div>
          <label>Nom<br><input type="text" class="ws-ze-name" data-i="${i}" value="${(z.name||'').replace(/"/g,'&quot;')}"></label>
          <label>Prix (€)<br><input type="number" min="0" step="0.01" class="ws-ze-price" data-i="${i}" value="${(typeof z.price==='number'?z.price:0)}"></label>
          <button type="button" class="button button-link-delete ws-ze-remove" data-i="${i}">Supprimer</button>
        `;
        list.appendChild(row);
      });
    }

    function makeDraggable(el){
      let sx=0, sy=0, ox=0, oy=0, moving=false;
      el.addEventListener('pointerdown', e=>{
        if(e.target.classList.contains('ws-h')) return;
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
      const p = toPercentRect(rect);
      const z = zones[side][i] || {};
      zones[side][i] = Object.assign({}, z, p); // keep name/price
      save(); render(); // refresh badges positions/text
    }

    // UI: side switch + add/clear
    root.querySelectorAll('.ws-ze-side').forEach(b=>{
      b.addEventListener('click', ()=> setSide(b.getAttribute('data-side')));
    });
    const addBtn = root.querySelector('.ws-ze-add');
    if(addBtn){
      addBtn.addEventListener('click', ()=>{
        const c=canvasRect();
        const w=Math.min( c.width*0.5, 420 ), h=Math.min( c.height*0.5, 420 );
        const rectPct = toPercentRect({ x:(c.width-w)/2, y:(c.height-h)/2, w, h });
        zones[side].push( Object.assign({ name:'', price:0 }, rectPct) );
        save(); render();
      });
    }
    const clearBtn = root.querySelector('.ws-ze-clear');
    if(clearBtn){
      clearBtn.addEventListener('click', ()=>{
        if(!confirm('Supprimer toutes les zones de ce côté ?')) return;
        zones[side] = []; save(); render();
      });
    }

    // Sidebar inputs events
    list.addEventListener('input', e=>{
      if(e.target.classList.contains('ws-ze-name')){
        const i = parseInt(e.target.dataset.i,10);
        if(zones[side] && zones[side][i]) {
          zones[side][i].name = e.target.value;
          save();
          // update badge text quickly
          const rect = canvas.querySelector(`.ws-ze-rect[data-index="${i}"] .ws-badge`);
          if(rect) rect.textContent = zones[side][i].name || ('Zone '+(i+1));
        }
      } else if(e.target.classList.contains('ws-ze-price')){
        const i = parseInt(e.target.dataset.i,10);
        if(zones[side] && zones[side][i]) {
          zones[side][i].price = parseFloat(e.target.value||'0') || 0;
          save();
        }
      }
    });
    list.addEventListener('click', e=>{
      if(e.target.classList.contains('ws-ze-remove')){
        const i = parseInt(e.target.dataset.i,10);
        if(zones[side] && zones[side][i]){
          zones[side].splice(i,1);
          save(); render();
        }
      }
    });

    // init
    setSide('front');
    window.addEventListener('resize', ()=> render(), { passive:true });
  }

  if(document.readyState!=='loading') boot(); else document.addEventListener('DOMContentLoaded', boot);
})();
