jQuery(function($){
  var $overlay = $('#winshirt-modal-overlay'),
      $open    = $('#winshirt-open-modal'),
      $close   = $('#winshirt-modal-close');

  $open.on('click', function(e){
    e.preventDefault();
    $overlay.fadeIn(200);
    // TODO: ici lancer init de la librairie de personnalisation (canvas/SVG)
  });

  $close.on('click', function(){
    $overlay.fadeOut(200);
  });

  // fermer au clic en dehors du container
  $overlay.on('click', function(e){
    if ( e.target === this ) {
      $overlay.fadeOut(200);
    }
  });
});
