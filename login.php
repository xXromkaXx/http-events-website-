<?php
session_start();
require_once 'init.php';
require_once 'functions/auth.php';

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    $old = ['email' => $email];

    if (empty($email)) {
        $errors['email'] = "Введіть email";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Введіть коректний email";
    }

    if (empty($password)) {
        $errors['password'] = "Введіть пароль";
    }

    if (empty($errors)) {
        $user = verifyLogin($email, $password);
        if ($user) {
            $_SESSION['user'] = $user;
            header('Location: index.php');
            exit;
        } else {
            $errors['general'] = "Невірний email або пароль";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Увійти | Events YC</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-page">
<div class="auth-background">
    <div class="zigzag-line auth-zigzag-1"></div>
    <div class="zigzag-line auth-zigzag-2"></div>
    <div class="zigzag-line auth-zigzag-3"></div>
</div>

<div class="auth-container">
    <div class="auth-header">
        <h2>Увійти в акаунт</h2>
        <p>Ласкаво просимо назад!</p>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div class="auth-error-message">
            ❌ <?= htmlspecialchars($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST" class="auth-form">
        <div class="form-group">
            <label for="email">Email <span class="required">*</span></label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                   class="<?= !empty($errors['email']) ? 'field-error' : '' ?>"
                   placeholder="your@email.com">
            <?php if (!empty($errors['email'])): ?>
                <div class="field-error-text"><?= htmlspecialchars($errors['email']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Пароль <span class="required">*</span></label>
            <input type="password" id="password" name="password"
                   class="<?= !empty($errors['password']) ? 'field-error' : '' ?>"
                   placeholder="Введіть ваш пароль">
            <?php if (!empty($errors['password'])): ?>
                <div class="field-error-text"><?= htmlspecialchars($errors['password']) ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="auth-btn">Увійти</button>
    </form>

    <div class="auth-link">
        Ще не маєте акаунта? <a href="register.php">Зареєструватися</a>
    </div>
</div>

<script src="assets/js/auth.js"></script>
</body>
</html>