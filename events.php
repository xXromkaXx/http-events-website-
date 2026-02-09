<?php

require_once 'init.php';
require_once 'helpers.php';


$pdo = getPDO();

// Отримуємо параметри фільтрів
$category = $_GET['category'] ?? 'Усі';
$date = $_GET['date'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$location = trim($_GET['location'] ?? '');
$random = isset($_GET['random']);

$userId = $_SESSION['user']['id'] ?? null;
$userRole = $_SESSION['user']['role'] ?? 'user';
$isAdmin = ($userRole === 'admin');
$excludeMy = isset($_GET['exclude_my']) && $userId !== null;
$isMyRequest = isset($_GET['my']) && $_GET['my'] == '1' && $userId;


$sql = "SELECT 
            events.*, 
            users.username,
            users.avatar
        FROM events
        LEFT JOIN users ON users.id = events.user_id
        WHERE 1";
$params = [];


// Фільтр по категорії
if ($category !== 'Усі' && empty($search)) {
    $sql .= " AND category = :category";
    $params[':category'] = $category;
}

if (!$isAdmin && !$isMyRequest) {
    $sql .= " AND events.moderation_status = 'published'";
}

/* 🔥 МОЇ ПОДІЇ */
if ($isMyRequest) {
    $sql .= " AND events.user_id = :my_user_id";
    $params[':my_user_id'] = $userId;
}
/* 🚫 ВИКЛЮЧИТИ МОЇ ПОДІЇ */
if ($excludeMy && $userId !== null) {
    $sql .= " AND (events.user_id IS NULL OR events.user_id != :exclude_user_id)";
    $params[':exclude_user_id'] = (int)$userId;
}



// Фільтр по даті
if ($date !== 'all') {
    $today = date('Y-m-d');

    switch($date) {
        case 'today':
            $sql .= " AND event_date = :today";
            $params[':today'] = $today;
            error_log("Додано фільтр дати: сьогодні (" . $today . ")");
            break;
        case 'tomorrow':
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $sql .= " AND event_date = :tomorrow";
            $params[':tomorrow'] = $tomorrow;

            break;
        case 'weekend':
            // Поточні вихідні
            $saturday = date('Y-m-d', strtotime('next saturday'));
            $sunday = date('Y-m-d', strtotime('next sunday'));
            $sql .= " AND (event_date = :saturday OR event_date = :sunday)";
            $params[':saturday'] = $saturday;
            $params[':sunday'] = $sunday;

            break;
        case 'week':
            $startOfWeek = date('Y-m-d', strtotime('monday this week'));
            $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
            $sql .= " AND event_date BETWEEN :startWeek AND :endWeek";
            $params[':startWeek'] = $startOfWeek;
            $params[':endWeek'] = $endOfWeek;

            break;
        default:
            // Якщо дата у форматі YYYY-MM-DD
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $sql .= " AND event_date = :custom_date";
                $params[':custom_date'] = $date;

            }
            break;
    }
}
if (empty($search) && !empty($location)) {
    $sql .= " AND location LIKE :location";
    $params[':location'] = "%$location%";
}

// Пошук по тексту (НЕ залежить від великих/малих літер)
if (!empty($search)) {
    $sql .= " AND (
        title LIKE :search
        OR category LIKE :search
        OR description LIKE :search
        OR location LIKE :search
    )";
    $params[':search'] = "%$search%";
}




// Сортування
if ($random) {
    $sql .= " ORDER BY RAND()";
} else {
    $sql .= " ORDER BY event_date ASC, event_time ASC";
}

// Обмеження кількості результатів
$sql .= " LIMIT 50";


try {
    error_log("SQL: " . $sql);
    error_log("PARAMS: " . json_encode($params));
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Форматуємо дані для фронтенду
    foreach ($events as &$event) {
        $event = formatEventForDisplay($event);
        include __DIR__ . '/components/event_card.php';
    }

   // echo json_encode($events, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Помилка бази даних: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Помилка бази даних',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Форматує подію для відображення на фронтенді
 */
function formatEventForDisplay($event) {
    // Форматуємо дату
    $event['formatted_date'] = formatEventDate($event['event_date']);

    // Форматуємо час
    $event['formatted_time'] = formatEventTime($event['event_time']);

    // Створюємо короткий опис
    $event['short_description'] = shortDescription($event['description']);

    // Обробляємо зображення
    if (empty($event['image'])) {
        $event['image'] = 'assets/img/default-event.jpg';
    }

    return $event;
}
?>
