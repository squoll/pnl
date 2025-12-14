<?php
// includes/auth.php - Защищенная версия
session_start();

// Время жизни сессии (30 минут)
$session_timeout = 1800;

// Подключаем конфигурацию базы данных (если еще не подключена)
if (!isset($conn)) {
    require_once __DIR__ . '/../config/db.php';
}

// Объявление функций ДО их использования
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function logout() {
    if (isset($_SESSION['user_id'])) {
        global $conn;
        
        // Логируем выход
        try {
            // Используем правильную структуру таблицы
            if (class_exists('Security')) {
                global $security;
                $security->logSecurityEvent('logout', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 
                    $_SESSION['username'] ?? null, 'Пользователь вышел из системы', 'low');
            }
        } catch(Exception $e) {
            error_log("Error logging logout: " . $e->getMessage());
        }
    }
    
    // Очищаем сессию
    $_SESSION = array();
    
    // Удаляем куки сессии
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Уничтожаем сессию
    session_destroy();
    
    // Редирект на страницу входа
    $redirect = strpos($_SERVER['PHP_SELF'], '/pages/') !== false ? '../login.php' : 'login.php';
    header('Location: ' . $redirect);
    exit();
}

// Подключаем систему безопасности
require_once __DIR__ . '/security.php';
$security = new Security($conn);

// Проверка безопасности сессии
if (isLoggedIn()) {
    if (!$security->secureSession()) {
        logout();
        exit();
    }
}

// Проверяем таймаут сессии
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
    session_unset();
    session_destroy();
    $redirect = strpos($_SERVER['PHP_SELF'], '/pages/') !== false ? '../login.php?timeout=1' : 'login.php?timeout=1';
    header('Location: ' . $redirect);
    exit();
}

// Обновляем время активности
$_SESSION['last_activity'] = time();

// Получение информации о текущем пользователе
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Error getting user: " . $e->getMessage());
        return null;
    }
}

// Проверка прав пользователя (можно расширить для ролей)
function hasPermission($permission) {
    $user = getCurrentUser();
    
    if (!$user) {
        return false;
    }
    
    // Здесь можно добавить проверку ролей/прав
    // Пока что все залогиненные пользователи имеют полные права
    return true;
}

// Проверка необходимости смены пароля (например, если не менялся 90 дней)
function passwordChangeRequired() {
    $user = getCurrentUser();
    
    if (!$user || !isset($user['password_changed_at'])) {
        return false;
    }
    
    $password_age = time() - strtotime($user['password_changed_at']);
    $max_age = 90 * 24 * 60 * 60; // 90 дней в секундах
    
    return $password_age > $max_age;
}

// Автоматический выход при неактивности
if (isLoggedIn() && isset($_SESSION['last_activity'])) {
    $inactive = time() - $_SESSION['last_activity'];
    if ($inactive > $session_timeout) {
        logout();
    }
}