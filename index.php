<?php
require_once 'init.php';
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events YC</title>
    <link rel="stylesheet" href="assets/css/main.css?v=<?= filemtime(__DIR__ . '/assets/css/main.css') ?>">
    <link rel="stylesheet" href="assets/css/events.css?v=<?= filemtime(__DIR__ . '/assets/css/events.css') ?>">
    <link rel="stylesheet" href="assets/css/modal.css?v=<?= filemtime(__DIR__ . '/assets/css/modal.css') ?>">
</head>


<body>

<?php include 'includes/header.php'; ?>

<div class="zigzag-bg">
    <div class="zigzag-line zigzag-1"></div>
    <div class="zigzag-line zigzag-2"></div>
    <div class="zigzag-line zigzag-3"></div>
    <div class="zigzag-line zigzag-4"></div>
    <div class="zigzag-line zigzag-5"></div>
</div>

<main class="hero-section">
    <div class="hero-content">
        <h1>КРУТО ПРОВЕДИ СВІЙ ВЕЧІР З НАМИ <span class="highlight">EVENTS <strong>YC</strong></span></h1>
        <ul>
            <li>допоможемо вам весело та позитивно провести свій вільний час</li>
            <li>знайдемо цікаву подію на будь-який смак зранку та у вечері</li>
        </ul>
    </div>

    <div class="hero-gallery">
        <img src="assets/img/concert.jpg" alt="concert">
        <img src="assets/img/outdoor-cinema.jpg" alt="cinema">
        <img src="assets/img/camping.jpg" alt="camping">
    </div>

    <!-- ==== БЛОК ПОДІЙ ==== -->
    <div class="events-page" id="eventsSection">
        <h2 class="events-title">події</h2>

        <div class="filter-bar">
            <div class="filter_and_search">
            <!-- Кнопка фільтра -->
            <div class="filter-container">
                <button class="filter-btn" id="filterBtn">фільтр</button>
                <div class="filter-menu" id="filterMenu">
                    <!-- Компактні секції -->
                    <div class="filter-section">
                        <h3>📁 Категорія</h3>
                        <select class="filter-select" id="categorySelect">
                            <option value="Усі">Усі категорії</option>
                            <option value="Футбол">Футбол</option>
                            <option value="Волейбол">Волейбол</option>
                            <option value="Прогулянка">Прогулянка</option>
                            <option value="Концерт">Концерт</option>
                            <option value="Вечірка">Вечірка</option>
                            <option value="Зустріч">Зустріч</option>
                            <option value="Навчання">Навчання</option>
                            <option value="Інше">Інше</option>
                        </select>
                    </div>

                    <div class="filter-section">
                        <h3>📅 Дата</h3>
                        <select class="filter-select" id="dateSelect">
                            <option value="all">Всі дати</option>
                            <option value="today">Сьогодні</option>
                            <option value="tomorrow">Завтра</option>
                            <option value="weekend">Вихідні</option>
                            <option value="week">Цей тиждень</option>
                        </select>
                        <div class="date-input-compact">
                            <label>Або обрати дату:</label>
                            <input type="date" id="dateFilter" class="date-input">
                        </div>
                    </div>

                    <div class="filter-section">
                        <h3>📝 Назва події</h3>
                        <input type="text" id="titleFilter" placeholder="Введіть назву..." class="location-input">
                    </div>

                    <div class="filter-section">
                        <h3>📍 Місце</h3>
                        <input type="text" id="locationFilter" placeholder="Введіть місце..." class="location-input">
                    </div>

                    <!-- Кнопки -->
                    <div class="filter-actions">
                        <button class="apply-filters btn-view">Застосувати</button>
                        <button class="clear-filters btn-delete">Очистити</button>
                    </div>
                </div>
            </div>

            <!-- Пошук -->
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="пошук подій...">
                <button class="search-btn">🔍</button>
            </div>
            </div>
            <!-- Активні фільтри -->
            <div class="active-filters" id="activeFilters">
                <!-- Тут будуть відображатися активні фільтри -->
            </div>

            <div class="events-scope">
                <div class="events-scope-toggle" role="group" aria-label="Область подій">
                    <button type="button" class="scope-btn active" id="scopeAllBtn">Усі</button>
                    <button
                        type="button"
                        class="scope-btn"
                        id="scopeMyBtn"
                        <?= isset($_SESSION['user']) ? '' : 'disabled title="Увійдіть, щоб бачити свої події"' ?>
                    >
                        Мої
                    </button>
                </div>
                <div class="events-scope-status" id="eventsScopeStatus">Зараз показано: усі події</div>
            </div>


        </div>

        <!-- Місце для подій -->
        <div id="eventsContainer" class="events-grid">
            <div class="loading-message">Завантаження подій...</div>
        </div>
        <div class="events-pagination" id="eventsPagination" style="display:none;">
            <button type="button" class="events-page-btn" id="eventsPrevBtn">← Назад</button>
            <div class="events-page-info" id="eventsPageInfo"></div>
            <button type="button" class="events-page-btn" id="eventsNextBtn">Вперед →</button>
            <label class="events-page-size-label" for="eventsPageSize">На сторінці:</label>
            <select id="eventsPageSize" class="events-page-size-select">
                <option value="12">12</option>
                <option value="24">24</option>
                <option value="36">36</option>
            </select>
        </div>

        <!-- Повідомлення про відсутність подій -->
        <div id="noEventsMessage" class="no-events-message" style="display: none;">
            <p>Не знайдено подій за вашим запитом</p>
            <button class="btn-create-first" onclick="clearFiltersAndShowAll()">Показати всі події</button>
        </div>
    </div>

</main>

<?php
include 'components/event_modal.php';
include 'includes/footer.php';
?>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
<script src="assets/js/main.js?v=<?= filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
<script src="assets/js/events.js?v=<?= filemtime(__DIR__ . '/assets/js/events.js') ?>"></script>
<script src="assets/js/modal.js?v=<?= filemtime(__DIR__ . '/assets/js/modal.js') ?>"></script>
<script>
    window.isLoggedIn = <?= isset($_SESSION['user']) ? 'true' : 'false' ?>;
</script>

</body>
</html>
