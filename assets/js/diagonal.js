(function () {
  const speedWheel = 0.02;
  const speedDrag  = -0.1;

  // Pour chaque instance présente sur la page
  document.querySelectorAll('.winshirt-diagonal').forEach((root) => {
    let progress = 50;
    let startX = 0;
    let active = 0;
    let isDown = false;

    const $items   = root.querySelectorAll('.carousel-item');
    const $cursors = root.querySelectorAll('.cursor');

    // calcule le z-index par rapport à l’item actif
    const getZindex = (array, index) =>
      array.map((_, i) => (index === i) ? array.length : array.length - Math.abs(index - i));

    const displayItems = (item, index, active) => {
      const zIndex = getZindex([ ...$items ], active)[index];
      item.style.setProperty('--zIndex', zIndex);
      item.style.setProperty('--active', (index - active) / $items.length);
    };

    const animate = () => {
      progress = Math.max(0, Math.min(progress, 100));
      active = Math.floor(progress / 100 * ($items.length - 1));
      $items.forEach((item, index) => displayItems(item, index, active));
    };
    animate();

    // click : sauter à un item
    $items.forEach((item, i) => {
      item.addEventListener('click', () => {
        progress = (i / $items.length) * 100 + 10;
        animate();
      });
    });

    // handlers scope "root"
    const handleWheel = (e) => {
      e.preventDefault();
      const wheelProgress = (e.deltaY || e.wheelDelta || -e.detail) * speedWheel;
      progress = progress + wheelProgress;
      animate();
    };

    const handleMouseMove = (e) => {
      const clientX = e.clientX || (e.touches && e.touches[0].clientX) || 0;
      const clientY = e.clientY || (e.touches && e.touches[0].clientY) || 0;

      // cursors
      $cursors.forEach(($cursor) => {
        $cursor.style.transform = `translate(${clientX - root.getBoundingClientRect().left}px, ${clientY - root.getBoundingClientRect().top}px)`;
      });

      if (!isDown) return;
      const mouseProgress = (clientX - startX) * speedDrag;
      progress = progress + mouseProgress;
      startX = clientX;
      animate();
    };

    const handleMouseDown = (e) => {
      isDown = true;
      startX = e.clientX || (e.touches && e.touches[0].clientX) || 0;
    };

    const handleMouseUp = () => { isDown = false; };

    // listeners limités au container
    root.addEventListener('wheel',        handleWheel,     { passive: false });
    root.addEventListener('mousedown',    handleMouseDown);
    root.addEventListener('mousemove',    handleMouseMove);
    root.addEventListener('mouseup',      handleMouseUp);
    root.addEventListener('mouseleave',   handleMouseUp);
    root.addEventListener('touchstart',   handleMouseDown, { passive: true });
    root.addEventListener('touchmove',    handleMouseMove, { passive: false });
    root.addEventListener('touchend',     handleMouseUp,   { passive: true });
  });
})();
