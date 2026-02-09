<?php
$showEditDelete = $showEditDelete ?? false;
$hideCreator = $hideCreator ?? false;
$descriptionText = trim((string)($event['description'] ?? ''));
$descriptionPreview = $event['short_description'] ?? $event['description_short'] ?? null;
$moderationStatus = $event['moderation_status'] ?? 'published';
$statusLabels = [
    'draft' => 'Чернетка',
    'pending' => 'На модерації',
    'published' => 'Опубліковано',
    'rejected' => 'Відхилено',
];
$statusLabel = $statusLabels[$moderationStatus] ?? 'Невідомо';

if ($descriptionPreview === null && $descriptionText !== '') {
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        $descriptionPreview = mb_strlen($descriptionText) > 100
            ? mb_substr($descriptionText, 0, 100) . '...'
            : $descriptionText;
    } else {
        $descriptionPreview = strlen($descriptionText) > 100
            ? substr($descriptionText, 0, 100) . '...'
            : $descriptionText;
    }
}
?>
<div class="event-card"
     data-id="<?= $event['id'] ?>"
     data-title="<?= htmlspecialchars($event['title']) ?>"
     data-category="<?= htmlspecialchars($event['category'] ?? '') ?>"
     data-location="<?= htmlspecialchars($event['location'] ?? '') ?>"
     data-date="<?= htmlspecialchars($event['event_date'] ?? '') ?>"
     data-time="<?= htmlspecialchars($event['event_time'] ?? '') ?>"
     data-description="<?= htmlspecialchars($event['description'] ?? '') ?>"
     data-image="<?= htmlspecialchars($event['image'] ?? 'assets/img/default-event.jpg') ?>"
     data-creator-id="<?= (int)($event['user_id'] ?? 0) ?>"
        <?php if (!($hideCreator ?? false)): ?>
            data-creator="<?= htmlspecialchars($event['username']) ?>"
            data-avatar="<?= htmlspecialchars($event['avatar'] ?? 'assets/img/default-avatar.png') ?>"
        <?php endif; ?>
>
    <div class="event-flip">

        <!-- FRONT (твій існуючий код 1 в 1) -->
        <div class="event-front">

<div class="event-image">
    <img src="<?= htmlspecialchars($event['image'] ?? 'assets/img/default-event.jpg') ?>"
             alt="<?= htmlspecialchars($event['title']) ?>"
             onerror="this.src='assets/img/default-event.jpg'">
    </div>

    <div class="event-info">
        <h3><?= htmlspecialchars($event['title']) ?></h3>

        <?php if ($moderationStatus !== 'published'): ?>
            <p class="event-moderation-status status-<?= htmlspecialchars($moderationStatus) ?>">
                <?= htmlspecialchars($statusLabel) ?>
            </p>
            <?php if ($moderationStatus === 'rejected' && !empty($event['rejection_reason'])): ?>
                <p class="event-rejection-reason">Причина: <?= htmlspecialchars($event['rejection_reason']) ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($event['category'])): ?>
            <p class="event-category">Категорія: <?= htmlspecialchars($event['category']) ?></p>
        <?php endif; ?>

        <?php if (!empty($event['location'])): ?>
            <p class="event-location">📍 <?= htmlspecialchars($event['location']) ?></p>
        <?php endif; ?>

        <p class="event-date">
            📅 <?= htmlspecialchars($event['formatted_date'] ?? $event['event_date']) ?>
            <?php if (!empty($event['event_time'])): ?>
                🕒 <?= htmlspecialchars($event['formatted_time'] ?? $event['event_time']) ?>
            <?php endif; ?>
        </p>

        <?php if (!empty($descriptionPreview)): ?>
            <p class="event-description">
                <?= htmlspecialchars($descriptionPreview) ?>
            </p>
        <?php endif; ?>
    </div>
        <div class="event-profile-actions">
            <button class="btn-view" data-event-id="<?= $event['id'] ?>">
                Детальніше
            </button>

            <?php if ($showEditDelete ?? false): ?>
                <a href="<?= BASE_URL ?>/event_form.php?id=<?= $event['id'] ?>"
                   class="btn-edit"
                   onclick="event.stopPropagation()">
                    Редагувати
                </a>

                <button class="btn-delete"
                        data-event-id="<?= $event['id'] ?>"
                        data-event-title="<?= htmlspecialchars($event['title']) ?>"
                        onclick="event.stopPropagation()">
                    Видалити
                </button>
            <?php endif; ?>

        </div>
        </div>

        <!-- BACK -->
        <div class="event-back">
            <p><?= htmlspecialchars($event['title']) ?></p>
            <div class="event-qr"
                 id="qr-<?= $event['id'] ?>">
            </div>
        </div>
    </div>
</div>
