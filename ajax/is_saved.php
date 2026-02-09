<?php
require_once '../init.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $userId = $_SESSION['user']['id'] ?? null;
    $eventId = (int)($_GET['event_id'] ?? 0);

    if (!$userId || !$eventId) {
        echo json_encode(['saved' => false, 'success' => false]);
        exit;
    }

    $stmt = $pdo->prepare("
    SELECT id FROM saved_events
    WHERE user_id = ? AND event_id = ?
");
    $stmt->execute([$userId, $eventId]);

    echo json_encode(['saved' => (bool)$stmt->fetch(), 'success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('is_saved.php error: ' . $e->getMessage());
    echo json_encode(['saved' => false, 'success' => false, 'message' => 'Помилка перевірки збереження']);
}
