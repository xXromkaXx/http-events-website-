<?php
require_once __DIR__ . '/../config/db.php';

function registerUser($username, $email, $phone, $password_hash, $avatar = null) {
    $pdo = getPDO();
    $hashed = password_hash($password_hash, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, phone, password_hash, avatar)
        VALUES (?, ?, ?, ?, ?)
    ");

    return $stmt->execute([$username, $email, $phone, $hashed, $avatar]);
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
