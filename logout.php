<?php
session_start();

// Уничтожаем все данные сессии
$_SESSION = array();

// Уничтожаем сессионную cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Уничтожаем сессию
session_destroy();

// Перенаправляем на страницу входа
header('Location: index.php');
exit();
?> 