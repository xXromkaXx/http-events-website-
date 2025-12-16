<div class="avatar-editor">
    <label class="avatar-box">
        <?php if (!empty($currentAvatar)): ?>
            <img id="avatarPreview" src="<?= htmlspecialchars($currentAvatar) ?>">
        <?php else: ?>
            <span><?= strtoupper($username[0]) ?></span>
        <?php endif; ?>

        <div class="avatar-overlay">Змінити</div>
        <input type="file" id="avatarInput" accept="image/*" hidden>
    </label>



</div>
