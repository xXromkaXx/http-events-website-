// Універсальний менеджер модальних вікон для всіх сторінок
class UniversalModalManager {
    constructor() {
        this.currentEventId = null;
        this.isModalOpen = false;
        this.savedScrollY = 0;
        this.init();
    }
    setupMobileUI() {
        // Створюємо модалку для коментарів
        this.createMobileCommentsModal();

        // Налаштовуємо мобільні кнопки


    }
    init() {

        this.setupViewModal();
        this.setupEditModal();
        this.setupEventListeners();
        this.setupCommentSend();
        this.setupTouchGestures();
        this.setupMobileUI();
        this.setupActionButtons();

    }
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatDescription(text, limit = 130) {
        if (!text) return '';

        if (text.length <= limit) {
            return `<span>${this.escapeHtml(text)}</span>`;
        }

        const shortText = this.escapeHtml(text.slice(0, limit));
        const fullText = this.escapeHtml(text);

        return `<span class="short-text">${shortText}<span class="read-more">… Більше</span></span><span class="more-text" style="display:none;">${fullText}<span class="read-less"> Менше</span></span>`;

    }


    createMobileCommentsModal() {
        // Якщо модалка вже існує - не створюємо нову
        if (document.getElementById('mobileCommentsModal')) return;

        const modal = document.createElement('div');
        modal.className = 'mobile-comments-modal';
        modal.id = 'mobileCommentsModal';
        modal.innerHTML = `
        <div class="mobile-comments-header">
            <h3>💬 Коментарі</h3>
            <button class="close-comments">&times;</button>
        </div>
        <div class="mobile-comments-list" id="mobileCommentsList"></div>
        <div class="mobile-comment-input-area">
            <input type="text" id="mobileCommentText" placeholder="Написати коментар...">
            <button id="sendMobileComment" type="button">➤</button>
        </div>
    `;

        document.body.appendChild(modal);

        // Обробники подій
        modal.querySelector('.close-comments').addEventListener('click', () => {
            modal.classList.remove('show');
        });

        modal.querySelector('#sendMobileComment').addEventListener('click', () => {
            this.sendMobileComment();
        });

        modal.querySelector('#mobileCommentText').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMobileComment();
        });
    }



    async openMobileComments() {
        const modal = document.getElementById('mobileCommentsModal');
        if (!modal || !this.currentEventId) return;

        // Завантажуємо коментарі
        await this.loadMobileComments(this.currentEventId);

        // Показуємо модалку
        modal.classList.add('show');

        // Фокус на поле вводу
        setTimeout(() => {
            const input = modal.querySelector('#mobileCommentText');
            if (input) input.focus();
        }, 350);
    }

    async loadMobileComments(eventId) {
        const list = document.getElementById('mobileCommentsList');
        if (!list) return;

        list.innerHTML = '<div class="loading-comments">Завантаження...</div>';

        try {
            const res = await fetch(`functions/get_comments.php?event_id=${eventId}`);
            const comments = await res.json();

            if (!comments.length) {
                list.innerHTML = '<div class="no-comments">Коментарів ще немає. Будьте першим!</div>';
                return;
            }

            list.innerHTML = comments.map(comment => `
            <div class="mobile-comment">
                <div class="mobile-comment-header">
                    <strong>${comment.username || 'Користувач'}</strong>
                    <span>${comment.created_at || 'сьогодні'}</span>
                </div>
                <div class="mobile-comment-text">${comment.content}</div>
            </div>
        `).join('');

        } catch (error) {
            list.innerHTML = '<div class="error">Помилка завантаження</div>';
        }
    }

    async sendMobileComment() {
        const input = document.getElementById('mobileCommentText');
        const text = input?.value.trim();

        if (!text || !this.currentEventId) return;

        try {
            const res = await fetch('functions/add_comment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `event_id=${this.currentEventId}&content=${encodeURIComponent(text)}`
            });

            const data = await res.json();

            if (data.success) {
                input.value = '';
                await this.loadMobileComments(this.currentEventId);
                await this.loadStats(this.currentEventId);

                // Оновлюємо лічильник на мобільній панелі
                const countEl = document.getElementById('mobileCommentsCount');
                if (countEl) {
                    const current = parseInt(countEl.textContent) || 0;
                    countEl.textContent = current + 1;
                }
            }
        } catch (error) {
            console.error('Помилка:', error);
        }
    }

    shareEventMobile() {
        if (!this.currentEventId) return;

        const title = document.getElementById('modalTitle')?.textContent || 'Подія';
        const url = `${window.location.origin}/event/${this.currentEventId}`;

        // Перевіряємо Web Share API
        if (navigator.share) {
            navigator.share({
                title,
                text: 'Подивись цю цікаву подію!',
                url
            }).catch(err => {
                console.log('Помилка поділення через Web Share API:', err);
                this.fallbackCopyToClipboard(url, title);
            });
        } else {
            // Фолбек метод копіювання
            this.fallbackCopyToClipboard(url, title);
        }
    }

