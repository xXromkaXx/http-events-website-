<?php


require_once 'init.php';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['form_data']);
}
require_once 'functions/event_functions.php';
require_once 'functions/auth.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$errorMessage = '';
$successMessage = '';
$user_id = $_SESSION['user']['id'];
$isEdit = false;
$event = null;

if (isset($_GET['id'])) {
    $isEdit = true;
    $event_id = (int)$_GET['id'];

    $event = getEventById($event_id, $user_id);


    if (!$event) {
        die('Подію не знайдено або немає доступу');
    }
}

$fieldErrors = [
    'title' => '',
    'category' => '',
    'event_date' => '',
    'location' => '',
    'description' => '',
    'image' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $custom_category = trim($_POST['custom_category'] ?? '');
    $location = trim($_POST['location']);
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? null;

    if ($category === 'Інше' && $custom_category !== '') {
        $category = $custom_category;
    }

    $hasErrors = false;

    if ($title === '') {
        $fieldErrors['title'] = "Введіть назву події";
        $hasErrors = true;
    } elseif (strlen($title) < 3) {
        $fieldErrors['title'] = "Назва занадто коротка (мінімум 3 символи)";
        $hasErrors = true;
    }

    if ($category === '' || $category === '-- Оберіть категорію --') {
        $fieldErrors['category'] = "Оберіть категорію зі списку";
        $hasErrors = true;
    }

    if ($event_date === '') {
        $fieldErrors['event_date'] = "Вкажіть дату проведення події";
        $hasErrors = true;
    } else {
        $current_date = date('Y-m-d');
        if ($event_date < $current_date) {
            $fieldErrors['event_date'] = "Дата не може бути в минулому";
            $hasErrors = true;
        }
    }

    if ($location === '') {
        $fieldErrors['location'] = "Вкажіть місце проведення події";
        $hasErrors = true;
    }

    if ($description === '' || $description === 'Деталі про подію...') {
        $fieldErrors['description'] = "Додайте опис події";
        $hasErrors = true;
    } elseif (strlen($description) < 10) {
        $fieldErrors['description'] = "Опис занадто короткий (мінімум 10 символів)";
        $hasErrors = true;
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024;

        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $fieldErrors['image'] = "Дозволені тільки зображення (JPG, PNG, GIF, WebP)";
            $hasErrors = true;
        } elseif ($_FILES['image']['size'] > $max_size) {
            $fieldErrors['image'] = "Файл занадто великий (максимум 5MB)";
            $hasErrors = true;
        }
    }

    if (!$hasErrors) {
        try {
            $imagePath = uploadEventImage($_FILES['image'] ?? [], $category, $event['image'] ?? null);

            if ($isEdit) {
                $result = updateEvent(
                        $event_id,
                        $user_id,
                        $title,
                        $description,
                        $category,
                        $event_date,
                        $event_time,
                        $imagePath,
                        $location
                );
            } else {
                $result = createEvent(
                        $user_id,
                        $title,
                        $description,
                        $category,
                        $event_date,
                        $event_time,
                        $imagePath,
                        $location
                );
            }

            if ($result) {
                $successMessage = $isEdit
                        ? "✅ Подію оновлено"
                        : "✅ Подію створено";

                unset($_SESSION['form_data']);
            }

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
    }

}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasErrors) {
    // Показуємо те, що ввів користувач
    $formData = $_POST;
} elseif ($isEdit) {
    // Дані з БД
    $formData = $event;
} else {
    $formData = [];
}



?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">

    <title><?= $isEdit ? 'Редагувати подію' : 'Створити подію' ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/create_event.css">
    <link rel="stylesheet" href="assets/css/events.css">
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

