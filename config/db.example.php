<?php
// config/db.php - шаблон конфигурации

// Учетные данные базы данных
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'your_username';
$password = 'your_password';

// Скрываем подробности ошибок в продакшене
$show_detailed_errors = false; 

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Устанавливаем таймаут для запросов
    $conn->exec("SET SESSION wait_timeout = 600");
    
} catch(PDOException $e) {
    // Безопасное отображение ошибок
    if ($show_detailed_errors) {
        $error_message = "Ошибка подключения к базе данных: " . $e->getMessage();
    } else {
        $error_message = "Ошибка подключения к базе данных. Пожалуйста, обратитесь к администратору.";
    }
    
    // Логируем детальную ошибку
    error_log("[" . date('Y-m-d H:i:s') . "] DB Connection Error: " . $e->getMessage());
    
    die("
    <!DOCTYPE html>
    <html>
    <head>
        <title>Connection Error</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; text-align: center; }
            .error { color: #dc3545; margin: 20px 0; }
            .btn { display: inline-block; padding: 10px 20px; background: #007bff; 
                   color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>⚠️ System Error</h1>
        <div class='error'>$error_message</div>
    </body>
    </html>
    ");
}

// ... (остальные функции остаются такими же, но их можно вынести в отдельный файл includes/db_functions.php, если нужно, но пока оставим здесь для простоты)

// Функции для безопасной работы с базой
function safeQuery($sql, $params = []) {
    global $conn;
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("SQL Error: " . $e->getMessage());
        throw new Exception("Ошибка выполнения запроса");
    }
}

function setupDatabase() {
    global $conn;
    $conn->exec("CREATE TABLE IF NOT EXISTS security_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(100),
        ip_address VARCHAR(45),
        user_agent TEXT,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_action (action)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    try {
        $conn->exec("ALTER TABLE users 
            ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL,
            ADD COLUMN IF NOT EXISTS login_attempts INT DEFAULT 0,
            ADD COLUMN IF NOT EXISTS locked_until TIMESTAMP NULL,
            ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP NULL");
    } catch(PDOException $e) {}
}
setupDatabase();

function logSecurityAction($user_id, $action, $details = '') {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $stmt = $conn->prepare("INSERT INTO security_logs (user_id, action, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $ip, $user_agent, $details]);
}

function logAction($action, $details = '') {
    logSecurityAction(0, $action, $details);
}

function isUserLocked($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT locked_until FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user && $user['locked_until']) {
        if (time() < strtotime($user['locked_until'])) return true;
        $conn->prepare("UPDATE users SET locked_until = NULL, login_attempts = 0 WHERE id = ?")->execute([$user_id]);
    }
    return false;
}

function incrementFailedAttempts($user_id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?");
    $stmt->execute([$user_id]);
    $stmt = $conn->prepare("SELECT login_attempts FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user && $user['login_attempts'] >= 5) {
        $conn->prepare("UPDATE users SET locked_until = ? WHERE id = ?")->execute([date('Y-m-d H:i:s', time() + 900), $user_id]);
    }
}

function resetFailedAttempts($user_id) {
    global $conn;
    $conn->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?")->execute([$user_id]);
}
?>
