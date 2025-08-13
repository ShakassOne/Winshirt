wp_enqueue_script( 'winshirt-modal' );

/* ---- SAFETY NET: ouvre la modale sur tous les sélecteurs historiques ---- */
wp_add_inline_script( 'winshirt-modal', <<<JS
(function($){
  var SELECTORS = [
    '[data-winshirt-open]',
    '[data-ws-open-customizer]',
    '#winshirt-open',
    '.ws-open-customizer',
    '.winshirt-open',
    '.winshirt-btn-open',
    '.button-personnaliser',
    '.button.winshirt-open'
  ].join(',');

  $(document).on('click', SELECTORS, function(e){
    e.preventDefault(); e.stopPropagation();
    if (window.WinShirtModal && typeof WinShirtModal.open === 'function') {
      WinShirtModal.open();
    } else {
      // fallback: on essaie de créer le squelette si le contrôleur n’est pas prêt
      var $existing = $('.winshirt-customizer-modal');
      if (!$existing.length) {
        $('body').append([
          '<div class="winshirt-customizer-modal" aria-hidden="true" style="display:none">',
          '  <div class="winshirt-customizer-dialog" role="dialog" aria-modal="true">',
          '    <div class="ws-head"><button type="button" class="ws-close" aria-label="Fermer">Fermer</button></div>',
          '    <div class="winshirt-customizer-body">',
          '      <aside class="ws-l1">',
          '        <button class="ws-nav-btn is-active" data-panel="images">Images</button>',
          '        <button class="ws-nav-btn" data-panel="text">Texte</button>',
          '        <button class="ws-nav-btn" data-panel="layers">Calques</button>',
          '        <button class="ws-nav-btn" data-panel="qr">QR Code</button>',
          '      </aside>',
          '      <main id="winshirt-mockup-area"><div id="winshirt-canvas" style="position:relative;width:min(800px,90%);aspect-ratio:1/1;margin:24px auto"></div></main>',
          '      <aside class="ws-l2"><div class="ws-l2-title">Images</div><button class="ws-back" type="button">← Retour</button><div class="ws-l2-body"></div></aside>',
          '    </div>',
          '    <div class="ws-footer">',
          '      <button class="ws-pill js-ws-side is-active" data-side="front">Recto</button>',
          '      <button class="ws-pill js-ws-side" data-side="back">Verso</button>',
          '      <span style="flex:1"></span>',
          '      <button class="ws-cta js-ws-save">Enregistrer le design</button>',
          '      <button class="ws-cta js-ws-addcart">Ajouter au panier</button>',
          '    </div>',
          '  </div>',
          '</div>'
        ].join(''));
      }
      $('html,body').addClass('ws-modal-open');
      $('.winshirt-customizer-modal').show().attr('aria-hidden','false').addClass('is-open');
    }
  });

  // fermer proprement
  $(document).on('click', '.winshirt-customizer-modal', function(e){
    if (e.target !== this) return;
    $(this).removeClass('is-open').hide().attr('aria-hidden','true');
    $('html,body').removeClass('ws-modal-open');
  });
  $(document).on('click', '.winshirt-customizer-modal .ws-close', function(){
    var $m = $('.winshirt-customizer-modal');
    $m.removeClass('is-open').hide().attr('aria-hidden','true');
    $('html,body').removeClass('ws-modal-open');
  });
})(jQuery);
JS
);
