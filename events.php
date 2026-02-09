<?php

require_once 'init.php';
require_once 'helpers.php';

$pdo = getPDO();

$category = $_GET['category'] ?? 'Усі';
$date = $_GET['date'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$title = trim($_GET['title'] ?? '');
$location = trim($_GET['location'] ?? '');
$random = isset($_GET['random']);
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = (int)($_GET['limit'] ?? 12);
if ($limit < 1) {
    $limit = 12;
}
if ($limit > 48) {
    $limit = 48;
}
$offset = ($page - 1) * $limit;

$userId = $_SESSION['user']['id'] ?? null;
$userRole = $_SESSION['user']['role'] ?? 'user';
$isAdmin = ($userRole === 'admin');
$excludeMy = isset($_GET['exclude_my']) && $userId !== null;
$isMyRequest = isset($_GET['my']) && $_GET['my'] == '1' && $userId;

$fromWhereSql = "
    FROM events
    LEFT JOIN users ON users.id = events.user_id
    WHERE 1
";
$params = [];

if ($category !== 'Усі') {
    $fromWhereSql .= " AND category = :category";
    $params[':category'] = $category;
}

if (!$isMyRequest) {
    $fromWhereSql .= " AND events.moderation_status = 'published'";
}

if ($isMyRequest) {
    $fromWhereSql .= " AND events.user_id = :my_user_id";
    $params[':my_user_id'] = $userId;
}

if ($excludeMy && $userId !== null) {
    $fromWhereSql .= " AND (events.user_id IS NULL OR events.user_id != :exclude_user_id)";
    $params[':exclude_user_id'] = (int)$userId;
}

if ($date !== 'all') {
    $today = date('Y-m-d');

    switch ($date) {
        case 'today':
            $fromWhereSql .= " AND event_date = :today";
            $params[':today'] = $today;
            break;

        case 'tomorrow':
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $fromWhereSql .= " AND event_date = :tomorrow";
            $params[':tomorrow'] = $tomorrow;
            break;

        case 'weekend':
            $saturday = date('Y-m-d', strtotime('next saturday'));
            $sunday = date('Y-m-d', strtotime('next sunday'));
            $fromWhereSql .= " AND (event_date = :saturday OR event_date = :sunday)";
            $params[':saturday'] = $saturday;
            $params[':sunday'] = $sunday;
            break;

        case 'week':
            $startOfWeek = date('Y-m-d', strtotime('monday this week'));
            $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
            $fromWhereSql .= " AND event_date BETWEEN :startWeek AND :endWeek";
            $params[':startWeek'] = $startOfWeek;
            $params[':endWeek'] = $endOfWeek;
            break;

        default:
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $fromWhereSql .= " AND event_date = :custom_date";
                $params[':custom_date'] = $date;
            }
            break;
    }
}

if (!empty($location)) {
    $fromWhereSql .= " AND location LIKE :location";
    $params[':location'] = "%$location%";
}

if (!empty($title)) {
    $fromWhereSql .= " AND title LIKE :title";
    $params[':title'] = "%$title%";
}

if (!empty($search)) {
    $fromWhereSql .= " AND (
        title LIKE :search_title
        OR category LIKE :search_category
        OR description LIKE :search_description
        OR location LIKE :search_location
    )";
    $searchLike = "%$search%";
    $params[':search_title'] = $searchLike;
    $params[':search_category'] = $searchLike;
    $params[':search_description'] = $searchLike;
    $params[':search_location'] = $searchLike;
}

$sql = "SELECT events.*, users.username, users.avatar " . $fromWhereSql;
if ($random) {
    $sql .= " ORDER BY RAND()";
} else {
    $sql .= " ORDER BY event_date ASC, event_time ASC";
}
$sql .= " LIMIT $limit OFFSET $offset";

try {
    $countSql = "SELECT COUNT(*) " . $fromWhereSql;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $renderedCount = count($events);
    $hasMore = (($offset + $renderedCount) < $total);
    $totalPages = max(1, (int)ceil($total / $limit));

    ob_start();
    foreach ($events as &$event) {
        $event = formatEventForDisplay($event);
        include __DIR__ . '/components/event_card.php';
    }
    unset($event);

    $html = trim(ob_get_clean());
    if ($html !== '') {
        echo $html;
    } elseif ($page === 1) {
        echo '<div class="no-events">Подій не знайдено</div>';
    }

    echo '<div class="events-meta" data-has-more="' . ($hasMore ? '1' : '0') . '" data-page="' . $page . '" data-total="' . $total . '" data-total-pages="' . $totalPages . '" style="display:none;"></div>';
} catch (PDOException $e) {
    error_log('Помилка бази даних: ' . $e->getMessage());
    http_response_code(500);
    echo '<div class="no-events">Помилка завантаження подій</div>';
}

function formatEventForDisplay($event) {
    $event['formatted_date'] = formatEventDate($event['event_date']);
    $event['formatted_time'] = formatEventTime($event['event_time']);
    $event['short_description'] = shortDescription($event['description']);

    if (empty($event['image'])) {
        $event['image'] = 'assets/img/default-event.jpg';
    }

    return $event;
}
?>
