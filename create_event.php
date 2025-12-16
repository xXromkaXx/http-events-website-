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
    <link rel="stylesheet" href="assets/css/modal.css">
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

<main class="create-event">
    <h2>Створити нову подію</h2>

    <?php if (!empty($successMessage)): ?>
        <div class="success-message"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <?php if (!empty($errorMessage) && array_filter($fieldErrors)): ?>
        <div class="error-message">
            <strong><?= htmlspecialchars($errorMessage) ?></strong>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="event-form" id="eventForm">
        <div class="form-group">
            <label>Назва події: <span class="required">*</span></label>
            <input type="text" name="title" placeholder="Наприклад: Вечір футболу"
                   value="<?= htmlspecialchars($formData['title'] ?? '') ?>"
                   class="<?= !empty($fieldErrors['title']) ? 'field-error' : '' ?>">
            <?php if (!empty($fieldErrors['title'])): ?>
                <div class="error-text"><?= htmlspecialchars($fieldErrors['title']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Категорія: <span class="required">*</span></label>
            <select name="category" id="categorySelect" class="<?= !empty($fieldErrors['category']) ? 'field-error' : '' ?>">
                <option value="">-- Оберіть категорію --</option>
                <option value="Футбол" <?= (($formData['category'] ?? '') === 'Футбол') ? 'selected' : '' ?>>⚽ Футбол</option>
                <option value="Концерт" <?= (($formData['category'] ?? '') === 'Концерт') ? 'selected' : '' ?>>🎵 Концерт</option>
                <option value="Зустріч" <?= (($formData['category'] ?? '') === 'Зустріч') ? 'selected' : '' ?>>🤝 Зустріч</option>
                <option value="Навчання" <?= (($formData['category'] ?? '') === 'Навчання') ? 'selected' : '' ?>>📘 Навчання</option>
                <option value="Прогулянка" <?= (($formData['category'] ?? '') === 'Прогулянка') ? 'selected' : '' ?>>🚶 Прогулянка</option>
                <option value="Вечірка" <?= (($formData['category'] ?? '') === 'Вечірка') ? 'selected' : '' ?>>🎉 Вечірка</option>
                <option value="Інше" <?= (($formData['category'] ?? '') === 'Інше') ? 'selected' : '' ?>>✏️ Інше (ввести вручну)</option>
            </select>
            <?php if (!empty($fieldErrors['category'])): ?>
                <div class="error-text"><?= htmlspecialchars($fieldErrors['category']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group" id="custom-category" style="display: <?= (($formData['category'] ?? '') === 'Інше') ? 'block' : 'none' ?>;">
            <label>Власна категорія:</label>
            <input type="text" name="custom_category"
                   value="<?= htmlspecialchars($formData['custom_category'] ?? '') ?>"
                   placeholder="Введіть вашу категорію">
        </div>

        <div class="form-group">
            <label>Місце проведення: <span class="required">*</span></label>
            <input type="text" name="location" placeholder="Наприклад: Київ, вул. Хрещатик 12"
                   value="<?= htmlspecialchars($formData['location'] ?? '') ?>"
                   class="<?= !empty($fieldErrors['location']) ? 'field-error' : '' ?>">
            <?php if (!empty($fieldErrors['location'])): ?>
                <div class="error-text"><?= htmlspecialchars($fieldErrors['location']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Дата події: <span class="required">*</span></label>
            <input type="date" name="event_date" id="eventDate"
                   class="date-input <?= !empty($fieldErrors['event_date']) ? 'field-error' : '' ?>"
                   value="<?= htmlspecialchars($formData['event_date'] ?? '') ?>">
            <?php if (!empty($fieldErrors['event_date'])): ?>
                <div class="error-text"><?= htmlspecialchars($fieldErrors['event_date']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Час події (необов'язково):</label>
            <input type="time" name="event_time" id="eventTime" class="time-input"
                   value="<?= htmlspecialchars($formData['event_time'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Опис події: <span class="required">*</span></label>
            <textarea name="description" class="<?= !empty($fieldErrors['description']) ? 'field-error' : '' ?>"
                      placeholder="Деталі про подію..."><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
            <?php if (!empty($fieldErrors['description'])): ?>
                <div class="error-text"><?= htmlspecialchars($fieldErrors['description']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Головне зображення (необов'язково):</label>
            <div class="image-upload-wrapper" onclick="document.getElementById('eventImage').click()">
                <input type="file" id="eventImage" name="image" accept="image/*" style="display: none;">
                <div class="image-preview" id="imagePreview">
                    <span>📸 Натисніть, щоб додати фото</span>
                </div>
            </div>
            <?php if (!empty($fieldErrors['image'])): ?>
                <div class="error-text"><?= htmlspecialchars($fieldErrors['image']) ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-submit">✅ Створити подію</button>
    </form>
</main>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/create_event.js"></script>
</body>
</html>