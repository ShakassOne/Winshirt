(function () {
  'use strict';

  function log(){ try{ console.log.apply(console, arguments); }catch(e){} }

  function enhance(root){
    if(!root) return;

    var uid   = root.getAttribute('data-uid') || 'n/a';
    var car   = root.querySelector('.carousel');
    if(!car){
      log('[WinShirt:diagonal] abort(no .carousel) uid=', uid);
      return;
    }

    var items = car.querySelectorAll('.carousel-item');
    var n = items.length;
    log('[WinShirt:diagonal] init uid=', uid, 'items=', n);

    // Visible de base, on ajoute juste la couche "enhanced"
    root.classList.add('is-enhanced');

    // S’il n’y a aucun item, on ne fait rien de plus
    if(!n) return;

    // Cursors (optionnels, silencieux si non trouvés)
    var c1 = root.querySelector('.cursor');
    var c2 = root.querySelector('.cursor2');

    function onEnter(){
      if(c1){ c1.style.opacity = '1'; }
      if(c2){ c2.style.opacity = '1'; }
    }
    function onLeave(){
      if(c1){ c1.style.opacity = '0'; }
      if(c2){ c2.style.opacity = '0'; }
    }
    function onMove(e){
      var r = root.getBoundingClientRect();
      var x = e.clientX - r.left;
      var y = e.clientY - r.top;
      if(c1){ c1.style.transform = 'translate('+(x-9)+'px,'+(y-9)+'px)'; }
      if(c2){ c2.style.transform = 'translate('+(x-9)+'px,'+(y-9)+'px)'; }
    }

    root.addEventListener('mouseenter', onEnter);
    root.addEventListener('mouseleave', onLeave);
    root.addEventListener('mousemove',  onMove);
  }

  function boot(){
    var roots = document.querySelectorAll('.winshirt-diagonal');
    if(!roots.length){
      log('[WinShirt:diagonal] no roots');
      return;
    }
    roots.forEach(enhance);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
