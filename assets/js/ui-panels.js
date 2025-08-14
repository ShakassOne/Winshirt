(function($){
  'use strict';

  $(function(){
    const $modal = $('#winshirt-customizer-modal');
    if(!$modal.length) return;

    const $tabs  = $modal.find('.ws-tab');
    const $panels= $modal.find('.ws-panel');

    $tabs.on('click', function(){
      const tgt = $(this).data('panel');
      $tabs.removeClass('is-active'); $(this).addClass('is-active');
      $panels.removeClass('is-active').filter(`[data-panel="${tgt}"]`).addClass('is-active');

      // évènements spécifiques
      $(document).trigger('winshirt:panel:'+tgt, { l2: $modal.find(`.ws-panel[data-panel="${tgt}"]`) });
    });
  });

})(jQuery);
