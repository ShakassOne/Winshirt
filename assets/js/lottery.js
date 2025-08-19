(function($){
  'use strict';

  // Compteur universel (single & mini-cards)
  function pad(n){ n=Math.floor(n); return n<10?('0'+n):(''+n); }

  function tickOne($root, end){
    function step(){
      var now = Math.floor(Date.now()/1000);
      var diff = end - now;
      if (diff <= 0){
        $root.find('[data-u]').text('--');
        return;
      }
      var d = Math.floor(diff/86400),
          h = Math.floor((diff%86400)/3600),
          m = Math.floor((diff%3600)/60),
          s = Math.floor(diff%60);
      $root.find('[data-u="d"]').text(d);
      $root.find('[data-u="h"]').text(pad(h));
      $root.find('[data-u="m"]').text(pad(m));
      $root.find('[data-u="s"]').text(pad(s));
      setTimeout(step,1000);
    }
    step();
  }

  $(function(){
    // Single
    var $m = $('.ws-hero-metrics');
    if ($m.length){
      var end = parseInt($m.data('end'),10) || 0;
      var over = parseInt($m.data('over'),10)===1;
      if (end && !over) tickOne($m, end);
    }

    // Cards
    $('.ws-mini-timer').each(function(){
      var end = parseInt($(this).data('end'),10)||0;
      if (end) tickOne($(this), end);
    });
  });

})(jQuery);
