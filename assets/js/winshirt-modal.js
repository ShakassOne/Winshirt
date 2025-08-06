jQuery(function($){
  var $modal = $('#winshirt-customizer-modal'),
      $open  = $('#winshirt-open-modal'),
      $close = $('#winshirt-close-modal');

  $open.on('click', function(e){
    e.preventDefault();
    $modal.fadeIn(200);
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
  const toolIcons = document.querySelectorAll('.tool-icon');
  const panels = document.querySelectorAll('.right-sidebar');
  const isMobile = window.matchMedia('(max-width: 768px)').matches;
  let activePanel = null;

  function openPanel(panel){
    if (!panel) return;
    if (isMobile) {
      activePanel = panel;
      panel.classList.add('open');
      document.body.style.overflow = 'hidden';
    } else {
      panels.forEach(p => p.style.display = 'none');
      panel.style.display = 'flex';
    }
  }

  function closeActivePanel(){
    if (!isMobile) return;
    if (activePanel){
      activePanel.classList.remove('open');
      document.body.style.overflow = '';
      activePanel = null;
    }
  }

  if (isMobile) {
    panels.forEach(p => {
      p.classList.add('mobile-panel');
      const closeBtn = document.createElement('button');
      closeBtn.className = 'panel-close';
      closeBtn.innerHTML = '&times;';
      closeBtn.addEventListener('click', closeActivePanel);
      p.prepend(closeBtn);
      let startY = 0;
      p.addEventListener('touchstart', e => { startY = e.touches[0].clientY; });
      p.addEventListener('touchend', e => {
        const dy = e.changedTouches[0].clientY - startY;
        if (dy > 50) closeActivePanel();
      });
    });
  }

  toolIcons.forEach(icon => {
    icon.addEventListener('click', function(){
      toolIcons.forEach(i => i.classList.remove('active'));
      this.classList.add('active');
      const target = this.dataset.target;
      const panel = target ? document.querySelector(target) : null;
      openPanel(panel);
    });
  });

  const viewBtns = document.querySelectorAll('.view-btn');
  const tshirt = document.querySelector('.tshirt');
  viewBtns.forEach(btn => {
    btn.addEventListener('click', function(){
      viewBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      if (tshirt && this.dataset.img) {
        tshirt.style.backgroundImage = `url('${this.dataset.img}')`;
      }
    });
  });

  const filterTabs = document.querySelectorAll('.filter-tab');
  const designItems = document.querySelectorAll('.design-item');
  const designArea  = document.getElementById('design-area');
  const designImg   = document.getElementById('design-item');

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
    document.querySelectorAll('.layer').forEach(l => l.classList.toggle('selected', l.id === id));
    layerItems.forEach(li => li.classList.toggle('active', li.dataset.layer === id));
    const layer = document.getElementById(id);
    if (layer && layerOpacity) {
      layerOpacity.value = Math.round((parseFloat(layer.style.opacity) || 1) * 100);
    } else if(layerOpacity){
      layerOpacity.value = 100;
    }
  }

  function initLayerInteractions($el){
    if(!$el.length) return;
    $el.draggable({ containment: '#design-area' })
       .resizable({
         handles: 'n,e,s,w,ne,se,sw,nw',
         containment: '#design-area'
       })
       .rotatable();
    $el.on('mousedown', function(e){
      e.stopPropagation();
      setActiveLayer(this.id);
    });
  }

  $('#design-area').on('mousedown', function(){
    setActiveLayer(null);
  });

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
    layerDiv.className = 'layer';
    layerDiv.id = id;
    layerDiv.innerHTML = content;
    designArea.appendChild(layerDiv);
    initLayerInteractions($(layerDiv));
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

  designItems.forEach(item => {
    item.addEventListener('click', function(){
      const img = this.dataset.img;
      if (img) {
        if (designImg) designImg.src = img;
        const evt = new CustomEvent('winshirt:load-design', { detail: { src: img } });
        document.dispatchEvent(evt);
        if (isMobile) closeActivePanel();
      }
    });
  });

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

  const uploadBtn = document.querySelector('#image-panel .upload-btn');
  if (uploadBtn) {
    uploadBtn.addEventListener('click', function(){
      alert('Fonctionnalité d\'upload à implémenter');
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
    currentTextLayer.innerHTML = text;
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
      if (isMobile) closeActivePanel();
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
        currentQrLayer.innerHTML = qrPreview.innerHTML;
      }
      if (isMobile) closeActivePanel();
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

  // Size controls
  const sizeBtns = document.querySelectorAll('.size-btn');
  sizeBtns.forEach(btn => {
    btn.addEventListener('click', function(){
      sizeBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      if (designArea) {
        designArea.style.width = this.dataset.width + 'px';
        designArea.style.height = this.dataset.height + 'px';
        designArea.style.top = this.dataset.top + 'px';
        designArea.style.left = this.dataset.left + 'px';
        designArea.style.borderRadius = '20px';
      }
    });
  });
  const defaultSizeBtn = document.querySelector('.size-btn');
  if (defaultSizeBtn) defaultSizeBtn.click();
});
