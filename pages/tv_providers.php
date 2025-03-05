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

// Обработка формы добавления провайдера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_provider'])) {
    $operator = trim($_POST['operator'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $referral_link = trim($_POST['referral_link'] ?? '');
    $balance = floatval($_POST['balance'] ?? 0);

    if (empty($operator)) {
        $error = "Поле 'Оператор' обязательно для заполнения.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO tv_providers (operator, website, login, password, referral_link, balance) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$operator, $website, $login, $password, $referral_link, $balance]);
            $success_message = "Провайдер успешно добавлен!";
        } catch(PDOException $e) {
            $error_message = "Ошибка при добавлении провайдера: " . $e->getMessage();
        }
    }
}

// Обработка изменения баланса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_balance'])) {
    $provider_id = intval($_POST['provider_id']);
    $new_balance = floatval($_POST['new_balance']);

    try {
        $stmt = $conn->prepare("UPDATE tv_providers SET balance = ? WHERE id = ?");
        $stmt->execute([$new_balance, $provider_id]);
        $success_message = "Баланс успешно обновлен!";
    } catch(PDOException $e) {
        $error_message = "Ошибка при обновлении баланса: " . $e->getMessage();
    }
}

// Получение списка провайдеров
$query = "SELECT * FROM tv_providers";
$result = $conn->query($query);
$providers = $result->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV Providers Management</title>
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
        .accordion {
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
        .accordion:hover {
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
        .form-group input {
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
            .accordion {
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
<h1>TV Providers Management</h1>

<?php if (isset($success_message)): ?>
    <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<!-- Кнопка аккордеона для добавления нового провайдера -->
<button class="accordion">Добавить нового провайдера</button>
<div class="panel">
    <form method="POST">
        <div class="form-group">
            <label for="operator">Оператор:</label>
            <input type="text" id="operator" name="operator" required>
        </div>
        <div class="form-group">
            <label for="website">Сайт:</label>
            <input type="text" id="website" name="website">
        </div>
        <div class="form-group">
            <label for="login">Логин:</label>
            <input type="text" id="login" name="login">
        </div>
        <div class="form-group">
            <label for="password">Пароль:</label>
            <input type="text" id="password" name="password">
        </div>
        <div class="form-group">
            <label for="referral_link">Реферальная ссылка:</label>
            <input type="text" id="referral_link" name="referral_link">
        </div>
        <div class="form-group">
            <label for="balance">Баланс:</label>
            <input type="number" id="balance" name="balance" step="0.01">
        </div>
        <button type="submit" name="add_provider" style="background-color: #28a745; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; width: 100%;">Добавить провайдера</button>
    </form>
</div>

<!-- Вывод списка провайдеров -->
<h2>Список провайдеров</h2>
<?php if (!empty($providers)): ?>
    <table>
        <thead>
            <tr>
                <th>Оператор</th>
                <th>Сайт</th>
                <th>Логин</th>
                <th>Пароль</th>
                <th>Реферальная ссылка</th>
                <th>Баланс</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($providers as $provider): ?>
                <tr>
                    <td><?= htmlspecialchars($provider['operator']) ?></td>
                    <td><?= htmlspecialchars($provider['website']) ?></td>
                    <td><?= htmlspecialchars($provider['login']) ?></td>
                    <td><?= htmlspecialchars($provider['password']) ?></td>
                    <td>
                        <a href="#" onclick="copyToClipboard('<?= htmlspecialchars($provider['referral_link']) ?>')"><?= htmlspecialchars($provider['referral_link']) ?></a>
                    </td>
                    <td>
                        <span onclick="showEditBalance(<?= $provider['id'] ?>, <?= $provider['balance'] ?>)" style="cursor: pointer; color: blue; text-decoration: underline;"><?= htmlspecialchars($provider['balance']) ?></span>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="provider_id" value="<?= $provider['id'] ?>">
                            <input type="number" name="new_balance" step="0.01" placeholder="Новый баланс" required>
                            <button type="submit" name="update_balance" style="background-color: #ffc107; color: white; border: none; padding: 5px; border-radius: 4px; cursor: pointer;">Обновить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Провайдеры не найдены.</p>
<?php endif; ?>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Ссылка скопирована!');
    }, function(err) {
        alert('Не удалось скопировать ссылку.');
    });
}

// Аккордеон функциональность
var acc = document.getElementsByClassName("accordion");
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

// Функция для отображения формы изменения баланса
function showEditBalance(providerId, currentBalance) {
    const newBalanceInput = document.querySelector(`input[name="new_balance"]`);
    newBalanceInput.value = currentBalance; // Устанавливаем текущее значение баланса
    newBalanceInput.focus(); // Фокусируемся на поле ввода
}
</script>
</body>
</html>

