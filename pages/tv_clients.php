<?php
// Включение отображения ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Определяем путь и подключаем конфигурацию
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/db.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit();
}

// Получение списка провайдеров для выпадающего списка
$query_providers = "SELECT * FROM tv_providers";
$result_providers = $conn->query($query_providers);
$providers = $result_providers->fetchAll();

// Обработка формы добавления клиента
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_client'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $provider_id = intval($_POST['provider_id'] ?? 0);
    $subscription_date = trim($_POST['subscription_date'] ?? '');
    $months = intval($_POST['months'] ?? 0);
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $device_count = intval($_POST['device_count'] ?? 0);
    $viewing_program = trim($_POST['viewing_program'] ?? '');

    if (empty($first_name) || empty($phone) || empty($address) || empty($provider_id) || empty($subscription_date) || empty($months) || empty($login) || empty($password)) {
        $error = "Все поля обязательны для заполнения.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO tv_clients (first_name, phone, address, provider_id, subscription_date, months, login, password, device_count, viewing_program) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $phone, $address, $provider_id, $subscription_date, $months, $login, $password, $device_count, $viewing_program]);
            $success_message = "Клиент успешно добавлен!";
        } catch(PDOException $e) {
            $error_message = "Ошибка при добавлении клиента: " . $e->getMessage();
        }
    }
}

// Обработка изменения информации о клиенте
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_client'])) {
    $client_id = intval($_POST['client_id']);
    $new_subscription_date = trim($_POST['new_subscription_date']);
    $new_months = intval($_POST['new_months']);

    try {
        $stmt = $conn->prepare("UPDATE tv_clients SET subscription_date = ?, months = ? WHERE id = ?");
        $stmt->execute([$new_subscription_date, $new_months, $client_id]);
        $success_message = "Информация о клиенте успешно обновлена!";
    } catch(PDOException $e) {
        $error_message = "Ошибка при обновлении информации: " . $e->getMessage();
    }
}

// Обработка удаления клиента
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_client'])) {
    $client_id = intval($_POST['client_id']);

    try {
        $stmt = $conn->prepare("DELETE FROM tv_clients WHERE id = ?");
        $stmt->execute([$client_id]);
        $success_message = "Клиент успешно удален!";
    } catch(PDOException $e) {
        $error_message = "Ошибка при удалении клиента: " . $e->getMessage();
    }
}

// Поиск клиентов
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

$query_clients = "SELECT c.*, p.operator FROM tv_clients c JOIN tv_providers p ON c.provider_id = p.id WHERE c.first_name LIKE ? OR c.phone LIKE ? OR c.address LIKE ?";
$params = ["%$search_query%", "%$search_query%", "%$search_query%"];
$stmt_clients = $conn->prepare($query_clients);
$stmt_clients->execute($params);
$clients = $stmt_clients->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV Clients Management</title>
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
        .accordiontv {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            padding: 10px;
            border: none;
            text-align: left;
            outline: none;
            font-size: 15px;
            transition: background-color 0.3s;
            width: 100%;
            border-radius: 4px;
        }
        .accordiontv:hover {
            background-color: #0056b3;
        }
        .panel {
            padding: 0 18px;
            display: none;
            overflow: hidden;
            background-color: white;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        }
        .success-message, .error-message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
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
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .accordiontv {
                font-size: 14px;
            }
            .form-group input {
                padding: 8px;
            }
            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
<h1>TV Clients Management</h1>

<?php if (isset($success_message)): ?>
    <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<!-- Поиск клиентов -->
<form method="GET" style="margin-bottom: 20px;">
    <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Поиск по имени, адресу или телефону" required>
    <input type="hidden" name="page" value="tv_clients">
    <button type="submit" class="btn-search" style="background-color: #007bff; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer;">Поиск</button>
    <a href="admin.php?page=tv_clients" class="btn-clear" style="background-color: #dc3545; color: white; padding: 10px; border-radius: 4px; text-decoration: none; margin-left: 10px;">Очистить поиск</a>
</form>

