/**
 * WinShirt - UI Router (panneaux L1/L2/L3)
 * - Pile de panneaux (push/pop)
 * - Retour (back) et retour ciblé (backTo(level))
 * - Événements jQuery: winshirt:panelPushed, winshirt:panelPopped, winshirt:panelChange
 * - Auto-boot : crée le conteneur root si absent.
 *
 * Attentes DOM minimales (créées si manquantes) :
 *  <div id="winshirt-panel-root" class="ws-panels" data-active-level="0">
 *    <div class="ws-panel ws-panel-l1" data-level="1"></div>
 *    <div class="ws-panel ws-panel-l2" data-level="2"></div>
 *    <div class="ws-panel ws-panel-l3" data-level="3"></div>
 *  </div>
 *
 * Chaque "vue" poussée est un objet :
 *  {
 *    id: 'images|text|qr|... (unique au niveau)',
 *    level: 1|2|3,
 *    title: 'Texte affiché en en-tête',
 *    render: function($mount, payload) { ... }, // doit remplir $mount
 *    onShow?: function(){}, onHide?: function(){},
 *  }
 */

(function($){
  'use strict';

  const DEFAULT_ROOT_ID = 'winshirt-panel-root';

  const Router = {
    $root: null,
    $l1: null,
    $l2: null,
    $l3: null,
    stack: [],         // [{level, id, title, render, onShow, onHide, $mount}]
    activeLevel: 0,    // 0=none, 1..3

    init(options = {}) {
      const rootId = options.rootId || DEFAULT_ROOT_ID;
      this.$root = $('#' + rootId);

      if(!this.$root.length){
        // Crée le root minimal si absent
        this.$root = $(`
          <div id="${rootId}" class="ws-panels" data-active-level="0">
            <div class="ws-panel ws-panel-l1" data-level="1"></div>
            <div class="ws-panel ws-panel-l2" data-level="2"></div>
            <div class="ws-panel ws-panel-l3" data-level="3"></div>
          </div>
        `);
        // Tentative d'injection près du modal si repérable
        const $modal = $('#winshirt-customizer-modal');
        if($modal.length){
          $modal.append(this.$root);
        } else {
          $('body').append(this.$root);
        }
      }

      this.$l1 = this.$root.find('.ws-panel-l1[data-level="1"]');
      this.$l2 = this.$root.find('.ws-panel-l2[data-level="2"]');
      this.$l3 = this.$root.find('.ws-panel-l3[data-level="3"]');

      // Back bouton délégué (si ton template en fournit un)
      $(document).on('click', '[data-ws-back]', (e) => {
        e.preventDefault();
        this.back();
      });

      $(document).trigger('winshirt:routerReady', [this]);
      return this;
    },

    /**
     * Retourne le conteneur jQuery pour un niveau (1..3)
     */
    _containerFor(level){
      if(level === 1) return this.$l1;
      if(level === 2) return this.$l2;
      if(level === 3) return this.$l3;
      return $(); // vide
    },

    /**
     * Efface le contenu d'un niveau et masque son panneau
     */
    _clearLevel(level){
      const $c = this._containerFor(level);
      $c.empty().removeClass('is-active').attr('aria-hidden', 'true');
      if(this.activeLevel === level){
        this.activeLevel = Math.max(0, level - 1);
        this.$root.attr('data-active-level', String(this.activeLevel));
      }
    },

    /**
     * Affiche un niveau, masque les supérieurs
     */
    _activateLevel(level){
      // Masquer tous
      [1,2,3].forEach(l=>{
        const $c = this._containerFor(l);
        if(l === level){
          $c.addClass('is-active').attr('aria-hidden','false');
        }else{
          $c.removeClass('is-active').attr('aria-hidden','true');
        }
      });
      this.activeLevel = level;
      this.$root.attr('data-active-level', String(level));
      $(document).trigger('winshirt:panelChange', [level, this.peek()]);
    },

    /**
     * Retourne l'élément top de pile (ou null)
     */
    peek(){
      return this.stack.length ? this.stack[this.stack.length-1] : null;
    },

    /**
     * Vide entièrement la pile et les panneaux
     */
    clear(){
      this.stack = [];
      [1,2,3].forEach(l => this._clearLevel(l));
      this.activeLevel = 0;
      this.$root.attr('data-active-level', '0');
      $(document).trigger('winshirt:panelChange', [0, null]);
    },

    /**
     * push(view, payload) : rend et affiche une vue au niveau donné
     */
    push(view, payload = {}){
      if(!view || !view.level || !view.render){
        console.error('WinShirt Router push: view invalide', view);
        return;
      }
      const level = view.level;
      if(level < 1 || level > 3){
        console.error('WinShirt Router push: level invalide', level);
        return;
      }

      // Pop auto des niveaux supérieurs/égaux
      while(this.stack.length && this.peek().level >= level){
        this._popInternal(false);
      }

      const $mount = this._containerFor(level);
      $mount.empty();

      // Header minimal facultatif (titre + back)
      if(view.title){
        const header = $(`
          <div class="ws-panel-header">
            <button class="ws-back" data-ws-back aria-label="Retour" title="Retour"></button>
            <div class="ws-title"></div>
          </div>
        `);
        header.find('.ws-title').text(view.title);
        $mount.append(header);
      }

      // Zone de contenu
      const $content = $('<div class="ws-panel-content"></div>');
      $mount.append($content);

      // Rendu de la vue
      try {
        view.render($content, payload);
      } catch(err){
        console.error('WinShirt Router render error:', err);
      }

      const entry = {
        id: view.id || ('lvl'+level+'-'+Date.now()),
        level: level,
        title: view.title || '',
        render: view.render,
        onShow: view.onShow || null,
        onHide: view.onHide || null,
        $mount: $mount
      };

      this.stack.push(entry);
      this._activateLevel(level);

      if(entry.onShow){
        try { entry.onShow(); } catch(e){}
      }

      $(document).trigger('winshirt:panelPushed', [entry]);
    },

    /**
     * pop() : revient d'un cran
     */
    back(){
      if(!this.stack.length) return;
      this._popInternal(true);
    },

    /**
     * backTo(level) : remonte jusqu'au niveau demandé (1..3)
     */
    backTo(level){
      if(level < 0 || level > 3) return;
      while(this.stack.length && this.peek().level > level){
        this._popInternal(false);
      }
      if(level === 0){
        this.clear();
      } else if(this.activeLevel !== level){
        this._activateLevel(level);
      }
    },

    /**
     * Implémentation du pop (interne)
     */
    _popInternal(emit){
      const top = this.peek();
      if(!top) return;

      if(top.onHide){
        try { top.onHide(); } catch(e){}
      }

      // Nettoie le panneau top
      this._clearLevel(top.level);
      this.stack.pop();

      const newTop = this.peek();
      const newLevel = newTop ? newTop.level : 0;

      if(newTop && newTop.$mount && newTop.$mount.length){
        newTop.$mount.addClass('is-active').attr('aria-hidden','false');
      }

      this.activeLevel = newLevel;
      this.$root.attr('data-active-level', String(newLevel));

      if(emit){
        $(document).trigger('winshirt:panelPopped', [top, newLevel]);
        $(document).trigger('winshirt:panelChange', [newLevel, newTop]);
      }
    }
  };

  // Expose global
  window.WinShirtUIRouter = Router;

  // Boot auto
  $(function(){ Router.init(); });

})(jQuery);
