(function(){
  const designArea = document.getElementById('design-area');
  if(!designArea){ return; }
  let zone = null;

  function applyZone(z){
    zone = z;
    if(!zone){ return; }
    designArea.style.width = zone.w + 'px';
    designArea.style.height = zone.h + 'px';
    designArea.style.top = zone.y + 'px';
    designArea.style.left = zone.x + 'px';
    initResizable();
    fitItem();
  }

  document.addEventListener('winshirt:zone-change', function(e){
    const box = e.detail && e.detail.box;
    if(box){ applyZone(box); }
  });

  const item = designArea.querySelector('.draggable-item');
  const dataInput = document.getElementById('design-coords');
  let coords = { x: 0, y: 0, w: 0, h: 0 };

  function updateData(){
    if(dataInput){ dataInput.value = JSON.stringify(coords); }
  }

  function fitItem(){
    if(!item || !zone || !item.naturalWidth){ return; }
    const areaRatio = zone.w / zone.h;
    const imgRatio  = item.naturalWidth / item.naturalHeight;
    if(imgRatio > areaRatio){
      item.style.width = '100%';
      item.style.height = 'auto';
    } else {
      item.style.width = 'auto';
      item.style.height = '100%';
    }
    coords.w = item.offsetWidth;
    coords.h = item.offsetHeight;
    coords.x = (zone.w - coords.w) / 2;
    coords.y = (zone.h - coords.h) / 2;
    item.style.transform = `translate(${coords.x}px, ${coords.y}px)`;
    updateData();
  }

  if(item){ item.addEventListener('load', fitItem); }

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
    if(!item || !zone){ return; }
    interact(item).resizable({
      edges: { left:true, right:true, bottom:true, top:true },
      modifiers: [
        interact.modifiers.restrictEdges({ outer: designArea }),
        interact.modifiers.restrictSize({
          min: { width:20, height:20 },
          max: { width: zone.w, height: zone.h }
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

  document.addEventListener('winshirt:load-design', function(e){
    if(!item) return;
    item.src = e.detail.src;
  });
})();
