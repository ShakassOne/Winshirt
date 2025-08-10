jQuery(function($){
  var $modal = $('#winshirt-customizer-modal'),
      $open  = $('#winshirt-open-modal'),
      $close = $('#winshirt-close-modal');

  $open.on('click', function(e){
    e.preventDefault();
    $modal.fadeIn(200);
    initVisuels();
    setSide('front');
    requestAnimationFrame(()=>requestAnimationFrame(computePrintZone));
    // TODO: ici lancer init de la librairie de personnalisation (canvas/SVG)
  });

  $close.on('click', function(){
    $modal.fadeOut(200);
  });

  // fermer au clic sur l'overlay
  $modal.on('click', function(e){
    if ( e.target === this ) {
      $modal.fadeOut(200);
    }
  });

  // Interactive functionality
  const $toolIcons = $('.tool-icon');
  const $panels = $('.right-sidebar');
  const isMobile = window.matchMedia('(max-width: 768px)').matches;

  function closePanels(){
    $('.mobile-panel').removeClass('open');
    $('body').css('overflow', '');
  }

  if (isMobile) {
    $panels.addClass('mobile-panel').each(function(){
      if(!$(this).find('.panel-close').length){
        $(this).prepend('<button class="panel-close">&times;</button>');
      }
    });
  }

  // Bouton clair pour fermer le volet latéral sur mobile
  $('.panel-close').off('click').on('click', function(){
    $(this).closest('.mobile-panel').removeClass('open');
    $('body').css('overflow', '');
  });

  // Gestion de l'ouverture correcte des panneaux latéraux
  $toolIcons.on('click', function(){
    $toolIcons.removeClass('active');
    $(this).addClass('active');
    let targetPanel = $($(this).data('target'));
    if(isMobile){
      if(targetPanel.hasClass('open')){
        targetPanel.removeClass('open');
        $('body').css('overflow', '');
      } else {
        $('.mobile-panel').removeClass('open');
        targetPanel.addClass('open');
        $('body').css('overflow', 'hidden');
      }
    } else {
      $panels.hide();
      targetPanel.css('display','flex');
    }
  });

  const filterTabs = document.querySelectorAll('.filter-tab');
  const designItems = document.querySelectorAll('.design-item');
  const designArea  = document.getElementById('design-area');
  const mockupImg   = document.getElementById('mockup-img');
  const printZone   = designArea ? designArea.querySelector('.print-zone') : null;
  let currentSide   = 'front';

  function computePrintZone(){
    if(!mockupImg || mockupImg.clientWidth === 0) return;
    const zones = (window.WinShirtData && window.WinShirtData.zones && window.WinShirtData.zones[currentSide]) ? window.WinShirtData.zones[currentSide] : [];
    if(!zones.length || !printZone) return;
    const zone = zones[0];
    const w = mockupImg.clientWidth * zone.width / 100;
    const h = mockupImg.clientHeight * zone.height / 100;
    const x = mockupImg.clientWidth * zone.left / 100;
    const y = mockupImg.clientHeight * zone.top / 100;
    printZone.style.width  = Math.round(w) + 'px';
    printZone.style.height = Math.round(h) + 'px';
    printZone.style.left   = Math.round(x) + 'px';
    printZone.style.top    = Math.round(y) + 'px';
  }

  function setSide(side){
    if(!mockupImg || !window.WinShirtData) return;
    currentSide = side;
    mockupImg.src = window.WinShirtData[side] || '';
  }

  if(mockupImg){
    mockupImg.addEventListener('load', computePrintZone);
    if(mockupImg.complete){ computePrintZone(); }
    const ro = new ResizeObserver(computePrintZone);
    if(mockupImg.parentElement){ ro.observe(mockupImg.parentElement); }
  }

  const viewBtns = document.querySelectorAll('.view-btn');
  viewBtns.forEach(btn => {
    btn.addEventListener('click', function(){
      const side = this.dataset.side;
      viewBtns.forEach(b => b.setAttribute('aria-pressed', String(b === this)));
      setSide(side);
      requestAnimationFrame(()=>requestAnimationFrame(computePrintZone));
    });
  });

  filterTabs.forEach(tab => {
    tab.addEventListener('click', function(){
      filterTabs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      const term = this.dataset.term;
      designItems.forEach(item => {
        const terms = item.dataset.terms ? item.dataset.terms.split(' ') : [];
        if (!term || term === 'all' || terms.includes(term)) {
          item.style.display = 'flex';
        } else {
          item.style.display = 'none';
        }
      });
    });
  });

  let activeLayerId = null;

  function setActiveLayer(id){
    activeLayerId = id;
    document.querySelectorAll('.design-element').forEach(l => l.classList.toggle('selected', l.id === id));
    layerItems.forEach(li => li.classList.toggle('active', li.dataset.layer === id));
    const layer = document.getElementById(id);
    if (layer && layerOpacity) {
      layerOpacity.value = Math.round((parseFloat(layer.style.opacity) || 1) * 100);
    } else if(layerOpacity){
      layerOpacity.value = 100;
    }
  }

  if(designArea){
    designArea.addEventListener('mousedown', function(e){
      const el = e.target.closest('.design-element');
      if(el){
        setActiveLayer(el.id);
      } else {
        setActiveLayer(null);
      }
    });
  }

  function addLayerItemListeners(li, layerDiv){
    li.addEventListener('click', function(){ setActiveLayer(layerDiv.id); });
    const visBtn = li.querySelector('.layer-vis');
    const lockBtn = li.querySelector('.layer-lock');
    const delBtn = li.querySelector('.layer-del');
    if (visBtn) visBtn.addEventListener('click', function(e){ e.stopPropagation(); layerDiv.style.display = layerDiv.style.display === 'none' ? 'flex' : 'none'; });
    if (lockBtn) lockBtn.addEventListener('click', function(e){ e.stopPropagation(); layerDiv.style.pointerEvents = layerDiv.style.pointerEvents === 'none' ? 'auto' : 'none'; });
    if (delBtn) delBtn.addEventListener('click', function(e){ e.stopPropagation(); layerDiv.remove(); li.remove(); refreshLayerItems(); setActiveLayer(null); });
  }

  const layersList = document.getElementById('layers-list');
  let layerItems = document.querySelectorAll('.layer-item');
  const layerOpacity = document.getElementById('layer-opacity');
  const posBtns = document.querySelectorAll('.pos-btn');
  const newLayerBtn = document.getElementById('new-layer-btn');

  function refreshLayerItems(){
    layerItems = document.querySelectorAll('.layer-item');
  }

  function createLayer(name, content){
    const id = 'layer-' + Date.now();
    const layerDiv = document.createElement('div');
    layerDiv.className = 'design-element';
    layerDiv.id = id;
    layerDiv.innerHTML = `<div class="content">${content}</div><div class="handle" data-handle="resize-locked"></div><div class="handle" data-handle="resize-free"></div><div class="handle" data-handle="rotate"></div><div class="handle" data-handle="delete"></div>`;
    designArea.appendChild(layerDiv);
    const li = document.createElement('li');
    li.className = 'layer-item';
    li.dataset.layer = id;
    li.innerHTML = `<span class="layer-name">${name}</span><div class="layer-actions"><button class="layer-vis">👁️</button><button class="layer-lock">🔒</button><button class="layer-del">🗑️</button></div>`;
    layersList.appendChild(li);
    addLayerItemListeners(li, layerDiv);
    refreshLayerItems();
    setActiveLayer(id);
    return layerDiv;
  }

  // Initialisation du visuel interactif
  function initVisuels() {
    $('.design-item').off('click').on('click', function(){
      const url = this.dataset.full || this.dataset.img;
      const layerDiv = createLayer('Design', `<img src="${url}" alt="" />`);
      layerDiv.style.width = '120px';
      layerDiv.style.height = '120px';
      const daRect = designArea.getBoundingClientRect();
      layerDiv.style.left = Math.max((daRect.width - 120) / 2, 0) + 'px';
      layerDiv.style.top = Math.max((daRect.height - 120) / 2, 0) + 'px';
      const img = layerDiv.querySelector('img');
      if(img){
        img.style.objectFit = 'contain';
        img.style.pointerEvents = 'none';
      }
      if (isMobile) closePanels();
    });
  }

  if (layerOpacity) {
    layerOpacity.addEventListener('input', function(){
      const layer = document.getElementById(activeLayerId);
      if (layer) layer.style.opacity = this.value / 100;
    });
  }

  posBtns.forEach(btn => {
    btn.addEventListener('click', function(){
      const layer = document.getElementById(activeLayerId);
      if (!layer) return;
      const pos = this.dataset.pos;
      const v = pos.charAt(0);
      const h = pos.charAt(1);
      layer.style.alignItems = v === 't' ? 'flex-start' : v === 'c' ? 'center' : 'flex-end';
      layer.style.justifyContent = h === 'l' ? 'flex-start' : h === 'c' ? 'center' : 'flex-end';
    });
  });

  if (newLayerBtn) {
    newLayerBtn.addEventListener('click', function(){
      createLayer('Nouveau calque', '');
    });
  }

  const uploadBtn = document.getElementById('upload-btn');
  if (uploadBtn) {
    uploadBtn.addEventListener('click', function(){
      alert('Fonctionnalité d\'upload en cours de développement');
    });
  }

  const textInput = document.getElementById('text-input');
  const fontSelect = document.getElementById('font-select');
  const fontSize = document.getElementById('font-size');
  const sizeValue = document.getElementById('size-value');
  const colorOptions = document.querySelectorAll('.color-option');
  const styleBtns = document.querySelectorAll('.style-btn');
  const addTextBtn = document.getElementById('add-text-btn');
  const mockupColors = document.querySelectorAll('.color-btn');

  let currentTextStyle = {
    text: '',
    font: 'Arial',
    size: 48,
    color: '#000000',
    bold: false,
    italic: false,
    underline: false
  };

  let currentTextLayer = null;

  function updateTextPreview(){
    if (!currentTextLayer) return;
    const text = textInput.value || 'Exemple de texte';
    const fontWeight = currentTextStyle.bold ? 'bold' : 'normal';
    const fontStyle = currentTextStyle.italic ? 'italic' : 'normal';
    const textDecoration = currentTextStyle.underline ? 'underline' : 'none';
    const textContent = currentTextLayer.querySelector('.content');
    if(textContent){ textContent.textContent = text; }
    currentTextLayer.style.fontSize = currentTextStyle.size + 'px';
    currentTextLayer.style.color = currentTextStyle.color;
    currentTextLayer.style.fontFamily = fontSelect.value;
    currentTextLayer.style.fontWeight = fontWeight;
    currentTextLayer.style.fontStyle = fontStyle;
    currentTextLayer.style.textDecoration = textDecoration;
    currentTextLayer.style.display = text ? 'flex' : 'none';
  }

  if (fontSize) {
    fontSize.addEventListener('input', function(){
      currentTextStyle.size = this.value;
      sizeValue.textContent = this.value;
      updateTextPreview();
    });
  }

  colorOptions.forEach(option => {
    option.addEventListener('click', function(){
      colorOptions.forEach(c => c.classList.remove('active'));
      this.classList.add('active');
      currentTextStyle.color = this.dataset.color;
      updateTextPreview();
    });
  });
  mockupColors.forEach(btn => {
    btn.addEventListener('click', function(){
      mockupColors.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
    });
  });

  styleBtns.forEach(btn => {
    btn.addEventListener('click', function(){
      this.classList.toggle('active');
      if (btn.id === 'bold-btn') {
        currentTextStyle.bold = !currentTextStyle.bold;
      } else if (btn.id === 'italic-btn') {
        currentTextStyle.italic = !currentTextStyle.italic;
      } else if (btn.id === 'underline-btn') {
        currentTextStyle.underline = !currentTextStyle.underline;
      }
      updateTextPreview();
    });
  });

  if (addTextBtn) {
    addTextBtn.addEventListener('click', function(){
      if (!currentTextLayer) {
        currentTextLayer = createLayer('Texte', '');
      }
      updateTextPreview();
      if (isMobile) closePanels();
    });
  }

  if (textInput) {
    textInput.addEventListener('input', function(){
      currentTextStyle.text = this.value;
      updateTextPreview();
    });
  }

  if (fontSelect) {
    fontSelect.addEventListener('change', function(){
      currentTextStyle.font = this.value;
      updateTextPreview();
    });
  }

  // Product panel
  const productType = document.getElementById('product-type');
  const productMaterial = document.getElementById('product-material');
  const productPrice = document.getElementById('product-price');
  const addToCartBtn = document.getElementById('add-to-cart-btn');

  function updatePrice(){
    let price = 20; // base price
    if (productType) {
      switch(productType.value){
        case 'hoodie': price += 10; break;
        case 'debardeur': price += 2; break;
        case 'polo': price += 5; break;
        case 'casquette': price += 3; break;
        case 'sac': price += 4; break;
      }
    }
    if (productMaterial) {
      switch(productMaterial.value){
        case 'mix': price += 2; break;
        case 'bio': price += 3; break;
        case 'premium': price += 5; break;
      }
    }
    if (productPrice) productPrice.textContent = price.toFixed(2) + '€';
  }

  if (productType) productType.addEventListener('change', updatePrice);
  if (productMaterial) productMaterial.addEventListener('change', updatePrice);

  if (addToCartBtn) {
    addToCartBtn.addEventListener('click', function(){
      alert('Produit ajouté au panier (simulation).');
    });
  }

  // QR Code panel
  let qrDataInput;
  const qrType = document.getElementById('qr-type');
  const qrSize = document.getElementById('qr-size');
  const qrColor = document.getElementById('qr-color');
  const qrPreview = document.getElementById('qr-preview');
  const applyQrBtn = document.getElementById('apply-qr-btn');
  const qrInputWrapper = document.getElementById('qr-input-wrapper');
  let currentQrLayer = null;

  function hexToRgb(hex){
    const bigint = parseInt(hex.slice(1), 16);
    const r = (bigint >> 16) & 255;
    const g = (bigint >> 8) & 255;
    const b = bigint & 255;
    return r + '-' + g + '-' + b;
  }

  function getQrData(){
    if (!qrType) return '';
    if (qrType.value === 'vcard') {
      const name = document.getElementById('qr-name')?.value || '';
      const phone = document.getElementById('qr-phone')?.value || '';
      const mail = document.getElementById('qr-mail')?.value || '';
      return `BEGIN:VCARD\nVERSION:3.0\nN:${name}\nTEL:${phone}\nEMAIL:${mail}\nEND:VCARD`;
    }
    return qrDataInput && qrDataInput.value ? qrDataInput.value : '';
  }

  function updateQRPreview(){
    if (!qrPreview) return;
    const size = qrSize.value;
    if (qrType.value === 'image') {
      const file = qrDataInput && qrDataInput.files ? qrDataInput.files[0] : null;
      if (file) {
        const reader = new FileReader();
        reader.onload = e => {
          qrPreview.innerHTML = `<img src="${e.target.result}" width="${size}" height="${size}" />`;
        };
        reader.readAsDataURL(file);
      } else {
        qrPreview.innerHTML = '';
      }
      return;
    }
    const data = getQrData() || ' ';
    const color = hexToRgb(qrColor.value);
    const url = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&color=${color}&data=${encodeURIComponent(data)}`;
    qrPreview.innerHTML = `<img src="${url}" width="${size}" height="${size}" />`;
  }

  function buildQrFields(){
    if (!qrInputWrapper) return;
    qrInputWrapper.innerHTML = '';
    if (qrType.value === 'url') {
      qrInputWrapper.innerHTML = '<label>URL :</label><input type="url" id="qr-data" style="width:100%; padding:10px; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:15px;" />';
    } else if (qrType.value === 'text') {
      qrInputWrapper.innerHTML = '<label>Texte :</label><textarea id="qr-data" style="width:100%; padding:10px; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:15px;"></textarea>';
    } else if (qrType.value === 'vcard') {
      qrInputWrapper.innerHTML = '<label>Nom :</label><input type="text" id="qr-name" style="width:100%; padding:10px; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:10px;" /><label>Téléphone :</label><input type="tel" id="qr-phone" style="width:100%; padding:10px; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:10px;" /><label>Email :</label><input type="email" id="qr-mail" style="width:100%; padding:10px; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:10px;" />';
    } else if (qrType.value === 'image') {
      qrInputWrapper.innerHTML = '<label>Image :</label><input type="file" id="qr-data" accept="image/*" style="margin-bottom:15px;" />';
    }
    qrDataInput = document.getElementById('qr-data');
    const inputs = qrInputWrapper.querySelectorAll('input,textarea');
    inputs.forEach(inp => inp.addEventListener('input', updateQRPreview));
    if (qrType.value === 'image' && qrDataInput) {
      qrDataInput.addEventListener('change', updateQRPreview);
    }
    updateQRPreview();
  }

  if (qrType) {
    qrType.addEventListener('change', buildQrFields);
    buildQrFields();
  }

  if (qrSize) qrSize.addEventListener('input', updateQRPreview);
  if (qrColor) qrColor.addEventListener('input', updateQRPreview);

  if (applyQrBtn) {
    applyQrBtn.addEventListener('click', function(){
      if (!currentQrLayer) {
        currentQrLayer = createLayer('QR Code', qrPreview.innerHTML);
      } else {
        const qrContent = currentQrLayer.querySelector('.content');
        if(qrContent){ qrContent.innerHTML = qrPreview.innerHTML; }
      }
      if (isMobile) closePanels();
    });
  }

  // AI panel
  const aiDescription = document.getElementById('ai-description');
  const aiExamples = document.querySelectorAll('.ai-example');
  const aiGenerateBtn = document.getElementById('ai-generate-btn');
  const aiStatus = document.getElementById('ai-status');
  const aiResults = document.getElementById('ai-results');

  aiExamples.forEach(btn => {
    btn.addEventListener('click', function(){
      if (aiDescription) aiDescription.value = this.dataset.prompt;
    });
  });

  if (aiGenerateBtn) {
    aiGenerateBtn.addEventListener('click', function(){
      if (aiStatus) aiStatus.textContent = 'Génération...';
      setTimeout(function(){
        if (aiStatus) aiStatus.textContent = 'Résultats';
        if (aiResults) {
          aiResults.innerHTML = '';
          for (let i=0; i<9; i++) {
            const img = document.createElement('div');
            img.className = 'design-item';
            img.innerHTML = `<img src="https://via.placeholder.com/150?text=IA" alt="AI" />`;
            img.addEventListener('click', function(){
              createLayer('Image', this.innerHTML);
            });
            aiResults.appendChild(img);
          }
        }
      }, 2000);
    });
  }

});

// ===================== WINSHIRT START: Manipulation éléments =====================
// Config selectors (adapte si besoin)
const WS_CFG = {
  stage:      '#design-area',         // conteneur où se déplacent les éléments
  clampZone:  '.print-zone',          // zone d'impression (doit exister)
  elementSel: '.design-element',      // éléments manipulables
  handleSel:  '.handle',              // poignées
  ratioHandle:'[data-handle="resize-locked"]',
  freeHandle: '[data-handle="resize-free"]',
  rotHandle:  '[data-handle="rotate"]',
  delHandle:  '[data-handle="delete"]'
};

(function WinshirtManipulator(){
  const stage = document.querySelector(WS_CFG.stage);
  if(!stage){ console.error('WinShirt: stage introuvable'); return; }

  let selected = null;
  let dragState = null;
  let moveListenersAttached = false;

  // Utilitaires
  const getClampRect = () => {
    const clampEl = stage.querySelector(WS_CFG.clampZone) || stage;
    const sRect = stage.getBoundingClientRect();
    const cRect = clampEl.getBoundingClientRect();
    // coords clamp en repère stage
    return {
      left:  cRect.left - sRect.left,
      top:   cRect.top - sRect.top,
      right: (cRect.right - sRect.left),
      bottom:(cRect.bottom - sRect.top),
      width: cRect.width,
      height:cRect.height
    };
  };

  const px = (n)=>`${Math.round(n)}px`;

  const bringToFront = (el)=>{
    let maxZ = 1;
    stage.querySelectorAll(WS_CFG.elementSel).forEach(d=>{
      const z = parseInt(window.getComputedStyle(d).zIndex||1,10);
      if(z>maxZ) maxZ=z;
    });
    el.style.zIndex = String(maxZ+1);
  };

  // Sélection
  function select(el){
    stage.querySelectorAll(WS_CFG.elementSel).forEach(d=>d.classList.remove('selected'));
    el.classList.add('selected');
    selected = el;
    bringToFront(el);
  }

  // DRAG
  function startDrag(ev, el){
    ev.preventDefault(); ev.stopPropagation();
    select(el);

    const sRect = stage.getBoundingClientRect();
    const rect  = el.getBoundingClientRect();
    const clamp = getClampRect();

    const start = pointFromEvent(ev);
    dragState = {
      mode:'move',
      el,
      offsetX: start.x - (rect.left - sRect.left),
      offsetY: start.y - (rect.top  - sRect.top),
      clamp
    };
    attachMoveListeners();
  }

  // ROTATE
  function startRotate(ev, el){
    ev.preventDefault(); ev.stopPropagation();
    select(el);
    const sRect = stage.getBoundingClientRect();
    const r = el.getBoundingClientRect();
    const center = { x: (r.left - sRect.left) + r.width/2, y: (r.top - sRect.top) + r.height/2 };
    dragState = { mode:'rotate', el, center };
    attachMoveListeners();
  }

  // RESIZE
  function startResize(ev, el, lockAspect){
    ev.preventDefault(); ev.stopPropagation();
    select(el);

    const rect = el.getBoundingClientRect();
    const sRect = stage.getBoundingClientRect();
    const clamp = getClampRect();

    dragState = {
      mode:'resize',
      el,
      start: pointFromEvent(ev),
      startW: rect.width,
      startH: rect.height,
      startLeft: rect.left - sRect.left,
      startTop:  rect.top  - sRect.top,
      ratio: rect.width / rect.height,
      lock: !!lockAspect,
      clamp
    };
    attachMoveListeners();
  }

  function deleteElement(el){
    if(el && el.parentNode) el.parentNode.removeChild(el);
    selected = null;
  }

  // Mouvements
  function onPointerMove(ev){
    if(!dragState) return;
    const p = pointFromEvent(ev);
    switch(dragState.mode){
      case 'move': doMove(p); break;
      case 'rotate': doRotate(p); break;
      case 'resize': doResize(p); break;
    }
  }

  function doMove(p){
    const {el, offsetX, offsetY, clamp} = dragState;
    const w = el.offsetWidth, h = el.offsetHeight;

    let left = p.x - offsetX;
    let top  = p.y - offsetY;

    left = Math.max(clamp.left, Math.min(left, clamp.right - w));
    top  = Math.max(clamp.top,  Math.min(top,  clamp.bottom - h));

    el.style.left = px(left);
    el.style.top  = px(top);
  }

  function doRotate(p){
    const {el, center} = dragState;
    const dx = p.x - center.x;
    const dy = p.y - center.y;
    const angle = Math.atan2(dy, dx) * 180 / Math.PI + 90; // aligné avec ta préview
    el.style.transform = `rotate(${angle}deg)`;
    el.dataset.angle = String(angle);
  }

  function doResize(p){
    const s = dragState;
    let dX = p.x - s.start.x;
    let dY = p.y - s.start.y;

    // lock ratio = on prend le delta le plus fort et on impose le ratio
    let newW = s.startW + (s.lock ? Math.max(dX, dY) : dX);
    let newH = s.lock ? (newW / s.ratio) : (s.startH + dY);

    newW = Math.max(30, newW);
    newH = Math.max(30, newH);

    // clamp: si on dépasse la zone, on réduit
    const maxW = s.clamp.right  - s.startLeft;
    const maxH = s.clamp.bottom - s.startTop;
    newW = Math.min(newW, maxW);
    newH = Math.min(newH, maxH);

    s.el.style.width  = px(newW);
    s.el.style.height = px(newH);
  }

  function onPointerUp(){
    detachMoveListeners();
    dragState = null;
  }

  function pointFromEvent(ev){
    const sRect = stage.getBoundingClientRect();
    const e = ('touches' in ev && ev.touches.length) ? ev.touches[0] : ev;
    return { x: e.clientX - sRect.left, y: e.clientY - sRect.top };
  }

  function attachMoveListeners(){
    if(moveListenersAttached) return;
    moveListenersAttached = true;
    window.addEventListener('mousemove', onPointerMove);
    window.addEventListener('mouseup', onPointerUp);
    window.addEventListener('touchmove', onPointerMove, {passive:false});
    window.addEventListener('touchend', onPointerUp);
  }
  function detachMoveListeners(){
    if(!moveListenersAttached) return;
    moveListenersAttached = false;
    window.removeEventListener('mousemove', onPointerMove);
    window.removeEventListener('mouseup', onPointerUp);
    window.removeEventListener('touchmove', onPointerMove);
    window.removeEventListener('touchend', onPointerUp);
  }

  // Délégation d’événements (compatible contenus dynamiques)
  stage.addEventListener('mousedown', (e)=>{
    const el = e.target.closest(WS_CFG.elementSel);
    if(!el) return;
    const handle = e.target.closest(WS_CFG.handleSel);
    if(!handle){
      startDrag(e, el);
    }else{
      const h = handle.matches(WS_CFG.rotHandle)  ? 'rotate'
              : handle.matches(WS_CFG.delHandle)  ? 'delete'
              : handle.matches(WS_CFG.ratioHandle)? 'resize-locked'
              : 'resize-free';
      if(h==='delete')      deleteElement(el);
      else if(h==='rotate') startRotate(e, el);
      else startResize(e, el, h==='resize-locked');
    }
  });

  stage.addEventListener('touchstart', (e)=>{
    const el = e.target.closest(WS_CFG.elementSel);
    if(!el) return;
    const handle = e.target.closest(WS_CFG.handleSel);
    if(!handle){
      startDrag(e, el);
    }else{
      const h = handle.matches(WS_CFG.rotHandle)  ? 'rotate'
              : handle.matches(WS_CFG.delHandle)  ? 'delete'
              : handle.matches(WS_CFG.ratioHandle)? 'resize-locked'
              : 'resize-free';
      if(h==='delete')      deleteElement(el);
      else if(h==='rotate') startRotate(e, el);
      else startResize(e, el, h==='resize-locked');
    }
  }, {passive:false});

  // API minimale si besoin ailleurs
  window.WinshirtManip = {
    select, getSelection:()=>selected,
    recomputeClamp: ()=>getClampRect()
  };
})();
// ===================== WINSHIRT END =====================
