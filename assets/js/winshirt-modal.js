/**
 * WinShirt - Modal
 * - Ouvre/ferme le modal au clic sur [data-ws-open-customizer] (ou [data-ws-open="customizer"])
 * - Charge le HTML du customizer via AJAX (admin-ajax.php?action=winshirt_modal)
 * - Émet l’évènement 'winshirt:mounted' après injection (pour ré-init UI & Layers)
 */
(function($){
  'use strict';

  const Modal = {
    $overlay: null,
    $body: null,
    isOpen: false,

    ensureDOM(){
      if(this.$overlay && this.$overlay.length) return;

      this.$overlay = $(`
        <div class="winshirt-customizer-modal" aria-modal="true" role="dialog" style="position:fixed;inset:0;z-index:99999;background:rgba(17,24,39,.6);display:none;">
          <div class="winshirt-customizer-dialog" style="position:absolute;inset:4% 2%;background:#fff;border-radius:14px;overflow:auto;box-shadow:0 10px 30px rgba(0,0,0,.25);">
            <div class="winshirt-customizer-head" style="position:sticky;top:0;background:#fff;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;padding:10px 12px;z-index:2">
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

    load(productId){
      const data = {
        action: 'winshirt_modal',
        product_id: productId || (window.WinShirtData && WinShirtData.product && WinShirtData.product.id) || 0,
        _wpnonce: (window.WinShirtData && WinShirtData.nonce) || undefined
      };
      $.post( (window.WinShirtData && WinShirtData.ajaxUrl) || ajaxurl, data )
        .done((res)=>{
          if(res && res.success && res.data && res.data.html){
            this.$body.html(res.data.html);
            // Notifier les autres modules pour (ré)initialiser
            document.dispatchEvent(new CustomEvent('winshirt:mounted'));
          }else{
            this.$body.html('<div style="padding:24px;color:#b91c1c">Erreur de chargement.</div>');
          }
        })
        .fail(()=>{
          this.$body.html('<div style="padding:24px;color:#b91c1c">Erreur réseau.</div>');
        });
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

  // Expose (debug)
  window.WinShirtModal = Modal;

})(jQuery);
