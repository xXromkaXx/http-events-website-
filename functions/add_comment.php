<?php
require_once __DIR__ . '/../init.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Користувач не авторизований'
    ]);
    exit;
}

if (empty($_POST['event_id']) || empty($_POST['content'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Некоректні дані'
    ]);
    exit;
}

$userId  = (int) $_SESSION['user']['id'];
$eventId = (int) $_POST['event_id'];
$content = trim($_POST['content']);

if ($content === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Коментар не може бути порожнім'
    ]);
    exit;
}

try {
    $role = (string)($_SESSION['user']['role'] ?? 'user');
    $isAdmin = ($role === 'admin');

    $stmt = $pdo->prepare("SELECT user_id, moderation_status FROM events WHERE id = ? LIMIT 1");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        echo json_encode([
            'success' => false,
            'message' => 'Подію не знайдено'
        ]);
        exit;
    }

    $isOwner = ((int)$event['user_id'] === $userId);
    $isPublished = (($event['moderation_status'] ?? 'published') === 'published');
    if (!$isPublished && !$isOwner && !$isAdmin) {
        echo json_encode([
            'success' => false,
            'message' => 'Подія недоступна для коментування'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO comments (event_id, user_id, content)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$eventId, $userId, $content]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('add_comment.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Помилка сервера'
    ]);
}
