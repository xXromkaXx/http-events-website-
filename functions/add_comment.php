<?php
require_once __DIR__ . '/../init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Користувач не авторизований'
    ]);
    exit;
}

if (empty($_POST['event_id']) || empty($_POST['content'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Некоректні дані'
    ]);
    exit;
}

$userId  = (int) $_SESSION['user']['id'];
$eventId = (int) $_POST['event_id'];
$content = trim($_POST['content']);

$stmt = $pdo->prepare("
    INSERT INTO comments (event_id, user_id, content)
    VALUES (?, ?, ?)
");
$stmt->execute([$eventId, $userId, $content]);

echo json_encode(['success' => true]);


