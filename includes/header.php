<?php
// Этот файл содержит общий заголовок для всех страниц

$is_in_pages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$base_path = $is_in_pages ? '../' : '';
$pages_path = $is_in_pages ? '' : 'pages/';

require_once $base_path . 'includes/i18n.php';

// Ensure we have a valid language code for the HTML tag
$lang_code = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ru';

// Определяем активную страницу
$current_page = basename($_SERVER['PHP_SELF']);
$is_active = function($page) use ($current_page) {
    return $current_page === $page ? 'active' : '';
};
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang_code) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPTV Dashboard</title>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css'>
    <link rel='stylesheet' href='https://unicons.iconscout.com/release/v3.0.6/css/line.css'>
    <link rel="stylesheet" href="<?= $base_path ?>css/style.css">
</head>
<body>

<!-- Боковая панель -->
<aside class="sidebar position-fixed top-0 left-0 overflow-auto h-100 float-left" id="show-side-navigation1">
    <i class="uil-bars close-aside d-md-none d-lg-none" data-close="show-side-navigation1"></i>
    <div class="sidebar-header d-flex justify-content-center align-items-center px-3 py-4">
        <img class="rounded-pill img-fluid" width="65" src="https://standigital.lv/favicon/favicon.png" alt="">
        <div class="ms-2">
            <h5 class="fs-6 mb-0">
                <a class="text-decoration-none" href="<?= $base_path ?>index.php"><?php echo htmlspecialchars(isset($_SESSION['username']) ? $_SESSION['username'] : t('dashboard_brand')); ?></a>
            </h5>
            <p class="mt-1 mb-0"><?= htmlspecialchars(t('subtitle')) ?></p>
        </div>
    </div>

    <div class="search position-relative text-center px-4 py-3 mt-2">
        <input type="text" id="global-search" class="form-control w-100 border-0 bg-transparent" placeholder="<?= htmlspecialchars(t('search_clients_placeholder')) ?>">
        <i class="uil uil-search position-absolute d-block fs-6"></i>
    </div>

    <ul class="categories list-unstyled">
        <li class="<?= $is_active('index.php') ?>">
            <i class="uil-estate fa-fw"></i>
            <a href="<?= $base_path ?>index.php" class="text-decoration-none"><?= htmlspecialchars(t('home')) ?></a>
        </li>
        <li class="<?= $is_active('add_client.php') ?>">
            <i class="uil-user-plus"></i>
            <a href="<?= $pages_path ?>add_client.php" class="text-decoration-none"><?= htmlspecialchars(t('add_client')) ?></a>
        </li>
        <li class="<?= $is_active('tv_clients.php') ?>">
            <i class="uil-tv-retro"></i>
            <a href="<?= $pages_path ?>tv_clients.php" class="text-decoration-none"><?= htmlspecialchars(t('clients')) ?></a>
        </li>
        <li class="<?= $is_active('tv_providers.php') ?>">
            <i class="uil-briefcase-alt"></i>
            <a href="<?= $pages_path ?>tv_providers.php" class="text-decoration-none"><?= htmlspecialchars(t('providers')) ?></a>
        </li>
        <li class="<?= $is_active('map.php') ?>">
            <i class="uil-map-marker"></i>
            <a href="<?= $pages_path ?>map.php" class="text-decoration-none"><?= htmlspecialchars(t('map')) ?></a>
        </li>
        <li class="<?= $is_active('security_logs.php') ?>">
            <i class="uil-shield-check"></i>
            <a href="<?= $pages_path ?>security_logs.php" class="text-decoration-none"><?= htmlspecialchars(t('security_logs')) ?></a>
        </li>
    </ul>
</aside>

<!-- Навигационная панель -->
<nav class="navbar navbar-expand-md">
    <div class="container-fluid mx-2">
        <div class="navbar-header">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#toggle-navbar" aria-controls="toggle-navbar" aria-expanded="false" aria-label="Toggle navigation">
                <i class="uil-bars text-white"></i>
            </button>
            <a class="navbar-brand" href="<?= $base_path ?>index.php"><?= htmlspecialchars(t('dashboard_brand')) ?></a>
        </div>
        <div class="collapse navbar-collapse" id="toggle-navbar">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-decoration-none" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo htmlspecialchars(isset($_SESSION['username']) ? $_SESSION['username'] : t('settings')); ?>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item text-decoration-none" href="<?= $pages_path ?>account.php"><?= htmlspecialchars(t('account')) ?></a></li>
                        <li><a class="dropdown-item text-decoration-none" href="<?= $pages_path ?>sms_settings.php"><?= htmlspecialchars(t('sms_settings')) ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-decoration-none" href="<?= $base_path ?>logout.php"><?= htmlspecialchars(t('logout')) ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="px-3"><?= htmlspecialchars(t('language') ?? 'Язык') ?>:</li>
                        <?php
                        $current_url = $_SERVER['REQUEST_URI'];
                        $separator = strpos($current_url, '?') !== false ? '&' : '?';
                        $base_url = strtok($current_url, '?');
                        ?>
                        <li><a class="dropdown-item <?= $lang_code === 'ru' ? 'active' : '' ?>" href="<?= $base_url ?>?lang=ru">RU - Русский</a></li>
                        <li><a class="dropdown-item <?= $lang_code === 'lv' ? 'active' : '' ?>" href="<?= $base_url ?>?lang=lv">LV - Latviešu</a></li>
                        <li><a class="dropdown-item <?= $lang_code === 'en' ? 'active' : '' ?>" href="<?= $base_url ?>?lang=en">EN - English</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <?php
                    // Получаем количество подписок, которые заканчиваются
                    try {
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM tv_clients WHERE DATE_ADD(subscription_date, INTERVAL months MONTH) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 MONTH)");
                        $stmt->execute();
                        $expiring_soon = $stmt->fetchColumn();
                    } catch(PDOException $e) {
                        $expiring_soon = 0;
                    }
                    ?>
                    <a class="nav-link text-decoration-none" href="<?= $pages_path ?>tv_clients.php" style="position: relative;">
                        <i class="uil-user-exclamation"></i>
                        <?php if ($expiring_soon > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?= $expiring_soon ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-decoration-none" href="#">
                        <i data-show="show-side-navigation1" class="uil-bars show-side-btn"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Основной контент -->
<section id="wrapper">

<!-- Плавающая кнопка меню для мобильных устройств -->
<button class="mobile-menu-btn d-md-none" id="mobile-menu-toggle" aria-label="Открыть меню">
    <i class="uil-bars"></i>
</button>

<script>
// Обработчик для плавающей кнопки меню на мобильных
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('show-side-navigation1');
    
    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show-sidebar');
        });
    }
});
</script>
