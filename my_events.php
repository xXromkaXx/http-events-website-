<?php
session_start();
require_once 'init.php';
require_once 'helpers.php';
$isProfile = isset($_GET['profile']);

if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}
$hideCreator = true;
$userId = $_SESSION['user']['id'];


$successMessage = $_SESSION['success'] ?? '';
$errorMessage = $_SESSION['error'] ?? '';
unset($_SESSION['success']);
unset($_SESSION['error']);

// Кількість МОЇХ подій
$stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE user_id = ?");
$stmt->execute([$userId]);
$myEventsCount = (int)$stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Мої події | Events YC</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/events.css">
    <link rel="stylesheet" href="assets/css/modal.css">
    <link rel="stylesheet" href="assets/css/profile.css">

</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="zigzag-bg">
    <div class="zigzag-line zigzag-1"></div>
    <div class="zigzag-line zigzag-2"></div>
    <div class="zigzag-line zigzag-3"></div>
    <div class="zigzag-line zigzag-4"></div>
    <div class="zigzag-line zigzag-5"></div>
</div>
<?php if ($isProfile): ?>


    <div class="profile-info">
        <h2 class="profile-title">Мій профіль</h2>

        <div class="profile-container">
            <div class="profile-avatar-name-stats">
                <!-- Аватарка -->
                <div class="profile-avatar">
                    <?php if (!empty($_SESSION['user']['avatar'])): ?>
                        <img src="<?= htmlspecialchars($_SESSION['user']['avatar']) ?>" alt="Avatar">
                    <?php else: ?>
                        <span><?= strtoupper($_SESSION['user']['username'][0]) ?></span>
                    <?php endif; ?>
                </div>
                <div class="profile-name-stats">
                    <!-- Ім'я -->
                    <h1 class="profile-name"><?= htmlspecialchars($_SESSION['user']['username']) ?></h1>

                    <!-- Статистика -->
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-count"><?= $myEventsCount ?></span>
                            <span class="stat-label">Подій</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-count">0</span>
                            <span class="stat-label">Підписники</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-count">0</span>
                            <span class="stat-label">Підписки</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- email -->

            <div class="profile-bio">
                <?= htmlspecialchars($_SESSION['user']['email']) ?>
                <br>
                Зареєстровано: <?= htmlspecialchars($_SESSION['user']['created_at']) ?>
            </div>

            <!-- Кнопки дій -->
            <div class="profile-actions">
                <a href="edit_profile.php" class="btn-edit-profile">
                    <i class="fas fa-edit"></i>
                    Редагувати профіль
                </a>
                <a href="?archive=1" class="btn-secondary">
                    <i class="fas fa-archive"></i>
                    Архів подій
                </a>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Вийти
                </a>
            </div>
        </div>

        <!-- Таби для подій -->
        <div class="events-section">
            <div class="events-tabs">
                <button class="tab-item" data-tab="my">Мої</button>
                <button class="tab-item" data-tab="saved">Збережені</button>
                <button class="tab-item" data-tab="participating">Беру участь</button>
            </div>

                <div class="events-grid" id="profileEvents">
                    <!-- AJAX сюди підвантажить event_card -->
                    <div class="events-loading">
                        <div class="loading-spinner"></div>
                        <p>Завантаження подій...</p>
                    </div>
                </div>

                <div class="events-empty" style="display: none;">
                    <i class="fas fa-calendar-times"></i>
                    <p>Подій ще немає</p>
                    <a href="/event_form.php" class="btn-create-first">Створити першу подію</a>
                </div>
        </div>
    </div>


<?php endif; ?>

<main class="events-page">


    <?php if (!empty($successMessage)): ?>
        <div class="success-message" style="max-width: 600px; margin: 20px auto; text-align: center;">
            ✅ <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="error-message" style="max-width: 600px; margin: 20px auto; text-align: center;">
            ❌ <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>



</main>

<?php
include 'components/event_modal.php';
include 'includes/footer.php';
?>

<script src="assets/js/main.js"></script>
<script src="assets/js/events.js"></script>
<script src="assets/js/modal.js"></script>
</body>
</html>