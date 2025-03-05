<?php
session_start();

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Если не авторизован, перенаправляем на страницу входа
    header('Location: index.php');
    exit();
}
?> 