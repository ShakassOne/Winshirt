/**
 * WinShirt - Router Hooks (no-op pour éviter erreurs tant que rien n'est branché)
 * Sert de point d'extension. N'émet aucun effet si rien n'écoute.
 */
(function($){
  'use strict';
  // Exemple : quand le router est prêt, on pourrait synchroniser des boutons externes.
  $(document).on('winshirt:routerReady', function(e, router){
    // Pas d'action pour l'instant.
    window.__WS_ROUTER_READY__ = true; // simple marqueur debug
  });
})(jQuery);
