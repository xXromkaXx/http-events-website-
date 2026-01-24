<?php
session_start();
require_once 'init.php';
require_once 'functions/auth.php';
require_once __DIR__ . '/functions/mail.php';

$errors = [];
$old = [];

$showRegisterForm = true;
$showCodeForm = false;

$avatarPath = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =========================
       ПІДТВЕРДЖЕННЯ КОДУ
    ========================= */
    if (isset($_POST['verify_code'], $_SESSION['register_data'])) {

        $data = $_SESSION['register_data'];

        if ($data['expires'] < time()) {
            $errors['general'] = 'Код прострочений';
        } elseif ((string)$_POST['verify_code'] !== (string)$data['code']) {
            $errors['general'] = 'Невірний код';
        } else {

            $userId = registerUser(
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['password'],
                $data['avatar']
            );

            $_SESSION['user'] = getUserByEmail($data['email']);
            unset($_SESSION['register_data']);

            header('Location: index.php');
            exit;
        }

        $showRegisterForm = false;
        $showCodeForm = true;
    }

    /* =========================
       ПЕРШИЙ КЛІК — РЕЄСТРАЦІЯ
    ========================= */
    if (!isset($_POST['verify_code'])) {

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        $old = compact('name', 'email', 'phone');

        if (empty($name)) {
            $errors['name'] = "Введіть ім'я";
        } elseif (strlen($name) < 2) {
            $errors['name'] = "Мінімум 2 символи";
        }

        if (empty($email)) {
            $errors['email'] = "Введіть email";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Некоректний email";
        } elseif (getUserByEmail($email)) {
            $errors['email'] = "Email вже зайнятий";
        }

        if (empty($phone)) {
            $errors['phone'] = "Введіть телефон";
        }

        if (empty($password)) {
            $errors['password'] = "Введіть пароль";
        } elseif (strlen($password) < 6) {
            $errors['password'] = "Мінімум 6 символів";
        }

        if ($password !== $confirm) {
            $errors['confirm'] = "Паролі не співпадають";
        }

        if (!empty($_POST['cropped_avatar']) &&
                preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $_POST['cropped_avatar'])) {

            $dataImg = explode(',', $_POST['cropped_avatar'])[1];

            $dir = 'uploads/avatars/';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $avatarPath = $dir . uniqid('reg_avatar_') . '.jpg';
            file_put_contents($avatarPath, base64_decode($dataImg));
        }

        if (empty($errors)) {

            $code = random_int(100000, 999999);

            $_SESSION['register_data'] = [
                    'name'     => $name,
                    'email'    => $email,
                    'phone'    => $phone,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'avatar'   => $avatarPath,
                    'code'     => $code,
                    'expires'  => time() + 900
            ];


            sendMail($email, 'Код підтвердження', "Ваш код: $code");

            $showRegisterForm = false;
            $showCodeForm = true;
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

    <link rel="stylesheet" href="assets/css/avatar_cropper.css">
    <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.1/dist/cropper.css">

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
    <?php if ($showRegisterForm): ?>
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
            <input type="password" id="password" name="password" autocomplete="new-password"
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

            <input type="hidden" name="action" value="profile">
            <?php

            include 'components/avatar_cropper.php';
            ?>
            <input type="hidden" name="cropped_avatar" id="croppedAvatar">

        </div>

        <button type="submit" class="auth-btn">Зареєструватися</button>
    </form>
    <?php endif; ?>
    <?php if ($showCodeForm): ?>
        <form method="POST" class="auth-form no-validate">

        <h3>Введіть код з email</h3>

            <input type="text"
                   name="verify_code"
                   maxlength="6"
                   required
                   placeholder="123456">

            <button class="auth-btn">Підтвердити</button>
        </form>
    <?php endif; ?>

    <div class="auth-link">
        Вже маєте акаунт? <a href="login.php">Увійти</a>
    </div>
</div>
<!-- Модалка кропу -->
<div class="avatar-cropper-wrapper" id="avatarCropper">
    <div class="avatar-cropper-box">
        <img id="cropperImage">

        <div class="cropper-actions">
            <button type="button" id="cropCancel">Скасувати</button>
            <button type="button" id="cropSave">Зберегти</button>
        </div>
    </div>
</div>
<script src="assets/js/auth.js"></script>
<script src="https://unpkg.com/cropperjs@1.6.1/dist/cropper.js" defer></script>
<script src="assets/js/avatar_cropper_register.js" defer></script>

</body>
</html>