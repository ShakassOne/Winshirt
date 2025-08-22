/* ===== WinShirt diagonal (by Shakass Communication) ===== */
(function () {
  function initInstance(root) {
    try {
      const track = root.querySelector('.carousel');
      const items = Array.from(root.querySelectorAll('.carousel-item'));
      if (!track || !items.length) {
        console.warn('[WinShirt:diagonal] aucun item', root);
        return;
      }

      // Positionnement diagonal simple
      const n = items.length;
      const mid = (n - 1) / 2;
      const gapX = 80;   // px vers la droite
      const gapY = -24;  // px vers le haut
      const rot  = -10;  // degrés

      items.forEach((el, i) => {
        const k = i - mid;
        const x = Math.round(k * gapX);
        const y = Math.round(k * gapY);
        const r = k * rot;
        const z = 100 + i; // ordre d’empilement croissant

        el.style.setProperty('--x', x + 'px');
        el.style.setProperty('--y', y + 'px');
        el.style.setProperty('--rot', r + 'deg');
        el.style.setProperty('--zIndex', z);
        el.style.setProperty('--opacity', '1');
      });

      // Cursors (optionnel)
      const c1 = root.querySelector('.cursor');
      const c2 = root.querySelector('.cursor2');
      root.addEventListener('mousemove', (e) => {
        if (!c1 || !c2) return;
        const rect = root.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        c1.style.transform = `translate(${x}px, ${y}px)`;
        c2.style.transform = `translate(${x}px, ${y}px)`;
      });

      console.info('[WinShirt:diagonal] OK, items=', n, 'uid=', root.dataset.uid);
    } catch (err) {
      console.error('[WinShirt:diagonal] erreur', err);
    }
  }

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    document
      .querySelectorAll('.winshirt-diagonal')
      .forEach(initInstance);
  });
})();
