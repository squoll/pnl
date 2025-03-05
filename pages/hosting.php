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

// Обработка формы добавления хостинга
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_hosting'])) {
    $client_id = trim($_POST['client_id'] ?? '');
    $website_url = trim($_POST['website_url'] ?? '');
    $hosting_start = trim($_POST['hosting_start'] ?? '');
    $hosting_end = trim($_POST['hosting_end'] ?? '');
    $price = 14.52; // Фиксированная цена

    if (empty($client_id) || empty($website_url) || empty($hosting_start) || empty($hosting_end)) {
        $error = "Все поля обязательны для заполнения.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO hosting (client_id, website_url, hosting_start, hosting_end, price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$client_id, $website_url, $hosting_start, $hosting_end, $price]);
            $success_message = "Хостинг успешно добавлен!";
        } catch(PDOException $e) {
            $error_message = "Ошибка при добавлении хостинга: " . $e->getMessage();
        }
    }
}

// Получаем список клиентов
$clients = [];
try {
    $stmt = $conn->query("SELECT * FROM clients");
    $clients = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Ошибка при получении клиентов: " . $e->getMessage();
}

// Получаем хостинги с информацией о клиентах
$hostings = [];
try {
    $stmt = $conn->query("
        SELECT h.*, c.first_name, c.last_name 
        FROM hosting h 
        JOIN clients c ON h.client_id = c.id
    ");
    $hostings = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Ошибка при получении хостингов: " . $e->getMessage();
}

// Проверяем, загружается ли файл напрямую
$is_direct_access = !isset($page);

if ($is_direct_access):
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Хостинг</title>
    <link rel="stylesheet" href="https://pnl.standigital.lv/css/admin.css">
    <link rel="stylesheet" href="https://pnl.standigital.lv/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        h1, h2 {
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .form-group button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }
        .form-group button:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
<?php endif; ?>

    <h1>Хостинг</h1>
    
    <?php if (isset($success_message)): ?>
        <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <h2>Добавить новый хостинг</h2>
    <form method="post" action="">
        <div class="form-group">
            <label for="client_id">Выберите клиента:</label>
            <select name="client_id" id="client_id" required>
                <option value="">Выберите клиента</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="website_url">URL сайта:</label>
            <input type="text" name="website_url" id="website_url" placeholder="URL сайта" required>
        </div>
        <div class="form-group">
            <label for="hosting_start">Дата начала:</label>
            <input type="date" name="hosting_start" id="hosting_start" required>
        </div>
        <div class="form-group">
            <label for="hosting_end">Дата окончания:</label>
            <input type="date" name="hosting_end" id="hosting_end" required>
        </div>
        <div class="form-group">
            <button type="submit" name="add_hosting">Добавить хостинг</button>
        </div>
    </form>

    <h2>Список хостингов</h2>
    <table>
        <thead>
            <tr>
                <th>Клиент</th>
                <th>URL сайта</th>
                <th>Дата начала</th>
                <th>Дата окончания</th>
                <th>Осталось</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($hostings)): ?>
                <?php foreach ($hostings as $hosting): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($hosting['first_name'] . ' ' . $hosting['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($hosting['website_url']); ?></td>
                        <td><?php echo htmlspecialchars($hosting['hosting_start']); ?></td>
                        <td><?php echo htmlspecialchars($hosting['hosting_end']); ?></td>
                        <td>
                            <?php
                            // Рассчитываем оставшееся время
                            $end_date = new DateTime($hosting['hosting_end']);
                            $now = new DateTime();
                            $interval = $now->diff($end_date);
                            echo $interval->format('%m месяцев %d дней');
                            ?>
                        </td>
                        <td>
                            <a href="download_invoice.php?id=<?php echo $hosting['id']; ?>">Скачать счет</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Нет хостингов для отображения.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html> 