(function(){
  function tick(node, endStr){
    if(!endStr) return;
    var end = new Date(endStr.replace(' ', 'T'));
    function update(){
      var now = new Date();
      var diff = Math.max(0, end - now);
      var d = Math.floor(diff/86400000);
      var h = Math.floor((diff%86400000)/3600000);
      var m = Math.floor((diff%3600000)/60000);
      var dd = node.querySelector('.wsov-dd');
      var hh = node.querySelector('.wsov-hh');
      var mm = node.querySelector('.wsov-mm');
      if(dd) dd.textContent = d;
      if(hh) hh.textContent = h;
      if(mm) mm.textContent = m;
    }
    update();
    setInterval(update, 60000); // maj chaque minute
  }
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.wsov-overlay[data-wsov-end]').forEach(function(el){
      tick(el, el.getAttribute('data-wsov-end'));
    });
  });
})();
