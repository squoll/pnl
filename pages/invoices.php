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

// Проверка подключения к базе данных
if (!$conn) {
    die("Ошибка подключения к базе данных: " . mysqli_connect_error());
} else {
    error_log("Подключение к базе данных успешно.");
}

// Обработка формы загрузки счета
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_invoice'])) {
    $client_id = trim($_POST['client_id'] ?? '');
    $invoice_date = trim($_POST['invoice_date'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $file_path = '';

    // Обработка загрузки файла
    if (isset($_FILES['invoice_file']) && $_FILES['invoice_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['invoice_file']['tmp_name'];
        $file_name = basename($_FILES['invoice_file']['name']);
        $file_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file_name); // Очистка имени файла
        $upload_dir = './uploads/';
        $file_path = $upload_dir . $file_name;

        // Проверка существования директории
        if (!is_dir($upload_dir)) {
            error_log("Директория $upload_dir не существует.");
            $error_message = "Директория для загрузки не найдена.";
        } elseif (!is_writable($upload_dir)) {
            error_log("Директория $upload_dir недоступна для записи.");
            $error_message = "Нет прав на запись в директорию загрузки.";
        } else {
            // Перемещение загруженного файла
            if (!move_uploaded_file($file_tmp, $file_path)) {
                $error_message = "Ошибка при перемещении загруженного файла. Путь: $file_path. Код ошибки: " . $_FILES['invoice_file']['error'];
                error_log($error_message); // Логируем ошибку
            }
        }
    } else {
        $error_message = "Файл не загружен. Код ошибки: " . $_FILES['invoice_file']['error'];
        error_log($error_message); // Логируем ошибку
    }

    // Если все поля заполнены, добавляем счет в базу данных
    if (empty($error_message) && !empty($client_id) && !empty($invoice_date) && !empty($amount)) {
        $tax = $amount * 0.10; // 10% налога
        $total = $amount - $tax;

        try {
            $stmt = $conn->prepare("INSERT INTO invoices (client_id, invoice_date, due_date, amount, tax, total, file_path, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$client_id, $invoice_date, $due_date, $amount, $tax, $total, $file_path, $description]);
            $success_message = "Счет успешно загружен!";
        } catch(PDOException $e) {
            $error_message = "Ошибка при добавлении счета: " . $e->getMessage();
            error_log($error_message); // Логируем ошибку
        }
    }
}

// Обработка удаления счета
if (isset($_POST['delete_invoice'])) {
    $invoice_id = $_POST['invoice_id'];
    
    // Получаем путь к файлу перед удалением
    try {
        $stmt = $conn->prepare("SELECT file_path FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        $invoice = $stmt->fetch();

        if ($invoice) {
            $file_path = $invoice['file_path'];

            // Удаление файла
            if (file_exists($file_path)) {
                if (!unlink($file_path)) {
                    $error_message = "Ошибка при удалении файла: $file_path.";
                    error_log($error_message); // Логируем ошибку
                }
            } else {
                $error_message = "Файл не найден: $file_path.";
                error_log($error_message); // Логируем ошибку
            }
        }

        // Удаление счета из базы данных
        $stmt = $conn->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        $success_message = "Счет успешно удален!";
    } catch(PDOException $e) {
        $error_message = "Ошибка при удалении счета: " . $e->getMessage();
        error_log($error_message); // Логируем ошибку
    }
}

// Получаем список клиентов
$clients = [];
try {
    $stmt = $conn->query("SELECT * FROM clients");
    $clients = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Ошибка при получении клиентов: " . $e->getMessage();
    error_log($error_message); // Логируем ошибку
}

// Получаем счета для каждого клиента
$invoices = [];
try {
    $stmt = $conn->query("SELECT * FROM invoices");
    $invoices = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Ошибка при получении счетов: " . $e->getMessage();
    error_log($error_message); // Логируем ошибку
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Счета</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/style.css"> <!-- Подключаем основной стиль -->
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
        .success-message, .error-message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
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
    </style>
</head>
<body>

    <h1>Счета</h1>
    
    <?php if (isset($success_message)): ?>
        <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <h2>Загрузить новый счет</h2>
    <form method="post" action="" enctype="multipart/form-data">
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
            <label for="invoice_date">Дата выставления:</label>
            <input type="date" name="invoice_date" id="invoice_date" required>
        </div>
        <div class="form-group">
            <label for="due_date">Дата оплаты:</label>
            <input type="date" name="due_date" id="due_date">
        </div>
        <div class="form-group">
            <label for="amount">Сумма (в евро):</label>
            <input type="number" name="amount" id="amount" placeholder="Сумма" step="0.01" required>
        </div>
        <div class="form-group">
            <label for="description">Назначение счета:</label>
            <input type="text" name="description" id="description" placeholder="Назначение счета" required>
        </div>
        <div class="form-group">
            <label for="invoice_file">Файл счета:</label>
            <input type="file" name="invoice_file" id="invoice_file" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
        </div>
        <div class="form-group">
            <button type="submit" name="upload_invoice">Загрузить счет</button>
        </div>
    </form>

    <h2>Список счетов</h2>
    <table>
        <thead>
            <tr>
                <th>Клиент</th>
                <th>Дата выставления</th>
                <th>Сумма (в евро)</th>
                <th>Налог (10%)</th>
                <th>Итого (в евро)</th>
                <th>Назначение</th>
                <th>Файл</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($invoices)): ?>
                <?php foreach ($invoices as $invoice): ?>
                    <tr id="invoice-<?php echo $invoice['id']; ?>">
                        <td class="client-name"><?php
                            // Получаем имя клиента
                            $stmt = $conn->prepare("SELECT first_name, last_name FROM clients WHERE id = ?");
                            $stmt->execute([$invoice['client_id']]);
                            $client = $stmt->fetch();
                            echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']);
                        ?></td>
                        <td class="invoice-date">
                            <?php
                            $invoiceDate = new DateTime($invoice['invoice_date']);
                            echo $invoiceDate->format('d.m.Y'); // Формат: день месяц год
                            ?>
                        </td>
                        <td class="amount"><?php echo number_format(htmlspecialchars($invoice['amount']), 2, '.', '') . ' €'; ?></td>
                        <td class="tax"><?php echo number_format(htmlspecialchars($invoice['tax']), 2, '.', '') . ' €'; ?></td>
                        <td class="total"><?php echo number_format(htmlspecialchars($invoice['total']), 2, '.', '') . ' €'; ?></td>
                        <td class="description"><?php echo htmlspecialchars($invoice['description']); ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($invoice['file_path']); ?>" target="_blank">Скачать</a>
                        </td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                <button type="submit" name="delete_invoice" onclick="return confirm('Вы уверены, что хотите удалить этот счет?');">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">Нет счетов для отображения.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html> 