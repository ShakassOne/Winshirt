(function(){
  'use strict';

  function qs(s,ctx){return (ctx||document).querySelector(s);}
  function qsa(s,ctx){return Array.from((ctx||document).querySelectorAll(s));}

  let modal, openers, closers;

  function open(){
    if(!modal) return;
    modal.classList.add('ws-open');
    document.body.classList.add('ws-modal-open');
    // événement interne
    document.dispatchEvent(new CustomEvent('winshirt:modal:open'));
  }
  function close(){
    if(!modal) return;
    modal.classList.remove('ws-open');
    document.body.classList.remove('ws-modal-open');
    document.dispatchEvent(new CustomEvent('winshirt:modal:close'));
  }

  function onKey(e){
    if(e.key === 'Escape') close();
  }

  function boot(){
    modal   = qs('#winshirt-customizer-modal');
    if(!modal) return;

    openers = qsa('[data-ws-open-customizer]');
    openers.forEach(b=> b.addEventListener('click', open));

    closers = qsa('[data-ws-close]', modal);
    closers.forEach(b=> b.addEventListener('click', close));

    document.addEventListener('keydown', onKey);

    // L1 navigation → ouvre L2 (+ titre)
    const l1 = qsa('.ws-l1-item', modal);
    l1.forEach(btn=>{
      btn.addEventListener('click', ()=>{
        l1.forEach(b=>b.classList.remove('is-active'));
        btn.classList.add('is-active');
        const panel = btn.getAttribute('data-panel');
        const title = btn.textContent.trim();
        qs('.ws-l2 .ws-l2-title', modal).textContent = title;
        document.dispatchEvent(new CustomEvent('winshirt:panel:'+panel, { detail:{ l2: qs('.ws-l2', modal) } }));
      });
    });

    qs('.ws-l2-back', modal).addEventListener('click', ()=>{
      // simple effet : remonter en haut
      qs('.ws-l2 .ws-l2-body', modal).scrollTo({top:0,behavior:'smooth'});
    });
  }

  if(document.readyState !== 'loading') boot(); else document.addEventListener('DOMContentLoaded', boot);
})();
