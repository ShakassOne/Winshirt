/**
 * WinShirt - Text Tools
 * - UI L2 pour ajouter/éditer du texte
 * - Ajout d’un calque texte via WinShirtLayers.addText()
 * - Edition du calque sélectionné (contenu, taille, gras, italique)
 */
(function($){
  'use strict';

  function tpl(){
    return `
      <div class="ws-text-tool" style="display:flex;flex-direction:column;gap:10px;padding:10px">
        <label style="display:block">
          <div style="font-size:12px;opacity:.7;margin-bottom:4px">Texte</div>
          <input type="text" class="ws-txt-content" placeholder="Votre texte" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px">
        </label>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <label> Taille
            <input type="number" class="ws-txt-size" value="32" min="8" max="200" style="width:80px;margin-left:6px;padding:6px;border:1px solid #ddd;border-radius:8px">
          </label>
          <label style="display:flex;gap:6px;align-items:center">
            <input type="checkbox" class="ws-txt-bold"> <span>Gras</span>
          </label>
          <label style="display:flex;gap:6px;align-items:center">
            <input type="checkbox" class="ws-txt-italic"> <span>Italique</span>
          </label>
          <button class="button ws-txt-add">Ajouter</button>
        </div>
        <div class="ws-txt-help" style="font-size:12px;opacity:.7">Astuce : clique le texte sur le mockup pour l’éditer directement.</div>
      </div>
    `;
  }

  function mount($l2){
    const $body = $l2.find('.ws-l2-body');
    $body.html(tpl());

    // Ajouter
    $body.on('click', '.ws-txt-add', function(){
      const text  = $body.find('.ws-txt-content').val() || 'Votre texte';
      const size  = parseInt($body.find('.ws-txt-size').val()||32,10);
      const bold  = $body.find('.ws-txt-bold').is(':checked');
      const italic= $body.find('.ws-txt-italic').is(':checked');

      if(window.WinShirtLayers && typeof WinShirtLayers.addText==='function'){
        WinShirtLayers.addText({ text, size, bold, italic });
      } else {
        alert('Calques non initialisés.');
      }
    });

    // Edition live du layer sélectionné si c’est du texte
    $(document).off('keyup.wstxt change.wstxt');
    $(document).on('keyup.wstxt change.wstxt', '.ws-txt-content, .ws-txt-size, .ws-txt-bold, .ws-txt-italic', function(){
      const L = window.WinShirtLayers;
      if(!L || !L.$active || !L.$active.length || !L.$active.hasClass('ws-type-text')) return;

      const $txt = L.$active.find('.ws-text');
      if(!$txt.length) return;

      const content = $body.find('.ws-txt-content').val();
      const size    = parseInt($body.find('.ws-txt-size').val()||32,10);
      const bold    = $body.find('.ws-txt-bold').is(':checked');
      const italic  = $body.find('.ws-txt-italic').is(':checked');

      if(typeof content === 'string') $txt.text(content);
      $txt.css({
        fontSize: size+'px',
        fontWeight: bold ? '700' : '400',
        fontStyle: italic ? 'italic' : 'normal'
      });
      // ajuste la bounding box
      setTimeout(()=>{ L.clampInside(L.$active); }, 0);
    });

    // Quand on sélectionne un layer texte → refléter dans la UI
    $('.winshirt-mockup-canvas').off('click.wstxt').on('click.wstxt', '.ws-type-text', function(){
      const $txt = $(this).find('.ws-text');
      $body.find('.ws-txt-content').val($txt.text());
      $body.find('.ws-txt-size').val(parseInt($txt.css('font-size'),10)||32);
      $body.find('.ws-txt-bold').prop('checked', ($txt.css('font-weight')||'400') >= '700');
      $body.find('.ws-txt-italic').prop('checked', ($txt.css('font-style')||'normal') === 'italic');
    });
  }

  $(document).on('winshirt:panel:text', function(e, ctx){
    mount(ctx.l2);
  });

})(jQuery);
