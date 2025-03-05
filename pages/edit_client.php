<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
    require_once BASE_PATH . '/config/db.php';
    session_start();
}

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit();
}

// Включаем отображение ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Получаем клиента для редактирования
$client_id = $_GET['id'] ?? null;
if (!$client_id) {
    header('Location: admin.php?page=clients');
    exit();
}

$client = null;
try {
    $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Ошибка при получении клиента: " . $e->getMessage();
}

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_client'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $company_number = trim($_POST['company_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($first_name) || empty($last_name)) {
        $error = "Имя и фамилия обязательны для заполнения.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE clients SET first_name = ?, last_name = ?, company = ?, company_number = ?, phone = ?, email = ?, address = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $company, $company_number, $phone, $email, $address, $client_id]);
            $success_message = "Клиент успешно обновлен!";
            // Перенаправляем обратно на страницу клиентов
            header('Location: admin.php?page=clients');
            exit();
        } catch(PDOException $e) {
            $error_message = "Ошибка при обновлении клиента: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Редактировать клиента</title>
    <style>
        /* Используйте те же стили, что и в clients.php */
    </style>
</head>
<body>
    <div class="clients-container">
        <div class="clients-header">
            <h1>Редактировать клиента</h1>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($client): ?>
        <div class="clients-form">
            <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                <div class="form-group">
                    <label for="first_name">Имя</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($client['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Фамилия</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($client['last_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="company">Компания</label>
                    <input type="text" id="company" name="company" value="<?php echo htmlspecialchars($client['company']); ?>">
                </div>
                <div class="form-group">
                    <label for="company_number">Номер компании</label>
                    <input type="text" id="company_number" name="company_number" value="<?php echo htmlspecialchars($client['company_number']); ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($client['phone']); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>">
                </div>
                <div class="form-group">
                    <label for="address">Адрес компании</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($client['address']); ?>">
                </div>
                <div class="form-group">
                    <button type="submit" name="update_client">Обновить клиента</button>
                </div>
            </form>
        </div>
        <?php else: ?>
            <p>Клиент не найден.</p>
        <?php endif; ?>
    </div>
</body>
</html>