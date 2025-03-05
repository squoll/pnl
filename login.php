<?php
session_start();
require_once 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: admin.php");
        exit();
    }
    $error = "Неверный логин или пароль";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Вход в панель администратора</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-form">
        <h2>Вход в систему</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <div class="login-logo">
            <img src="images/log.png" alt="Логотип сайта" class="login-logo-img">
        </div>
        <form class="login-form" method="post" action="">
            <input type="text" name="username" placeholder="Логин" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
        </form>
    </div>
</body>
</html> 