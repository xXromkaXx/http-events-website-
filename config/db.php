<?php
function getPDO() {
    $host = 'localhost';
    $db   = 'events_db';
    $user = 'root';
    $pass = 'root';
    $port = '8889';
    $charset = 'utf8mb4';

    // Додаємо порт до DSN
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        die('Помилка підключення до БД: ' . $e->getMessage());
    }
}

