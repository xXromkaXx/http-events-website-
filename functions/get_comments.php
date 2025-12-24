<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');
$eventId = (int)$_GET['event_id'];

$stmt = $pdo->prepare("
    SELECT c.content, c.created_at, u.username
    FROM comments c
    JOIN users u ON u.id = c.user_id
    WHERE c.event_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$eventId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
