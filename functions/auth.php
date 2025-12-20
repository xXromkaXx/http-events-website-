<?php
require_once __DIR__ . '/../config/db.php';

function registerUser($name, $email, $phone, $passwordHash, $avatarPath = null) {
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, phone, password_hash, avatar)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $name,
        $email,
        $phone,
        $passwordHash,
        $avatarPath
    ]);

    return $pdo->lastInsertId();
}




function getUserByEmail($email) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function verifyLogin($email, $password_hash) {
    $user = getUserByEmail($email);
    if ($user && password_verify($password_hash, $user['password_hash'])) {
        return $user;
    }
    return false;
}
