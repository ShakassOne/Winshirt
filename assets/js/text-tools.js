(function($){
  'use strict';

  function mount($wrap){
    $wrap.html(`
      <div class="ws-text-tool">
        <label>Texte <input type="text" class="t" value="Winshirt"></label>
        <label>Taille <input type="number" class="s" value="32" min="8" step="1"></label>
        <label><input type="checkbox" class="b"> Gras</label>
        <label><input type="checkbox" class="i"> Italique</label>
        <button class="add button">Ajouter</button>
        <p class="ws-hint">Astuce : éditez le calque texte en le sélectionnant sur le mockup.</p>
      </div>
    `);

    $wrap.off('click.wst');
    $wrap.on('click.wst','.add', function(){
      if(!window.WinShirtLayers) return;
      const t = $wrap.find('.t').val() || 'Texte';
      const s = parseInt($wrap.find('.s').val(), 10) || 32;
      const b = $wrap.find('.b').is(':checked');
      const i = $wrap.find('.i').is(':checked');
      WinShirtLayers.addText({ text:t, size:s, bold:b, italic:i });
    });
  }

  $(document).on('winshirt:panel:text', function(e, ctx){
    mount(ctx.l2.find('.ws-l2-body'));
  });

})(jQuery);
