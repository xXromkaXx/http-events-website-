<?php
require_once '../init.php';
ini_set('display_errors', 0);
error_reporting(0);



header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();

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

$stmt = $pdo->prepare("SELECT user_id, image FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Подію не знайдено']);
    exit;
}

if ((int)$event['user_id'] !== (int)$_SESSION['user']['id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Недостатньо прав']);
    exit;
}

// 🧹 видалення зображення
if (!empty($event['image'])) {
    $path = realpath(__DIR__ . '/../' . $event['image']);
    $base = realpath(__DIR__ . '/../uploads');

    if ($path && $base && strpos($path, $base) === 0 && file_exists($path)) {
        unlink($path);
    }
}

$stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
$stmt->execute([$event_id]);

echo json_encode(['success' => true]);
exit;
