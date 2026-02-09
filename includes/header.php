<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="navbar">
    <div class="logo-container">
        <a href="<?= BASE_URL ?>/index.php" class="logo">
            <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Events YC logo">
        </a>
        <a href="#about">Про нас</a>
        <a href="#contact">Контакти</a>
    </div>
    <nav class="nav-links">
        <?php if (isset($_SESSION['user'])): ?>

            <a href="<?= BASE_URL ?>/index.php#eventsSection" class="active">Події</a>
            <a href="<?= BASE_URL ?>/event_form.php" class="create">Створити</a>
            <a href="<?= BASE_URL ?>/my_events.php?profile=1" class="my-events">Профіль</a>
            <a href="<?= BASE_URL ?>/logout.php" class="logout">Вийти</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php" class="btn-login">Увійти</a>
            <a href="<?= BASE_URL ?>/register.php" class="btn-register">Зареєструватися</a>
        <?php endif; ?>
    </nav>

</header>

<?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
    <a href="<?= BASE_URL ?>/admin_panel.php" class="admin-fab" title="Адмін панель">Адмін панель</a>
<?php endif; ?>

<script>
    window.BASE_URL = <?= json_encode(BASE_URL) ?>;
</script>


<script>

</script>
