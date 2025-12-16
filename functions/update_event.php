<?php
session_start();
require_once '../init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Необхідно увійти в систему']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Метод не дозволено']);
    exit;
}

try {
    $eventId = $_POST['id'] ?? null;
    $userId = $_SESSION['user']['id'];

    if (!$eventId) {
        throw new Exception('ID події не вказано');
    }

    // Перевіряємо, чи належить подія користувачу
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $eventId, ':user_id' => $userId]);
    $existingEvent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingEvent) {
        throw new Exception('Подія не знайдена або у вас немає прав для її редагування');
    }

    // Отримуємо дані з форми
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $eventTime = $_POST['event_time'] ?? '';
    $description = trim($_POST['description'] ?? '');

    // Валідація
    if (empty($title)) {
        throw new Exception('Назва події обов\'язкова');
    }

    if (empty($eventDate)) {
        throw new Exception('Дата події обов\'язкова');
    }

    // Обробка зображення
    $imagePath = $existingEvent['image']; // Залишаємо старе зображення за замовчуванням

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image'];

        // Перевірка типу файлу
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($image['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Дозволені тільки зображення у форматах: JPG, PNG, GIF, WebP');
        }

        // Перевірка розміру файлу (5MB)
        if ($image['size'] > 5 * 1024 * 1024) {
            throw new Exception('Файл занадто великий. Максимальний розмір: 5MB');
        }

        // Генеруємо унікальне ім'я файлу
        $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $uploadPath = '../assets/uploads/events/' . $filename;

        // Створюємо директорію, якщо не існує
        if (!is_dir('../assets/uploads/events/')) {
            mkdir('../assets/uploads/events/', 0755, true);
        }

        // Завантажуємо файл
        if (move_uploaded_file($image['tmp_name'], $uploadPath)) {
            $imagePath = 'assets/uploads/events/' . $filename;

            // Видаляємо старе зображення, якщо воно не стандартне
            if ($existingEvent['image'] && !str_contains($existingEvent['image'], 'default-event.jpg')) {
                $oldImagePath = '../' . $existingEvent['image'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
        } else {
            throw new Exception('Помилка при завантаженні зображення');
        }
    }

    // Оновлюємо подію в базі даних
    $stmt = $pdo->prepare("
        UPDATE events 
        SET title = :title, category = :category, location = :location, 
            event_date = :event_date, event_time = :event_time, 
            description = :description, image = :image 
        WHERE id = :id AND user_id = :user_id
    ");

    $success = $stmt->execute([
        ':title' => $title,
        ':category' => $category,
        ':location' => $location,
        ':event_date' => $eventDate,
        ':event_time' => $eventTime,
        ':description' => $description,
        ':image' => $imagePath,
        ':id' => $eventId,
        ':user_id' => $userId
    ]);

    if (!$success) {
        throw new Exception('Помилка при оновленні події в базі даних');
    }

    // Отримуємо оновлену подію
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = :id");
    $stmt->execute([':id' => $eventId]);
    $updatedEvent = $stmt->fetch(PDO::FETCH_ASSOC);

    // Форматуємо дату та час для відображення
    if ($updatedEvent['event_date']) {
        $date = new DateTime($updatedEvent['event_date']);
        $updatedEvent['formatted_date'] = $date->format('d.m.Y');
    }

    if ($updatedEvent['event_time']) {
        $time = DateTime::createFromFormat('H:i:s', $updatedEvent['event_time']);
        $updatedEvent['formatted_time'] = $time ? $time->format('H:i') : $updatedEvent['event_time'];
    }

    // Створюємо короткий опис
    $updatedEvent['short_description'] = mb_strlen($updatedEvent['description']) > 100
        ? mb_substr($updatedEvent['description'], 0, 100) . '...'
        : $updatedEvent['description'];

    echo json_encode([
        'success' => true,
        'message' => 'Подію успішно оновлено',
        'event' => $updatedEvent
    ]);

} catch (Exception $e) {
    error_log("Помилка при оновленні події: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>