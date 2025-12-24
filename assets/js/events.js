class EventsManager {
    constructor() {
        this.currentFilters = {
            category: 'Усі',
            date: 'all',
            location: '',
            search: ''
        };
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadEvents();
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
        }

        // Глобальний пошук
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.currentFilters.search = searchInput.value.trim().toLowerCase();
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
            case 'search':
                this.currentFilters.search = '';
                document.getElementById('searchInput').value = '';
                break;
        }

        this.updateActiveFilters();
        this.loadEvents();
    }

    loadEvents() {
        if (this.isLoading) return;

        this.isLoading = true;
        const eventsContainer = document.getElementById('eventsContainer');
        const noEventsMessage = document.getElementById('noEventsMessage');

        if (eventsContainer) {
            eventsContainer.innerHTML = '<div class="loading-message">Завантаження подій...</div>';
        }
        if (noEventsMessage) {
            noEventsMessage.style.display = 'none';
        }

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

        if (this.currentFilters.search) {
            params.append('search', this.currentFilters.search);
        }

        // Додаємо параметр для випадкових подій, якщо немає фільтрів
        if (params.toString() === '') {
            params.append('random', '1');
        }

        const url = 'events.php?' + params.toString();

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Помилка завантаження: ' + response.status);
                }
                return response.json();
            })
            .then(events => {
                this.displayEvents(events);
                this.isLoading = false;
            })
            .catch(error => {
                if (eventsContainer) {
                    eventsContainer.innerHTML = '<div class="error-message">Помилка завантаження подій. Спробуйте ще раз.</div>';
                }
                this.isLoading = false;
            });
    }

    displayEvents(events) {
        const eventsContainer = document.getElementById('eventsContainer');
        const noEventsMessage = document.getElementById('noEventsMessage');

        if (!eventsContainer || !noEventsMessage) return;

        if (!Array.isArray(events)) {
            eventsContainer.innerHTML = '<div class="error-message">Помилка формату даних</div>';
            return;
        }

        if (events.length === 0) {
            eventsContainer.style.display = 'none';
            noEventsMessage.style.display = 'block';
            return;
        }

        eventsContainer.style.display = 'grid';
        noEventsMessage.style.display = 'none';
        eventsContainer.innerHTML = '';

        events.forEach(event => {
            const eventCard = this.createEventCard(event);
            eventsContainer.appendChild(eventCard);
        });
    }

    createEventCard(event) {
        const card = document.createElement('div');
        card.className = 'event-card';

        // Додаємо всі необхідні data-атрибути для модального вікна
        card.setAttribute('data-id', event.id);
        card.setAttribute('data-title', event.title || 'Без назви');
        card.setAttribute('data-category', event.category || '');
        card.setAttribute('data-location', event.location || '');
        card.setAttribute('data-date', event.event_date || '');
        card.setAttribute('data-time', event.event_time || '');
        card.setAttribute('data-description', event.description || '');
        card.setAttribute('data-image', event.image || 'assets/img/default-event.jpg');
        card.setAttribute('data-creator', event.username || '');
        card.setAttribute('data-avatar', event.avatar || 'assets/img/default-avatar.png');

        const description = event.description_short ||
            (event.description ? event.description.substring(0, 100) + '...' : 'Опис відсутній');

        card.innerHTML = `
            <div class="event-image">
                <img src="${event.image || 'assets/img/default-event.jpg'}" alt="${event.title || 'Подія'}" 
                     onerror="this.src='assets/img/default-event.jpg'">
            </div>
            <div class="event-info">
                <h3>${this.escapeHtml(event.title || 'Без назви')}</h3>
                <div class="event-category">${this.escapeHtml(event.category || 'Без категорії')}</div>
                <div class="event-location">📍 ${this.escapeHtml(event.location || 'Без локації')}</div>
                <div class="event-date">📅 ${this.escapeHtml(event.event_date || 'Дата не вказана')}</div>
                <p class="event-description">${this.escapeHtml(description)}</p>
            </div>
            <div class="event-buttons">
                <button class="btn-view" data-event-id="${event.id}">Детальніше</button>
            </div>
        `;


        return card;
    }



    clearAllFilters() {
        this.currentFilters = {
            category: 'Усі',
            date: 'all',
            location: '',
            search: ''
        };

        // Скидуємо UI
        document.getElementById('categorySelect').value = 'Усі';
        document.getElementById('dateSelect').value = 'all';
        document.getElementById('dateFilter').value = '';
        document.getElementById('locationFilter').value = '';
        document.getElementById('searchInput').value = '';

        this.updateActiveFilters();
        this.loadEvents();

        const filterMenu = document.getElementById('filterMenu');
        if (filterMenu) {
            filterMenu.style.display = 'none';
        }
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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