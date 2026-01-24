<?php
session_start();
require_once '../init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Необхідно авторизуватися']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$event_id = $data['id'] ?? null;

if (!filter_var($event_id, FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Невірний ID']);
    exit;
}

// 🔐 Отримуємо подію
$stmt = $pdo->prepare("SELECT user_id, image FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Подію не знайдено']);
    exit;
}

// 🔐 Перевірка власника
if ((int)$event['user_id'] !== (int)$_SESSION['user']['id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Недостатньо прав']);
    exit;
}

// 🔐 Безпечне видалення зображення
if (!empty($event['image'])) {
    $path = realpath(__DIR__ . '/../' . $event['image']);
    $base = realpath(__DIR__ . '/../uploads');

    if ($path && str_starts_with($path, $base)) {
        unlink($path);
    }
}

// 🔐 Видалення події
$stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
$stmt->execute([$event_id]);

echo json_encode(['success' => true, 'message' => 'Подію видалено']);
exit;