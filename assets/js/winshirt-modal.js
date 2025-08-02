document.addEventListener('DOMContentLoaded', function() {
  const openBtn  = document.getElementById('winshirt-open-modal');
  const closeBtn = document.getElementById('winshirt-close-modal');
  const modal    = document.getElementById('winshirt-customizer-modal');

  if (!openBtn || !modal) return;

  openBtn.addEventListener('click', () => {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // bloque scroll arrière-plan
  });

  closeBtn.addEventListener('click', () => {
    modal.style.display = 'none';
    document.body.style.overflow = '';
  });

  // Fermer au clic hors du contenu
  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.style.display = 'none';
      document.body.style.overflow = '';
    }
  });

  // (Pour la suite : intégrer interact.js, html2canvas, drag/resize…)
});
