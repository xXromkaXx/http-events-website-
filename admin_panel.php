<?php
require_once 'init.php';
require_once 'helpers.php';

if (!isset($_SESSION['user']['id']) || (($_SESSION['user']['role'] ?? '') !== 'admin')) {
    header('Location: index.php');
    exit;
}

$flash = null;
$action = $_POST['action'] ?? null;
$commentUserId = (int)($_GET['comment_user_id'] ?? $_POST['comment_user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    try {
        switch ($action) {
            case 'event_publish':
                $eventId = (int)($_POST['event_id'] ?? 0);
                if ($eventId > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE events
                        SET moderation_status='published',
                            rejection_reason=NULL,
                            moderated_by=?,
                            moderated_at=NOW()
                        WHERE id=?
                    ");
                    $stmt->execute([$_SESSION['user']['id'], $eventId]);
                    $flash = ['type' => 'success', 'text' => 'Подію опубліковано'];
                }
                break;

            case 'event_reject':
                $eventId = (int)($_POST['event_id'] ?? 0);
                $reason = trim($_POST['reason'] ?? '');
                if ($eventId > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE events
                        SET moderation_status='rejected',
                            rejection_reason=?,
                            moderated_by=?,
                            moderated_at=NOW()
                        WHERE id=?
                    ");
                    $stmt->execute([$reason ?: 'Без уточнення', $_SESSION['user']['id'], $eventId]);
                    $flash = ['type' => 'success', 'text' => 'Подію відхилено'];
                }
                break;

            case 'event_to_pending':
                $eventId = (int)($_POST['event_id'] ?? 0);
                if ($eventId > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE events
                        SET moderation_status='pending',
                            rejection_reason=NULL,
                            moderated_by=?,
                            moderated_at=NOW()
                        WHERE id=?
                    ");
                    $stmt->execute([$_SESSION['user']['id'], $eventId]);
                    $flash = ['type' => 'success', 'text' => 'Подію повернуто на модерацію'];
                }
                break;

            case 'organizer_approve':
                $userId = (int)($_POST['user_id'] ?? 0);
                if ($userId > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET role='organizer', organizer_status='approved'
                        WHERE id=?
                    ");
                    $stmt->execute([$userId]);
                    $flash = ['type' => 'success', 'text' => 'Організатора підтверджено'];
                }
                break;

            case 'organizer_reject':
                $userId = (int)($_POST['user_id'] ?? 0);
                if ($userId > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET role='user', organizer_status='rejected'
                        WHERE id=?
                    ");
                    $stmt->execute([$userId]);
                    $flash = ['type' => 'success', 'text' => 'Заявку організатора відхилено'];
                }
                break;

            case 'comment_delete':
                $commentId = (int)($_POST['comment_id'] ?? 0);
                $commentUserId = (int)($_POST['comment_user_id'] ?? $commentUserId);
                if ($commentId > 0) {
                    $stmt = $pdo->prepare("DELETE FROM comments WHERE id=?");
                    $stmt->execute([$commentId]);
                    $flash = ['type' => 'success', 'text' => 'Коментар видалено'];
                }
                break;
        }
    } catch (Throwable $e) {
        $flash = ['type' => 'error', 'text' => 'Помилка: ' . $e->getMessage()];
    }
}

