<?php

require_once __DIR__ . '/../helpers.php';

session_start();
require_once '../init.php';
// ВСТАНОВЛЮЄМО JSON ЗАГОЛОВОК ПЕРШИМ
header('Content-Type: application/json');

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Помилка підключення до бази даних']);
    exit;
}

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Необхідно авторизуватися']);
    exit;
}

$event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$event_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Невірний ідентифікатор події']);
    exit;
}

try {
    // Отримуємо інформацію про подію для перевірки власника
    $stmt = $pdo->prepare("SELECT user_id, image FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Подію не знайдено']);
        exit;
    }

    // Перевіряємо, чи належить подія поточному користувачу
    if ($event['user_id'] != $_SESSION['user']['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Ви не маєте прав для видалення цієї події']);
        exit;
    }

    // Видаляємо зображення, якщо воно не стандартне
    if ($event['image'] && $event['image'] !== 'assets/img/no-image.jpg' && file_exists(__DIR__ . '/../' . $event['image'])) {
        unlink(__DIR__ . '/../' . $event['image']);
    }

    // Видаляємо подію з бази даних
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$event_id]);

    echo json_encode(['success' => true, 'message' => 'Подію успішно видалено']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Помилка при видаленні події: ' . $e->getMessage()]);
}
exit;