<div class="event-card"
     data-id="<?= $event['id'] ?>"
     data-title="<?= htmlspecialchars($event['title']) ?>"
     data-category="<?= htmlspecialchars($event['category'] ?? '') ?>"
     data-location="<?= htmlspecialchars($event['location'] ?? '') ?>"
     data-date="<?= htmlspecialchars($event['event_date'] ?? '') ?>"
     data-time="<?= htmlspecialchars($event['event_time'] ?? '') ?>"
     data-description="<?= htmlspecialchars($event['description'] ?? '') ?>"
     data-image="<?= htmlspecialchars($event['image'] ?? 'assets/img/default-event.jpg') ?>"
        <?php if (!($hideCreator ?? false)): ?>
            data-creator="<?= htmlspecialchars($event['username']) ?>"
            data-avatar="<?= htmlspecialchars($event['avatar'] ?? 'assets/img/default-avatar.png') ?>"
        <?php endif; ?>
>


<div class="event-image">
    <img src="<?= htmlspecialchars($event['image'] ?? 'assets/img/default-event.jpg') ?>"
             alt="<?= htmlspecialchars($event['title']) ?>"
             onerror="this.src='assets/img/default-event.jpg'">
    </div>

    <div class="event-content">
        <h3><?= htmlspecialchars($event['title']) ?></h3>

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

        <?php if (!empty($event['description'])): ?>
            <p class="event-description">
                <?= htmlspecialchars($event['short_description'] ??
                        (mb_strlen($event['description']) > 100
                                ? mb_substr($event['description'], 0, 100) . '...'
                                : $event['description'])) ?>
            </p>
        <?php endif; ?>

        <div class="event-profile-actions">
            <button class="btn-view" data-event-id="<?= $event['id'] ?>">
                👁️ Детальніше
            </button>

            <?php if ($showEditDelete ?? false): ?>
                <a href="/event_form.php?id=<?= $event['id'] ?>"
                   class="btn-edit"
                   onclick="event.stopPropagation()">
                    ✏️ Редагувати
                </a>

                <button class="btn-delete"
                        data-event-id="<?= $event['id'] ?>"
                        data-event-title="<?= htmlspecialchars($event['title']) ?>">
                    🗑️ Видалити
                </button>
            <?php endif; ?>

        </div>
    </div>
</div>