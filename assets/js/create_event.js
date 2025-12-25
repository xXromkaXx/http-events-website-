// Логіка для сторінки створення події
class CreateEventManager {


    constructor() {
        this.form = document.getElementById('createEventForm');
        this.categorySelect = document.getElementById('categorySelect');
        this.customCategory = document.getElementById('custom-category');

        this.init();
    }

    init() {
        this.setupCategoryToggle();
        this.setupImagePreview();
        this.setupDateValidation();
        this.setupErrorClearing();
        this.setupSubmitValidation();
        this.updateCategoryPreview();
        this.disableEnterSubmit();

    }
    disableEnterSubmit() {
        this.form.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {

                // textarea — дозволяємо Enter
                if (e.target.tagName === 'TEXTAREA') return;

                e.preventDefault();

                const fields = Array.from(
                    this.form.querySelectorAll('input, select, textarea')
                ).filter(el => !el.hidden && !el.disabled);

                const index = fields.indexOf(e.target);
                if (index > -1 && fields[index + 1]) {
                    fields[index + 1].focus();
                }
            }
        });
    }

    setupCategoryToggle() {
        if (!this.categorySelect || !this.customCategory) return;

        this.categorySelect.addEventListener('change', () => {
            if (this.categorySelect.value === 'Інше') {
                this.customCategory.classList.remove('hidden');
                this.customCategory.setAttribute('required', 'required');
                this.customCategory.focus();
            } else {
                this.customCategory.classList.add('hidden');
                this.customCategory.removeAttribute('required');
                this.customCategory.value = '';
            }

            this.updateCategoryPreview();
        });

        this.customCategory.addEventListener('input', () => {
            this.updateCategoryPreview();
        });
    }


    updateCategoryPreview() {
        const previewCategory = document.getElementById('previewCategory');
        if (!previewCategory) return;

        const placeholder = 'Категорія';

        if (
            this.categorySelect.value === 'Інше' &&
            this.customCategory.value.trim()
        ) {
            previewCategory.textContent = this.customCategory.value.trim();
        } else if (this.categorySelect.value) {
            previewCategory.textContent = this.categorySelect.value;
        } else {
            previewCategory.textContent = placeholder;
        }
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



    setupSubmitValidation() {
        if (!this.form) return;

        this.form.addEventListener('submit', (e) => {
            if (
                this.categorySelect.value === 'Інше' &&
                !this.customCategory.value.trim()
            ) {
                e.preventDefault();

                this.customCategory.classList.add('field-error');
                this.customCategory.focus();
            }
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
    ['eventTitle','eventLocation','categorySelect','eventDescription']
        .forEach(id => {
            const el = document.getElementById(id);
            if (el) el.dispatchEvent(new Event('input'));
        });
    const map = {
        eventTitle: 'previewTitle',
        eventLocation: 'previewLocation',
        categorySelect: 'previewCategory'
    };

    Object.keys(map).forEach(inputId => {
        const categorySelect = document.getElementById('categorySelect');
        const customCategory = document.getElementById('custom-category');
        const previewCategory = document.getElementById('previewCategory');
        const categoryPlaceholder = previewCategory.textContent;

        function updateCategoryPreview() {
            if (categorySelect.value === 'Інше' && customCategory.value.trim()) {
                previewCategory.textContent = customCategory.value.trim();
            } else if (categorySelect.value) {
                previewCategory.textContent = categorySelect.value;
            } else {
                previewCategory.textContent = categoryPlaceholder;
            }
        }


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


