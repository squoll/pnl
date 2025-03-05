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

// Получаем ID клиента из параметров запроса
$client_id = $_GET['client_id'] ?? null;

if ($client_id) {
    // Получаем счета для данного клиента
    $invoices = [];
    try {
        $stmt = $conn->prepare("SELECT * FROM invoices WHERE client_id = ?");
        $stmt->execute([$client_id]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error_message = "Ошибка при получении счетов: " . $e->getMessage();
    }
} else {
    $error_message = "ID клиента не указан.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Счета клиента</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

    <h1>Счета клиента</h1>
    
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <h2>Список счетов</h2>
    <table>
        <thead>
            <tr>
                <th>Дата выставления</th>
                <th>Дата оплаты</th>
                <th>Сумма</th>
                <th>Пояснение</th>
                <th>Файл</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($invoices)): ?>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td>
                            <?php
                            $invoiceDate = new DateTime($invoice['invoice_date']);
                            echo $invoiceDate->format('d.m.Y'); // Формат: день месяц год
                            ?>
                        </td>
                        <td>
                            <?php
                            $dueDate = new DateTime($invoice['due_date']);
                            echo $dueDate->format('d.m.Y'); // Формат: день месяц год
                            ?>
                        </td>
                        <td><?php echo number_format(htmlspecialchars($invoice['amount']), 2, '.', '') . ' €'; ?></td>
                        <td><?php echo htmlspecialchars($invoice['description']); ?></td>
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
                    <td colspan="6">Нет счетов для отображения.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html> 