// Допоміжна функція для копіювання в буфер обміну
    fallbackCopyToClipboard(text, title) {
        // Метод 1: Використання Clipboard API (сучасний)
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                this.showNotification(`Посилання на подію "${title}" скопійовано!`, 'success');
            }).catch(err => {
                console.error('Clipboard API помилка:', err);
                this.oldSchoolCopy(text, title);
            });
        } else {
            // Метод 2: Старий метод (для старих браузерів/небезпечних контекстів)
            this.oldSchoolCopy(text, title);
        }
    }

// Старий метод копіювання
    oldSchoolCopy(text, title) {
        try {
            // Створюємо тимчасовий textarea
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);

            // Вибираємо текст
            textArea.focus();
            textArea.select();

            // Копіюємо
            const successful = document.execCommand('copy');

            // Прибираємо textarea
            document.body.removeChild(textArea);

            if (successful) {
                this.showNotification(`Посилання на подію "${title}" скопійовано!`, 'success');
            } else {
                this.showCopyFallbackDialog(text, title);
            }
        } catch (err) {
            console.error('Старий метод копіювання не працює:', err);
            this.showCopyFallbackDialog(text, title);
        }
    }

// Діалог для ручного копіювання
    showCopyFallbackDialog(text, title) {
        // Створюємо модалку для ручного копіювання
        const modal = document.createElement('div');
        modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        padding: 20px;
    `;

        modal.innerHTML = `
        <div style="
            background: #1a1a2e;
            border-radius: 12px;
            padding: 24px;
            max-width: 500px;
            width: 100%;
            border: 2px solid var(--accent);
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        ">
            <h3 style="margin: 0 0 16px 0; color: var(--accent);">Поділитися подією</h3>
            <p style="margin: 0 0 12px 0; color: #fff; font-size: 14px;">
                Скопіюйте посилання нижче:
            </p>
            <div style="
                background: rgba(255,255,255,0.1);
                border-radius: 8px;
                padding: 12px;
                margin-bottom: 20px;
                border: 1px solid rgba(255,255,255,0.2);
                word-break: break-all;
                color: #fff;
                font-family: monospace;
                font-size: 14px;
                user-select: all;
                cursor: text;
            ">${text}</div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button id="copyManuallyBtn" style="
                    padding: 10px 20px;
                    background: var(--accent);
                    color: #000;
                    border: none;
                    border-radius: 8px;
                    font-weight: bold;
                    cursor: pointer;
                ">Скопіювати</button>
                <button id="closeCopyDialog" style="
                    padding: 10px 20px;
                    background: rgba(255,255,255,0.1);
                    color: #fff;
                    border: 1px solid rgba(255,255,255,0.3);
                    border-radius: 8px;
                    cursor: pointer;
                ">Закрити</button>
            </div>
        </div>
    `;

        document.body.appendChild(modal);

        // Обробка копіювання
        modal.querySelector('#copyManuallyBtn').addEventListener('click', () => {
            const textToCopy = text;
            const textArea = document.createElement('textarea');
            textArea.value = textToCopy;
            document.body.appendChild(textArea);
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    this.showNotification(`Посилання на подію "${title}" скопійовано!`, 'success');
                    document.body.removeChild(modal);
                }
            } catch (err) {
                console.error('Не вдалося скопіювати:', err);
                this.showNotification('Не вдалося скопіювати. Скопіюйте посилання вручну.', 'error');
            }

            document.body.removeChild(textArea);
        });

        // Обробка закриття
        modal.querySelector('#closeCopyDialog').addEventListener('click', () => {
            document.body.removeChild(modal);
        });

        // Закриття по кліку на фон
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
    }

// В методі loadStats додайте оновлення мобільних лічильників:
    async loadStats(eventId) {
        try {
            const res = await fetch(`./functions/get_event_stats.php?event_id=${eventId}`);
            const data = await res.json();

            // всі лічильники лайків
            document.querySelectorAll('[data-likes-count]')
                .forEach(el => el.textContent = data.likes_count || 0);

            // всі лічильники коментарів
            document.querySelectorAll('[data-comments-count]')
                .forEach(el => el.textContent = data.comments_count || 0);

            // всі кнопки лайку
            document.querySelectorAll('[data-action="like"]')
                .forEach(btn => {
                    btn.classList.toggle('liked', data.is_liked);
                    btn.classList.toggle('active', data.is_liked);
                });


        } catch (e) {
            console.error('Помилка статистики:', e);
        }
    }

    setupEventListeners() {
        // Використовуємо делегування подій для всіх кнопок
        document.addEventListener('click', (e) => {
            const target = e.target;

            // Кнопка "Детальніше"
            if (target.classList.contains('btn-view') || target.closest('.btn-view')) {
                const btn = target.classList.contains('btn-view') ? target : target.closest('.btn-view');
                const eventId = btn.getAttribute('data-event-id');
                if (eventId) {
                    this.openViewModal(eventId);
                }
                e.preventDefault();
                e.stopPropagation();
            }

            // Кнопка "Редагувати" (якщо існує на сторінці)
            if (target.classList.contains('btn-edit') || target.closest('.btn-edit')) {
                const btn = target.classList.contains('btn-edit') ? target : target.closest('.btn-edit');
                const eventId = btn.getAttribute('data-event-id');
                if (eventId) {
                    this.openEditModal(eventId);
                }
                e.preventDefault();
            }

            // Кнопка "Видалити"
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
            if (e.key === 'Escape' && this.isModalOpen) {
                this.closeAllModals();
            }
        });
    }

    setupViewModal() {
        const modal = document.getElementById('eventModal');
        if (!modal) {
            console.warn('Модальне вікно перегляду не знайдено');
            return;
        }

        const modalContent = modal.querySelector('.event-modal-content');
        modalContent.addEventListener('scroll', () => {
            modalContent.classList.toggle('scrolled', modalContent.scrollTop > 40);
        });

        if (modalContent) {
            modalContent.addEventListener('click', e => e.stopPropagation());
        }

        const closeBtn = modal.querySelector('.close-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeViewModal());
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                e.stopPropagation();
                this.closeViewModal();
            }
        });
    }

    setupEditModal() {
        const editForm = document.getElementById('editEventForm');
        if (!editForm) {
            // Це нормально для сторінок, де немає форми редагування
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
                    e.stopPropagation();
                }
            });
        }

        // Обробка зміни зображення
        const editImageInput = document.getElementById('editImage');
        if (editImageInput) {
            editImageInput.addEventListener('change', (e) => this.handleImageChange(e));
        }
    }

    setupTouchGestures() {
        const modal = document.getElementById('eventModal');
        if (!modal) return;

        const modalContent = modal.querySelector('.event-modal-content');
        if (!modalContent) return;

        let startY = 0;
        let endY = 0;

        modalContent.addEventListener('touchstart', (e) => {
            startY = e.touches[0].clientY;
        }, { passive: true });

        modalContent.addEventListener('touchmove', (e) => {
            endY = e.touches[0].clientY;
        }, { passive: true });

        modalContent.addEventListener('touchend', () => {
            const diff = endY - startY;

            // свайп вниз + ми на початку скролу
            if (diff > 120 && modalContent.scrollTop === 0) {
                this.closeViewModal();
            }
        });
    }




    async openViewModal(eventId) {
        this.currentEventId = eventId;

        const card = document.querySelector(`.event-card[data-id="${eventId}"]`);
        if (!card) {
            console.error('Картку події не знайдено для ID:', eventId);
            return;
        }

        const modal = document.getElementById('eventModal');
        const modalContent = modal.querySelector('.event-modal-content');
        if (!modal) return;

        // Отримуємо всі необхідні елементи
        const elements = {
            image: document.getElementById('modalImage'),
            title: document.getElementById('modalTitle'),
            category: document.querySelector('.modal-category'),
            location: document.querySelector('.modal-location .info-text'),
            date: document.querySelector('.modal-date .info-text'),
            time: document.querySelector('.modal-time .info-text'),
            description: document.getElementById('modalDescription')
           };

        // Дані з картки
        const eventData = {
            image: card.dataset.image || 'assets/img/default-event.jpg',
            title: card.dataset.title || 'Без назви',
            category: card.dataset.category || 'Без категорії',
            location: card.dataset.location || 'Не вказано',
            date: card.dataset.date || 'Не вказано',
            time: card.dataset.time || '',
            description: card.dataset.description || 'Опис відсутній',
            creator: card.dataset.creator,
            avatar: card.hasAttribute('data-avatar')
                ? card.dataset.avatar
                : 'assets/img/default-avatar.png'
        };

        // Заповнюємо модалку
        if (elements.image) elements.image.src = eventData.image;
        if (elements.image) elements.image.alt = eventData.title;
        if (elements.title) elements.title.textContent = eventData.title;
        if (elements.category) elements.category.textContent = eventData.category;
        if (elements.location) elements.location.textContent = eventData.location;
        if (elements.date) elements.date.textContent = eventData.date;
        if (elements.time) elements.time.textContent = eventData.time || 'Не вказано';
        if (elements.description) elements.description.innerHTML = this.formatDescription(eventData.description);

        elements.description.addEventListener('click', (e) => {
            if (e.target.classList.contains('read-more')) {
                const shortText = e.target.closest('.short-text');
                const moreText = shortText.nextElementSibling;
                moreText.style.whiteSpace = 'pre-wrap';
                shortText.style.display = 'none';
                moreText.style.display = 'inline';
            }

            if (e.target.classList.contains('read-less')) {
                const moreText = e.target.closest('.more-text');
                const shortText = moreText.previousElementSibling;
                shortText.style.whiteSpace = 'normal';
                moreText.style.display = 'none';
                shortText.style.display = 'inline';
            }
        });




        const author = this.getActiveAuthorElements();

        if (eventData.creator && eventData.creator !== 'Невідомий користувач') {
            if (author.name) author.name.textContent = eventData.creator;
            if (author.avatar) {
                author.avatar.src = eventData.avatar;
                author.avatar.alt = `Аватар ${eventData.creator}`;
            }
            if (author.container) author.container.style.display = 'flex';
        } else {
            if (author.container) author.container.style.display = 'none';
        }

        // Показуємо модалку
        this.lockBodyScroll();
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        document.querySelector('header')?.classList.add('hidden');

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                modalContent.scrollTop = 0;
            });
        });



        // Завантажуємо коментарі та статистику
        await Promise.all([
            this.loadComments(eventId),
            this.loadStats(eventId)
        ]);

        this.bindSaveButtons(eventId);
        this.loadSaveState(eventId);


    }
    bindSaveButtons(eventId) {
        document.querySelectorAll('[data-action="save"]').forEach(btn => {
            btn.dataset.eventId = eventId;

            btn.onclick = async () => {
                const id = btn.dataset.eventId;

                const res = await fetch('/ajax/save_event.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ event_id: id })
                });

                const data = await res.json();

                document.querySelectorAll(
                    `[data-action="save"][data-event-id="${id}"]`
                ).forEach(b => {
                    b.classList.toggle('saved', data.saved);
                });
            };
        });
    }

    async loadSaveState(eventId) {
        try {
            const res = await fetch(`/ajax/is_saved.php?event_id=${eventId}`);
            const data = await res.json();

            document.querySelectorAll('[data-action="save"]').forEach(btn => {
                btn.classList.toggle('saved', data.saved);
            });
        } catch (e) {
            console.error('Load save state error', e);
        }
    }


    getActiveAuthorElements() {
        const isMobile = window.matchMedia('(max-width: 900px)').matches;

        return {
            container: document.getElementById(
                isMobile ? 'eventAuthorMobile' : 'eventAuthorDesktop'
            ),
            avatar: document.getElementById(
                isMobile ? 'authorAvatarMobile' : 'authorAvatarDesktop'
            ),
            name: document.getElementById(
                isMobile ? 'modalAuthorNameMobile' : 'modalAuthorNameDesktop'
            )
        };
    }

    closeViewModal() {
        const modal = document.getElementById('eventModal');
        const commentsModal = document.getElementById('mobileCommentsModal');

        if (modal) {
            modal.classList.remove('show');
        }

        if (commentsModal) {
            commentsModal.classList.remove('show');
        }

        // Видаляємо класи з body і header
        document.querySelector('header')?.classList.remove('hidden');
        document.body.classList.remove('modal-open');

        this.unlockBodyScroll();

        this.currentEventId = null;
    }

     unlockBodyScroll() {
         if (window.innerWidth <= 768) return;

         document.body.style.position = '';
         document.body.style.top = '';
         document.body.style.width = '';
         window.scrollTo(0, this.savedScrollY);
    }


     lockBodyScroll() {
         if (window.innerWidth <= 768) return;

         this.savedScrollY = window.scrollY;
         document.body.style.position = 'fixed';
         document.body.style.top = `-${this.savedScrollY}px`;
         document.body.style.width = '100%';
    }

    openEditModal(eventId) {
        console.log('Відкриття редагування для події:', eventId);

        const card = document.querySelector(`.event-card[data-id="${eventId}"]`);
        if (!card) {
            console.error('Картку події не знайдено для редагування:', eventId);
            return;
        }

        // Отримуємо необхідні елементи
        const editEventId = document.getElementById('editEventId');
        const editTitle = document.getElementById('editTitle');
        const editCategory = document.getElementById('editCategory');
        const editLocation = document.getElementById('editLocation');
        const editDate = document.getElementById('editDate');
        const editTime = document.getElementById('editTime');
        const editDescription = document.getElementById('editDescription');

        if (!editEventId || !editTitle) {
            console.error('Не знайдено необхідні елементи форми редагування');
            return;
        }

        // Заповнюємо форму
        editEventId.value = eventId;
        editTitle.value = card.dataset.title || '';
        editCategory.value = card.dataset.category || '';
        editLocation.value = card.dataset.location || '';
        editDate.value = card.dataset.date || '';
        editTime.value = card.dataset.time || '';
        editDescription.value = card.dataset.description || '';

        // Показуємо поточне зображення
        const imagePreview = document.getElementById('currentImagePreview');
        if (imagePreview) {
            const imageUrl = card.dataset.image || 'assets/img/default-event.jpg';
            imagePreview.innerHTML = `
                <p style="margin-bottom: 10px; font-size: 14px; color: #666;">Поточне зображення:</p>
                <img src="${imageUrl}" alt="Поточне зображення" style="max-width: 100%; max-height: 200px; border-radius: 8px; margin-bottom: 10px;">
                <p style="font-size: 12px; color: #999;">Якщо ви не виберете нове зображення, залишиться це</p>
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
        }
    }

    closeEditModal() {
        const modal = document.getElementById('editEventModal');
        if (modal) {
            modal.classList.remove('show');
        }
    }

    closeAllModals() {
        // Закриваємо всі модалки
        const modals = [
            document.getElementById('eventModal'),
            document.getElementById('editEventModal'),
            document.getElementById('mobileCommentsModal')
        ];

        modals.forEach(modal => {
            if (modal) modal.classList.remove('show');
        });


        // Прибираємо класи
        document.querySelector('header')?.classList.remove('hidden');
        document.body.classList.remove('modal-open');

        this.currentEventId = null;
        this.isModalOpen = false;
    }

    async handleEditFormSubmit(e) {
        e.preventDefault();
        console.log('Відправка форми редагування');

        const formData = new FormData(e.target);
        const saveButton = e.target.querySelector('.btn-save');
        const originalText = saveButton?.textContent;

        try {
            // Показуємо індикатор завантаження
            if (saveButton) {
                saveButton.textContent = 'Збереження...';
                saveButton.disabled = true;
            }

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
            if (saveButton) {
                saveButton.textContent = originalText;
                saveButton.disabled = false;
            }
        }
    }

    handleImageChange(e) {
        const file = e.target.files[0];
        const imagePreview = document.getElementById('currentImagePreview');

        if (!file || !imagePreview) return;

        // Перевірка розміру
        if (file.size > 5 * 1024 * 1024) {
            this.showNotification('Файл занадто великий. Максимальний розмір: 5MB', 'error');
            e.target.value = '';
            return;
        }

        // Перевірка типу
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            this.showNotification('Дозволені тільки зображення у форматах: JPG, PNG, GIF, WebP', 'error');
            e.target.value = '';
            return;
        }

        // Показуємо прев'ю
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.innerHTML = `
                <p style="margin-bottom: 10px; font-size: 14px; color: #666;">Нове зображення:</p>
                <img src="${e.target.result}" alt="Попередній перегляд" style="max-width: 100%; max-height: 200px; border-radius: 8px; margin-bottom: 10px;">
                <p style="font-size: 12px; color: #999;">Попередній перегляд нового зображення</p>
            `;
        };
        reader.readAsDataURL(file);
    }

    updateEventCard(updatedEvent) {
        const card = document.querySelector(`.event-card[data-id="${updatedEvent.id}"]`);
        if (!card) {
            console.error('Картку для оновлення не знайдено');
            return;
        }

        // Оновлюємо dataset картки
        card.dataset.title = updatedEvent.title;
        card.dataset.category = updatedEvent.category;
        card.dataset.location = updatedEvent.location;
        card.dataset.date = updatedEvent.event_date;
        card.dataset.time = updatedEvent.event_time;
        card.dataset.description = updatedEvent.description;
        if (updatedEvent.image) {
            card.dataset.image = updatedEvent.image;
        }

        // Оновлюємо відображені елементи
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
            const formattedDate = updatedEvent.formatted_date || updatedEvent.event_date;
            const formattedTime = updatedEvent.formatted_time || updatedEvent.event_time;
            dateElement.innerHTML = `📅 ${formattedDate}`;
            if (formattedTime) {
                dateElement.innerHTML += ` 🕒 ${formattedTime}`;
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

            const url = `functions/delete_event.php?id=${eventId}&t=${Date.now()}`;
            const response = await fetch(url);

            const responseText = await response.text();
            console.log('Сира відповідь:', responseText);

            // Спрощена логіка перевірки успіху
            if (response.ok && (responseText.includes('успішно') || responseText.includes('success'))) {
                this.removeEventCard(eventId);
                this.showNotification('Подію успішно видалено!', 'success');
                return;
            }

            // Спроба парсингу JSON
            try {
                const result = JSON.parse(responseText);
                if (result.success) {
                    this.removeEventCard(eventId);
                    this.showNotification(result.message, 'success');
                } else {
                    throw new Error(result.message || 'Помилка при видаленні');
                }
            } catch (parseError) {
                if (responseText.includes('<') && (responseText.includes('br') || responseText.includes('DOCTYPE'))) {
                    throw new Error('Серверна помилка. Спробуйте пізніше.');
                }
                throw new Error('Невідома помилка сервера');
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
            // Анімація видалення
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'translateX(-100px)';

            setTimeout(() => {
                card.remove();
                this.checkEmptyEvents();
            }, 300);
        } else {
            // Якщо картку не знайдено, перезавантажуємо сторінку
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    }

    checkEmptyEvents() {
        const eventsContainer = document.querySelector('.events-grid');
        if (eventsContainer && eventsContainer.children.length === 0) {
            eventsContainer.innerHTML = `
                <div class="no-events">
                    <p>Подій не знайдено</p>
                </div>
            `;
        }
    }

    showNotification(message, type = 'info') {
        // Якщо є глобальна функція для сповіщень, використовуємо її
        if (typeof showNotification === 'function') {
            showNotification(message, type);
        } else {
            // Створюємо просте сповіщення
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                background: ${type === 'success' ? '#4CAF50' : '#f44336'};
                color: white;
                border-radius: 5px;
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    }

    async loadComments(eventId) {
        const modal = document.getElementById('eventModal');
        if (!modal) return;

        const list = modal.querySelector('.comments-list');
        const countEl = modal.querySelector('#commentsCount');
        if (!list) return;

        list.innerHTML = '<div class="loading">Завантаження коментарів...</div>';

        try {
            const res = await fetch(`functions/get_comments.php?event_id=${eventId}`);
            if (!res.ok) throw new Error('Помилка завантаження коментарів');

            const comments = await res.json();

            if (countEl) {
                countEl.textContent = comments.length;
            }

            if (!comments.length) {
                list.innerHTML = '<div class="no-comments">Коментарів ще немає. Будьте першим!</div>';
                return;
            }

            list.innerHTML = comments.map(c => `
                <div class="comment">
                    <div class="comment-header">
                        <span class="comment-author">${c.username || 'Анонім'}</span>
                        <span class="comment-time">${c.created_at || 'Нещодавно'}</span>
                    </div>
                    <p>${c.content || ''}</p>
                </div>
            `).join('');

        } catch (error) {
            console.error('Помилка завантаження коментарів:', error);
            list.innerHTML = '<div class="error">Не вдалося завантажити коментарі</div>';
        }
    }



    setupCommentSend() {
        const btn = document.getElementById('sendComment');
        const input = document.getElementById('commentText');

        if (!btn || !input) return;

        btn.addEventListener('click', () => this.sendComment());
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendComment();
            }
        });
    }
    setupActionButtons() {
        const desktop = document.getElementById('eventActions');
        const mobile = document.getElementById('eventActionsMob');

        const getActivePanel = () => {
            return window.innerWidth <= 768 ? mobile : desktop;
        };

        const handler = (e) => {
            const btn = e.target.closest('.event-action');
            if (!btn) return;

            const action = btn.dataset.action;

            if (action === 'like') this.toggleLikeUnified();

            if (action === 'comment') {
                if (window.innerWidth <= 768) {
                    this.openMobileComments();
                } else {
                    document.getElementById('commentText')?.focus();
                }
            }

            if (action === 'share') this.shareEventMobile();
        };

        // вішаємо на ОБИДВІ панелі
        desktop?.addEventListener('click', handler);
        mobile?.addEventListener('click', handler);

        // керуємо яка з них видима
        const syncPanels = () => {
            if (window.innerWidth <= 768) {
                if (desktop) desktop.style.display = "none";
                if (mobile) mobile.style.display = "flex";
            } else {
                if (desktop) desktop.style.display = "flex";
                if (mobile) mobile.style.display = "none";
            }
        };

        window.addEventListener('resize', syncPanels);
        syncPanels();
    }

    async toggleLikeUnified() {
        if (!this.currentEventId) return;

        // Знаходимо активну кнопку лайку (mobile або desktop)
        const activePanel = window.innerWidth <= 768
            ? document.getElementById('eventActionsMob')
            : document.getElementById('eventActions');

        const likeBtn = activePanel?.querySelector('[data-action="like"]');
        const countElement = likeBtn?.querySelector('[data-likes-count]');

        try {
            const res = await fetch('/functions/toggle_like.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `event_id=${this.currentEventId}`
            });

            const data = await res.json();

            // Обробка всіх можливих помилок
            if (!data.success) {
                switch (data.error) {
                    case 'auth':
                        this.showNotification('Увійдіть, щоб брати участь у подіях', 'error');
                        break;
                    case 'own_event':
                        this.showNotification( (data.message || 'Не можна брати участь у власній події'), 'error');
                        break;
                    case 'already_liked':
                        this.showNotification(data.message || 'Ви вже берете участь у цій події', 'info');
                        break;
                    default:
                        this.showNotification(data.message || 'Помилка', 'error');
                }
                return;
            }

            // ✅ Сервер підтвердив лайк - оновлюємо UI
            this.updateLikeUI(data.liked, data.count);

            // Додаємо анімацію для числа
            if (countElement) {
                countElement.style.transform = 'scale(1.3)';
                setTimeout(() => {
                    countElement.style.transform = 'scale(1)';
                }, 200);
            }

        } catch (e) {
            console.error('Like error:', e);
            this.showNotification('Не вдалося поставити лайк', 'error');
        }
    }
    updateLikeUI(isLiked, count) {
        // Всі кнопки лайку (desktop + mobile)
        document.querySelectorAll('[data-action="like"]').forEach(btn => {
            // Перемикаємо класи
            btn.classList.toggle('liked', isLiked);
            btn.classList.toggle('active', isLiked);

            // Якщо це кнопка з SVG іконкою руки, додаємо клас для анімації
            if (btn.querySelector('.hand-icon')) {
                btn.classList.toggle('liked', isLiked);
            }
        });

        // Всі лічильники
        document.querySelectorAll('[data-likes-count]').forEach(el => {
            el.textContent = count;
        });
    }



    async sendComment() {
        const input = document.getElementById('commentText');
        const text = input?.value.trim();

        if (!text || !this.currentEventId) {
            this.showNotification('Введіть текст коментаря', 'error');
            return;
        }

        const btn = document.getElementById('sendComment');
        const originalText = btn?.innerHTML;

        try {
            if (btn) {
                btn.innerHTML = '...';
                btn.disabled = true;
            }

            const res = await fetch('functions/add_comment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `event_id=${this.currentEventId}&content=${encodeURIComponent(text)}`
            });

            const data = await res.json();

            if (!data.success) {
                throw new Error(data.message || 'Помилка додавання коментаря');
            }

            // Очищаємо поле вводу
            if (input) input.value = '';

            // Оновлюємо коментарі та статистику
            await Promise.all([
                this.loadComments(this.currentEventId),
                this.loadStats(this.currentEventId)
            ]);
            this.showNotification('Коментар додано!', 'success');

        } catch (error) {
            console.error('Помилка відправки коментаря:', error);
            this.showNotification(error.message, 'error');
        } finally {
            if (btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    }




}

// Ініціалізація універсального менеджера при завантаженні сторінки
document.addEventListener('DOMContentLoaded', function() {
    window.eventModalManager = new UniversalModalManager();
});

// Додаємо CSS для анімацій та сповіщень
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .loading, .no-comments, .error {
        text-align: center;
        padding: 20px;
        color: rgba(255,255,255,0.7);
        font-size: 14px;
    }
    
    .notification {
        animation: slideIn 0.3s ease;
    }
`;
document.head.appendChild(style);