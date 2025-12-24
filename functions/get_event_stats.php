<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../init.php';
header('Content-Type: application/json; charset=utf-8');


$eventId = (int)($_GET['event_id'] ?? 0);
$userId  = $_SESSION['user']['id'] ?? null;

if ($eventId <= 0) {
    echo json_encode([
        'likes_count' => 0,
        'comments_count' => 0,
        'is_liked' => false
    ]);
    exit;
}

/* ===== кількість лайків ===== */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM event_likes WHERE event_id = ?");
$stmt->execute([$eventId]);
$likesCount = (int)$stmt->fetchColumn();

/* ===== кількість коментарів ===== */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE event_id = ?");
$stmt->execute([$eventId]);
$commentsCount = (int)$stmt->fetchColumn();

/* ===== чи лайкнув користувач ===== */
$isLiked = false;
if ($userId) {
    $stmt = $pdo->prepare("
        SELECT 1 FROM event_likes
        WHERE event_id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$eventId, $userId]);
    $isLiked = (bool)$stmt->fetchColumn();
}

echo json_encode([
    'likes_count'    => $likesCount,
    'comments_count' => $commentsCount,
    'is_liked'       => $isLiked
]);
