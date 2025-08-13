/**
 * WinShirt - Modal shell (open/close + bulles d'événements stables)
 * - Ouvre sur [data-ws-open-customizer]
 * - Ferme sur backdrop, bouton Fermer, ou ESC
 * - Empêche le clic intérieur de fermer le modal (stopPropagation)
 */

(function ($) {
  'use strict';

  const SEL = {
    modal:        '#winshirt-modal',
    backdrop:     '#winshirt-modal .ws-backdrop',
    dialog:       '#winshirt-modal .winshirt-customizer-dialog',
    body:         '#winshirt-modal .winshirt-customizer-body',
    close:        '#winshirt-modal .ws-close',
    openTrigger:  '[data-ws-open-customizer]'
  };

  const CSS = {
    open: 'ws-open',
    bodyLock: 'ws-modal-open'
  };

  function open() {
    const $modal = $(SEL.modal);
    if (!$modal.length) return;
    $('body').addClass(CSS.bodyLock);
    $modal.addClass(CSS.open);
    // Déclencheur pour brancher les panneaux/outils
    $(document).trigger('winshirt:modal:open', { modal: $modal });
  }

  function close() {
    const $modal = $(SEL.modal);
    if (!$modal.length) return;
    $modal.removeClass(CSS.open);
    $('body').removeClass(CSS.bodyLock);
    $(document).trigger('winshirt:modal:close', { modal: $modal });
  }

  function bind() {
    // Ouvrir
    $(document).on('click.ws', SEL.openTrigger, function (e) {
      e.preventDefault();
      open();
    });

    // Fermer (backdrop + bouton)
    $(document).on('click.ws', `${SEL.backdrop}, ${SEL.close}`, function (e) {
      e.preventDefault();
      close();
    });

    // Bloquer la propagation des clics à l’intérieur du dialog (sinon ça ferme)
    $(document).on('click.ws', `${SEL.dialog}, ${SEL.body}`, function (e) {
      e.stopPropagation();
    });

    // ESC pour fermer
    $(document).on('keydown.ws', function (e) {
      if (e.key === 'Escape') {
        close();
      }
    });
  }

  // Boot
  if (document.readyState !== 'loading') bind();
  else document.addEventListener('DOMContentLoaded', bind);
})(jQuery);
