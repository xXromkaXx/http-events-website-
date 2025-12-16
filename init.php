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


?>
