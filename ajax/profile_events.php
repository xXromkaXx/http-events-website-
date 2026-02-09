<?php
require_once '../init.php';
require_once '../helpers.php';

$userId = $_SESSION['user']['id'] ?? null;
$type = $_GET['type'] ?? 'my';

if (!$userId) {
    http_response_code(401);
    exit;
}

try {
    switch ($type) {
        case 'saved':
            $stmt = $pdo->prepare("
            SELECT e.*, u.username, u.avatar
            FROM saved_events s
            JOIN events e ON e.id = s.event_id
            LEFT JOIN users u ON e.user_id = u.id
            WHERE s.user_id = ?
              AND e.moderation_status = 'published'
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
              AND e.moderation_status = 'published'
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

    foreach ($events as &$event) {
        $event['formatted_date'] = formatEventDate($event['event_date']);
        $event['formatted_time'] = formatEventTime($event['event_time']);
        $event['short_description'] = shortDescription((string)($event['description'] ?? ''), 100);
    }
    unset($event);

    foreach ($events as $event) {
        if ($type === 'my') {
            $showEditDelete = true;   // ✏️ 🗑️ показуємо
            $hideCreator = true;      // автора ховаємо
        } else {
            $showEditDelete = false;  // для saved / participating
            $hideCreator = false;
        }
        include '../components/event_card.php';
    }
} catch (Throwable $e) {
    http_response_code(500);
    error_log('profile_events.php error: ' . $e->getMessage());
    echo '<div class="no-events">Помилка завантаження подій</div>';
}
?>
