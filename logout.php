<?php
// logout.php
session_start();

// Логируем выход если пользователь был залогинен
if (isset($_SESSION['user_id'])) {
    require_once 'config/db.php';
    
    try {
        $stmt = $conn->prepare("INSERT INTO security_logs (user_id, action, ip_address, user_agent) 
                               VALUES (?, 'logout', ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch(PDOException $e) {
        error_log("Error logging logout: " . $e->getMessage());
    }
}

// Полностью уничтожаем сессию
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Редирект на страницу входа
header('Location: login.php');
exit();
?>