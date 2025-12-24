<?php
session_start();
require_once 'init.php';
require_once 'functions/event_functions.php';
require_once 'functions/auth.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$errorMessage = '';
$successMessage = '';
$user_id = $_SESSION['user']['id'];

$fieldErrors = [
        'title' => '',
        'category' => '',
        'event_date' => '',
        'location' => '',
        'description' => '',
        'image' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['form_data'] = $_POST;

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
            $imagePath = uploadEventImage($_FILES['image'] ?? [], $category);
            if (createEvent($user_id, $title, $description, $category, $event_date, $event_time, $imagePath, $location)) {
                $successMessage = "✅ Подію успішно створено!";
                unset($_SESSION['form_data']);
                $_POST = [];
            } else {
                $errorMessage = "Помилка при створенні події! Спробуйте ще раз.";
            }
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
    } else {
        $errorMessage = "Виправте помилки у формі";
    }
}

$formData = $_SESSION['form_data'] ?? $_POST;
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Створити подію | Events YC</title>
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

    <!-- 🔴 ЛІВЕ ПРЕВʼЮ (як реальна картка події) -->
    <div class="event-preview">

        <div class="event-card preview-card">

            <div class="event-image" id="previewImage">
                <span class="preview-placeholder">📸 Фото події</span>
            </div>

            <div class="event-info">

                <h3 id="previewTitle">Назва події</h3>

                <p class="event-category" id="previewCategory">
                    Категорія
                </p>

                <p class="event-location" id="previewLocation">
                    📍 Локація
                </p>

                <p class="event-date" id="previewDate">
                    📅 Дата
                </p>

                <p class="event-description" id="previewDescription">
                    Короткий опис події буде тут
                </p>

            </div>

        </div>

    </div>



    <!-- 🟢 ПРАВА ЧАСТИНА — ФОРМА -->
    <form class="create-event-form">

        <input type="text" id="eventTitle" placeholder="Назва події">

        <select id="categorySelect">
            <option value="">Категорія</option>
            <option value="Футбол">Футбол</option>
            <option value="Волейбол">Волейбол</option>
            <option value="Прогулянка">Прогулянка</option>
            <option value="Концерт">Концерт</option>
            <option value="Вечірка">Вечірка</option>
            <option value="Зустріч">Зустріч</option>
            <option value="Інше">Інше</option>
        </select>

        <input type="text" id="eventLocation" placeholder="Локація">

        <input type="date" id="eventDate">

        <textarea
                id="eventDescription"
                placeholder="Опис події"
                maxlength="500">
</textarea>

        <input type="file" id="eventImage" hidden>
        <label for="eventImage" class="upload-btn">Додати фото</label>

        <button type="submit">Створити подію</button>
    </form>

</div>


<?php include 'includes/footer.php'; ?>

<script src="assets/js/create_event.js"></script>

</body>
</html>