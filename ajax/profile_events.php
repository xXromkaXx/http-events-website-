<?php
session_start();
require_once '../init.php';

$userId = $_SESSION['user']['id'] ?? null;
$type = $_GET['type'] ?? 'my';

if (!$userId) exit;

switch ($type) {
    case 'saved':
        $stmt = $pdo->prepare("
            SELECT e.*, u.username, u.avatar
            FROM saved_events s
            JOIN events e ON e.id = s.event_id
            LEFT JOIN users u ON e.user_id = u.id
            WHERE s.user_id = ?
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$userId]);
        break;

    case 'participating':
        // Події, де користувач поставив лайк (бере участь)
        $stmt = $pdo->prepare("
            SELECT e.*, u.username, u.avatar
            FROM event_likes el
            JOIN events e ON el.event_id = e.id
            LEFT JOIN users u ON e.user_id = u.id
            WHERE el.user_id = ?
            ORDER BY el.created_at DESC
        ");
        $stmt->execute([$userId]);
        break;

    default: // my
        $stmt = $pdo->prepare("
            SELECT e.*, u.username, u.avatar
            FROM events e
            LEFT JOIN users u ON e.user_id = u.id
            WHERE e.user_id = ?
            ORDER BY e.event_date DESC
        ");
        $stmt->execute([$userId]);
}

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$events) {
    echo '<div class="no-events">Подій немає</div>';
    exit;
}

foreach ($events as $event) {
    // Додаємо прапорець, щоб приховати автора у картках
    $hideCreator = true;
    include '../components/event_card.php';
}
?>