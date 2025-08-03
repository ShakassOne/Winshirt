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
  const imagePanel = document.querySelector('.right-sidebar:not(#text-panel)');
  const textPanel = document.getElementById('text-panel');
  const toolIcons = document.querySelectorAll('.tool-icon');

  toolIcons.forEach((icon, index) => {
    icon.addEventListener('click', function(){
      toolIcons.forEach(i => i.classList.remove('active'));
      this.classList.add('active');

      if (index === 1) {
        imagePanel.style.display = 'flex';
        textPanel.style.display = 'none';
      } else if (index === 2) {
        imagePanel.style.display = 'none';
        textPanel.style.display = 'flex';
      } else {
        imagePanel.style.display = 'flex';
        textPanel.style.display = 'none';
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
  designItems.forEach(item => {
    item.addEventListener('click', function(){
      const designArea = document.querySelector('.design-area');
      designArea.innerHTML = this.innerHTML;
      designArea.style.fontSize = '200px';
      designArea.style.color = '#333';
    });
  });

  const uploadBtn = document.querySelector('.upload-btn');
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

  if (fontSize) {
    fontSize.addEventListener('input', function(){
      currentTextStyle.size = this.value;
      sizeValue.textContent = this.value;
    });
  }

  colorOptions.forEach(option => {
    option.addEventListener('click', function(){
      colorOptions.forEach(c => c.classList.remove('active'));
      this.classList.add('active');
      currentTextStyle.color = this.dataset.color;
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
    });
  });

  if (addTextBtn) {
    addTextBtn.addEventListener('click', function(){
      const text = textInput.value || 'Exemple de texte';
      const designArea = document.querySelector('.design-area');
      const fontWeight = currentTextStyle.bold ? 'bold' : 'normal';
      const fontStyle = currentTextStyle.italic ? 'italic' : 'normal';
      const textDecoration = currentTextStyle.underline ? 'underline' : 'none';

      designArea.innerHTML = text;
      designArea.style.fontSize = currentTextStyle.size + 'px';
      designArea.style.color = currentTextStyle.color;
      designArea.style.fontFamily = fontSelect.value;
      designArea.style.fontWeight = fontWeight;
      designArea.style.fontStyle = fontStyle;
      designArea.style.textDecoration = textDecoration;
    });
  }

  if (textInput) {
    textInput.addEventListener('input', function(){
      currentTextStyle.text = this.value;
    });
  }

  if (fontSelect) {
    fontSelect.addEventListener('change', function(){
      currentTextStyle.font = this.value;
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
