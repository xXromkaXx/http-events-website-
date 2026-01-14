<?php
session_start();
require_once '../init.php';

$userId = $_SESSION['user']['id'] ?? null;
$eventId = (int)($_GET['event_id'] ?? 0);

if (!$userId || !$eventId) {
    echo json_encode(['saved' => false]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id FROM saved_events
    WHERE user_id = ? AND event_id = ?
");
$stmt->execute([$userId, $eventId]);

echo json_encode(['saved' => (bool)$stmt->fetch()]);
