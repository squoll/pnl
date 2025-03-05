<?php
session_start();

// Включаем отображение ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';

// Проверяем подключение к БД
if (!$conn) {
    die("Ошибка подключения к базе данных: " . mysqli_connect_error());
}

// Проверяем наличие пользователей в таблице с отладкой
$check_users = $conn->query("SELECT COUNT(*) as count FROM users");
if (!$check_users) {
    die("Ошибка запроса проверки пользователей: " . $conn->error);
}

$users_exist = ($check_users && $check_users->fetch_assoc()['count'] > 0);

// Добавим отладочную информацию
echo "<!-- Отладка: Пользователи существуют: " . ($users_exist ? 'да' : 'нет') . " -->";

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$users_exist) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = "Пожалуйста, заполните все поля";
    } else {
        $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            die("Ошибка подготовки запроса: " . $conn->error);
        }
        
        $stmt->bind_param("ss", $username, $password);
        
        if ($stmt->execute()) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: admin.php");
            exit();
        } else {
            $error = "Ошибка при регистрации: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $users_exist) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = "Пожалуйста, заполните все поля";
    } else {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            die("Ошибка подготовки запроса: " . $conn->error);
        }
        
        $stmt->bind_param("s", $username);
        
        if (!$stmt->execute()) {
            die("Ошибка выполнения запроса: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($password === $user['password']) {
                $_SESSION['admin_logged_in'] = true;
                header("Location: admin.php");
                exit();
            } else {
                $error = "Неверный пароль";
            }
        } else {
            $error = "Пользователь с таким логином не найден";
        }
        $stmt->close();
    }
}

// Если уже авторизован, перенаправляем в админку
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin.php");
    exit();
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
                       required value="<?php echo htmlspecialchars($username ?? ''); ?>">
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