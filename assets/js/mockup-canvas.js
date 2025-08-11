(function($){
  'use strict';

  function log(){ if(window.console){ console.log.apply(console, ['[WinShirt mockup]'].concat([].slice.call(arguments))); } }

  function applyMockups(){
    const $front = $('#winshirt-canvas .winshirt-mockup-img[data-side="front"]');
    const $back  = $('#winshirt-canvas .winshirt-mockup-img[data-side="back"]');

    // 1) Si le template a déjà un src (shortcode front_img/back_img), on NE TOUCHE PAS
    let tplFront = $front.attr('src') || '';
    let tplBack  = $back.attr('src') || '';

    // 2) Sinon on prend WinShirtData.mockups.{front,back} s’ils existent
    const dsFront = (window.WinShirtData && WinShirtData.mockups && WinShirtData.mockups.front) ? WinShirtData.mockups.front : '';
    const dsBack  = (window.WinShirtData && WinShirtData.mockups && WinShirtData.mockups.back ) ? WinShirtData.mockups.back  : '';

    if(!tplFront && dsFront){ $front.attr('src', dsFront); tplFront = dsFront; }
    if(!tplBack  && dsBack ){ $back.attr('src',  dsBack ); tplBack  = dsBack; }

    // 3) Log clair
    log('front:', tplFront || '(vide)', 'back:', tplBack || '(vide)');

    // 4) Affiche recto par défaut
    $front.toggle(!!tplFront);
    $back.hide();
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
      if($z.length){ $z.css({ left:x, top:y, width:w, height:h }); }
    });
  }

  function setSide(side){
    if(!window.WinShirtState) return;
    WinShirtState.setSide(side);

    $('#winshirt-canvas .winshirt-mockup-img').hide();
    $(`#winshirt-canvas .winshirt-mockup-img[data-side="${side}"]`).show();

    $('#winshirt-canvas .ws-print-zone').hide();
    $(`#winshirt-canvas .ws-print-zone[data-side="${side}"]`).show();

    $('.ws-side-btn').removeClass('active').attr('aria-selected','false');
    $(`.ws-side-btn[data-ws-side="${side}"]`).addClass('active').attr('aria-selected','true');

    if(window.WinShirtLayers && WinShirtLayers.$canvas){
      WinShirtLayers.renderSide(side);
    }
  }

  $(function(){
    // Wrap layout si besoin
    const $modal = $('#winshirt-customizer-modal');
    if($modal.length && !$modal.find('.ws-shell').length){
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

    let t=null;
    $(window).on('resize', function(){ clearTimeout(t); t=setTimeout(applyZones,120); });
    $(document).on('click', '.ws-side-btn', function(){ setSide($(this).data('ws-side')); });
    $(document).on('winshirt:sideChanged', function(e, side){ setSide(side); });
    $(document).on('winshirt:zoneChanged', function(){ applyZones(); });
  });

})(jQuery);

