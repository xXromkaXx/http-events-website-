class EventsManager {
    constructor() {
        this.currentFilters = {
            category: 'Усі',
            date: 'all',
            title: '',
            location: '',
            search: '',
            my: false,
            excludeMy: true
        };
        this.isLoading = false;
        this.currentPage = 1;
        this.pageSize = 12;
        this.totalPages = 1;
        this.totalResults = 0;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.updateScopeToggle();
        if (document.getElementById('profileEvents')) {
// 👤 Профіль → тільки профільні події
            this.setupProfileTabs(); // тут всередині вже є автозагрузка "my"
        } else {
// 🌍 Головна сторінка → всі події
            this.loadEvents();
        }
    }

    setupProfileTabs() {
        const tabs = document.querySelectorAll('.tab-item');
        const container = document.getElementById('profileEvents');

        // якщо це не сторінка профілю — нічого не робимо
        if (!tabs.length || !container) return;

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {

                // активний таб (стилі)
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                // показуємо лоадер
                container.innerHTML = `
                <div class="events-loading">
                    <div class="loading-spinner"></div>
                    <p>Завантаження подій...</p>
                </div>
            `;

                // вантажимо події
                this.loadProfileEvents(tab.dataset.tab);
            });
        });

        // Автоматично завантажуємо перший таб
        if (tabs.length > 0) {
            const firstTab = document.querySelector('.tab-item[data-tab="my"]') || tabs[0];
            firstTab.classList.add('active');
            this.loadProfileEvents(firstTab.dataset.tab);
        }
    }
    loadProfileEvents(type) {
        const container = document.getElementById('profileEvents');
        if (!container) return;

        fetch(`ajax/profile_events.php?type=${type}`)
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.text();
            })
            .then(html => {
                container.innerHTML = html;

                // Якщо немає подій, показуємо відповідне повідомлення
                if (html.includes('no-events') || html.trim() === '<div class="no-events">Подій немає</div>') {
                    this.showEmptyProfileState(type);
                }

                // Ініціалізуємо модалки для нових карток
                if (window.eventModalManager) {
                    window.eventModalManager.init();
                }
            })
            .catch(error => {
                console.error('Error loading profile events:', error);
                container.innerHTML = `
                <div class="no-events">
                    <p>❌ Помилка завантаження подій</p>
                    <button onclick="eventsManager.loadProfileEvents('${type}')">Спробувати знову</button>
                </div>
            `;
            });
    }
    showEmptyProfileState(type) {
        const container = document.getElementById('profileEvents');
        if (!container) return;
        const base = window.BASE_URL || '';

        let message = '';
        let button = '';

        switch(type) {
            case 'my':
                message = 'Ви ще не створили жодної події';
                button = `<a href="${base}/event_form.php" class="btn-create-first">Створити першу подію</a>`;
                break;
            case 'saved':
                message = 'У вас немає збережених подій';
                button = `<a href="${base}/index.php#eventsSection" class="btn-create-first">Знайти події для збереження</a>`;
                break;
            case 'participating':
                message = 'Ви ще не взяли участь у жодній події';
                button = `<a href="${base}/index.php#eventsSection" class="btn-create-first">Знайти події для участі</a>`;
                break;
        }

        container.innerHTML = `
        <div class="no-events">
            <div class="no-events-icon">
                <i class="fas fa-calendar-times"></i>
            </div>
            <h3>${message}</h3>
            ${button}
        </div>
    `;
    }
    setupEventListeners() {
        const filterBtn = document.getElementById('filterBtn');
        const filterMenu = document.getElementById('filterMenu');

        // Кнопка фільтра
        if (filterBtn && filterMenu) {
            filterBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                filterMenu.style.display = filterMenu.style.display === 'block' ? 'none' : 'block';
            });

            document.addEventListener('click', (e) => {
                if (!filterMenu.contains(e.target) && e.target !== filterBtn) {
                    filterMenu.style.display = 'none';
                }
            });


            filterMenu.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }

        // Select для категорій
        const categorySelect = document.getElementById('categorySelect');
        if (categorySelect) {
            categorySelect.addEventListener('change', () => {
                this.currentFilters.category = categorySelect.value;
                this.updateActiveFilters();
            });
        }

        // Select для дат
        const dateSelect = document.getElementById('dateSelect');
        if (dateSelect) {
            dateSelect.addEventListener('change', () => {
                this.currentFilters.date = dateSelect.value;
                this.updateActiveFilters();
            });
        }

        // Календар дати
        const dateFilter = document.getElementById('dateFilter');
        if (dateFilter) {
            dateFilter.addEventListener('change', () => {
                if (dateFilter.value) {
                    this.currentFilters.date = dateFilter.value;
                    if (dateSelect) dateSelect.value = 'custom';
                    this.updateActiveFilters();
                }
            });
        }

        const titleFilter = document.getElementById('titleFilter');
        if (titleFilter) {
            let titleTimeout;
            titleFilter.addEventListener('input', () => {
                clearTimeout(titleTimeout);
                titleTimeout = setTimeout(() => {
                    this.currentFilters.title = titleFilter.value.trim();
                    this.updateActiveFilters();
                }, 400);
            });

            titleFilter.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.currentFilters.title = titleFilter.value.trim();
                    this.updateActiveFilters();
                    this.loadEvents();
                }
            });
        }

        // Пошук за місцем
        const locationFilter = document.getElementById('locationFilter');
        if (locationFilter) {
            let locationTimeout;
            locationFilter.addEventListener('input', () => {
                clearTimeout(locationTimeout);
                locationTimeout = setTimeout(() => {
                    this.currentFilters.location = locationFilter.value.trim();
                    this.updateActiveFilters();
                }, 500);
            });

            locationFilter.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.currentFilters.location = locationFilter.value.trim();
                    this.updateActiveFilters();
                    this.loadEvents();
                }
            });
        }

        // Глобальний пошук
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.currentFilters.search = searchInput.value.trim();
                    this.updateActiveFilters();
                    this.loadEvents();
                }, 300);
            });
        }

        // Кнопка пошуку
        const searchBtn = document.querySelector('.search-btn');
        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                this.loadEvents();
            });
        }

        // Застосування фільтрів
        const applyFiltersBtn = document.querySelector('.apply-filters');
        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', () => {
                this.loadEvents();
                if (filterMenu) {
                    filterMenu.style.display = 'none';
                }
            });
        }

        // Очищення фільтрів
        const clearFiltersBtn = document.querySelector('.clear-filters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                this.clearAllFilters();
            });
        }

        // Делегування подій для видалення активних фільтрів
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-filter')) {
                const type = e.target.getAttribute('data-type');
                this.removeFilter(type);
            }
        });

        const scopeAllBtn = document.getElementById('scopeAllBtn');
        const scopeMyBtn = document.getElementById('scopeMyBtn');
        const prevBtn = document.getElementById('eventsPrevBtn');
        const nextBtn = document.getElementById('eventsNextBtn');
        const pageSizeSelect = document.getElementById('eventsPageSize');
        if (scopeAllBtn) {
            scopeAllBtn.addEventListener('click', () => {
                this.setEventScope('all');
            });
        }
        if (scopeMyBtn) {
            scopeMyBtn.addEventListener('click', () => {
                this.setEventScope('my');
            });
        }
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                this.goToPage(this.currentPage - 1);
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                this.goToPage(this.currentPage + 1);
            });
        }
        if (pageSizeSelect) {
            pageSizeSelect.value = String(this.pageSize);
            pageSizeSelect.addEventListener('change', () => {
                const nextSize = parseInt(pageSizeSelect.value, 10) || 12;
                this.pageSize = Math.max(12, Math.min(36, nextSize));
                this.currentPage = 1;
                this.loadEvents();
            });
        }



    }

    setEventScope(scope) {
        if (scope === 'my') {
            if (!window.isLoggedIn) {
                alert('Увійдіть у профіль, щоб бачити свої події');
                return;
            }

            this.currentFilters.my = true;
            this.currentFilters.excludeMy = false;
            this.currentFilters.category = 'Усі';
            this.currentFilters.date = 'all';
            this.currentFilters.title = '';
            this.currentFilters.location = '';
            this.currentFilters.search = '';

            const categorySelect = document.getElementById('categorySelect');
            const dateSelect = document.getElementById('dateSelect');
            const dateFilter = document.getElementById('dateFilter');
            const titleFilter = document.getElementById('titleFilter');
            const locationFilter = document.getElementById('locationFilter');
            const searchInput = document.getElementById('searchInput');

            if (categorySelect) categorySelect.value = 'Усі';
            if (dateSelect) dateSelect.value = 'all';
            if (dateFilter) dateFilter.value = '';
            if (titleFilter) titleFilter.value = '';
            if (locationFilter) locationFilter.value = '';
            if (searchInput) searchInput.value = '';
        } else {
            this.currentFilters.my = false;
            this.currentFilters.excludeMy = true;
        }

        this.updateActiveFilters();
        this.updateScopeToggle();
        this.loadEvents();
    }

    updateScopeToggle() {
        const scopeAllBtn = document.getElementById('scopeAllBtn');
        const scopeMyBtn = document.getElementById('scopeMyBtn');
        const scopeStatus = document.getElementById('eventsScopeStatus');
        if (!scopeAllBtn || !scopeMyBtn) return;

        const isMy = !!this.currentFilters.my;
        scopeAllBtn.classList.toggle('active', !isMy);
        scopeMyBtn.classList.toggle('active', isMy);

        if (scopeStatus) {
            scopeStatus.textContent = isMy
                ? 'Зараз показано: мої події'
                : 'Зараз показано: усі події';
        }
    }

    updateActiveFilters() {
        const activeFiltersContainer = document.getElementById('activeFilters');
        if (!activeFiltersContainer) return;

        activeFiltersContainer.innerHTML = '';

        // Додаємо тільки активні фільтри (не за замовчуванням)
        if (this.currentFilters.category !== 'Усі') {
            this.addActiveFilter('category', this.currentFilters.category, 'Категорія');
        }

        if (this.currentFilters.date !== 'all') {
            let dateText = this.getDateDisplayText(this.currentFilters.date);
            this.addActiveFilter('date', dateText, 'Дата');
        }

        if (this.currentFilters.title) {
            this.addActiveFilter('title', this.currentFilters.title, 'Назва');
        }

        if (this.currentFilters.location) {
            this.addActiveFilter('location', this.currentFilters.location, 'Місце');
        }

        if (this.currentFilters.search) {
            this.addActiveFilter('search', this.currentFilters.search, 'Пошук');
        }
    }

    getDateDisplayText(dateValue) {
        switch(dateValue) {
            case 'today': return 'Сьогодні';
            case 'tomorrow': return 'Завтра';
            case 'weekend': return 'Вихідні';
            case 'week': return 'Цей тиждень';
            default:
                if (dateValue.match(/^\d{4}-\d{2}-\d{2}$/)) {
                    const date = new Date(dateValue);
                    return date.toLocaleDateString('uk-UA');
                }
                return dateValue;
        }
    }

    addActiveFilter(type, value, label) {
        const activeFiltersContainer = document.getElementById('activeFilters');
        const filterTag = document.createElement('div');
        filterTag.className = 'active-filter-tag';
        filterTag.innerHTML = `
            ${label}: ${value}
            <button class="remove-filter" data-type="${type}">×</button>
        `;
        activeFiltersContainer.appendChild(filterTag);
    }

    removeFilter(type) {
        switch(type) {
            case 'category':
                this.currentFilters.category = 'Усі';
                document.getElementById('categorySelect').value = 'Усі';
                break;
            case 'date':
                this.currentFilters.date = 'all';
                document.getElementById('dateSelect').value = 'all';
                document.getElementById('dateFilter').value = '';
                break;
            case 'location':
                this.currentFilters.location = '';
                document.getElementById('locationFilter').value = '';
                break;
            case 'title':
                this.currentFilters.title = '';
                document.getElementById('titleFilter').value = '';
                break;
            case 'search':
                this.currentFilters.search = '';
                document.getElementById('searchInput').value = '';
                break;
        }

        this.updateActiveFilters();
        this.loadEvents();
    }

    parseEventsResponse(html) {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        const meta = temp.querySelector('.events-meta');
        let hasMore = false;
        let page = 1;
        let total = 0;
        let totalPages = 1;

        if (meta) {
            hasMore = meta.dataset.hasMore === '1';
            page = parseInt(meta.dataset.page || '1', 10) || 1;
            total = parseInt(meta.dataset.total || '0', 10) || 0;
            totalPages = parseInt(meta.dataset.totalPages || '1', 10) || 1;
            meta.remove();
        }

        const outputHtml = temp.innerHTML.trim();
        return { html: outputHtml, hasMore, page, total, totalPages };
    }

    updatePaginationUI() {
        const wrap = document.getElementById('eventsPagination');
        const prevBtn = document.getElementById('eventsPrevBtn');
        const nextBtn = document.getElementById('eventsNextBtn');
        const info = document.getElementById('eventsPageInfo');
        if (!wrap || !prevBtn || !nextBtn || !info || document.getElementById('profileEvents')) return;

        if (this.totalResults <= 0) {
            wrap.style.display = 'none';
            return;
        }

        wrap.style.display = 'flex';
        prevBtn.disabled = this.currentPage <= 1 || this.isLoading;
        nextBtn.disabled = this.currentPage >= this.totalPages || this.isLoading;
        info.textContent = `Сторінка ${this.currentPage} / ${this.totalPages} • Знайдено: ${this.totalResults}`;
    }

    goToPage(page) {
        const target = Math.max(1, Math.min(this.totalPages || 1, page));
        if (target === this.currentPage || this.isLoading) return;
        this.currentPage = target;
        this.loadEvents();
    }

    loadEvents() {
        if (this.isLoading) return;
        if (document.getElementById('profileEvents')) {
            return;
        }

        this.isLoading = true;
        const eventsContainer = document.getElementById('eventsContainer');
        const noEventsMessage = document.getElementById('noEventsMessage');
        const prevBtn = document.getElementById('eventsPrevBtn');
        const nextBtn = document.getElementById('eventsNextBtn');

        if (eventsContainer) {
            eventsContainer.innerHTML = '<div class="loading-message">Завантаження подій...</div>';
        }
        if (noEventsMessage) {
            noEventsMessage.style.display = 'none';
        }
        if (prevBtn) prevBtn.disabled = true;
        if (nextBtn) nextBtn.disabled = true;

        // Формуємо параметри запиту
        const params = new URLSearchParams();

        if (this.currentFilters.category !== 'Усі') {
            params.append('category', this.currentFilters.category);
        }

        if (this.currentFilters.date !== 'all') {
            params.append('date', this.currentFilters.date);
        }

        if (this.currentFilters.location) {
            params.append('location', this.currentFilters.location);
        }
        if (this.currentFilters.title) {
            params.append('title', this.currentFilters.title);
        }

        if (this.currentFilters.search) {
            params.append('search', this.currentFilters.search);
        }
        if (this.currentFilters.excludeMy && window.isLoggedIn) {
            params.append('exclude_my', '1');
        }

        if (this.currentFilters.my) {
            params.append('my', '1');
        }
        params.append('page', String(this.currentPage));
        params.append('limit', String(this.pageSize));

        const url = 'events.php?' + params.toString();



        fetch(url)
            .then(r => {
                if (!r.ok) {
                    throw new Error('Помилка завантаження подій');
                }
                return r.text();
            })
            .then(html => {
                const parsed = this.parseEventsResponse(html);
                this.currentPage = parsed.page;
                this.totalResults = parsed.total;
                this.totalPages = parsed.totalPages;
                eventsContainer.innerHTML = parsed.html || '<div class="no-events">Подій не знайдено</div>';
                this.updatePaginationUI();
            })
            .catch((error) => {
                console.error('loadEvents error:', error);
                if (eventsContainer) {
                    eventsContainer.innerHTML = `
                        <div class="no-events">
                            <p>❌ Помилка завантаження подій</p>
                            <button onclick="window.eventsManager && window.eventsManager.loadEvents()">Спробувати знову</button>
                        </div>
                    `;
                }
                this.totalResults = 0;
                this.totalPages = 1;
                this.currentPage = 1;
                this.updatePaginationUI();
            })
            .finally(() => {
                this.isLoading = false;
                this.updatePaginationUI();
            });

    }





    clearAllFilters() {
        this.currentFilters = {
            category: 'Усі',
            date: 'all',
            title: '',
            location: '',
            search: '',
            my: false
        };

        // Скидуємо UI
        document.getElementById('categorySelect').value = 'Усі';
        document.getElementById('dateSelect').value = 'all';
        document.getElementById('dateFilter').value = '';
        document.getElementById('titleFilter').value = '';
        document.getElementById('locationFilter').value = '';
        document.getElementById('searchInput').value = '';

        this.updateActiveFilters();
        this.currentFilters.my = false;
        this.currentFilters.excludeMy = true;
        this.currentPage = 1;
        this.totalPages = 1;
        this.totalResults = 0;
        this.updateScopeToggle();
        this.loadEvents();


        const filterMenu = document.getElementById('filterMenu');
        if (filterMenu) {
            filterMenu.style.display = 'none';
        }
    }


}

function clearFiltersAndShowAll() {
    if (window.eventsManager) {
        window.eventsManager.clearAllFilters();
    }
}


document.addEventListener('DOMContentLoaded', function() {
    window.eventsManager = new EventsManager();

});
