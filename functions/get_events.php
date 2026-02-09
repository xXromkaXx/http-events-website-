<?php
require_once 'init.php';
require_once 'helpers.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();
$filter = $_GET['filter'] ?? 'Усі';
$search = trim($_GET['search'] ?? '');



$sql = "SELECT 
            events.*, 
            users.username 
        FROM events
        LEFT JOIN users ON users.id = events.user_id
        WHERE 1";
$params = [];

if ($filter !== 'Усі') {
    $sql .= " AND category = :filter";
    $params[':filter'] = $filter;
}
if (!empty($search)) {
    $sql .= " AND (
        title LIKE :search_title
        OR description LIKE :search_description
        OR location LIKE :search_location
    )";
    $searchLike = "%$search%";
    $params[':search_title'] = $searchLike;
    $params[':search_description'] = $searchLike;
    $params[':search_location'] = $searchLike;
}

$sql .= " ORDER BY event_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Форматуємо перед JSON
foreach ($events as &$e) {
    $e['event_date'] = formatEventDate($e['event_date']);
    $e['event_time'] = formatEventTime($e['event_time']);
    $e['description_short'] = shortDescription($e['description']);
    $e['avatar'] = $e['avatar'] ?: 'assets/img/default-avatar.png';
}

echo json_encode($events, JSON_UNESCAPED_UNICODE);