<!-- Кнопка аккордеона для добавления нового клиента -->
<button class="accordiontv">Добавить нового клиента</button>
<div class="panel">
    <form method="POST">
        <div class="form-group">
            <label for="first_name">Имя:</label>
            <input type="text" id="first_name" name="first_name" required>
        </div>
        <div class="form-group">
            <label for="phone">Телефон:</label>
            <input type="text" id="phone" name="phone" required>
        </div>
        <div class="form-group">
            <label for="address">Адрес:</label>
            <input type="text" id="address" name="address" required>
        </div>
        <div class="form-group">
            <label for="provider_id">Провайдер:</label>
            <select id="provider_id" name="provider_id" required>
                <option value="">Выберите провайдера</option>
                <?php foreach ($providers as $provider): ?>
                    <option value="<?= $provider['id'] ?>"><?= htmlspecialchars($provider['operator']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="subscription_date">Подписка куплена:</label>
            <input type="date" id="subscription_date" name="subscription_date" required>
        </div>
        <div class="form-group">
            <label for="months">Месяцев:</label>
            <input type="number" id="months" name="months" required>
        </div>
        <div class="form-group">
            <label for="login">Логин:</label>
            <input type="text" id="login" name="login" required>
        </div>
        <div class="form-group">
            <label for="password">Пароль:</label>
            <input type="text" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="device_count">Количество устройств:</label>
            <input type="number" id="device_count" name="device_count" required>
        </div>
        <div class="form-group">
            <label for="viewing_program">Программа для просмотра:</label>
            <input type="text" id="viewing_program" name="viewing_program" required>
        </div>
        <button type="submit" name="add_client" style="background-color: #28a745; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; width: 100%;">Добавить клиента</button>
    </form>
</div>

<!-- Вывод списка клиентов -->
<h2>Список клиентов</h2>
<?php if (!empty($clients)): ?>
    <table>
        <thead>
            <tr>
                <th>Имя</th>
                <th>Телефон</th>
                <th>Адрес</th>
                <th>Провайдер</th>
                <th>Дата покупки</th>
                <th>Месяцев</th>
                <th>Осталось</th>
                <th>Логин</th>
                <th>Пароль</th>
                <th>Кол-во устройств</th>
                <th>Программа для просмотра</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $client): ?>
                <tr>
                    <td><?= htmlspecialchars($client['first_name']) ?></td>
                    <td><?= htmlspecialchars($client['phone']) ?></td>
                    <td><?= htmlspecialchars($client['address']) ?></td>
                    <td><?= htmlspecialchars($client['operator']) ?></td>
                    <td><?= htmlspecialchars($client['subscription_date']) ?></td>
                    <td>
                        <span onclick="showEditClient(<?= $client['id'] ?>, <?= $client['months'] ?>)" style="cursor: pointer; color: blue; text-decoration: underline;"><?= htmlspecialchars($client['months']) ?></span>
                    </td>
                    <td>
                        <?php
                        // Рассчитываем оставшееся время
                        $end_date = new DateTime($client['subscription_date']);
                        $end_date->modify("+{$client['months']} months");
                        $now = new DateTime();
                        $interval = $now->diff($end_date);
                        echo $interval->format('%m месяцев %d дней');
                        if ($interval->m == 1 && $interval->d == 0) {
                            echo "<span style='color: red;'> (Остался 1 месяц)</span>";
                        }
                        ?>
                    </td>
                    <td><?= htmlspecialchars($client['login']) ?></td>
                    <td><?= htmlspecialchars($client['password']) ?></td>
                    <td><?= htmlspecialchars($client['device_count']) ?></td>
                    <td><?= htmlspecialchars($client['viewing_program']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                            <input type="date" name="new_subscription_date" placeholder="Новая дата" required>
                            <input type="number" name="new_months" placeholder="Новые месяцы" required>
                            <button type="submit" name="update_client" style="background-color: #ffc107; color: white; border: none; padding: 5px; border-radius: 4px; cursor: pointer;">Обновить</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                            <button type="submit" name="delete_client" style="background-color: #dc3545; color: white; border: none; padding: 5px; border-radius: 4px; cursor: pointer;">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Клиенты не найдены.</p>
<?php endif; ?>

<script>
    // Аккордеон функциональность
    var acc = document.getElementsByClassName("accordiontv");
    for (let i = 0; i < acc.length; i++) {
        acc[i].addEventListener("click", function() {
            this.classList.toggle("active");
            var panel = this.nextElementSibling;
            if (panel.style.display === "block") {
                panel.style.display = "none";
            } else {
                panel.style.display = "block";
            }
        });
    }

    // Функция для отображения формы изменения клиента
    function showEditClient(clientId, currentMonths) {
        // Здесь можно добавить логику для отображения формы редактирования
    }
</script>
</body>
</html> 
