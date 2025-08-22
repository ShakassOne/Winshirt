(function(){
  function init(root){
    var gap = parseInt(root.dataset.gap||'24',10);
    var cols = parseInt(root.dataset.columns||'4',10);
    var track = root.querySelector('.ws-track');
    if(!track) return;
    // Positionnement diagonal: décale verticalement chaque carte en fonction de son index colonne
    var cards = Array.from(track.children);
    cards.forEach(function(card,i){
      var col = i % cols;
      card.style.transform = 'translateY(' + (col * (gap*0.8)) + 'px)';
    });
    // Scroll nav
    var prev = root.querySelector('[data-prev]');
    var next = root.querySelector('[data-next]');
    function scrollBy(delta){
      track.scrollBy({left: delta, behavior:'smooth'});
    }
    if(prev) prev.addEventListener('click', function(){ scrollBy(-Math.min(track.clientWidth, 500)); });
    if(next) next.addEventListener('click', function(){ scrollBy(Math.min(track.clientWidth, 500)); });
    // Style: rendre le track scrollable horizontalement
    track.style.overflowX = 'auto';
    track.style.scrollSnapType = 'x proximity';
    cards.forEach(function(card){
      card.style.scrollSnapAlign = 'start';
    });
  }
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.ws-diagonal').forEach(init);
  });
})();
