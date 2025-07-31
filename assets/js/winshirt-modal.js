document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('winshirt-customizer-modal');

    function openModal() {
        if (modal) {
            modal.style.display = 'block';
        }
    }

    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
        }
    }

    document.body.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-personnaliser')) {
            e.preventDefault();
            openModal();
        }
        if (e.target.classList.contains('winshirt-modal-close') || e.target === modal) {
            closeModal();
        }
    });

    window.openModal = openModal;
    window.closeModal = closeModal;
});
