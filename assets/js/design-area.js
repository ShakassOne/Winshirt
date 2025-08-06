(function(){
  // Ensure interact.js and localized data are available
  let zone = window.winshirtDesign ? window.winshirtDesign.zone : null;
  const designArea = document.getElementById('design-area');
  if(!designArea || !zone){ return; }

  // apply zone dimensions
  function applyZone(z){
    zone = z;
    designArea.style.width  = z.width + 'px';
    designArea.style.height = z.height + 'px';
    designArea.style.top    = z.top + 'px';
    designArea.style.left   = z.left + 'px';
    initResizable();
  }
  applyZone(zone);

  // listen to size buttons to change zone
  const sizeButtons = document.querySelectorAll('.size-btn');
  sizeButtons.forEach(btn => {
    btn.addEventListener('click', function(){
      sizeButtons.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      const newZone = {
        width: parseInt(this.dataset.width,10),
        height: parseInt(this.dataset.height,10),
        top: parseInt(this.dataset.top,10),
        left: parseInt(this.dataset.left,10)
      };
      applyZone(newZone);
      fitItem();
    });
  });

  const item = designArea.querySelector('.draggable-item');
  const dataInput = document.getElementById('design-coords');
  let coords = { x: 0, y: 0, w: 0, h: 0 };

  function updateData(){
    if(dataInput){ dataInput.value = JSON.stringify(coords); }
  }

  // fit image to zone
  function fitItem(){
    if(!item || !item.naturalWidth){ return; }
    const areaRatio = zone.width / zone.height;
    const imgRatio  = item.naturalWidth / item.naturalHeight;
    if(imgRatio > areaRatio){
      item.style.width  = '100%';
      item.style.height = 'auto';
    } else {
      item.style.width  = 'auto';
      item.style.height = '100%';
    }
    coords.w = item.offsetWidth;
    coords.h = item.offsetHeight;
    coords.x = (zone.width - coords.w) / 2;
    coords.y = (zone.height - coords.h) / 2;
    item.style.transform = `translate(${coords.x}px, ${coords.y}px)`;
    updateData();
  }

  if(item){
    item.addEventListener('load', fitItem);
  }

  // drag & resize with interact.js
  interact(item).draggable({
    modifiers: [
      interact.modifiers.restrictRect({ restriction: designArea, endOnly: true })
    ],
    listeners: {
      move(event){
        coords.x += event.dx;
        coords.y += event.dy;
        event.target.style.transform = `translate(${coords.x}px, ${coords.y}px)`;
        updateData();
      }
    }
  });

  function initResizable(){
    if(!item) return;
    interact(item).resizable({
      edges: { left: true, right: true, bottom: true, top: true },
      modifiers: [
        interact.modifiers.restrictEdges({ outer: designArea }),
        interact.modifiers.restrictSize({
          min: { width: 20, height: 20 },
          max: { width: zone.width, height: zone.height }
        })
      ],
      listeners: {
        move(event){
          coords.x += event.deltaRect.left;
          coords.y += event.deltaRect.top;
          coords.w = event.rect.width;
          coords.h = event.rect.height;
          Object.assign(event.target.style, {
            width: coords.w + 'px',
            height: coords.h + 'px',
            transform: `translate(${coords.x}px, ${coords.y}px)`
          });
          updateData();
        }
      }
    });
  }

  initResizable();

  // listen for external design load
  document.addEventListener('winshirt:load-design', function(e){
    if(!item) return;
    item.src = e.detail.src;
  });
})();
