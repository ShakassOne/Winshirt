(function(){
  'use strict';
  function ready(fn){ if(document.readyState!=='loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }

  // Empêche l’effet "drag ghost" du navigateur sur les vignettes
  function killNativeDrag(el){
    if(!el) return;
    el.addEventListener('dragstart', function(e){ e.preventDefault(); return false; });
  }

  function onClickThumbnail(e){
    const t = e.target.closest('[data-ws-add-image], .ws-grid-item img, .ws-gallery img, .ws-panel--images img, .ws-design-thumb img');
    if(!t) return;
    e.preventDefault();

    // URL de l’image
    const card = t.closest('[data-src]');
    const url = (card && card.getAttribute('data-src')) || t.getAttribute('data-ws-add-image') || t.getAttribute('src');
    if(!url) return;

    // Envoi vers le canvas
    if(window.WinShirtLayers && typeof window.WinShirtLayers.addImage === 'function'){
      window.WinShirtLayers.addImage(url);
    } else if (window.WinShirtCanvas && typeof window.WinShirtCanvas.addImage === 'function'){
      window.WinShirtCanvas.addImage(url);
    } else {
      console.warn('WinShirtLayers.addImage introuvable');
    }
  }

  function boot(){
    // délégation globale → marche pour ta grille REST ET les listes statiques
    document.addEventListener('click', onClickThumbnail);
    // tue le drag natif sur la zone galerie si présente
    document.querySelectorAll('.ws-gallery img, .ws-grid-item img, .ws-panel--images img, [data-ws-add-image]').forEach(killNativeDrag);
  }

  ready(boot);
})();
