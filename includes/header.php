<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="navbar">
    <div class="logo-container">
        <a href="/index.php" class="logo">
            <img src="/assets/img/logo.png" alt="Events YC logo">
        </a>
        <a href="#about">Про нас</a>
        <a href="#contact">Контакти</a>
    </div>
    <nav class="nav-links">
        <?php if (isset($_SESSION['user'])): ?>

            <a href="/index.php#eventsSection" class="active">Події</a>
            <a href="/event_form.php" class="create">Створити</a>
            <a href="/my_events.php?profile=1" class="my-events">Профіль</a>
            <a href="/logout.php" class="logout">Вийти</a>
        <?php else: ?>
            <a href="/login.php" class="btn-login">Увійти</a>
            <a href="/register.php" class="btn-register">Зареєструватися</a>
        <?php endif; ?>
    </nav>

</header>



<script>
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 550) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
</script>
