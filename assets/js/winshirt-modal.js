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

  toolIcons.forEach(icon => {
    icon.addEventListener('click', function(){
      toolIcons.forEach(i => i.classList.remove('active'));
      this.classList.add('active');
      panels.forEach(p => p.style.display = 'none');
      const target = this.dataset.target;
      if (target) {
        const panel = document.querySelector(target);
        if (panel) panel.style.display = 'flex';
      }
    });
  });

  const viewBtns = document.querySelectorAll('.view-btn');
  viewBtns.forEach(btn => {
    btn.addEventListener('click', function(){
      viewBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
    });
  });

  const filterTabs = document.querySelectorAll('.filter-tab');
  filterTabs.forEach(tab => {
    tab.addEventListener('click', function(){
      filterTabs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
    });
  });

  const designItems = document.querySelectorAll('.design-item');
  const baseLayer = document.getElementById('layer-design');
  designItems.forEach(item => {
    item.addEventListener('click', function(){
      baseLayer.innerHTML = this.innerHTML;
      baseLayer.style.fontSize = '200px';
      baseLayer.style.color = '#333';
      baseLayer.style.display = 'flex';
    });
  });

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

  let currentTextStyle = {
    text: '',
    font: 'Arial',
    size: 48,
    color: '#000000',
    bold: false,
    italic: false,
    underline: false
  };

  function updateTextPreview(){
    const layer = document.getElementById('layer-text');
    const text = textInput.value || 'Exemple de texte';
    const fontWeight = currentTextStyle.bold ? 'bold' : 'normal';
    const fontStyle = currentTextStyle.italic ? 'italic' : 'normal';
    const textDecoration = currentTextStyle.underline ? 'underline' : 'none';
    layer.innerHTML = text;
    layer.style.fontSize = currentTextStyle.size + 'px';
    layer.style.color = currentTextStyle.color;
    layer.style.fontFamily = fontSelect.value;
    layer.style.fontWeight = fontWeight;
    layer.style.fontStyle = fontStyle;
    layer.style.textDecoration = textDecoration;
    layer.style.display = text ? 'flex' : 'none';
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
      updateTextPreview();
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

  // Layers panel
  const layersList = document.getElementById('layers-list');
  let layerItems = document.querySelectorAll('.layer-item');
  const layerOpacity = document.getElementById('layer-opacity');
  const posBtns = document.querySelectorAll('.pos-btn');
  const newLayerBtn = document.getElementById('new-layer-btn');
  let activeLayerId = 'layer-design';

  function setActiveLayer(id){
    activeLayerId = id;
    layerItems.forEach(li => li.classList.toggle('active', li.dataset.layer === id));
    const layer = document.getElementById(id);
    if (layer && layerOpacity) {
      layerOpacity.value = Math.round((parseFloat(layer.style.opacity) || 1) * 100);
    }
  }

  layerItems.forEach(li => {
    li.addEventListener('click', function(){ setActiveLayer(this.dataset.layer); });
    const layerId = li.dataset.layer;
    const layer = document.getElementById(layerId);
    const visBtn = li.querySelector('.layer-vis');
    const lockBtn = li.querySelector('.layer-lock');
    const delBtn = li.querySelector('.layer-del');
    if (visBtn) visBtn.addEventListener('click', function(e){ e.stopPropagation(); layer.style.display = layer.style.display === 'none' ? 'flex' : 'none'; });
    if (lockBtn) lockBtn.addEventListener('click', function(e){ e.stopPropagation(); layer.style.pointerEvents = layer.style.pointerEvents === 'none' ? 'auto' : 'none'; });
    if (delBtn) delBtn.addEventListener('click', function(e){ e.stopPropagation(); layer.innerHTML = ''; layer.style.display = 'none'; });
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
      const v = pos.charAt(0); // t,c,b
      const h = pos.charAt(1); // l,c,r
      layer.style.alignItems = v === 't' ? 'flex-start' : v === 'c' ? 'center' : 'flex-end';
      layer.style.justifyContent = h === 'l' ? 'flex-start' : h === 'c' ? 'center' : 'flex-end';
    });
  });

  if (newLayerBtn) {
    newLayerBtn.addEventListener('click', function(){
      const id = 'layer-custom-' + Date.now();
      const layerDiv = document.createElement('div');
      layerDiv.className = 'layer';
      layerDiv.id = id;
      document.getElementById('design-area').appendChild(layerDiv);
      const li = document.createElement('li');
      li.className = 'layer-item';
      li.dataset.layer = id;
      li.innerHTML = '<span class="layer-name">Nouveau calque</span><div class="layer-actions"><button class="layer-vis">👁️</button><button class="layer-lock">🔒</button><button class="layer-del">🗑️</button></div>';
      layersList.appendChild(li);
      layerItems = document.querySelectorAll('.layer-item');
      li.addEventListener('click', function(){ setActiveLayer(id); });
      const visBtn = li.querySelector('.layer-vis');
      const lockBtn = li.querySelector('.layer-lock');
      const delBtn = li.querySelector('.layer-del');
      visBtn.addEventListener('click', function(e){ e.stopPropagation(); layerDiv.style.display = layerDiv.style.display === 'none' ? 'flex' : 'none'; });
      lockBtn.addEventListener('click', function(e){ e.stopPropagation(); layerDiv.style.pointerEvents = layerDiv.style.pointerEvents === 'none' ? 'auto' : 'none'; });
      delBtn.addEventListener('click', function(e){ e.stopPropagation(); layerDiv.remove(); li.remove(); });
    });
  }

  setActiveLayer('layer-design');

  // QR Code panel
  const qrType = document.getElementById('qr-type');
  const qrDataInput = document.getElementById('qr-data');
  const qrSize = document.getElementById('qr-size');
  const qrColor = document.getElementById('qr-color');
  const qrPreview = document.getElementById('qr-preview');
  const applyQrBtn = document.getElementById('apply-qr-btn');

  function hexToRgb(hex){
    const bigint = parseInt(hex.slice(1), 16);
    const r = (bigint >> 16) & 255;
    const g = (bigint >> 8) & 255;
    const b = bigint & 255;
    return r + '-' + g + '-' + b;
  }

  function updateQRPreview(){
    if (!qrPreview) return;
    const data = qrDataInput.value || ' '; // avoid empty
    const size = qrSize.value;
    const color = hexToRgb(qrColor.value);
    const url = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&color=${color}&data=${encodeURIComponent(data)}`;
    qrPreview.innerHTML = `<img src="${url}" width="${size}" height="${size}" />`;
  }

  if (qrDataInput) qrDataInput.addEventListener('input', updateQRPreview);
  if (qrSize) qrSize.addEventListener('input', updateQRPreview);
  if (qrColor) qrColor.addEventListener('input', updateQRPreview);
  if (qrType) {
    qrType.addEventListener('change', function(){
      if (qrType.value === 'image') {
        qrDataInput.type = 'file';
      } else {
        qrDataInput.type = 'text';
      }
    });
  }

  if (applyQrBtn) {
    applyQrBtn.addEventListener('click', function(){
      const layer = document.getElementById('layer-qr');
      layer.innerHTML = qrPreview.innerHTML;
      layer.style.display = 'flex';
    });
  }

  updateQRPreview();

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
              baseLayer.innerHTML = this.innerHTML;
              baseLayer.style.display = 'flex';
            });
            aiResults.appendChild(img);
          }
        }
      }, 2000);
    });
  }

  // Size controls
  const designArea = document.querySelector('.design-area');
  const sizeBtns = document.querySelectorAll('.size-btn');
  const sizes = {
    'A4': { w: 550, h: 650 },
    'A3': { w: 600, h: 750 },
    'Cœur': { w: 300, h: 300, radius: '50%' },
    'Poche': { w: 200, h: 200 },
    'Full': { w: 650, h: 750 }
  };

  sizeBtns.forEach(btn => {
    btn.addEventListener('click', function(){
      sizeBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      const label = this.textContent.trim();
      const opt = sizes[label];
      if (opt) {
        designArea.style.width = opt.w + 'px';
        designArea.style.height = opt.h + 'px';
        designArea.style.borderRadius = opt.radius || '20px';
      }
    });
  });

  const defaultSizeBtn = document.querySelector('.size-btn');
  if (defaultSizeBtn) defaultSizeBtn.classList.add('active');
});
