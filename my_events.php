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

try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE user_id = :user_id ORDER BY event_date DESC");
    $stmt->execute([':user_id' => $userId]);
    $myEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Помилка при отриманні подій: " . htmlspecialchars($e->getMessage()));
}
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

                <div class="profile-avatar">
                    <?php if (!empty($_SESSION['user']['avatar'])): ?>
                        <img src="<?= htmlspecialchars($_SESSION['user']['avatar']) ?>" alt="Avatar">
                    <?php else: ?>
                        <span><?= strtoupper($_SESSION['user']['username'][0]) ?></span>
                    <?php endif; ?>
                </div>

            <div class="info-row">
                <span class="label">Ім'я</span>
                <span class="value"><?= htmlspecialchars($_SESSION['user']['username']) ?></span>
            </div>

            <div class="info-row">
                <span class="label">Email</span>
                <span class="value"><?= htmlspecialchars($_SESSION['user']['email']) ?></span>
            </div>

            <div class="info-row">
                <span class="label">Дата реєстрації</span>
                <span class="value"><?= htmlspecialchars($_SESSION['user']['created_at']) ?></span>
            </div>
        </div>
    </div>
    <div class="profile-actions">
        <a href="edit_profile.php" class="btn-edit-profile">✏️ Редагувати профіль</a>
    </div>



<?php endif; ?>

<main class="events-page">
    <h2 class="events-title">Мої події</h2>

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

    <div class="events-grid">
        <?php if ($myEvents): ?>
            <?php foreach ($myEvents as $event): ?>
                <?php $showEditDelete = true; ?>
                <?php include 'components/event_card.php'; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-events-message">
                <p>У вас ще немає створених подій.</p>
                <a href="create_event.php" class="btn-create-first">Створити першу подію</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
include 'components/event_modal.php';
include 'components/edit_modal.php';
include 'includes/footer.php';
?>

<script src="assets/js/main.js"></script>
<script src="assets/js/events.js"></script>
<script src="assets/js/modal.js"></script>
</body>
</html>