<?php
// templates/modal-customizer.php

// Récupère de manière fiable l'ID du produit courant
$product_id = get_queried_object_id();
if ( ! $product_id ) {
    global $product;
    if ( $product instanceof WC_Product ) {
        $product_id = $product->get_id();
    }
}

// ID du mockup associé au produit
$mockup_id = $product_id ? get_post_meta( $product_id, WinShirt_Product_Customization::MOCKUP_META_KEY, true ) : 0;

$front = $back = '';
$colors = [];
$zones  = [];
if ( $mockup_id ) {
    $front = get_post_meta( $mockup_id, '_ws_mockup_front', true );
    $back  = get_post_meta( $mockup_id, '_ws_mockup_back', true );
    $color_string = get_post_meta( $mockup_id, '_ws_mockup_colors', true );
    if ( $color_string ) {
        $colors = array_filter( array_map( 'trim', explode( ',', $color_string ) ) );
    }
    $zones = get_post_meta( $mockup_id, '_ws_mockup_zones', true );
    if ( ! is_array( $zones ) ) {
        $zones = [];
    }
}
$default_zone = $zones[0] ?? [ 'width' => 600, 'height' => 650, 'top' => 0, 'left' => 0 ];
?>
<div id="winshirt-customizer-modal" class="winshirt-modal-overlay" style="display:none;">
  <div class="winshirt-modal-content">
    <button class="winshirt-modal-close" id="winshirt-close-modal">&times;</button>

    <div class="header"></div>

    <div class="main-container">
      <!-- Left Sidebar -->
      <aside class="left-sidebar">
        <div class="tool-icon" title="Produit" data-target="#product-panel">
          <svg class="svg-icon" viewBox="0 0 24 24">
            <path d="M16,12V4H17V2H7V4H8V12L6,14V16H11.2V22H12.8V16H18V14L16,12Z"/>
          </svg>
        </div>
        <div class="tool-icon active" title="Images" data-target="#image-panel">
          <svg class="svg-icon" viewBox="0 0 24 24">
            <path d="M21,19V5C21,3.89 20.1,3 19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19M19,19H5V5H19V19M13.96,12.29L11.21,15.83L9.25,13.47L6.5,17H17.5L13.96,12.29Z"/>
          </svg>
        </div>
        <div class="tool-icon" title="Texte" data-target="#text-panel">
          <svg class="svg-icon" viewBox="0 0 24 24">
            <path d="M18.5,4L19.66,8.35L18.7,8.61C18.25,7.74 17.79,6.87 17.26,6.43C16.73,6 16.11,6 15.5,6H13V16.5C13,17 13,17.5 13.33,17.75C13.67,18 14.33,18 15,18V19H9V18C9.67,18 10.33,18 10.67,17.75C11,17.5 11,17 11,16.5V6H8.5C7.89,6 7.27,6 6.74,6.43C6.21,6.87 5.75,7.74 5.3,8.61L4.34,8.35L5.5,4H18.5Z"/>
          </svg>
        </div>
        <div class="tool-icon" title="Calques" data-target="#layers-panel">
          <svg class="svg-icon" viewBox="0 0 24 24">
            <path d="M12,16L19.36,10.27L21,9L12,2L3,9L4.63,10.27M12,18.54L4.62,12.81L3,14.07L12,21.07L21,14.07L19.37,12.8L12,18.54Z"/>
          </svg>
        </div>
        <div class="tool-icon" title="QR Code" data-target="#qr-panel">
          <svg class="svg-icon" viewBox="0 0 24 24">
            <path d="M3,11H5V13H3V11M11,5H13V9H11V5M9,11H13V15H9V11M15,11H17V13H15V11M19,11H21V13H19V11M5,19H7V21H5V19M3,5H9V9H3V5M5,7V7H7V9H5V7M15,3H21V9H15V3M17,5V7H19V9H17V5M3,15H9V21H3V15Z"/>
          </svg>
        </div>
        <div class="tool-icon" title="IA" data-target="#ai-panel">
          <svg class="svg-icon" viewBox="0 0 24 24">
            <path d="M12,2A2,2 0 0,1 14,4C14,4.74 13.6,5.39 13,5.73V7H14A7,7 0 0,1 21,14H22A1,1 0 0,1 23,15V18A1,1 0 0,1 22,19H21V20A2,2 0 0,1 19,22H5A2,2 0 0,1 3,20V19H2A1,1 0 0,1 1,18V15A1,1 0 0,1 2,14H3A7,7 0 0,1 10,7H11V5.73C10.4,5.39 10,4.74 10,4A2,2 0 0,1 12,2M7.5,13A2.5,2.5 0 0,0 5,15.5A2.5,2.5 0 0,0 7.5,18A2.5,2.5 0 0,0 10,15.5A2.5,2.5 0 0,0 7.5,13M16.5,13A2.5,2.5 0 0,0 14,15.5A2.5,2.5 0 0,0 16.5,18A2.5,2.5 0 0,0 19,15.5A2.5,2.5 0 0,0 16.5,13Z"/>
          </svg>
        </div>
      </aside>

      <!-- Central Area -->
      <main class="central-area">
        <div class="view-controls">
          <button class="view-btn active" data-img="<?php echo esc_url( $front ); ?>">Front</button>
          <?php if ( $back ) : ?>
          <button class="view-btn" data-img="<?php echo esc_url( $back ); ?>">Back</button>
          <?php endif; ?>
        </div>

        <?php if ( ! empty( $colors ) ) : ?>
        <div class="color-controls">
          <?php foreach ( $colors as $color ) : ?>
            <button class="color-btn" data-color="<?php echo esc_attr( $color ); ?>" style="background: <?php echo esc_attr( $color ); ?>;"></button>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="tshirt-container">
          <div class="tshirt" style="background-image:url('<?php echo esc_url( $front ); ?>'); background-repeat:no-repeat; background-size:contain; background-position:center;">
            <div class="design-area" id="design-area" style="width:<?php echo esc_attr( $default_zone['width'] ); ?>px;height:<?php echo esc_attr( $default_zone['height'] ); ?>px;top:<?php echo esc_attr( $default_zone['top'] ); ?>px;left:<?php echo esc_attr( $default_zone['left'] ); ?>px;">
              <div class="layer" id="layer-design">Design Principal</div>
              <div class="layer" id="layer-text"></div>
              <div class="layer" id="layer-qr"></div>
            </div>
          </div>
        </div>

        <div class="size-controls">
          <?php foreach ( $zones as $zone ) : ?>
            <button class="size-btn" data-width="<?php echo esc_attr( $zone['width'] ); ?>" data-height="<?php echo esc_attr( $zone['height'] ); ?>" data-top="<?php echo esc_attr( $zone['top'] ); ?>" data-left="<?php echo esc_attr( $zone['left'] ); ?>" data-price="<?php echo esc_attr( $zone['price'] ); ?>">
              <?php echo esc_html( $zone['name'] ); ?>
            </button>
          <?php endforeach; ?>
        </div>
      </main>

      <!-- Image Panel -->
      <aside class="right-sidebar" id="image-panel">
        <div class="sidebar-header">
          <h2 class="sidebar-title">Galerie de designs</h2>
          <div class="filter-tabs">
            <div class="filter-tab active">Tous</div>
            <div class="filter-tab">Animaux</div>
            <div class="filter-tab">Nature</div>
            <div class="filter-tab">Humour</div>
            <div class="filter-tab">Abstrait</div>
            <div class="filter-tab">Sport</div>
            <div class="filter-tab">Vintage</div>
          </div>
        </div>

        <div class="gallery-content">
          <button class="upload-btn">Upload your own design</button>

          <div class="design-grid">
            <div class="design-item">🦁</div>
            <div class="design-item">🌲</div>
            <div class="design-item">😂</div>
            <div class="design-item">🎨</div>
            <div class="design-item">⚽</div>
            <div class="design-item">🎸</div>
            <div class="design-item">🌙</div>
            <div class="design-item">🔥</div>
            <div class="design-item">💎</div>
            <div class="design-item">🚀</div>
            <div class="design-item">🌊</div>
            <div class="design-item">⭐</div>
            <div class="design-item">🎯</div>
            <div class="design-item">🌺</div>
            <div class="design-item">🎭</div>
          </div>
        </div>
      </aside>

      <!-- Text Panel (hidden by default) -->
      <aside class="right-sidebar" id="text-panel" style="display: none;">
        <div class="sidebar-header">
          <h2 class="sidebar-title">Options de texte</h2>
        </div>

        <div class="gallery-content">
          <div class="text-option">
            <label>Texte :</label>
            <input type="text" id="text-input" placeholder="Entrez votre texte..." style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px; margin-bottom: 15px;">
          </div>

          <div class="text-option">
            <label>Police :</label>
            <select id="font-select" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px; margin-bottom: 15px;">
              <option value="Arial">Arial</option>
              <option value="Georgia">Georgia</option>
              <option value="Times New Roman">Times New Roman</option>
              <option value="Helvetica">Helvetica</option>
              <option value="Impact">Impact</option>
              <option value="Comic Sans MS">Comic Sans MS</option>
            </select>
          </div>

          <div class="text-option">
            <label>Taille :</label>
            <input type="range" id="font-size" min="12" max="120" value="48" style="width: 100%; margin-bottom: 10px;">
            <div style="text-align: center; color: #666; font-size: 12px;"><span id="size-value">48</span>px</div>
          </div>

          <div class="text-option" style="margin-top: 20px;">
            <label>Couleur :</label>
            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
              <div class="color-option" data-color="#000000" style="background: #000000;"></div>
              <div class="color-option" data-color="#ffffff" style="background: #ffffff; border: 1px solid #ccc;"></div>
              <div class="color-option" data-color="#ff0000" style="background: #ff0000;"></div>
              <div class="color-option" data-color="#00ff00" style="background: #00ff00;"></div>
              <div class="color-option" data-color="#0000ff" style="background: #0000ff;"></div>
              <div class="color-option" data-color="#ffff00" style="background: #ffff00;"></div>
              <div class="color-option" data-color="#ff00ff" style="background: #ff00ff;"></div>
              <div class="color-option" data-color="#00ffff" style="background: #00ffff;"></div>
            </div>
          </div>

          <div class="text-option" style="margin-top: 20px;">
            <label>Style :</label>
            <div style="display: flex; gap: 10px; margin-top: 10px;">
              <button class="style-btn" id="bold-btn" style="font-weight: bold;">B</button>
              <button class="style-btn" id="italic-btn" style="font-style: italic;">I</button>
              <button class="style-btn" id="underline-btn" style="text-decoration: underline;">U</button>
            </div>
          </div>

          <button class="upload-btn" id="add-text-btn" style="margin-top: 30px;">Ajouter le texte</button>
        </div>
      </aside>

      <!-- Product Panel -->
      <aside class="right-sidebar" id="product-panel" style="display:none;">
        <div class="sidebar-header">
          <h2 class="sidebar-title">Produit</h2>
        </div>
        <div class="gallery-content">
          <div class="text-option">
            <label>Type de produit :</label>
            <select id="product-type" style="width:100%; padding:10px; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:15px;">
              <option value="tshirt">T-shirt</option>
              <option value="hoodie">Hoodie</option>
              <option value="debardeur">Débardeur</option>
              <option value="polo">Polo</option>
              <option value="casquette">Casquette</option>
              <option value="sac">Sac</option>
            </select>
          </div>

          <div class="text-option">
            <label>Couleur :</label>
            <select id="product-color" style="width:100%; padding:10px; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:15px;">
              <option value="white">Blanc</option>
              <option value="black">Noir</option>
              <option value="red">Rouge</option>
              <option value="blue">Bleu</option>
            </select>
          </div>

          <div class="text-option">
            <label>Taille :</label>
            <select id="product-size" style="width:100%; padding:10px; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:15px;">
              <option value="XS">XS</option>
              <option value="S">S</option>
              <option value="M">M</option>
              <option value="L">L</option>
              <option value="XL">XL</option>
              <option value="XXL">XXL</option>
            </select>
          </div>

          <div class="text-option">
            <label>Matériau :</label>
            <select id="product-material" style="width:100%; padding:10px; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:15px;">
              <option value="cotton">100% Coton</option>
              <option value="mix">Coton/Polyester</option>
              <option value="bio">Bio</option>
              <option value="premium">Premium</option>
            </select>
          </div>

          <div class="text-option" style="text-align:center; margin-bottom:20px;">
            <strong>Prix estimé : <span id="product-price">20.00€</span></strong>
          </div>

          <button class="upload-btn" id="add-to-cart-btn">Ajouter au panier</button>
        </div>
      </aside>

      <!-- Layers Panel -->
      <aside class="right-sidebar" id="layers-panel" style="display:none;">
        <div class="sidebar-header">
          <h2 class="sidebar-title">Calques</h2>
        </div>
        <div class="gallery-content">
          <ul id="layers-list" style="list-style:none; padding:0; margin:0;">
            <li class="layer-item" data-layer="layer-design">
              <span class="layer-name">Design Principal</span>
              <div class="layer-actions">
                <button class="layer-vis">👁️</button>
                <button class="layer-lock">🔒</button>
                <button class="layer-del">🗑️</button>
              </div>
            </li>
            <li class="layer-item" data-layer="layer-text">
              <span class="layer-name">Texte</span>
              <div class="layer-actions">
                <button class="layer-vis">👁️</button>
                <button class="layer-lock">🔒</button>
                <button class="layer-del">🗑️</button>
              </div>
            </li>
            <li class="layer-item" data-layer="layer-qr">
              <span class="layer-name">QR Code</span>
              <div class="layer-actions">
                <button class="layer-vis">👁️</button>
                <button class="layer-lock">🔒</button>
                <button class="layer-del">🗑️</button>
              </div>
            </li>
          </ul>

          <div class="text-option" style="margin-top:20px;">
            <label>Opacité :</label>
            <input type="range" id="layer-opacity" min="0" max="100" value="100" style="width:100%;">
          </div>

          <div class="text-option" style="margin-top:20px;">
            <label>Position :</label>
            <div id="position-controls" style="display:grid; grid-template-columns:repeat(3,1fr); gap:5px;">
              <button class="pos-btn" data-pos="tl">↖</button>
              <button class="pos-btn" data-pos="tc">↑</button>
              <button class="pos-btn" data-pos="tr">↗</button>
              <button class="pos-btn" data-pos="cl">←</button>
              <button class="pos-btn" data-pos="cc">•</button>
              <button class="pos-btn" data-pos="cr">→</button>
              <button class="pos-btn" data-pos="bl">↙</button>
              <button class="pos-btn" data-pos="bc">↓</button>
              <button class="pos-btn" data-pos="br">↘</button>
            </div>
          </div>

          <button class="upload-btn" id="new-layer-btn" style="margin-top:20px;">Nouveau calque</button>
        </div>
      </aside>

      <!-- QR Code Panel -->
      <aside class="right-sidebar" id="qr-panel" style="display:none;">
        <div class="sidebar-header">
          <h2 class="sidebar-title">QR Code</h2>
        </div>
        <div class="gallery-content">
          <div class="text-option">
            <label>Type :</label>
            <select id="qr-type" style="width:100%; padding:10px; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:15px;">
              <option value="url">URL</option>
              <option value="text">Texte</option>
              <option value="vcard">VCard</option>
              <option value="image">Image</option>
            </select>
          </div>
          <div class="text-option" id="qr-input-wrapper">
            <label>Données :</label>
            <input type="text" id="qr-data" style="width:100%; padding:10px; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:15px;">
          </div>
          <div class="text-option">
            <label>Taille :</label>
            <input type="range" id="qr-size" min="100" max="400" value="200" style="width:100%; margin-bottom:10px;">
          </div>
          <div class="text-option">
            <label>Couleur :</label>
            <input type="color" id="qr-color" value="#000000" style="width:100%; height:40px; border:1px solid #e0e0e0; border-radius:6px;">
          </div>
          <div class="text-option" style="text-align:center; margin-top:20px;">
            <div id="qr-preview" class="qr-preview" style="margin:auto;"></div>
          </div>
          <button class="upload-btn" id="apply-qr-btn" style="margin-top:20px;">Appliquer</button>
        </div>
      </aside>

      <!-- AI Panel -->
      <aside class="right-sidebar" id="ai-panel" style="display:none;">
        <div class="sidebar-header">
          <h2 class="sidebar-title">Génération IA</h2>
        </div>
        <div class="gallery-content">
          <div class="text-option">
            <label>Description :</label>
            <textarea id="ai-description" style="width:100%; padding:10px; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:15px;"></textarea>
          </div>
          <div class="text-option">
            <label>Style :</label>
            <select id="ai-style" style="width:100%; padding:10px; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:15px;">
              <option value="realiste">Réaliste</option>
              <option value="cartoon">Cartoon</option>
              <option value="anime">Anime</option>
              <option value="abstrait">Abstrait</option>
              <option value="peinture">Peinture</option>
              <option value="pixel">Pixel Art</option>
              <option value="photo">Photographie</option>
            </select>
          </div>
          <div class="text-option">
            <label>Résolution :</label>
            <select id="ai-resolution" style="width:100%; padding:10px; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:15px;">
              <option value="512">512x512</option>
              <option value="768">768x768</option>
              <option value="1024">1024x1024</option>
            </select>
          </div>
          <div class="text-option" id="ai-examples" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:15px;">
            <button class="style-btn ai-example" data-prompt="Chien qui joue">Chien</button>
            <button class="style-btn ai-example" data-prompt="Paysage futuriste">Futur</button>
            <button class="style-btn ai-example" data-prompt="Portrait ancien">Portrait</button>
            <button class="style-btn ai-example" data-prompt="Dragon">Dragon</button>
            <button class="style-btn ai-example" data-prompt="Voiture rapide">Voiture</button>
            <button class="style-btn ai-example" data-prompt="Fleurs colorées">Fleurs</button>
          </div>
          <button class="upload-btn" id="ai-generate-btn">Générer</button>
          <div id="ai-status" style="text-align:center; margin-top:10px; color:#666;"></div>
          <div class="design-grid" id="ai-results" style="margin-top:20px;"></div>
        </div>
      </aside>
    </div>
  </div>
</div>
