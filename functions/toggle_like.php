<?php
require_once __DIR__ . '/../init.php';
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['user']['id'])) {
        echo json_encode(['success' => false, 'message' => 'Не авторизований']);
        exit;
    }

    $eventId = (int)($_POST['event_id'] ?? 0);
    $userId  = (int)$_SESSION['user']['id'];
    if ($eventId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Невірний ID події']);
        exit;
    }

    // перевірка події
    $stmt = $pdo->prepare("SELECT user_id, moderation_status FROM events WHERE id = ? LIMIT 1");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        echo json_encode([
            'success' => false,
            'error' => 'not_found',
            'message' => 'Подію не знайдено'
        ]);
        exit;
    }

    $eventOwnerId = (int)$event['user_id'];
    $userRole = (string)($_SESSION['user']['role'] ?? 'user');
    $isAdmin = ($userRole === 'admin');
    $isPublished = (($event['moderation_status'] ?? 'published') === 'published');

    if (!$isPublished && !$isAdmin && $eventOwnerId !== $userId) {
        echo json_encode([
            'success' => false,
            'error' => 'forbidden',
            'message' => 'Подія недоступна'
        ]);
        exit;
    }

    if ($eventOwnerId === $userId) {
        echo json_encode([
        'success' => false,
        'error' => 'own_event',
        'message' => 'Ця подія ваша'
    ]);
        exit;
    }
    /* перевірка */
    $stmt = $pdo->prepare("
    SELECT id FROM event_likes
    WHERE event_id = ? AND user_id = ?
");
    $stmt->execute([$eventId, $userId]);
    $likeId = $stmt->fetchColumn();

    if ($likeId) {
        /* прибрати лайк */
        $stmt = $pdo->prepare("DELETE FROM event_likes WHERE id = ?");
        $stmt->execute([$likeId]);
        $liked = false;
    } else {
        /* додати лайк */
        $stmt = $pdo->prepare("
        INSERT INTO event_likes (event_id, user_id)
        VALUES (?, ?)
    ");
        $stmt->execute([$eventId, $userId]);
        $liked = true;
    }

    /* нова кількість */
    $stmt = $pdo->prepare("
    SELECT COUNT(*) FROM event_likes WHERE event_id = ?
");
    $stmt->execute([$eventId]);
    $count = (int)$stmt->fetchColumn();

    echo json_encode([
    'success' => true,
    'liked'   => $liked,
    'count'   => $count
]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('toggle_like.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'server',
        'message' => 'Помилка сервера'
    ]);
}
