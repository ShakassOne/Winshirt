/**
 * WinShirt - Modal (hotfix assets)
 * - Ouvre/ferme le modal au clic sur [data-ws-open-customizer] (ou [data-ws-open="customizer"])
 * - Avant d'afficher le HTML, s'assure que TOUTES les CSS/JS nécessaires sont chargées
 * - Charge le template via AJAX, puis déclenche 'winshirt:mounted'
 */
(function($){
  'use strict';

  const ASSETS = {
    css: [
      'css/winshirt-helpers.css',
      'css/winshirt-panels.css',
      'css/winshirt-layers.css',
      'css/winshirt-modal.css',
      'css/winshirt-mobile.css'
    ],
    js: [
      'js/mockup-canvas.js',
      'js/ui-panels.js',
      'js/layers.js',
      'js/router-hooks.js' // ok si absent, on ignore l'erreur réseau
    ]
  };

  function assetUrl(rel){
    const base = (window.WinShirtData && WinShirtData.assetsUrl) || (window.winshirtAssetsBase || '');
    return base.replace(/\/?$/,'/') + rel;
  }

  function ensureCSS(href){
    return new Promise((resolve)=>{
      // déjà présent ?
      const exists = Array.from(document.styleSheets).some(ss => {
        try { return ss.href && ss.href.indexOf(href) !== -1; } catch(e){ return false; }
      });
      if(exists) return resolve();

      // <link> à ajouter
      const link = document.createElement('link');
      link.rel  = 'stylesheet';
      link.href = href + (href.indexOf('?')===-1 ? '?v='+(window.WinShirtData?.version||'1') : '');
      link.onload = ()=> resolve();
      link.onerror = ()=> resolve(); // on ne bloque pas
      document.head.appendChild(link);
    });
  }

  function ensureJS(src){
    return new Promise((resolve)=>{
      // déjà chargé ?
      const exists = Array.from(document.scripts).some(s => s.src && s.src.indexOf(src)!==-1);
      if(exists) return resolve();

      const s = document.createElement('script');
      s.src = src + (src.indexOf('?')===-1 ? '?v='+(window.WinShirtData?.version||'1') : '');
      s.async = false;
      s.onload = ()=> resolve();
      s.onerror = ()=> resolve(); // on ne bloque pas
      document.head.appendChild(s);
    });
  }

  async function ensureAllAssets(){
    // Injecte CSS en priorité
    for(const rel of ASSETS.css){
      await ensureCSS(assetUrl(rel));
    }
    // Puis JS indispensables
    for(const rel of ASSETS.js){
      await ensureJS(assetUrl(rel));
    }
  }

  const Modal = {
    $overlay: null,
    $body: null,
    isOpen: false,

    ensureDOM(){
      if(this.$overlay && this.$overlay.length) return;

      this.$overlay = $(`
        <div class="winshirt-customizer-modal" aria-modal="true" role="dialog"
             style="position:fixed;inset:0;z-index:99999;background:rgba(17,24,39,.6);display:none;">
          <div class="winshirt-customizer-dialog"
               style="position:absolute;inset:4% 2%;background:#fff;border-radius:14px;overflow:auto;
                      box-shadow:0 10px 30px rgba(0,0,0,.25);">
            <div class="winshirt-customizer-head"
                 style="position:sticky;top:0;background:#fff;border-bottom:1px solid #eee;
                        display:flex;justify-content:space-between;align-items:center;padding:10px 12px;z-index:2">
              <strong>Personnalisez</strong>
              <button type="button" class="button" data-ws-close-modal>Fermer</button>
            </div>
            <div class="winshirt-customizer-body" style="padding:12px 16px;">
              <div class="ws-loading" style="text-align:center;padding:40px 0;">Chargement…</div>
            </div>
          </div>
        </div>
      `).appendTo(document.body);

      this.$body = this.$overlay.find('.winshirt-customizer-body');

      // Fermer
      this.$overlay.on('click', '[data-ws-close-modal]', (e)=>{ e.preventDefault(); this.close(); });
      this.$overlay.on('click', (e)=>{
        if($(e.target).is('.winshirt-customizer-modal')) this.close();
      });

      // Escape
      $(document).on('keydown.winshirtModal', (e)=>{
        if(e.key === 'Escape' && this.isOpen) this.close();
      });
    },

    open(){
      this.ensureDOM();
      if(this.isOpen) return;
      $('body').addClass('ws-modal-open').css('overflow','hidden');
      this.$overlay.fadeIn(120);
      this.isOpen = true;
    },

    close(){
      if(!this.isOpen) return;
      this.$overlay.fadeOut(120, ()=>{
        this.$body.empty().append('<div class="ws-loading" style="text-align:center;padding:40px 0;">Chargement…</div>');
      });
      $('body').removeClass('ws-modal-open').css('overflow','');
      this.isOpen = false;
    },

    async load(productId){
      try{
        // 1) S’assurer des assets
        await ensureAllAssets();

        // 2) Charger le HTML du customizer
        const data = {
          action: 'winshirt_modal',
          product_id: productId || (window.WinShirtData && WinShirtData.product && WinShirtData.product.id) || 0,
          _wpnonce: (window.WinShirtData && WinShirtData.nonce) || undefined
        };

        $.post( (window.WinShirtData && WinShirtData.ajaxUrl) || (window.ajaxurl || '/wp-admin/admin-ajax.php'), data )
          .done((res)=>{
            if(res && res.success && res.data && res.data.html){
              this.$body.html(res.data.html);
              // 3) Notifier pour (re)monter l’UI et les calques
              document.dispatchEvent(new CustomEvent('winshirt:mounted'));
            }else{
              this.$body.html('<div style="padding:24px;color:#b91c1c">Erreur de chargement.</div>');
            }
          })
          .fail(()=>{
            this.$body.html('<div style="padding:24px;color:#b91c1c">Erreur réseau.</div>');
          });

      }catch(err){
        console.error(err);
        this.$body.html('<div style="padding:24px;color:#b91c1c">Erreur d’initialisation.</div>');
      }
    },

    handleOpenClick(e){
      e.preventDefault();
      this.open();
      this.load();
    },

    boot(){
      // Ouvre depuis bouton
      $(document).on('click', '[data-ws-open-customizer],[data-ws-open="customizer"]', (e)=> this.handleOpenClick(e));
    }
  };

  $(function(){ Modal.boot(); });
  window.WinShirtModal = Modal;

})(jQuery);
