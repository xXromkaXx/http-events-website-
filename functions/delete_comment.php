<?php
require_once __DIR__ . '/../init.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $userId = (int)($_SESSION['user']['id'] ?? 0);
    $commentId = (int)($_POST['comment_id'] ?? 0);
    error_log("delete_comment.php hit: user_id={$userId}, comment_id={$commentId}");

    if ($userId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Не авторизований'
        ]);
        exit;
    }

    if ($commentId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Некоректний ID коментаря'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        DELETE FROM comments
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$commentId, $userId]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Можна видаляти лише власні коментарі'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('delete_comment.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Помилка сервера'
    ]);
}
