<?php
session_start();
require_once '../init.php';

$userId = $_SESSION['user']['id'] ?? null;
$data = json_decode(file_get_contents('php://input'), true);
$eventId = (int)($data['event_id'] ?? 0);

if (!$userId || !$eventId) {
    echo json_encode(['saved' => false]);
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

    echo json_encode(['saved' => false]);
} else {
    $pdo->prepare("
        INSERT INTO saved_events (user_id, event_id)
        VALUES (?, ?)
    ")->execute([$userId, $eventId]);

    echo json_encode(['saved' => true]);
}
