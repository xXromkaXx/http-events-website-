<?php
require_once '../init.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $userId = $_SESSION['user']['id'] ?? null;
    $data = json_decode(file_get_contents('php://input'), true);
    $eventId = (int)($data['event_id'] ?? 0);

    if (!$userId || !$eventId) {
        echo json_encode(['saved' => false, 'success' => false]);
        exit;
    }

    $stmt = $pdo->prepare("
    SELECT id FROM saved_events
    WHERE user_id = ? AND event_id = ?
");
    $stmt->execute([$userId, $eventId]);

    if ($stmt->fetch()) {
        $pdo->prepare("
        DELETE FROM saved_events
        WHERE user_id = ? AND event_id = ?
    ")->execute([$userId, $eventId]);

        echo json_encode(['saved' => false, 'success' => true]);
    } else {
        $pdo->prepare("
        INSERT INTO saved_events (user_id, event_id)
        VALUES (?, ?)
    ")->execute([$userId, $eventId]);

        echo json_encode(['saved' => true, 'success' => true]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    error_log('save_event.php error: ' . $e->getMessage());
    echo json_encode(['saved' => false, 'success' => false, 'message' => 'Помилка збереження']);
}
