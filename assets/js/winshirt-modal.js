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
});
