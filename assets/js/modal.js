// Універсальний менеджер модальних вікон для всіх сторінок
class UniversalModalManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupViewModal();
        this.setupEditModal();
        this.setupEventListeners();
        console.log('UniversalModalManager ініціалізовано');
    }

    setupEventListeners() {
        // Використовуємо делегування подій для всіх кнопок
        document.addEventListener('click', (e) => {
            const target = e.target;

            // Кнопка "Детальніше" - працює на всіх сторінках
            if (target.classList.contains('btn-view') || target.closest('.btn-view')) {
                const btn = target.classList.contains('btn-view') ? target : target.closest('.btn-view');
                const eventId = btn.getAttribute('data-event-id');
                if (eventId) {
                    this.openViewModal(eventId);
                }
                e.preventDefault();
            }

            // Кнопка "Редагувати" - тільки на my_events.php
            if (target.classList.contains('btn-edit') || target.closest('.btn-edit')) {
                const btn = target.classList.contains('btn-edit') ? target : target.closest('.btn-edit');
                const eventId = btn.getAttribute('data-event-id');
                if (eventId) {
                    this.openEditModal(eventId);
                }
                e.preventDefault();
            }

            // Кнопка "Видалити" - тільки на my_events.php
            if (target.classList.contains('btn-delete') || target.closest('.btn-delete')) {
                const btn = target.classList.contains('btn-delete') ? target : target.closest('.btn-delete');
                const eventId = btn.getAttribute('data-event-id');
                const eventTitle = btn.getAttribute('data-event-title');
                if (eventId && eventTitle) {
                    this.confirmDelete(eventId, eventTitle, btn);
                }
                e.preventDefault();
            }
        });

        // Закриття по ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeViewModal();
                this.closeEditModal();
            }
        });
    }

    setupViewModal() {
        const modal = document.getElementById('eventModal');
        if (!modal) {
            console.warn('Модальне вікно перегляду не знайдено');
            return;
        }

        const closeBtn = modal.querySelector('.close-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeViewModal());
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeViewModal();
            }
        });
    }

    setupEditModal() {
        const editForm = document.getElementById('editEventForm');
        if (!editForm) {
            // Це нормально для index.php, де немає форми редагування
            return;
        }

        editForm.addEventListener('submit', (e) => this.handleEditFormSubmit(e));

        const closeBtn = document.querySelector('#editEventModal .close-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeEditModal());
        }

        const editModal = document.getElementById('editEventModal');
        if (editModal) {
            editModal.addEventListener('click', (e) => {
                if (e.target === editModal) {
                    this.closeEditModal();
                }
            });
        }

        // Обробка зміни зображення
        const editImageInput = document.getElementById('editImage');
        if (editImageInput) {
            editImageInput.addEventListener('change', (e) => this.handleImageChange(e));
        }
    }

    openViewModal(eventId) {
        // Шукаємо картку події
        const card = document.querySelector(`.event-card[data-id="${eventId}"]`);
        if (!card) {
            console.error('Картку події не знайдено для ID:', eventId);
            return;
        }

        const modal = document.getElementById('eventModal');
        const modalImage = document.getElementById('modalImage');
        const modalTitle = document.getElementById('modalTitle');
        const modalCategory = document.querySelector('.modal-category');
        const modalLocation = document.querySelector('.modal-location');
        const modalDate = document.querySelector('.modal-date');
        const modalDescription = document.getElementById('modalDescription');
        const modalTime = document.querySelector('.modal-time');
        const authorBadge = document.getElementById('authorBadge');
        const modalAuthorName = document.getElementById('modalAuthorName');

        if (modal && modalImage && modalTitle) {
            // Отримуємо дані з картки
            const imageUrl = card.dataset.image || 'assets/images/default-event.jpg';
            const title = card.dataset.title || 'Без назви';
            const category = card.dataset.category || 'Без категорії';
            const location = card.dataset.location || 'Без локації';
            const date = card.dataset.date || 'Не вказано';
            const time = card.dataset.time || '';
            const description = card.dataset.description || 'Опис відсутній';
            const creator = card.dataset.creator; // Може бути undefined

            // Заповнюємо модальне вікно
            modalImage.src = imageUrl;
            modalImage.alt = title;
            modalTitle.textContent = title;

            if (modalCategory) modalCategory.textContent = "Категорія: " + category;
            if (modalLocation) modalLocation.textContent = "📍 " + location;
            if (modalDate) modalDate.textContent = "📅 " + date;
            if (modalTime) modalTime.textContent = time ? "🕒 " + time : "";
            if (modalDescription) modalDescription.textContent = description;

            // Показуємо бейдж автора, якщо є інформація
            if (authorBadge && modalAuthorName) {
                if (creator && creator !== 'Невідомий користувач' && creator !== '') {
                    modalAuthorName.textContent = creator;
                    authorBadge.style.display = 'flex';
                } else {
                    authorBadge.style.display = 'none';
                }
            }

            modal.classList.add('show');
            document.querySelector('header')?.classList.add('hidden');
            document.body.classList.add('no-scroll');
        }
    }

    closeViewModal() {
        const modal = document.getElementById('eventModal');
        if (modal) {
            modal.classList.remove('show');
            document.querySelector('header')?.classList.remove('hidden');
            document.body.classList.remove('no-scroll');
        }
    }

    openEditModal(eventId) {
        console.log('Відкриття редагування для події:', eventId);

        const card = document.querySelector(`.event-card[data-id="${eventId}"]`);
        if (!card) {
            console.error('Картку події не знайдено для редагування:', eventId);
            return;
        }

        // Заповнюємо форму даними з картки
        document.getElementById('editEventId').value = eventId;
        document.getElementById('editTitle').value = card.dataset.title || '';
        document.getElementById('editCategory').value = card.dataset.category || '';
        document.getElementById('editLocation').value = card.dataset.location || '';
        document.getElementById('editDate').value = card.dataset.date || '';
        document.getElementById('editTime').value = card.dataset.time || '';
        document.getElementById('editDescription').value = card.dataset.description || '';

        // Показуємо поточне зображення
        const imagePreview = document.getElementById('currentImagePreview');
        if (imagePreview) {
            const imageUrl = card.dataset.image || 'assets/images/default-event.jpg';
            imagePreview.innerHTML = `
                <p>Поточне зображення:</p>
                <img src="${imageUrl}" alt="Поточне зображення" style="max-width: 200px; margin-top: 10px; border-radius: 8px;">
            `;
        }

        // Скидаємо поле вибору файлу
        const fileInput = document.getElementById('editImage');
        if (fileInput) {
            fileInput.value = '';
        }

        // Відкриваємо модальне вікно
        const editModal = document.getElementById('editEventModal');
        if (editModal) {
            editModal.classList.add('show');
            document.body.classList.add('no-scroll');
        }
    }

    closeEditModal() {
        const modal = document.getElementById('editEventModal');
        if (modal) {
            modal.classList.remove('show');
            document.body.classList.remove('no-scroll');
        }
    }

    async handleEditFormSubmit(e) {
        e.preventDefault();
        console.log('Відправка форми редагування');

        const formData = new FormData(e.target);
        const saveButton = e.target.querySelector('.btn-save');
        const originalText = saveButton.textContent;

        // Показуємо індикатор завантаження
        saveButton.textContent = 'Збереження...';
        saveButton.disabled = true;

        try {
            const response = await fetch('functions/update_event.php', {
                method: 'POST',
                body: formData
            });

            const responseText = await response.text();
            console.log('Відповідь сервера:', responseText);

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Помилка парсингу JSON:', parseError);
                throw new Error('Сервер повернув некоректну відповідь');
            }

            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }

            if (data.success) {
                this.updateEventCard(data.event);
                this.closeEditModal();
                this.showNotification('Подію успішно оновлено!', 'success');
            } else {
                throw new Error(data.message || 'Невідома помилка при оновленні');
            }

        } catch (error) {
            console.error('Помилка при оновленні:', error);
            this.showNotification(error.message, 'error');
        } finally {
            saveButton.textContent = originalText;
            saveButton.disabled = false;
        }
    }

    handleImageChange(e) {
        const file = e.target.files[0];
        const imagePreview = document.getElementById('currentImagePreview');

        if (file && imagePreview) {
            if (file.size > 5 * 1024 * 1024) {
                this.showNotification('Файл занадто великий. Максимальний розмір: 5MB', 'error');
                e.target.value = '';
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                this.showNotification('Дозволені тільки зображення у форматах: JPG, PNG, GIF, WebP', 'error');
                e.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.innerHTML = `
                    <p>Нове зображення:</p>
                    <img src="${e.target.result}" alt="Нове зображення" style="max-width: 200px; margin-top: 10px; border-radius: 8px;">
                    <p><small>Попередній перегляд нового зображення</small></p>
                `;
            };
            reader.readAsDataURL(file);
        }
    }

    updateEventCard(updatedEvent) {
        const card = document.querySelector(`.event-card[data-id="${updatedEvent.id}"]`);
        if (!card) {
            console.error('Картку для оновлення не знайдено');
            return;
        }

        // Оновлюємо дані картки
        card.dataset.title = updatedEvent.title;
        card.dataset.category = updatedEvent.category;
        card.dataset.location = updatedEvent.location;
        card.dataset.date = updatedEvent.event_date;
        card.dataset.time = updatedEvent.event_time;
        card.dataset.description = updatedEvent.description;
        if (updatedEvent.image) {
            card.dataset.image = updatedEvent.image;
        }

        // Оновлюємо відображення
        const titleElement = card.querySelector('h3');
        const categoryElement = card.querySelector('.event-category');
        const locationElement = card.querySelector('.event-location');
        const dateElement = card.querySelector('.event-date');
        const descriptionElement = card.querySelector('.event-details-description');
        const imageElement = card.querySelector('.event-image img');

        if (titleElement) titleElement.textContent = updatedEvent.title;
        if (categoryElement) categoryElement.textContent = 'Категорія: ' + (updatedEvent.category || 'Без категорії');
        if (locationElement) locationElement.textContent = '📍 ' + (updatedEvent.location || 'Без локації');

        if (dateElement) {
            dateElement.innerHTML = `📅 ${updatedEvent.formatted_date || updatedEvent.event_date}`;
            if (updatedEvent.event_time) {
                dateElement.innerHTML += ` 🕒 ${updatedEvent.formatted_time || updatedEvent.event_time}`;
            }
        }

        if (descriptionElement) {
            descriptionElement.textContent = updatedEvent.short_description || updatedEvent.description;
        }

        if (updatedEvent.image && imageElement) {
            imageElement.src = updatedEvent.image;
            imageElement.alt = updatedEvent.title;
        }

        // Оновлюємо дані для кнопок
        const editBtn = card.querySelector('.btn-edit');
        const deleteBtn = card.querySelector('.btn-delete');
        const viewBtn = card.querySelector('.btn-view');

        if (editBtn) editBtn.setAttribute('data-event-title', updatedEvent.title);
        if (deleteBtn) deleteBtn.setAttribute('data-event-title', updatedEvent.title);
        if (viewBtn) viewBtn.setAttribute('data-event-title', updatedEvent.title);
    }

    confirmDelete(eventId, eventTitle, button) {
        console.log('Видалення події:', eventId, eventTitle);

        if (confirm(`Ви дійсно хочете видалити подію "${eventTitle}"?\nЦя дія незворотня.`)) {
            const originalText = button.innerHTML;
            button.innerHTML = '🗑️ Видалення...';
            button.disabled = true;
            this.deleteEvent(eventId, button);
        }
    }

    async deleteEvent(eventId, button) {
        try {
            console.log('Видалення події ID:', eventId);

            // Додаємо timestamp для уникнення кешування
            const url = `functions/delete_event.php?id=${eventId}&t=${Date.now()}`;
            console.log('Запит до:', url);

            const response = await fetch(url);

            console.log('Статус відповіді:', response.status);
            console.log('Content-Type:', response.headers.get('content-type'));

            const responseText = await response.text();
            console.log('Сира відповідь:', responseText);

            // Спрощена логіка - якщо відповідь містить успіх, вважаємо що видалення пройшло
            if (response.ok && (responseText.includes('успішно') || responseText.includes('success'))) {
                console.log('Видалення успішне (за текстом)');
                this.removeEventCard(eventId);
                this.showNotification('Подію успішно видалено!', 'success');
                return;
            }

            // Спроба парсингу JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Помилка парсингу JSON:', parseError);

                // Якщо це HTML-помилка, показуємо загальне повідомлення
                if (responseText.includes('<br />') || responseText.includes('<b>') || responseText.includes('<!DOCTYPE')) {
                    throw new Error('Серверна помилка. Спробуйте пізніше.');
                }
                throw new Error('Невідома помилка сервера');
            }

            if (result.success) {
                this.removeEventCard(eventId);
                this.showNotification(result.message, 'success');
            } else {
                throw new Error(result.message || 'Помилка при видаленні');
            }

        } catch (error) {
            console.error('Помилка при видаленні:', error);
            this.showNotification(error.message, 'error');

            // Відновлюємо кнопку
            if (button) {
                button.innerHTML = '🗑️ Видалити';
                button.disabled = false;
            }
        }
    }

    removeEventCard(eventId) {
        const card = document.querySelector(`.event-card[data-id="${eventId}"]`);
        if (card) {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'translateX(-100px)';

            setTimeout(() => {
                card.remove();
                this.checkEmptyEvents();
            }, 300);
        } else {
            // Якщо картку не знайдено, просто перезавантажуємо сторінку
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    }

    showNotification(message, type = 'info') {
        if (typeof showNotification === 'function') {
            showNotification(message, type);
        } else {
            alert(message);
        }
    }
}

// Ініціалізація універсального менеджера
document.addEventListener('DOMContentLoaded', function() {
    window.eventModalManager = new UniversalModalManager();
});