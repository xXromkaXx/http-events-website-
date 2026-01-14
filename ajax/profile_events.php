<?php
session_start();
require_once '../init.php';

$userId = $_SESSION['user']['id'] ?? null;
$type = $_GET['type'] ?? 'my';

if (!$userId) exit;

switch ($type) {

    case 'saved':
        $stmt = $pdo->prepare("
            SELECT e.*
            FROM saved_events s
            JOIN events e ON e.id = s.event_id
            WHERE s.user_id = ?
            ORDER BY s.id DESC
        ");
        $stmt->execute([$userId]);
        break;

    case 'participating':
        echo '<div class="no-events">Скоро тут будуть події</div>';
        exit;

    default: // my
        $stmt = $pdo->prepare("
            SELECT *
            FROM events
            WHERE user_id = ?
            ORDER BY event_date DESC
        ");
        $stmt->execute([$userId]);
}

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$events) {
    echo '<div class="no-events">Подій немає</div>';
    exit;
}

foreach ($events as $event) {
    include '../components/event_card.php';
}