<div class="create-event-layout">
<!--    <h1>--><?php //= $isEdit ? 'Редагувати подію' : 'Створити подію' ?><!--</h1>-->

    <!-- 🔴 ЛІВЕ ПРЕВʼЮ (як реальна картка події) -->
    <div class="event-preview">

        <div class="event-card preview-card">

            <div class="event-image" id="previewImage">
                <?php if (!empty($formData['image'])): ?>
                    <img src="<?= htmlspecialchars($formData['image']) ?>">
                <?php else: ?>
                    <span class="preview-placeholder">📸 Фото події</span>
                <?php endif; ?>

            </div>


            <div class="event-info">

                <h3 id="previewTitle">
                    <?= htmlspecialchars($formData['title'] ?? 'Назва події') ?>
                </h3>


                <p class="event-category" id="previewCategory">
                    <?= htmlspecialchars($formData['category'] ?? 'Категорія') ?>
                </p>


                <p class="event-location" id="previewLocation">
                    📍 <?= htmlspecialchars($formData['location'] ?? 'Локація') ?>
                </p>
                <p class="event-date" id="previewDate">
                    📅 <?= !empty($formData['event_date'])
                            ? htmlspecialchars($formData['event_date'])
                            : 'Дата'
                    ?>
                </p>
                <p class="event-description" id="previewDescription">
                    <?= htmlspecialchars(
                            !empty($formData['description'])
                                    ? (mb_strlen($formData['description']) > 120
                                    ? mb_substr($formData['description'], 0, 120) . '…'
                                    : $formData['description'])
                                    : 'Короткий опис події буде тут'
                    ) ?>
                </p>


            </div>

        </div>

    </div>


    <!-- 🟢 ПРАВА ЧАСТИНА — ФОРМА -->
    <form class="create-event-form" method="POST" enctype="multipart/form-data" id="createEventForm">
        <div class="form-field">
            <input
                type="text"
                id="eventTitle"
                name="title"
                placeholder="Назва події"
                value="<?= htmlspecialchars($formData['title'] ?? '') ?>"
            >
            <div class="field-error-text">
                <?= $fieldErrors['title'] ?>
            </div>
        </div>

        <div class="form-field">
            <select id="categorySelect" name="category">
                <option value="">Категорія</option>
                <option value="Футбол" <?= ($formData['category'] ?? '') === 'Футбол' ? 'selected' : '' ?>>Футбол
                </option>
                <option value="Волейбол" <?= ($formData['category'] ?? '') === 'Волейбол' ? 'selected' : '' ?>>
                    Волейбол
                </option>
                <option value="Прогулянка" <?= ($formData['category'] ?? '') === 'Прогулянка' ? 'selected' : '' ?>>
                    Прогулянка
                </option>
                <option value="Концерт" <?= ($formData['category'] ?? '') === 'Концерт' ? 'selected' : '' ?>>Концерт
                </option>
                <option value="Вечірка" <?= ($formData['category'] ?? '') === 'Вечірка' ? 'selected' : '' ?>>Вечірка
                </option>
                <option value="Зустріч" <?= ($formData['category'] ?? '') === 'Зустріч' ? 'selected' : '' ?>>Зустріч
                </option>
                <option value="Музика" <?= ($formData['category'] ?? '') === 'Музика' ? 'selected' : '' ?>>Музика
                </option>
                <option value="Інше">Інше</option>
            </select>
            <input
                type="text"
                id="custom-category"
                name="custom_category"
                placeholder="Введіть свою категорію"
                class="hidden"
            />
            <div class="field-error-text">
                <?= $fieldErrors['category'] ?>
            </div>
        </div>

        <div class="form-field">
            <input
                type="text"
                id="eventLocation"
                name="location"
                placeholder="Локація"
                value="<?= htmlspecialchars($formData['location'] ?? '') ?>"
            >
            <div class="field-error-text">
                <?= $fieldErrors['location'] ?>
            </div>
        </div>


        <div class="form-field">
            <input
                type="date"
                id="eventDate"
                name="event_date"
                value="<?= htmlspecialchars($formData['event_date'] ?? '') ?>"
            >
            <div class="field-error-text">
                <?= $fieldErrors['event_date'] ?>
            </div>
        </div>

        <div class="form-field">
        <textarea
            id="eventDescription"
            name="description"
            maxlength="500"><?= htmlspecialchars($formData['description'] ?? '')?></textarea>
            <div class="field-error-text">
                <?= $fieldErrors['description'] ?>
            </div>
        </div>

        <div class="form-field">
            <input type="file" id="eventImage" name="image" hidden>
            <label for="eventImage" class="upload-btn">Додати фото</label>
            <div class="field-error-text">
                <?= $fieldErrors['image'] ?>
            </div>
        </div>

        <button type="submit">
            <?= $isEdit ? 'Зберегти зміни' : 'Створити подію' ?>
        </button>
    </form>

</div>


<?php include 'includes/footer.php'; ?>

<script src="assets/js/create_event.js"></script>

</body>
</html>