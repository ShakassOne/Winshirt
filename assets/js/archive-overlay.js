(function(){
  function tick(node, endStr){
    var end = new Date(endStr.replace(' ', 'T'));
    function update(){
      var now = new Date();
      var diff = Math.max(0, end - now);
      var d = Math.floor(diff/86400000);
      var h = Math.floor((diff%86400000)/3600000);
      var m = Math.floor((diff%3600000)/60000);
      node.querySelector('.wsov-dd') && (node.querySelector('.wsov-dd').textContent = d);
      node.querySelector('.wsov-hh') && (node.querySelector('.wsov-hh').textContent = h);
      node.querySelector('.wsov-mm') && (node.querySelector('.wsov-mm').textContent = m);
    }
    update();
    setInterval(update, 60000); // chaque minute suffit
  }

  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.wsov-overlay[data-wsov-end]').forEach(function(el){
      var end = el.getAttribute('data-wsov-end');
      if (end) tick(el, end);
    });
  });
})();
