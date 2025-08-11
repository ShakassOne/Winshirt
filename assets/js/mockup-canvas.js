/**
 * WinShirt - Mockup Canvas
 * - Affiche les mockups recto/verso depuis WinShirtData.mockups
 * - Calcule les zones d'impression (x/y/w/h en %) -> px
 * - Réagit aux boutons Recto/Verso et à WinShirtState
 * - Prépare le terrain pour Layers (calques)
 *
 * Dépendances: jQuery, WinShirtState, (optionnel) WinShirtLayers
 */
(function($){
  'use strict';

  function applyMockups(){
    const front = (window.WinShirtData && WinShirtData.mockups && WinShirtData.mockups.front) ? WinShirtData.mockups.front : '';
    const back  = (window.WinShirtData && WinShirtData.mockups && WinShirtData.mockups.back ) ? WinShirtData.mockups.back  : '';

    const $front = $('#winshirt-canvas .winshirt-mockup-img[data-side="front"]');
    const $back  = $('#winshirt-canvas .winshirt-mockup-img[data-side="back"]');
    if(front) $front.attr('src', front).show();
    if(back)  $back.attr('src',  back).hide(); // front par défaut
  }

  function getZoneCfg(side){
    const zones = (window.WinShirtData && WinShirtData.zones) ? WinShirtData.zones : null;
    if(!zones || !zones[side] || !zones[side].length){
      return { xPct:20, yPct:20, wPct:60, hPct:45 };
    }
    const idx = (window.WinShirtState ? WinShirtState.currentZoneIndex : 0) || 0;
    return zones[side][idx] || zones[side][0];
  }

  function applyZones(){
    const $cv = $('#winshirt-canvas');
    if(!$cv.length) return;
    const W = $cv.innerWidth(), H = $cv.innerHeight();

    ['front','back'].forEach(side=>{
      const cfg = getZoneCfg(side);
      const x = (cfg.xPct||20)/100 * W;
      const y = (cfg.yPct||20)/100 * H;
      const w = (cfg.wPct||60)/100 * W;
      const h = (cfg.hPct||45)/100 * H;

      const $z = $cv.find(`.ws-print-zone[data-side="${side}"]`);
      if($z.length){
        $z.css({ left:x, top:y, width:w, height:h });
      }
    });
  }

  function setSide(side){
    if(!window.WinShirtState) return;
    WinShirtState.setSide(side);

    // Switch images et zone
    $('#winshirt-canvas .winshirt-mockup-img').hide();
    $(`#winshirt-canvas .winshirt-mockup-img[data-side="${side}"]`).show();

    $('#winshirt-canvas .ws-print-zone').hide();
    $(`#winshirt-canvas .ws-print-zone[data-side="${side}"]`).show();

    // Boutons
    $('.ws-side-btn').removeClass('active').attr('aria-selected','false');
    $(`.ws-side-btn[data-ws-side="${side}"]`).addClass('active').attr('aria-selected','true');

    // Rendu Layers pour le côté (si initialisé)
    if(window.WinShirtLayers && WinShirtLayers.$canvas){
      WinShirtLayers.renderSide(side);
    }
  }

  // Init
  $(function(){
    // Injecte une "shell" si elle n'existe pas (layout desktop)
    const $modal = $('#winshirt-customizer-modal');
    if($modal.length && !$modal.find('.ws-shell').length){
      // wrap les sections déjà en place
      const $side   = $modal.find('.winshirt-side-buttons');
      const $area   = $modal.find('.winshirt-mockup-area');
      const $panels = $('#winshirt-panel-root');
      const $actions= $modal.find('.winshirt-actions');

      const $shell = $('<div class="ws-shell"></div>');
      $shell.append($side, $area, $panels, $actions);
      $modal.append($shell);
    }

    applyMockups();
    applyZones();
    setSide('front');

    // Resize -> recalcule zones
    let t=null;
    $(window).on('resize', function(){ clearTimeout(t); t=setTimeout(applyZones,120); });

    // Boutons
    $(document).on('click', '.ws-side-btn', function(){
      setSide($(this).data('ws-side'));
    });

    // Quand le state change côté ailleurs
    $(document).on('winshirt:sideChanged', function(e, side){ setSide(side); });
    $(document).on('winshirt:zoneChanged', function(){ applyZones(); });

    // Si Layers existe déjà, assure l'init sur le canvas
    const $canvas = $('.winshirt-mockup-canvas');
    if($canvas.length && window.WinShirtLayers){
      WinShirtLayers.init($canvas);
    }
  });

})(jQuery);
