(function(){
  const data = window.WinShirtData;
  const tshirt = document.getElementById('tshirt');
  const designArea = document.getElementById('design-area');
  const bar = document.getElementById('printzones-bar');
  const viewControls = document.getElementById('view-controls');
  const container = document.querySelector('.tshirt-container');
  if(!tshirt || !designArea || !bar || !viewControls){ return; }

  if(!data || !data.mockupId){
    if(container){
      const msg = document.createElement('p');
      msg.textContent = 'Pas de mockup associé à ce produit';
      container.appendChild(msg);
    }
    tshirt.style.display = 'none';
    designArea.style.display = 'none';
    bar.style.display = 'none';
    viewControls.style.display = 'none';
    return;
  }

  let currentSide = data.activeSide || 'front';
  const lastZoneByFace = {};
  let currentZoneKey = null;
  const natural = {};

  function loadNatural(side){
    return new Promise(resolve => {
      if(natural[side]){ return resolve(natural[side]); }
      const img = new Image();
      img.onload = function(){
        natural[side] = { w: img.naturalWidth, h: img.naturalHeight };
        resolve(natural[side]);
      };
      img.src = data[side] || '';
    });
  }

  function renderButtons(){
    bar.innerHTML = '';
    const zones = (data.zones && data.zones[currentSide]) ? data.zones[currentSide] : [];
    zones.forEach(z => {
      const btn = document.createElement('button');
      btn.className = 'printzones-btn';
      btn.textContent = z.id;
      btn.dataset.key = z.id;
      btn.addEventListener('click', () => applyZone(z.id));
      bar.appendChild(btn);
    });
    bar.style.display = zones.length ? 'flex' : 'none';
  }

  function setActiveButton(key){
    bar.querySelectorAll('.printzones-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.key === key);
    });
  }

  function applyZone(key){
    const zones = (data.zones && data.zones[currentSide]) ? data.zones[currentSide] : [];
    const zone = zones.find(z => z.id === key);
    if(!zone){
      designArea.style.display = 'none';
      return;
    }
    const disp = tshirt.getBoundingClientRect();
    const x = zone.x * disp.width / 100;
    const y = zone.y * disp.height / 100;
    const w = zone.w * disp.width / 100;
    const h = zone.h * disp.height / 100;

    designArea.style.display = 'block';
    designArea.style.left = Math.round(x) + 'px';
    designArea.style.top = Math.round(y) + 'px';
    designArea.style.width = Math.round(w) + 'px';
    designArea.style.height = Math.round(h) + 'px';

    document.querySelectorAll('#design-area .layer').forEach(layer => {
      const $l = jQuery(layer);
      if($l.data('ui-draggable')){ $l.draggable('option','containment','#design-area'); }
      if($l.data('ui-resizable')){ $l.resizable('option','containment','#design-area'); }
    });

    lastZoneByFace[currentSide] = key;
    currentZoneKey = key;
    setActiveButton(key);
  }

  function switchSide(side){
    currentSide = side;
    const img = data[side] || '';
    tshirt.style.backgroundImage = img ? `url('${img}')` : 'none';
    loadNatural(side).then(size => {
      const ratio = size.w ? (size.h / size.w) : 1;
      tshirt.style.setProperty('--ratio', ratio);
      renderButtons();
      const zones = (data.zones && data.zones[side]) ? data.zones[side] : [];
      if(zones.length){
        const key = lastZoneByFace[side] || zones[0].id;
        applyZone(key);
      } else {
        designArea.style.display = 'none';
        bar.style.display = 'none';
      }
      viewControls.querySelectorAll('.view-btn').forEach(btn => {
        btn.setAttribute('aria-pressed', String(btn.dataset.side === side));
      });
    });
  }

  viewControls.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', function(){
      const side = this.dataset.side;
      if(side && side !== currentSide){ switchSide(side); }
    });
  });

  window.addEventListener('resize', function(){
    if(currentZoneKey){ applyZone(currentZoneKey); }
  });

  switchSide(currentSide);
})();

