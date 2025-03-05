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

// Обработка формы загрузки выписки из банка
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_statement'])) {
    $invoice_id = trim($_POST['invoice_id'] ?? '');
    $amount_received = trim($_POST['amount_received'] ?? '');
    $file_path = '';

    // Обработка загрузки файла
    if (isset($_FILES['bank_statement']) && $_FILES['bank_statement']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['bank_statement']['tmp_name'];
        $file_name = basename($_FILES['bank_statement']['name']);
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
                $error_message = "Ошибка при перемещении загруженного файла. Путь: $file_path. Код ошибки: " . $_FILES['bank_statement']['error'];
                error_log($error_message); // Логируем ошибку
            }
        }
    } else {
        $error_message = "Файл не загружен. Код ошибки: " . $_FILES['bank_statement']['error'];
        error_log($error_message); // Логируем ошибку
    }

    // Если все поля заполнены, добавляем выписку в базу данных
    if (empty($error_message) && !empty($invoice_id) && !empty($amount_received)) {
        try {
            $stmt = $conn->prepare("INSERT INTO bank_statements (invoice_id, amount_received, file_path) VALUES (?, ?, ?)");
            $stmt->execute([$invoice_id, $amount_received, $file_path]);
            $success_message = "Выписка успешно загружена!";
        } catch(PDOException $e) {
            $error_message = "Ошибка при добавлении выписки: " . $e->getMessage();
            error_log($error_message); // Логируем ошибку
        }
    }
}

// Обработка удаления выписки
if (isset($_POST['delete_statement'])) {
    $statement_id = $_POST['statement_id'];
    
    // Получаем путь к файлу перед удалением
    try {
        $stmt = $conn->prepare("SELECT file_path FROM bank_statements WHERE id = ?");
        $stmt->execute([$statement_id]);
        $statement = $stmt->fetch();

        if ($statement) {
            $file_path = $statement['file_path'];

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

        // Удаление выписки из базы данных
        $stmt = $conn->prepare("DELETE FROM bank_statements WHERE id = ?");
        $stmt->execute([$statement_id]);
        $success_message = "Выписка успешно удалена!";
    } catch(PDOException $e) {
        $error_message = "Ошибка при удалении выписки: " . $e->getMessage();
        error_log($error_message); // Логируем ошибку
    }
}

// Получаем счета для каждого клиента
$invoices = [];
try {
    $stmt = $conn->query("SELECT * FROM invoices");
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Ошибка при получении счетов: " . $e->getMessage();
    error_log($error_message); // Логируем ошибку
}

// Получаем выписки из банка
$bank_statements = [];
try {
    $stmt = $conn->query("SELECT * FROM bank_statements");
    $bank_statements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Ошибка при получении выписок: " . $e->getMessage();
    error_log($error_message); // Логируем ошибку
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Выписки из банка</title>
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

    <h1>Выписки из банка</h1>
    
    <?php if (isset($success_message)): ?>
        <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <h2>Загрузить выписку из банка</h2>
    <form method="post" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="invoice_id">Выберите счет:</label>
            <select name="invoice_id" id="invoice_id" required>
                <option value="">Выберите счет</option>
                <?php foreach ($invoices as $invoice): ?>
                    <option value="<?php echo $invoice['id']; ?>"><?php echo htmlspecialchars($invoice['description']); ?> - <?php echo htmlspecialchars($invoice['invoice_date']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="amount_received">Сумма получена (в евро):</label>
            <input type="number" name="amount_received" id="amount_received" placeholder="Сумма" step="0.01" required>
        </div>
        <div class="form-group">
            <label for="bank_statement">Файл выписки:</label>
            <input type="file" name="bank_statement" id="bank_statement" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
        </div>
        <div class="form-group">
            <button type="submit" name="upload_statement">Загрузить выписку</button>
        </div>
    </form>

    <h2>Список выписок из банка</h2>
    <table>
        <thead>
            <tr>
                <th>Клиент</th>
                <th>Счет</th>
                <th>Сумма получена</th>
                <th>Файл</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($bank_statements)): ?>
                <?php foreach ($bank_statements as $statement): ?>
                    <tr>
                        <td>
                            <?php
                            // Получаем информацию о счете
                            $stmt = $conn->prepare("SELECT c.first_name, c.last_name, i.description FROM invoices i JOIN clients c ON i.client_id = c.id WHERE i.id = ?");
                            $stmt->execute([$statement['invoice_id']]);
                            $invoice = $stmt->fetch();
                            echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']);
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($invoice['description']); ?></td>
                        <td><?php echo number_format(htmlspecialchars($statement['amount_received']), 2, '.', '') . ' €'; ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($statement['file_path']); ?>" target="_blank">Скачать</a>
                        </td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="statement_id" value="<?php echo $statement['id']; ?>">
                                <button type="submit" name="delete_statement" onclick="return confirm('Вы уверены, что хотите удалить эту выписку?');">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">Нет выписок для отображения.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html> 