<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json; charset=utf-8');
$eventId = (int)($_GET['event_id'] ?? 0);
$currentUserId = (int)($_SESSION['user']['id'] ?? 0);

if ($eventId <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT c.id, c.user_id, c.content, c.created_at, u.username
    FROM comments c
    JOIN users u ON u.id = c.user_id
    WHERE c.event_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$eventId]);

$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($comments as &$comment) {
    $comment['can_delete'] = ((int)$comment['user_id'] === $currentUserId);
}
unset($comment);

echo json_encode($comments, JSON_UNESCAPED_UNICODE);
