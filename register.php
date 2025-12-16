<?php
session_start();
require_once 'init.php';
require_once 'functions/auth.php';

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    $old = compact('name', 'email', 'phone');



    if (empty($name)) {
        $errors['name'] = "Введіть ваше ім'я";
    } elseif (strlen($name) < 2) {
        $errors['name'] = "Ім'я повинно містити щонайменше 2 символи";
    }

    if (empty($email)) {
        $errors['email'] = "Введіть email";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Введіть коректний email";
    } elseif (getUserByEmail($email)) {
        $errors['email'] = "Користувач з таким email вже існує";
    }

    if (empty($phone)) {
        $errors['phone'] = "Введіть номер телефону";
    } elseif (!preg_match('/^[\d\s\-\+\(\)]{10,20}$/', $phone)) {
        $errors['phone'] = "Введіть коректний номер телефону";
    }

    if (empty($password)) {
        $errors['password'] = "Введіть пароль";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Пароль повинен містити щонайменше 6 символів";
    }

    if (empty($confirm)) {
        $errors['confirm'] = "Підтвердіть пароль";
    } elseif ($password !== $confirm) {
        $errors['confirm'] = "Паролі не співпадають";
    }

    $avatarPath = null;



    $username = $old['name'] ?? '';



    if (empty($errors)) {
        if (registerUser($name, $email, $phone, $password, $avatarPath)) {

            $_SESSION['user'] = getUserByEmail($email);
            $_SESSION['success'] = "Реєстрація успішна! Ласкаво просимо!";
            header('Location: index.php');
            exit;
        } else {
            $errors['general'] = "Сталася помилка при реєстрації. Спробуйте ще раз.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Реєстрація | Events YC</title>
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
        <h2>Створити акаунт</h2>
        <p>Приєднуйтесь до нашої спільноти!</p>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div class="auth-error-message">
            ❌ <?= htmlspecialchars($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST" class="auth-form" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Ім'я <span class="required">*</span></label>
            <input type="text" id="name" name="name"
                   value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                   class="<?= !empty($errors['name']) ? 'field-error' : '' ?>"
                   placeholder="Ваше ім'я">
            <?php if (!empty($errors['name'])): ?>
                <div class="field-error-text"><?= htmlspecialchars($errors['name']) ?></div>
            <?php endif; ?>
        </div>

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
            <label for="phone">Телефон <span class="required">*</span></label>
            <input type="tel" id="phone" name="phone"
                   value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                   class="<?= !empty($errors['phone']) ? 'field-error' : '' ?>"
                   placeholder="+380 (XX) XXX-XX-XX">
            <?php if (!empty($errors['phone'])): ?>
                <div class="field-error-text"><?= htmlspecialchars($errors['phone']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Пароль <span class="required">*</span></label>
            <input type="password" id="password" name="password"
                   class="<?= !empty($errors['password']) ? 'field-error' : '' ?>"
                   placeholder="Мінімум 6 символів">
            <?php if (!empty($errors['password'])): ?>
                <div class="field-error-text"><?= htmlspecialchars($errors['password']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="confirm">Підтвердження пароля <span class="required">*</span></label>
            <input type="password" id="confirm" name="confirm"
                   class="<?= !empty($errors['confirm']) ? 'field-error' : '' ?>"
                   placeholder="Повторіть пароль">
            <?php if (!empty($errors['confirm'])): ?>
                <div class="field-error-text"><?= htmlspecialchars($errors['confirm']) ?></div>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="avatar">Аватарка</label>
            <?php
            $currentAvatar = $user['avatar'];
            $username = $user['username'];
            include 'components/avatar_cropper.php';
            ?>

        </div>

        <button type="submit" class="auth-btn">Зареєструватися</button>
    </form>

    <div class="auth-link">
        Вже маєте акаунт? <a href="login.php">Увійти</a>
    </div>
</div>
<script src="assets/js/auth.js"></script>
</body>
</html>