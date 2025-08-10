(function(){
  const cfg = window.WINSHIRT_CONFIG || {};
  const tshirt = document.getElementById('tshirt');
  const designArea = document.getElementById('design-area');
  const bar = document.getElementById('printzones-bar');
  const viewControls = document.getElementById('view-controls');
  if(!tshirt || !designArea || !bar || !viewControls){ return; }

  let state = {
    side: (sessionStorage.getItem('ws_side') || cfg.activeSide || 'front'),
    zoneKey: sessionStorage.getItem('ws_zoneKey') || null
  };

  function applySide(side){
    const sideCfg = cfg.sides && cfg.sides[side];
    if(!sideCfg){ console.warn('Side config missing:', side); return; }
    state.side = side;
    sessionStorage.setItem('ws_side', side);

    if(sideCfg.image){
      tshirt.style.backgroundImage = `url('${sideCfg.image}')`;
    }

    renderZoneButtons(sideCfg.zones || []);

    const defaultZone = resolveDefaultZone(sideCfg.zones);
    if(defaultZone){
      applyZone(defaultZone);
    } else {
      designArea.style.width = '0px';
      designArea.style.height = '0px';
      designArea.style.left = '0px';
      designArea.style.top = '0px';
      bar.innerHTML += '<div class="printzones-empty">Aucune zone définie pour cette face.</div>';
    }

    viewControls.querySelectorAll('.view-btn').forEach(btn => {
      btn.setAttribute('aria-pressed', String(btn.dataset.side === side));
    });
  }

  function resolveDefaultZone(zones){
    if(!zones || !zones.length){ return null; }
    if(state.zoneKey){
      const z = zones.find(z => z.key === state.zoneKey);
      if(z){ return z; }
    }
    return zones[0];
  }

  function renderZoneButtons(zones){
    bar.innerHTML = '';
    if(!zones.length){ return; }
    zones.forEach(z => {
      const btn = document.createElement('button');
      btn.className = 'zone-btn';
      btn.type = 'button';
      btn.role = 'tab';
      btn.dataset.key = z.key;
      btn.textContent = z.label || z.key;
      btn.setAttribute('aria-pressed','false');
      btn.addEventListener('click', () => applyZone(z));
      bar.appendChild(btn);
    });
    if(state.zoneKey){
      const active = bar.querySelector(`.zone-btn[data-key="${state.zoneKey}"]`);
      if(active){ setActiveZoneButton(active); }
    }
  }

  function setActiveZoneButton(btn){
    bar.querySelectorAll('.zone-btn').forEach(b => {
      b.classList.remove('active');
      b.setAttribute('aria-pressed','false');
    });
    btn.classList.add('active');
    btn.setAttribute('aria-pressed','true');
  }

  function applyZone(zone){
    if(!zone){ return; }
    designArea.style.width = zone.w + 'px';
    designArea.style.height = zone.h + 'px';
    designArea.style.left = zone.x + 'px';
    designArea.style.top = zone.y + 'px';

    const btn = bar.querySelector(`.zone-btn[data-key="${zone.key}"]`);
    if(btn){ setActiveZoneButton(btn); }

    state.zoneKey = zone.key;
    sessionStorage.setItem('ws_zoneKey', zone.key);

    document.dispatchEvent(new CustomEvent('winshirt:zone-change', {
      detail: { side: state.side, key: zone.key, box: { w: zone.w, h: zone.h, x: zone.x, y: zone.y } }
    }));
  }

  viewControls.addEventListener('click', e => {
    const btn = e.target.closest('.view-btn');
    if(!btn){ return; }
    const side = btn.dataset.side;
    if(side && side !== state.side){
      const zones = cfg.sides && cfg.sides[side] ? cfg.sides[side].zones || [] : [];
      if(!zones.find(z => z.key === state.zoneKey)){
        state.zoneKey = null;
        sessionStorage.removeItem('ws_zoneKey');
      }
      applySide(side);
    }
  });

  applySide(state.side);
})();
