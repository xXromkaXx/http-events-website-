<?php
session_start();
require_once 'init.php';
require_once 'helpers.php';
require_once __DIR__ . '/functions/mail.php';


if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}
$isAjax = isset($_POST['ajax']);

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
}
$flashSuccess = $_SESSION['success'] ?? null;
$flashError = $_SESSION['error'] ?? null;

unset($_SESSION['success'], $_SESSION['error']);

ensureUsersProfileColumns($pdo);
$hasCity = hasUsersColumn($pdo, 'city');
$hasInstagram = hasUsersColumn($pdo, 'instagram');
$hasBio = hasUsersColumn($pdo, 'bio');

$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user']['id']]);
$freshUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
if ($freshUser) {
    $_SESSION['user'] = $freshUser;
}
$user = $_SESSION['user'];

/* === ОКРЕМІ МАСИВИ === */
$errorsProfile = [];
$successProfile = '';

$errorsEmail = [];
$successEmail = '';

$errorsPassword = [];
$successPassword = '';

/* ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (($_POST['action'] ?? '') === 'organizer_request') {
        if (($_SESSION['user']['role'] ?? 'user') === 'admin' || ($_SESSION['user']['role'] ?? 'user') === 'organizer') {
            $_SESSION['success'] = "Ваш акаунт вже має права організатора.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET organizer_status='pending' WHERE id=?");
            $stmt->execute([$_SESSION['user']['id']]);
            $_SESSION['user']['organizer_status'] = 'pending';
            $_SESSION['success'] = "Заявку організатора відправлено на модерацію.";
        }
        header('Location: edit_profile.php');
        exit;
    }


    /* ===== ПРОФІЛЬ ===== */
    if ($_POST['action'] === 'profile') {

        $name = trim($_POST['name'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $instagram = trim($_POST['instagram'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        $avatarPath = $user['avatar'];

        if ($name === '') {
            $errorsProfile['name'] = "Введіть імʼя";
        } elseif (mb_strlen($name) < 2) {
            $errorsProfile['name'] = "Імʼя повинно містити мінімум 2 символи";
        }

        $phoneRaw = $_POST['phone'] ?? '';
        $phoneDigits = preg_replace('/\D+/', '', $phoneRaw);
        $phone = $user['phone'];

        if ($phoneDigits !== '') {

            // якщо починається з 0 → додаємо 38
            if (strlen($phoneDigits) === 10 && $phoneDigits[0] === '0') {
                $phoneDigits = '38' . $phoneDigits;
            }

            // якщо 11 цифр і починається з 8 (старий формат)
            if (strlen($phoneDigits) === 11 && $phoneDigits[0] === '8') {
                $phoneDigits = '3' . $phoneDigits;
            }

            // фінальна перевірка
            if (strlen($phoneDigits) !== 12 || substr($phoneDigits, 0, 3) !== '380') {
                $errorsProfile['phone'] = "Введіть коректний український номер";
            } else {
                $phone = $phoneDigits;
            }
        }

        if ($hasCity && $city !== '' && mb_strlen($city) > 120) {
            $errorsProfile['city'] = "Місто занадто довге (макс. 120 символів)";
        }

        if ($hasInstagram && $instagram !== '' && mb_strlen($instagram) > 120) {
            $errorsProfile['instagram'] = "Нік Instagram занадто довгий";
        }

        if ($hasBio && $bio !== '' && mb_strlen($bio) > 1000) {
            $errorsProfile['bio'] = "Поле 'Про себе' до 1000 символів";
        }
        /* AVATAR */
        if (!empty($_POST['cropped_avatar']) &&
                preg_match('/^data:image\/jpeg;base64,/', $_POST['cropped_avatar'])) {

            if ($avatarPath && file_exists($avatarPath)) {
                unlink($avatarPath);
            }

            $data = explode(',', $_POST['cropped_avatar'])[1];
            $dir = 'uploads/avatars/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            $avatarPath = $dir . uniqid('avatar_') . '.jpg';
            file_put_contents($avatarPath, base64_decode($data));
        }

        if (empty($errorsProfile)) {

            $set = ["username = ?", "phone = ?"];
            $params = [$name, $phone];
            if ($hasCity) {
                $set[] = "city = ?";
                $params[] = ($city ?: null);
            }
            if ($hasInstagram) {
                $set[] = "instagram = ?";
                $params[] = ($instagram ?: null);
            }
            if ($hasBio) {
                $set[] = "bio = ?";
                $params[] = ($bio ?: null);
            }
            $set[] = "avatar = ?";
            $params[] = $avatarPath;
            $params[] = $user['id'];

            $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $set) . " WHERE id = ?");
            $stmt->execute($params);

            $_SESSION['user']['username'] = $name;
            $_SESSION['user']['phone'] = $phone;
            if ($hasCity) {
                $_SESSION['user']['city'] = $city ?: null;
            }
            if ($hasInstagram) {
                $_SESSION['user']['instagram'] = $instagram ?: null;
            }
            if ($hasBio) {
                $_SESSION['user']['bio'] = $bio ?: null;
            }
            $_SESSION['user']['avatar'] = $avatarPath;

            // 🔥 AJAX-відповідь
            if (isset($_POST['ajax'])) {
                echo json_encode([
                        'success' => true,
                        'username' => $name,
                        'phone' => $phone,
                        'city' => $city,
                        'instagram' => $instagram,
                        'bio' => $bio,
                        'avatar' => $avatarPath
                ]);
                exit;
            }

            $successProfile = "Профіль успішно оновлено ✅";
        }
        if (!empty($errorsProfile)) {
            if ($isAjax) {
                echo json_encode([
                        'success' => false,
                        'errors' => $errorsProfile
                ]);
                exit;
            }
        }

        // SUCCESS
        if ($isAjax) {
            echo json_encode([
                    'success' => true,
                    'username' => $name,
                    'phone' => $phone,
                    'city' => $city,
                    'instagram' => $instagram,
                    'bio' => $bio,
                    'avatar' => $avatarPath
            ]);
            exit;
        }

    }

    /* ===== EMAIL ===== */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {


            header('Content-Type: application/json; charset=utf-8');



        /* === НАДСИЛАННЯ КОДУ === */
        if ($_POST['action'] === 'email') {

            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password_confirm'] ?? '';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['error' => 'Некоректний email']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id=?");
            $stmt->execute([$_SESSION['user']['id']]);
            $u = $stmt->fetch();

            if (!$u || !password_verify($password, $u['password_hash'])) {
                echo json_encode(['error' => 'Невірний пароль']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['error' => 'Email вже використовується']);
                exit;
            }

            $code = random_int(100000, 999999);
            $expires = date('Y-m-d H:i:s', time() + 900);

            $stmt = $pdo->prepare("
            REPLACE INTO email_changes (user_id, new_email, code, expires_at)
            VALUES (?, ?, ?, ?)
        ");
            $stmt->execute([$_SESSION['user']['id'], $email, $code, $expires]);

            $sent = sendMail(
                    $email,
                    'Зміна email',
                    "Код підтвердження: $code\nДійсний 15 хв"
            );

            if (!$sent) {

                // ❌ якщо лист не доставлений — прибираємо код
                $stmt = $pdo->prepare("DELETE FROM email_changes WHERE user_id=?");
                $stmt->execute([$_SESSION['user']['id']]);

                echo json_encode([
                        'error' => 'Не вдалося надіслати лист. Перевірте правильність email.'
                ]);
                exit;
            } else {

                echo json_encode([
                        'success' => 'Код підтвердження надіслано'
                ]);
                exit;
            }
        }

        /* === ПЕРЕВІРКА КОДУ === */
        if ($_POST['action'] === 'confirm_email') {

            $code = trim($_POST['code'] ?? '');

            $stmt = $pdo->prepare("
            SELECT * FROM email_changes
            WHERE user_id=? AND code=?
        ");
            $stmt->execute([$_SESSION['user']['id'], $code]);
            $row = $stmt->fetch();

            if (!$row) {
                echo json_encode(['error' => 'Невірний код']);
                exit;
            }

            if (strtotime($row['expires_at']) < time()) {
                echo json_encode(['error' => 'Код прострочений']);
                exit;
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE users SET email=? WHERE id=?");
            $stmt->execute([$row['new_email'], $_SESSION['user']['id']]);

            $stmt = $pdo->prepare("DELETE FROM email_changes WHERE user_id=?");
            $stmt->execute([$_SESSION['user']['id']]);

            $pdo->commit();

            $_SESSION['user']['email'] = $row['new_email'];

            echo json_encode(['success' => 'Email змінено']);
            exit;
        }
    }


    /* ===== PASSWORD ===== */
    if ($_POST['action'] === 'password') {

        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['new_password_confirm'] ?? '';

        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id=?");
        $stmt->execute([$user['id']]);
        $dbUser = $stmt->fetch();

        if (!$dbUser || !password_verify($current, $dbUser['password_hash'])) {
            $errorsPassword['current'] = "Поточний пароль неправильний";
        }

        if (mb_strlen($new) < 8) {
            $errorsPassword['new'] = "Мінімум 8 символів";
        }

        if ($new !== $confirm) {
            $errorsPassword['confirm'] = "Паролі не співпадають";
        }

        if (empty($errorsPassword)) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $stmt->execute([$hash, $user['id']]);

            $_SESSION['success'] = "Пароль успішно змінено 🔐";
            header('Location: edit_profile.php');
            exit;
        } else {
            $_SESSION['error'] = "Помилка зміни пароля";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редагувати профіль</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/avatar_cropper.css">
    <link rel="stylesheet" href="assets/css/profile_edit.css?v=<?= filemtime(__DIR__ . '/assets/css/profile_edit.css') ?>">
    <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.1/dist/cropper.css">


</head>
<body class="auth-page profile-edit-page">

<div class="profile-edit-container">
    <div class="profile-edit-header">
        <div>
            <h2>Редагування профілю</h2>
            <p>Оновіть ім'я, контакти та параметри безпеки акаунту.</p>
        </div>
        <a href="my_events.php?profile=1" class="back-link">← Назад до профілю</a>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="profile-flash success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="profile-flash error"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>


    <!-- ===== ПРОФІЛЬ ===== -->
    <div class="section-card primary">
        <form method="POST" action="<?= BASE_URL ?>/edit_profile.php">
            <input type="hidden" name="action" value="profile">
            <?php
            $currentAvatar = $user['avatar'];
            $username = $user['username'];
            include 'components/avatar_cropper.php';
            ?>
            <input type="hidden" name="cropped_avatar" id="croppedAvatar">
            <h3>👤 Основна інформація</h3>


            <div class="profile-row <?= !empty($errorsProfile['name']) ? 'has-error' : '' ?>">

                <span class="label">Імʼя</span>

                <div class="field-wrapper">
                    <span class="value"><?= htmlspecialchars($user['username']) ?></span>

                    <input class="edit-input <?= !empty($errorsProfile['name']) ? 'field-error' : '' ?>"
                           type="text"
                           name="name"
                           value="<?= htmlspecialchars($user['username']) ?>">

                    <?php if (!empty($errorsProfile['name'])): ?>
                        <div class="field-error-text">
                            <?= htmlspecialchars($errorsProfile['name']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="actions">
                    <button type="button" class="edit-btn">✏️</button>
                    <button type="submit" class="save-btn">💾</button>
                    <button type="button" class="cancel-btn">✖</button>
                </div>

            </div>


            <div class="profile-row <?= !empty($errorsProfile['phone']) ? 'has-error' : '' ?>">

                <span class="label">Телефон</span>

                <div class="field-wrapper">
                    <span class="value"
                          data-raw="<?= htmlspecialchars($user['phone'] ?? '') ?>">
    <?= formatPhone($user['phone'] ?? null) ?>
</span>


                    <input
                            type="text"
                            name="phone"
                            id="phone"
                            class="edit-input <?= !empty($errorsProfile['phone']) ? 'field-error' : '' ?>"
                            value="<?= htmlspecialchars($user['phone'] ?? '') ?>"

                    >

                    <?php if (!empty($errorsProfile['phone'])): ?>
                        <div class="field-error-text">
                            <?= htmlspecialchars($errorsProfile['phone']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="actions">
                    <button type="button" class="edit-btn">✏️</button>
                    <button type="submit" class="save-btn">💾</button>
                    <button type="button" class="cancel-btn">✖</button>
                </div>

            </div>

            <?php if ($hasCity): ?>
            <div class="profile-row <?= !empty($errorsProfile['city']) ? 'has-error' : '' ?>">
                <span class="label">Місто</span>
                <div class="field-wrapper">
                    <span class="value"><?= htmlspecialchars($user['city'] ?? 'Не вказано') ?></span>
                    <input
                            type="text"
                            name="city"
                            class="edit-input <?= !empty($errorsProfile['city']) ? 'field-error' : '' ?>"
                            value="<?= htmlspecialchars($user['city'] ?? '') ?>"
                            maxlength="120"
                    >
                    <?php if (!empty($errorsProfile['city'])): ?>
                        <div class="field-error-text"><?= htmlspecialchars($errorsProfile['city']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="actions">
                    <button type="button" class="edit-btn">✏️</button>
                    <button type="submit" class="save-btn">💾</button>
                    <button type="button" class="cancel-btn">✖</button>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($hasInstagram): ?>
            <div class="profile-row <?= !empty($errorsProfile['instagram']) ? 'has-error' : '' ?>">
                <span class="label">Instagram</span>
                <div class="field-wrapper">
                    <span class="value"><?= htmlspecialchars($user['instagram'] ?? 'Не вказано') ?></span>
                    <input
                            type="text"
                            name="instagram"
                            class="edit-input <?= !empty($errorsProfile['instagram']) ? 'field-error' : '' ?>"
                            value="<?= htmlspecialchars($user['instagram'] ?? '') ?>"
                            maxlength="120"
                            placeholder="@username"
                    >
                    <?php if (!empty($errorsProfile['instagram'])): ?>
                        <div class="field-error-text"><?= htmlspecialchars($errorsProfile['instagram']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="actions">
                    <button type="button" class="edit-btn">✏️</button>
                    <button type="submit" class="save-btn">💾</button>
                    <button type="button" class="cancel-btn">✖</button>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($hasBio): ?>
            <div class="profile-row bio-row <?= !empty($errorsProfile['bio']) ? 'has-error' : '' ?>">
                <span class="label">Про себе</span>
                <div class="field-wrapper">
                    <span class="value"><?= htmlspecialchars($user['bio'] ?? 'Не вказано') ?></span>
                    <textarea
                            name="bio"
                            class="edit-input <?= !empty($errorsProfile['bio']) ? 'field-error' : '' ?>"
                            rows="4"
                            maxlength="1000"
                            placeholder="Коротко про себе..."
                    ><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    <?php if (!empty($errorsProfile['bio'])): ?>
                        <div class="field-error-text"><?= htmlspecialchars($errorsProfile['bio']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="actions">
                    <button type="button" class="edit-btn">✏️</button>
                    <button type="submit" class="save-btn">💾</button>
                    <button type="button" class="cancel-btn">✖</button>
                </div>
            </div>
            <?php endif; ?>

        </form>
    </div>

    <!-- ===== БЕЗПЕКА ===== -->
    <div class="section-card security">
        <h3>🔐 Безпека акаунту</h3>

        <form method="POST" class="security-form">
            <h3>Статус організатора</h3>
            <div class="security-row">
                <label>Роль</label>
                <strong><?= htmlspecialchars($_SESSION['user']['role'] ?? 'user') ?></strong>
            </div>
            <div class="security-row">
                <label>Статус заявки</label>
                <strong><?= htmlspecialchars($_SESSION['user']['organizer_status'] ?? 'none') ?></strong>
            </div>
            <?php if (($_SESSION['user']['role'] ?? 'user') === 'user' && (($_SESSION['user']['organizer_status'] ?? 'none') !== 'pending')): ?>
                <input type="hidden" name="action" value="organizer_request">
                <button class="auth-btn" type="submit">Подати заявку організатора</button>
            <?php endif; ?>
        </form>

        <form method="POST" id="emailForm" class="security-form">

            <h3>Змінити Email</h3>

            <input type="hidden" name="action" value="email">

            <div class="security-row">
                <label>Поточний email</label>
                <strong><?= htmlspecialchars($user['email'] ?? 'Не вказано') ?></strong>

            </div>

            <div class="security-row">
                <label>Новий email</label>
                <input type="email" name="email" required>
            </div>

            <div class="security-row">
                <label>Підтвердіть пароль</label>
                <input type="password" name="password_confirm" required>
            </div>
            <p id="emailMsg"></p>
            <button class="auth-btn">Надіслати код</button>


        </form>
        <form id="codeForm" class="security-form" style="display:none">
            <div class="security-row">
                <label>Код з email</label>
                <input type="text" name="code" maxlength="6" required>
            </div>


            <p id="codeMsg"></p>
            <button class="auth-btn">Підтвердити</button>


        </form>


        <!-- PASSWORD -->
        <form method="POST" class="security-form">
            <h3>Зміна пароля</h3>
            <input type="hidden" name="action" value="password">


            <div class="security-row">
                <label>Поточний пароль</label>
                <input type="password" name="current_password" required>
            </div>


            <div class="security-row">
                <label>Новий пароль</label>
                <input type="password" name="new_password" required>
            </div>

            <div class="security-row">
                <label>Повторіть новий пароль</label>
                <input type="password" name="new_password_confirm" required>
            </div>

            <button class="auth-btn danger">Змінити пароль</button>
        </form>
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
<script src="assets/js/auth.js" defer></script>
<script src="https://unpkg.com/cropperjs@1.6.1/dist/cropper.js" defer></script>
<script src="assets/js/avatar-cropper.js" defer></script>
<script src="assets/js/profile-edit.js" defer></script>

</body>
</html>
