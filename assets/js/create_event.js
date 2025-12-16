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

        if (categorySelect && customField) {
            categorySelect.addEventListener('change', function() {
                if (this.value === 'Інше') {
                    customField.style.display = 'block';
                } else {
                    customField.style.display = 'none';
                }
            });
        }
    }

    setupImagePreview() {
        const imageInput = document.getElementById('eventImage');
        const imagePreview = document.getElementById('imagePreview');

        if (imageInput && imagePreview) {
            imageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    // Перевірка розміру файлу
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Файл занадто великий. Максимальний розмір: 5MB');
                        this.value = '';
                        return;
                    }

                    // Перевірка типу файлу
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Дозволені тільки зображення у форматах: JPG, PNG, GIF, WebP');
                        this.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.innerHTML = `<img src="${e.target.result}" alt="Попередній перегляд">`;
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.innerHTML = `<span>📸 Натисніть, щоб додати фото</span>`;
                }
            });
        }
    }

    setupDateValidation() {
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById('eventDate');
            if (dateInput) {
                dateInput.setAttribute('min', today);
            }
        });
    }

    setupErrorClearing() {
        document.querySelectorAll('input, textarea, select').forEach(element => {
            element.addEventListener('input', function() {
                if (this.classList.contains('field-error')) {
                    this.classList.remove('field-error');
                    const errorText = this.parentElement.querySelector('.error-text');
                    if (errorText) {
                        errorText.remove();
                    }
                }
            });
        });
    }
}

// Ініціалізація на сторінці створення події
document.addEventListener('DOMContentLoaded', function() {
    window.createEventManager = new CreateEventManager();
});