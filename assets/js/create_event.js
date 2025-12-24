// Логіка для сторінки створення події
class CreateEventManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupCategoryToggle();
        this.setupImagePreview();
        this.setupDateValidation();
        this.setupErrorClearing();
    }

    setupCategoryToggle() {
        const categorySelect = document.getElementById('categorySelect');
        const customField = document.getElementById('custom-category');

        if (!categorySelect || !customField) return;

        categorySelect.addEventListener('change', function () {
            customField.style.display = this.value === 'Інше'
                ? 'block'
                : 'none';
        });
    }

    setupImagePreview() {
        const imageInput = document.getElementById('eventImage');
        const imagePreview = document.getElementById('previewImage');

        if (!imageInput || !imagePreview) return;

        imageInput.addEventListener('change', function () {
            const file = this.files[0];

            if (!file) {
                imagePreview.innerHTML = `<span>📸 Натисніть, щоб додати фото</span>`;
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                alert('Файл занадто великий. Максимум 5MB');
                this.value = '';
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Дозволені формати: JPG, PNG, GIF, WebP');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = e => {
                imagePreview.innerHTML = `<img src="${e.target.result}" alt="Попередній перегляд">`;
            };
            reader.readAsDataURL(file);
        });
    }

    setupDateValidation() {
        const dateInput = document.getElementById('eventDate');
        if (!dateInput) return;

        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
    }

    setupErrorClearing() {
        document.querySelectorAll('input, textarea, select').forEach(el => {
            el.addEventListener('input', function () {
                this.classList.remove('field-error');
                const error = this.parentElement.querySelector('.error-text');
                if (error) error.remove();
            });
        });
    }
}
function truncateText(text, maxLength = 120) {
    if (!text) return '';
    return text.length > maxLength
        ? text.substring(0, maxLength) + '...'
        : text;
}

// Ініціалізація
document.addEventListener('DOMContentLoaded', () => {
    new CreateEventManager();
    /* ===== 1. УНІВЕРСАЛЬНЕ ОНОВЛЕННЯ ТЕКСТУ ===== */

    const map = {
        eventTitle: 'previewTitle',
        eventLocation: 'previewLocation',
        categorySelect: 'previewCategory'
    };

    Object.keys(map).forEach(inputId => {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(map[inputId]);

        if (!input || !preview) return;

        const placeholder = preview.textContent;

        input.addEventListener('input', () => {
            preview.textContent = input.value || placeholder;
        });

        input.addEventListener('change', () => {
            preview.textContent = input.value || placeholder;
        });
    });

    /* ===== 2. ДАТА (ОКРЕМО, КРАСИВО) ===== */

    const eventDate = document.getElementById('eventDate');
    const previewDate = document.getElementById('previewDate');

    if (eventDate && previewDate) {
        eventDate.addEventListener('input', () => {
            if (!eventDate.value) {
                previewDate.textContent = '📅 Дата';
                return;
            }

            const d = new Date(eventDate.value);
            previewDate.textContent = `📅 ${d.toLocaleDateString('uk-UA')}`;
        });
    }

    /* ===== 3. ФОТО ===== */

    const imageInput = document.getElementById('eventImage');
    const previewImage = document.getElementById('previewImage');


    if (imageInput && previewImage) {
        imageInput.addEventListener('change', () => {
            const file = imageInput.files[0];
            if (!file) return;

            const reader = new FileReader();

            reader.onload = e => {
                previewImage.innerHTML = `<img src="${e.target.result}">`;
            };
            document.querySelector('.upload-btn').classList.toggle('has-file', imageInput.files.length > 0);
            reader.readAsDataURL(file);
        });
    }


    const descriptionInput = document.getElementById('eventDescription');
    const previewDescription = document.getElementById('previewDescription');
    const DESCRIPTION_LIMIT = 120;

    if (descriptionInput && previewDescription) {
        const placeholder = previewDescription.textContent;

        descriptionInput.addEventListener('input', () => {
            const text = descriptionInput.value.trim();

            previewDescription.textContent = text
                ? truncateText(text, DESCRIPTION_LIMIT)
                : placeholder;
        });
    }




});


