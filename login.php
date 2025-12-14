<?php
// login.php - Защищенная версия
session_start();

// Подключаем конфигурацию
require_once 'config/db.php';
require_once 'includes/security.php';
require_once 'includes/i18n.php';

$error = '';
$info = '';

// Инициализация системы безопасности
$security = new Security($conn);

// Получаем IP клиента
$client_ip = $security->getClientIp();

// Проверка на подозрительную активность
if ($security->detectSuspiciousActivity()) {
    http_response_code(403);
    die(t('access_denied'));
}

// Проверка IP на блокировку
if ($security->isIpBlocked($client_ip)) {
    $error = t('ip_blocked_msg');
} else {
    // Генерация CSRF токена
    $csrf_token = $security->generateCsrfToken();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Проверка CSRF токена
        if (!isset($_POST['csrf_token']) || !$security->verifyCsrfToken($_POST['csrf_token'])) {
            $security->logSecurityEvent('csrf_attack', $client_ip, null, t('security_csrf'), 'high');
            $error = t('invalid_csrf_token');
        }
        // Проверка honeypot (ловушка для ботов)
        elseif (!empty($_POST['email'])) {
            $security->logSecurityEvent('bot_detected', $client_ip, null, t('security_honeypot'), 'medium');
            sleep(3); // Задержка для ботов
            $error = t('invalid_credentials');
        }
        // Проверка лимита попыток
        elseif (!$security->checkLoginAttempts($client_ip)) {
            $error = t('too_many_attempts');
        }
        else {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            
            if (empty($username) || empty($password)) {
                $error = t('login_input_prompt');
            } else {
                try {
                    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($password, $user['password'])) {
                        // Успешный вход
                        $security->logLoginAttempt($client_ip, $username, true);
                        $security->logSecurityEvent('login_success', $client_ip, $username, 'Login success', 'low');
                        
                        // Регенерация сессии для защиты от фиксации
                        session_regenerate_id(true);
                        
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['login_time'] = time();
                        $_SESSION['session_id'] = session_id();
                        $_SESSION['session_ip'] = $client_ip;
                        $_SESSION['session_ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                        $_SESSION['session_created'] = time();
                        
                        header('Location: index.php');
                        exit();
                    } else {
                        // Неудачный вход
                        $security->logLoginAttempt($client_ip, $username, false);
                        $error = t('login_invalid_credentials');
                        
                        // Задержка для защиты от brute-force
                        sleep(2);
                    }
                } catch(PDOException $e) {
                    $security->logSecurityEvent('database_error', $client_ip, null, $e->getMessage(), 'high');
                    $error = t('server_error_try_later');
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars(isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('login_page_title')) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            width: 100%;
            max-width: 420px;
            backdrop-filter: blur(10px);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .logo i {
            font-size: 40px;
            color: white;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
            font-size: 16px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 15px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            border-radius: 10px;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border-left: 4px solid #28a745;
            font-size: 14px;
        }
        
        .info-box h6 {
            color: #28a745;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
        }
        
        .password-container {
            position: relative;
        }
        
        .copyright {
            text-align: center;
            margin-top: 30px;
            color: rgba(255,255,255,0.8);
            font-size: 14px;
        }
        
        .copyright a {
            color: white;
            text-decoration: none;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }
        
        .forgot-password {
            text-align: right;
            margin-top: 10px;
        }
        
        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .lang-switcher {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }
        
        .lang-btn {
            text-decoration: none;
            color: #6c757d;
            font-weight: 600;
            font-size: 14px;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        
        .lang-btn:hover {
            background-color: #f8f9fa;
            color: #667eea;
        }
        
        .lang-btn.active {
            background-color: #eef2ff;
            color: #667eea;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container" style="position: relative;">
        <div class="lang-switcher">
            <a href="?lang=ru" class="lang-btn <?= (!isset($_SESSION['lang']) || $_SESSION['lang'] === 'ru') ? 'active' : '' ?>">RU</a>
            <a href="?lang=en" class="lang-btn <?= (isset($_SESSION['lang']) && $_SESSION['lang'] === 'en') ? 'active' : '' ?>">EN</a>
        </div>
        <div class="logo-container">
            <div class="logo">
                <i class="fas fa-tv"></i>
            </div>
            <h3>IPTV Dashboard</h3>
            <p class="text-muted"><?= htmlspecialchars(t('login_subtitle')) ?></p>
        </div>
        
        <?php if ($info): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle"></i> <?= htmlspecialchars($info) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="post" id="loginForm">
            <!-- CSRF токен для защиты от атак -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            
            <!-- Honeypot поле - ловушка для ботов (скрыто от пользователей) -->
            <input type="text" name="email" value="" style="position:absolute;left:-9999px;width:1px;height:1px;" tabindex="-1" autocomplete="off" aria-hidden="true">
            
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fas fa-user"></i> <?= htmlspecialchars(t('username')) ?>
                </label>
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="<?= htmlspecialchars(t('enter_username')) ?>" required autofocus autocomplete="username">
            </div>
            
            <div class="mb-3 password-container">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> <?= htmlspecialchars(t('password')) ?>
                </label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="<?= htmlspecialchars(t('enter_password')) ?>" required autocomplete="current-password">
                <button type="button" class="password-toggle" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <div class="forgot-password">
                <a href="#" id="forgotPasswordLink"><?= htmlspecialchars(t('forgot_password')) ?></a>
            </div>
            
            <button type="submit" class="btn btn-login" id="loginButton">
                <i class="fas fa-sign-in-alt"></i> <?= htmlspecialchars(t('login_submit')) ?>
            </button>
            
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                <label class="form-check-label" for="rememberMe">
                    <?= htmlspecialchars(t('remember_me')) ?>
                </label>
            </div>
        </form>
        
        <?php if ($info): ?>

        <?php endif; ?>
    </div>
    
 

    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js'></script>
    <script>
        // Переключение видимости пароля
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
        
        // Забыли пароль
        document.getElementById('forgotPasswordLink').addEventListener('click', function(e) {
            e.preventDefault();
            alert('<?= addslashes(t('forgot_password_msg')) ?>');
        });
        
        // Обработка отправки формы
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginButton = document.getElementById('loginButton');
            const originalText = loginButton.innerHTML;
            
            // Показываем индикатор загрузки
            loginButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> <?= htmlspecialchars(t('login_loading')) ?>';
            loginButton.disabled = true;
            
            // Через 5 секунд восстанавливаем кнопку (на случай если что-то пошло не так)
            setTimeout(() => {
                loginButton.innerHTML = originalText;
                loginButton.disabled = false;
            }, 5000);
        });
        
        // Автофокус на поле пароля если имя пользователя заполнено
        document.getElementById('username').addEventListener('blur', function() {
            if (this.value.trim()) {
                document.getElementById('password').focus();
            }
        });
        
        // Сохранение логина в localStorage если выбрано "Запомнить меня"
        document.getElementById('loginForm').addEventListener('submit', function() {
            const rememberMe = document.getElementById('rememberMe').checked;
            const username = document.getElementById('username').value;
            
            if (rememberMe && username) {
                localStorage.setItem('remembered_username', username);
            } else {
                localStorage.removeItem('remembered_username');
            }
        });
        
        // Восстановление сохраненного логина при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            const rememberedUsername = localStorage.getItem('remembered_username');
            if (rememberedUsername) {
                document.getElementById('username').value = rememberedUsername;
                document.getElementById('rememberMe').checked = true;
                document.getElementById('password').focus();
            }
        });
        
        // Очистка формы по Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('username').value = '';
                document.getElementById('password').value = '';
                document.getElementById('username').focus();
            }
        });
    </script>
</body>
</html>
