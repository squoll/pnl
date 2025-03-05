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

// Обработка формы добавления клиента
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_client'])) {
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
            $stmt = $conn->prepare("INSERT INTO clients (first_name, last_name, company, company_number, phone, email, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $company, $company_number, $phone, $email, $address]);
            $success_message = "Клиент успешно добавлен!";
        } catch(PDOException $e) {
            $error_message = "Ошибка при добавлении клиента: " . $e->getMessage();
        }
    }
}

// Получаем список клиентов
$clients = [];
try {
    $stmt = $conn->query("SELECT * FROM clients");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Ошибка при получении клиентов: " . $e->getMessage();
}

// Проверяем, загружается ли файл напрямую
$is_direct_access = !isset($page);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Клиенты</title>
    <style>
        .clients-container {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        .clients-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .clients-header h1 {
            color: #333;
        }

        .clients-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .clients-table th,
        .clients-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .clients-table th {
            background-color: #007bff;
            color: white;
        }

        .clients-table tr:hover {
            background-color: #f1f1f1;
        }

        .clients-form {
            margin-top: 20px;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .clients-form .form-group {
            margin-bottom: 15px;
        }

        .clients-form label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .clients-form input,
        .clients-form select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .clients-form button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }

        .clients-form button:hover {
            background-color: #0056b3;
        }

        .success-message,
        .error-message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-weight: bold;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="clients-container">
        <div class="clients-header">
            <h1>Клиенты</h1>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="clients-form">
            <h2>Добавить нового клиента</h2>
            <form method="post" action="<?php echo $is_direct_access ? '' : '?page=clients'; ?>">
                <div class="form-group">
                    <label for="first_name">Имя</label>
                    <input type="text" id="first_name" name="first_name" placeholder="Имя" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Фамилия</label>
                    <input type="text" id="last_name" name="last_name" placeholder="Фамилия" required>
                </div>
                <div class="form-group">
                    <label for="company">Компания</label>
                    <input type="text" id="company" name="company" placeholder="Компания">
                </div>
                <div class="form-group">
                    <label for="company_number">Номер компании</label>
                    <input type="text" id="company_number" name="company_number" placeholder="Номер компании">
                </div>
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="text" id="phone" name="phone" placeholder="Телефон">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="text" id="email" name="email" placeholder="Email">
                </div>
                <div class="form-group">
                    <label for="address">Адрес компании</label>
                    <input type="text" id="address" name="address" placeholder="Адрес компании">
                </div>
                <div class="form-group">
                    <button type="submit" name="add_client">Добавить клиента</button>
                </div>
            </form>
        </div>

        <h2>Список клиентов</h2>
        <table class="clients-table">
            <thead>
                <tr>
                    <th>Имя</th>
                    <th>Фамилия</th>
                    <th>Компания</th>
                    <th>Номер компании</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Адрес</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($clients)): ?>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($client['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($client['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($client['company']); ?></td>
                            <td><?php echo htmlspecialchars($client['company_number']); ?></td>
                            <td><?php echo htmlspecialchars($client['phone']); ?></td>
                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                            <td><?php echo htmlspecialchars($client['address']); ?></td>
                            <td>
                                <a href="admin.php?page=edit_client&id=<?php echo $client['id']; ?>">Редактировать</a>
                                <a href="admin.php?page=client_invoices&client_id=<?php echo $client['id']; ?>">Счета</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">Нет клиентов для отображения.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>