<?php
session_start();

// Включаем отображение ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';

// Проверяем наличие пользователей
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetchColumn();
    $users_exist = ($count > 0);
} catch(PDOException $e) {
    if ($e->getCode() == '42S02') {
        // Создаем таблицу если её нет
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->exec($sql);
        $users_exist = false;
    } else {
        die("Ошибка базы данных: " . $e->getMessage());
    }
}

// Обработка форм
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = "Пожалуйста, заполните все поля";
    } else {
        try {
            if (!$users_exist) {
                // Регистрация первого администратора
                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                if ($stmt->execute([$username, $password])) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['username'] = $username;
                    header("Location: admin.php");
                    exit();
                } else {
                    $error = "Ошибка при регистрации";
                }
            } else {
                // Вход существующего пользователя
                $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
                $stmt->execute([$username, $password]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['username'] = $username;
                    header("Location: admin.php");
                    exit();
                } else {
                    $error = "Неверный логин или пароль";
                }
            }
        } catch(PDOException $e) {
            $error = "Ошибка: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $users_exist ? 'Вход в систему' : 'Регистрация администратора'; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="site-logo">
            <img src="images/log.png" alt="Логотип сайта" class="site-logo-img">
        </div>
        <form class="login-form" method="post" action="">
            <h1 class="text-center">
                <?php echo $users_exist ? 'Панель управления' : 'Регистрация администратора'; ?>
            </h1>
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="form-group">
                <input type="text" name="username" 
                       placeholder="<?php echo $users_exist ? 'Логин' : 'Создайте логин'; ?>" 
                       required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            <div class="form-group">
                <input type="password" name="password" 
                       placeholder="<?php echo $users_exist ? 'Пароль' : 'Создайте пароль'; ?>" 
                       required>
            </div>
            <div class="form-group">
                <button type="submit">
                    <?php echo $users_exist ? 'Войти' : 'Зарегистрироваться'; ?>
                </button>
            </div>
        </form>
    </div>
</body>
</html> 