$pendingEvents = $pdo->query("
    SELECT e.id, e.title, e.event_date, e.location, e.moderation_status, e.rejection_reason, u.username
    FROM events e
    LEFT JOIN users u ON u.id = e.user_id
    WHERE e.moderation_status IN ('pending','rejected','draft')
    ORDER BY FIELD(e.moderation_status,'pending','rejected','draft'), e.id DESC
    LIMIT 200
")->fetchAll(PDO::FETCH_ASSOC);

$pendingOrganizers = $pdo->query("
    SELECT id, username, email, created_at
    FROM users
    WHERE organizer_status = 'pending'
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$commentUsers = $pdo->query("
    SELECT u.id, u.username, COUNT(c.id) AS comments_count
    FROM comments c
    JOIN users u ON u.id = c.user_id
    GROUP BY u.id, u.username
    ORDER BY comments_count DESC, u.id DESC
    LIMIT 200
")->fetchAll(PDO::FETCH_ASSOC);

$selectedUserComments = [];
$selectedUserName = null;
if ($commentUserId > 0) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$commentUserId]);
    $selectedUserName = $stmt->fetchColumn() ?: null;

    $stmt = $pdo->prepare("
        SELECT c.id, c.content, c.created_at, e.title AS event_title
        FROM comments c
        JOIN events e ON e.id = c.event_id
        WHERE c.user_id = ?
        ORDER BY c.id DESC
        LIMIT 120
    ");
    $stmt->execute([$commentUserId]);
    $selectedUserComments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Адмін панель | Events YC</title>
    <link rel="stylesheet" href="assets/css/main.css?v=<?= filemtime(__DIR__ . '/assets/css/main.css') ?>">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= filemtime(__DIR__ . '/assets/css/admin.css') ?>">
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="admin-wrap">
    <h1>Адмін панель</h1>

    <?php if ($flash): ?>
        <div class="admin-flash <?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['text']) ?></div>
    <?php endif; ?>

    <section class="admin-card">
        <h2>Заявки організаторів</h2>
        <?php if (!$pendingOrganizers): ?>
            <p class="admin-empty">Заявок немає</p>
        <?php else: ?>
            <div class="admin-list">
                <?php foreach ($pendingOrganizers as $row): ?>
                    <article class="admin-item">
                        <div>
                            <strong><?= htmlspecialchars($row['username']) ?></strong>
                            <div><?= htmlspecialchars($row['email']) ?></div>
                            <small><?= htmlspecialchars($row['created_at']) ?></small>
                        </div>
                        <div class="admin-actions">
                            <form method="post">
                                <input type="hidden" name="action" value="organizer_approve">
                                <input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" class="ok">Підтвердити</button>
                            </form>
                            <form method="post">
                                <input type="hidden" name="action" value="organizer_reject">
                                <input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" class="bad">Відхилити</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <h2>Модерація подій</h2>
        <?php if (!$pendingEvents): ?>
            <p class="admin-empty">Подій для модерації немає</p>
        <?php else: ?>
            <div class="admin-list">
                <?php foreach ($pendingEvents as $row): ?>
                    <article class="admin-item">
                        <div>
                            <strong><?= htmlspecialchars($row['title']) ?></strong>
                            <div>Автор: <?= htmlspecialchars($row['username'] ?? '—') ?></div>
                            <div>Статус: <b><?= htmlspecialchars($row['moderation_status']) ?></b></div>
                            <small><?= htmlspecialchars(($row['event_date'] ?? '') . ' · ' . ($row['location'] ?? '')) ?></small>
                            <?php if (!empty($row['rejection_reason'])): ?>
                                <div class="reason">Причина: <?= htmlspecialchars($row['rejection_reason']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="admin-actions wide">
                            <form method="post">
                                <input type="hidden" name="action" value="event_publish">
                                <input type="hidden" name="event_id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" class="ok">Опублікувати</button>
                            </form>
                            <form method="post">
                                <input type="hidden" name="action" value="event_to_pending">
                                <input type="hidden" name="event_id" value="<?= (int)$row['id'] ?>">
                                <button type="submit">На модерацію</button>
                            </form>
                            <form method="post" class="reject-form">
                                <input type="hidden" name="action" value="event_reject">
                                <input type="hidden" name="event_id" value="<?= (int)$row['id'] ?>">
                                <input type="text" name="reason" placeholder="Причина відхилення">
                                <button type="submit" class="bad">Відхилити</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card" id="comments">
        <h2>Модерація коментарів</h2>
        <?php if (!$commentUsers): ?>
            <p class="admin-empty">Коментарів поки немає.</p>
        <?php else: ?>
            <p class="admin-empty">Оберіть користувача (#ID + ім'я), щоб переглянути його коментарі:</p>
            <div class="admin-user-grid">
                <?php foreach ($commentUsers as $user): ?>
                    <a
                        href="admin_panel.php?comment_user_id=<?= (int)$user['id'] ?>#comments"
                        class="admin-user-chip <?= $commentUserId === (int)$user['id'] ? 'active' : '' ?>"
                    >
                        <span>#<?= (int)$user['id'] ?></span>
                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                        <small><?= (int)$user['comments_count'] ?> ком.</small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($commentUserId > 0): ?>
            <h3 class="admin-subtitle">
                Коментарі користувача #<?= (int)$commentUserId ?>
                <?php if ($selectedUserName): ?>
                    (<?= htmlspecialchars($selectedUserName) ?>)
                <?php endif; ?>
            </h3>

            <?php if (!$selectedUserComments): ?>
                <p class="admin-empty">У цього користувача немає коментарів.</p>
            <?php else: ?>
                <div class="admin-list">
                    <?php foreach ($selectedUserComments as $row): ?>
                        <article class="admin-item">
                            <div>
                                <strong>Коментар #<?= (int)$row['id'] ?></strong>
                                <div><?= htmlspecialchars($row['event_title']) ?></div>
                                <small><?= htmlspecialchars($row['created_at']) ?></small>
                                <p><?= htmlspecialchars($row['content']) ?></p>
                            </div>
                            <div class="admin-actions">
                                <form method="post">
                                    <input type="hidden" name="action" value="comment_delete">
                                    <input type="hidden" name="comment_id" value="<?= (int)$row['id'] ?>">
                                    <input type="hidden" name="comment_user_id" value="<?= (int)$commentUserId ?>">
                                    <button type="submit" class="bad" onclick="return confirm('Видалити коментар #<?= (int)$row['id'] ?>?');">
                                        Видалити
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
