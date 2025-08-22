// assets/js/diagonal.js
(() => {
  "use strict";

  // Vitesse
  const SPEED_WHEEL = 0.02;
  const SPEED_DRAG  = -0.10;

  // Calcule un z-index “pyramidal”
  const getZindex = (arr, activeIdx) =>
    arr.map((_, i) => (activeIdx === i) ? arr.length : arr.length - Math.abs(activeIdx - i));

  // Initialise UNE instance (un conteneur .winshirt-diagonal)
  function initInstance(root) {
    if (!root || root.__winshirtDiagonalInited) return;
    const items = root.querySelectorAll('.carousel-item');
    if (!items.length) return;

    root.__winshirtDiagonalInited = true;
    root.setAttribute('data-count', String(items.length));

    // État local à l’instance
    let progress = 50;
    let startX   = 0;
    let active   = 0;
    let isDown   = false;

    // Rendu d’un item
    const displayItem = (item, index, activeIndex) => {
      const zIndex = getZindex([...items], activeIndex)[index];
      item.style.setProperty('--zIndex', zIndex);
      item.style.setProperty('--active', (index - activeIndex) / items.length);
    };

    // Animation
    const animate = () => {
      progress = Math.max(0, Math.min(progress, 100));
      active   = Math.floor((progress / 100) * (items.length - 1));
      items.forEach((item, index) => displayItem(item, index, active));
    };
    animate();

    // Click sur une carte
    items.forEach((item, i) => {
      item.addEventListener('click', () => {
        progress = (i / items.length) * 100 + 10;
        animate();
      });
    });

    // Handlers (scopés au container)
    const onWheel = (e) => {
      const delta = (e.deltaY || 0) * SPEED_WHEEL;
      progress += delta;
      animate();
    };

    const onMove = (e) => {
      // Curseurs “cosmétiques”
      const cursors = root.querySelectorAll('.cursor');
      if (e.type === 'mousemove') {
        cursors.forEach(c => {
          c.style.transform = `translate(${e.clientX}px, ${e.clientY}px)`;
        });
      }
      if (!isDown) return;
      const x = e.clientX || (e.touches && e.touches[0]?.clientX) || 0;
      const delta = (x - startX) * SPEED_DRAG;
      progress += delta;
      startX = x;
      animate();
    };

    const onDown = (e) => {
      isDown = true;
      startX = e.clientX || (e.touches && e.touches[0]?.clientX) || 0;
    };
    const onUp = () => { isDown = false; };

    // Listeners sur le conteneur (pas sur document)
    root.addEventListener('wheel', onWheel, { passive: true });
    root.addEventListener('mousedown', onDown);
    root.addEventListener('mousemove', onMove);
    root.addEventListener('mouseup', onUp);
    root.addEventListener('mouseleave', onUp);
    root.addEventListener('touchstart', onDown, { passive: true });
    root.addEventListener('touchmove', onMove,  { passive: true });
    root.addEventListener('touchend', onUp);

    // Petit log utile en dev (harmless en prod)
    if (window && window.console) {
      const uid = root.id || '(no-id)';
      console.log(`[WinShirt:diagonal] OK, items=${items.length} uid=${uid}`);
    }
  }

  // Init sur DOM prêt
  const boot = () => {
    document.querySelectorAll('.winshirt-diagonal').forEach(initInstance);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  // Support Elementor (édition / preview)
  if (window.elementorFrontend && window.elementorFrontend.hooks) {
    window.elementorFrontend.hooks.addAction('frontend/element_ready/widget', () => {
      boot();
    });
    // certains widgets utilisent 'frontend/element_ready/global'
    window.elementorFrontend.hooks.addAction('frontend/element_ready/global', () => {
      boot();
    });
  }
})();
