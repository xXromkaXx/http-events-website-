<?php
// Ініціалізація сесії
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Підключення бази даних
require_once __DIR__ . '/config/db.php';
$pdo = getPDO();
if (!isset($pdo)) {
    die("❌ \$pdo не створився! Перевір config/db.php");
}

require_once __DIR__ . '/helpers.php';
ensureUsersProfileColumns($pdo);
ensureUsersRoleColumns($pdo);
ensureEventsModerationColumns($pdo);

if (isset($_SESSION['user']['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $freshUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($freshUser) {
            $_SESSION['user'] = $freshUser;
        }
    } catch (Throwable $e) {
        error_log('init user refresh error: ' . $e->getMessage());
    }
}

if (!defined('BASE_URL')) {
    $normalizePath = static function (string $path): string {
        return rtrim(str_replace('\\', '/', $path), '/');
    };

    $documentRoot = $normalizePath(realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    $projectRoot = $normalizePath(realpath(__DIR__) ?: __DIR__);

    $baseUrl = '';
    if ($documentRoot !== '' && strpos($projectRoot, $documentRoot) === 0) {
        $relative = trim(substr($projectRoot, strlen($documentRoot)), '/');
        $baseUrl = $relative === '' ? '' : '/' . $relative;
    }

    define('BASE_URL', $baseUrl);
}

?>
