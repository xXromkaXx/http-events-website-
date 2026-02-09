<?php


require_once __DIR__ . '/../init.php';
header('Content-Type: application/json; charset=utf-8');


$eventId = (int)($_GET['event_id'] ?? 0);
$userId  = $_SESSION['user']['id'] ?? null;
$userRole = (string)($_SESSION['user']['role'] ?? 'user');
$isAdmin = ($userRole === 'admin');

if ($eventId <= 0) {
    echo json_encode([
        'likes_count' => 0,
        'comments_count' => 0,
        'is_liked' => false
    ]);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id, moderation_status FROM events WHERE id = ? LIMIT 1");
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo json_encode([
        'likes_count' => 0,
        'comments_count' => 0,
        'is_liked' => false
    ]);
    exit;
}

$isOwner = ((int)$event['user_id'] === (int)$userId);
$isPublished = (($event['moderation_status'] ?? 'published') === 'published');
if (!$isPublished && !$isAdmin && !$isOwner) {
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
