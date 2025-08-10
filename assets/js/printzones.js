(function(){
  const data = window.WinShirtMockup || {};
  const tshirt = document.getElementById('tshirt');
  const designArea = document.getElementById('design-area');
  const bar = document.getElementById('printzones-bar');
  const viewControls = document.getElementById('view-controls');
  if(!tshirt || !designArea || !bar || !viewControls){ return; }

  let currentSide = 'front';
  const lastZoneByFace = {};
  const natural = {};

  function loadNatural(side){
    return new Promise(resolve => {
      if(natural[side]){ return resolve(natural[side]); }
      const img = new Image();
      img.onload = function(){
        natural[side] = { w: img.naturalWidth, h: img.naturalHeight };
        resolve(natural[side]);
      };
      img.src = data[side] && data[side].image ? data[side].image : '';
    });
  }

  function renderButtons(){
    bar.innerHTML = '';
    const zones = data[currentSide] && data[currentSide].zones ? data[currentSide].zones : {};
    Object.keys(zones).forEach(key => {
      const btn = document.createElement('button');
      btn.className = 'printzones-btn';
      btn.textContent = key.toUpperCase();
      btn.dataset.key = key;
      btn.addEventListener('click', () => applyZone(key));
      bar.appendChild(btn);
    });
  }

  function setActiveButton(key){
    bar.querySelectorAll('.printzones-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.key === key);
    });
  }

  async function applyZone(key){
    const sideData = data[currentSide];
    if(!sideData || !sideData.zones || !sideData.zones[key]){ return; }
    const zone = sideData.zones[key];
    const size = await loadNatural(currentSide);
    if(!size.w || !size.h){ return; }
    const disp = tshirt.getBoundingClientRect();
    const scaleX = disp.width / size.w;
    const scaleY = disp.height / size.h;
    const x = zone.xr * size.w * scaleX;
    const y = zone.yr * size.h * scaleY;
    const w = zone.wr * size.w * scaleX;
    const h = zone.hr * size.h * scaleY;

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
    setActiveButton(key);
  }

  function switchSide(side){
    currentSide = side;
    const sideData = data[side] || {};
    if(sideData.image){
      tshirt.style.backgroundImage = `url('${sideData.image}')`;
    }
    renderButtons();
    const key = lastZoneByFace[side] || Object.keys(sideData.zones || {})[0];
    if(key){ applyZone(key); }
    viewControls.querySelectorAll('.view-btn').forEach(btn => {
      btn.setAttribute('aria-pressed', String(btn.dataset.side === side));
    });
  }

  viewControls.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', function(){
      const side = this.dataset.side;
      if(side && side !== currentSide){ switchSide(side); }
    });
  });

  switchSide(currentSide);
})();

