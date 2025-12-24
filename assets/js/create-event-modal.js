
document.addEventListener('DOMContentLoaded', () => {

    const modal = document.getElementById('eventModal');
    const closeBtn = modal.querySelector('.close-modal');

    document.querySelectorAll('.event-card').forEach(card => {
        card.addEventListener('click', () => {

            document.getElementById('modalTitle').textContent =
                card.dataset.title || '';

            document.querySelector('.modal-category').textContent =
                card.dataset.category || '';

            document.querySelector('.modal-location').textContent =
                card.dataset.location || '';

            document.querySelector('.modal-date').textContent =
                card.dataset.date || '';

            document.querySelector('.modal-time').textContent =
                card.dataset.time || '';

            document.getElementById('modalDescription').textContent =
                card.dataset.description || '';

            document.getElementById('modalImage').src =
                card.dataset.image || '/assets/img/no-image.png';

            modal.classList.add('active');
        });
    });

    closeBtn.addEventListener('click', () => {
        modal.classList.remove('active');
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });

});
