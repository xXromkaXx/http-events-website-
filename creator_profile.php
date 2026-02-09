<?php
require_once 'init.php';
require_once 'helpers.php';

ensureUsersProfileColumns($pdo);
$hasCity = hasUsersColumn($pdo, 'city');
$hasInstagram = hasUsersColumn($pdo, 'instagram');
$hasBio = hasUsersColumn($pdo, 'bio');

$creatorId = (int)($_GET['user_id'] ?? 0);
if ($creatorId <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, username, email, avatar, created_at,
           " . ($hasCity ? "city" : "NULL AS city") . ",
           " . ($hasInstagram ? "instagram" : "NULL AS instagram") . ",
           " . ($hasBio ? "bio" : "NULL AS bio") . "
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$creatorId]);
$creator = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$creator) {
    http_response_code(404);
    exit('Користувача не знайдено');
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE user_id = ?");
$stmt->execute([$creatorId]);
$eventsCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM event_likes l
    JOIN events e ON e.id = l.event_id
    WHERE e.user_id = ?
");
$stmt->execute([$creatorId]);
$likesCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT e.*, u.username, u.avatar
    FROM events e
    LEFT JOIN users u ON u.id = e.user_id
    WHERE e.user_id = ?
      AND e.moderation_status = 'published'
    ORDER BY e.event_date ASC, e.event_time ASC
");
$stmt->execute([$creatorId]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профіль автора | Events YC</title>
    <link rel="stylesheet" href="assets/css/main.css?v=<?= filemtime(__DIR__ . '/assets/css/main.css') ?>">
    <link rel="stylesheet" href="assets/css/events.css?v=<?= filemtime(__DIR__ . '/assets/css/events.css') ?>">
    <link rel="stylesheet" href="assets/css/modal.css?v=<?= filemtime(__DIR__ . '/assets/css/modal.css') ?>">
    <link rel="stylesheet" href="assets/css/profile.css?v=<?= filemtime(__DIR__ . '/assets/css/profile.css') ?>">
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

<div class="profile-info">
    <h2 class="profile-title">Профіль автора</h2>

    <div class="profile-container">
        <div class="profile-avatar-name-stats">
            <div class="profile-avatar">
                <?php if (!empty($creator['avatar'])): ?>
                    <img src="<?= htmlspecialchars($creator['avatar']) ?>" alt="Avatar">
                <?php else: ?>
                    <span><?= strtoupper($creator['username'][0] ?? 'U') ?></span>
                <?php endif; ?>
            </div>
            <div class="profile-name-stats">
                <h1 class="profile-name"><?= htmlspecialchars($creator['username']) ?></h1>
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-count"><?= $eventsCount ?></span>
                        <span class="stat-label">Подій</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-count"><?= $likesCount ?></span>
                        <span class="stat-label">Учасників</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-bio">
            <?php if (!empty($creator['city'])): ?>
                Місто: <?= htmlspecialchars($creator['city']) ?><br>
            <?php endif; ?>
            <?php if (!empty($creator['instagram'])): ?>
                Instagram: @<?= htmlspecialchars(ltrim($creator['instagram'], '@')) ?><br>
            <?php endif; ?>
            <?php if (!empty($creator['bio'])): ?>
                Про себе: <?= nl2br(htmlspecialchars($creator['bio'])) ?><br>
            <?php endif; ?>
            Зареєстровано: <?= htmlspecialchars($creator['created_at']) ?>
        </div>
    </div>

    <div class="events-section">
        <div class="events-tabs">
            <button class="tab-item active" type="button">Події автора</button>
        </div>

        <div class="events-grid" id="profileEvents">
            <?php if (empty($events)): ?>
                <div class="no-events">Подій поки немає</div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <?php
                    $showEditDelete = false;
                    $hideCreator = true;
                    $event['formatted_date'] = formatEventDate($event['event_date'] ?? null);
                    $event['formatted_time'] = formatEventTime($event['event_time'] ?? null);
                    $event['short_description'] = shortDescription((string)($event['description'] ?? ''), 100);
                    include __DIR__ . '/components/event_card.php';
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include 'components/event_modal.php';
include 'includes/footer.php';
?>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
<script src="assets/js/main.js?v=<?= filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
<script src="assets/js/modal.js?v=<?= filemtime(__DIR__ . '/assets/js/modal.js') ?>"></script>
<script>
    window.isLoggedIn = <?= isset($_SESSION['user']) ? 'true' : 'false' ?>;
</script>
</body>
</html>
