(function(){
  const $$ = (sel, ctx=document)=>Array.from(ctx.querySelectorAll(sel));

  // ===== util: timer =====
  function tickTimers(){
    const now = Math.floor(Date.now()/1000);
    $$('.wsl-timer').forEach(el=>{
      const end = parseInt(el.dataset.end||'0',10);
      const diff = Math.max(0, end - now);
      const d = Math.floor(diff/86400);
      const h = Math.floor((diff%86400)/3600);
      const m = Math.floor((diff%3600)/60);
      const s = diff%60;
      el.querySelector('.wsl-tj').textContent = d;
      el.querySelector('.wsl-th').textContent = h;
      el.querySelector('.wsl-tm').textContent = m;
      el.querySelector('.wsl-ts').textContent = s;
    });
  }
  tickTimers();
  setInterval(tickTimers, 1000); // 1s, simple & fiable

  // ===== sliders init =====
  $$('.wsl-swiper').forEach(root=>{
    const wrap = root.querySelector('.swiper');
    const dots = root.querySelector('.wsl-dots');
    const prev = root.querySelector('.wsl-prev');
    const next = root.querySelector('.wsl-next');

    const layout   = root.dataset.layout || 'slider';
    const gap      = parseInt(root.dataset.gap||'24',10);
    const cols     = Math.max(1, Math.min(4, parseInt(root.dataset.cols||'3',10)));
    const autoplay = parseInt(root.dataset.autoplay||'0',10);
    const speed    = parseInt(root.dataset.speed||'600',10);
    const loop     = root.dataset.loop === '1';

    const isDiagonal = layout === 'diagonal';

    const swiper = new Swiper(wrap, {
      speed,
      loop,                    // 0 = ne repart pas au début
      autoplay: autoplay ? { delay: autoplay, disableOnInteraction: false } : false,
      spaceBetween: isDiagonal ? 140 : gap,
      centeredSlides: true,
      slidesPerView: isDiagonal ? 'auto' : (cols >= 3 ? 3 : cols),
      allowTouchMove: true,
      grabCursor: true,
      navigation: { prevEl: prev, nextEl: next },
      pagination: { el: dots, clickable: true },
      breakpoints: {
        0:   { slidesPerView: isDiagonal ? 'auto' : 1, spaceBetween: isDiagonal ? 100 : gap },
        640: { slidesPerView: isDiagonal ? 'auto' : Math.min(2, cols), spaceBetween: isDiagonal ? 120 : gap },
        1024:{ slidesPerView: isDiagonal ? 'auto' : Math.min(3, cols), spaceBetween: isDiagonal ? 140 : gap }
      },
      on:{
        init(sw){
          // force classes pour diagonale (pour l’alignement visuel)
          if(isDiagonal){ sw.updateSlides(); }
        }
      }
    });

    // accessibilité : flèches clavier
    root.addEventListener('keydown', (e)=>{
      if(e.key==='ArrowRight') swiper.slideNext();
      if(e.key==='ArrowLeft')  swiper.slidePrev();
    });
  });
})();
