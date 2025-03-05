<?php
session_start();
require_once 'config/db.php';

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Включаем отображение ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <!-- Font Awesome Kit (both CSS and JS) -->
    <script src="https://kit.fontawesome.com/7623f015c6.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Accordion Styles */
        .sidebar .accordion {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar .accordion-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .accordion-header {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            cursor: pointer;
            color: #0073AA;
            transition: background-color 0.3s;
            background-color: transparent;
        }

        .sidebar .accordion-header:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .accordion-header.active {
            background-color: transparent;
        }

        .sidebar .accordion-header i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .sidebar .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background-color: rgba(0, 0, 0, 0.1);
        }

        .sidebar .accordion-content.active {
            max-height: 200px;
        }

        .sidebar .accordion-content a {
            display: block;
            padding: 10px 15px 10px 45px;
            color: #0073AA;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .sidebar .accordion-content a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .arrow {
            margin-left: auto;
            transition: transform 0.3s;
        }

        .sidebar .accordion-header.active .arrow {
            transform: rotate(180deg);
        }
    </style>
    <script src="js/admin.js"></script>
</head>
<body>
    <div class="admin-header">
        <div class="admin-logo">
            <img src="images/log.png" alt="Логотип админ-панели" class="admin-logo-img">
        </div>
        <div class="header-right">
            <a href="logout.php" class="admin-btn">Выйти</a>
        </div>
    </div>

    <div class="admin-content">
        <div class="sidebar">
            <ul>
                <li><a href="?page=dashboard"><i class="fas fa-home"></i> Главная</a></li>
                <li><a href="?page=clients"><i class="fas fa-users"></i> Клиенты</a></li>
                <li><a href="?page=invoices"><i class="fas fa-file-invoice"></i> Счета</a></li>
                <li><a href="?page=bank"><i class="fas fa-university"></i> Банк</a></li>
                <li><a href="?page=hosting"><i class="fas fa-server"></i> Хостинг</a></li>
                <li class="accordion">
                    <div class="accordion-header">
                        <i class="fa-solid fa-tv"></i>
                        <span>TV</span>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    </div>
                    <div class="accordion-content">
                        <?php
                        // Получаем количество клиентов с истекающей подпиской
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM tv_clients WHERE DATE_ADD(subscription_date, INTERVAL months MONTH) <= NOW() + INTERVAL 1 MONTH");
                        $stmt->execute();
                        $clients_with_expiring_subscriptions = $stmt->fetchColumn();
                        ?>
                        <a href="?page=tv_clients" style="position: relative;">
                            <i class="fas fa-user-friends"></i> Клиенты
                            <?php if ($clients_with_expiring_subscriptions > 0): ?>
                                <span class="notification-badge"><?php echo $clients_with_expiring_subscriptions; ?></span>
                            <?php endif; ?>
                        </a>
                        <style>
                            .notification-badge {
                                background-color: red;
                                color: white;
                                border-radius: 50%;
                                padding: 2px 6px;
                                font-size: 12px;
                                position: relative; /* Изменено с absolute на relative */
                                margin-left: 2px; /* Добавлено для отступа от текста */
                            }
                        </style>
                        <a href="?page=tv_providers"><i class="fas fa-broadcast-tower"></i> Провайдеры</a>
                    </div>
                </li>
            </ul>
        </div>

        <div class="main-area">
            <?php
            // Подгружаем контент в зависимости от выбранной страницы
            $page = $_GET['page'] ?? 'dashboard'; // По умолчанию показываем dashboard
            $allowed_pages = ['dashboard', 'clients', 'invoices', 'bank', 'hosting', 'edit_client', 'client_invoices', 'tv_clients', 'tv_providers'];

            if (in_array($page, $allowed_pages)) {
                include "pages/$page.php";
            } else {
                echo "<h1>Страница не найдена</h1>";
            }
            ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const accordionHeaders = document.querySelectorAll('.accordion-header');
            
            accordionHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    // Toggle active class on header
                    this.classList.toggle('active');
                    
                    // Toggle content visibility
                    const content = this.nextElementSibling;
                    content.classList.toggle('active');
                });
            });
        });
    </script>
</body>
</html>