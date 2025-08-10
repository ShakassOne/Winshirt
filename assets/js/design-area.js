(function(){
  const designArea = document.getElementById('design-area');
  if(!designArea){ return; }
  let zone = null;
  let ratio = 1;

  function applyZone(z){
    zone = z;
    if(!zone){ return; }
    designArea.style.width = zone.w + 'px';
    designArea.style.height = zone.h + 'px';
    designArea.style.top = zone.y + 'px';
    designArea.style.left = zone.x + 'px';
    placeItem();
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

  function placeItem(){
    if(!item || !zone || !item.naturalWidth){ return; }
    ratio = item.naturalWidth / item.naturalHeight || 1;
    coords.w = zone.w * 0.4;
    coords.h = coords.w / ratio;
    coords.x = (zone.w - coords.w) / 2;
    coords.y = (zone.h - coords.h) / 2;
    Object.assign(item.style, {
      width: coords.w + 'px',
      height: coords.h + 'px',
      transform: `translate(${coords.x}px, ${coords.y}px)`
    });
    updateData();
    initResizable();
  }

  if(item){ item.addEventListener('load', placeItem); }

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
        }),
        interact.modifiers.aspectRatio({ ratio })
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
