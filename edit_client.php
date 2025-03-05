<?php
session_start();
require_once '../config/db.php';

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Получаем ID клиента
$client_id = $_GET['id'] ?? null;

if ($client_id) {
    // Получаем информацию о клиенте
    $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();

    if (!$client) {
        die("Клиент не найден.");
    }
}

// Обработка формы редактирования клиента
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $hosting = trim($_POST['hosting'] ?? '');
    $company_number = trim($_POST['company_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    try {
        $stmt = $conn->prepare("UPDATE clients SET first_name = ?, last_name = ?, company = ?, hosting = ?, company_number = ?, phone = ?, email = ?, address = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $company, $hosting, $company_number, $phone, $email, $address, $client_id]);
        header("Location: clients.php");
        exit();
    } catch(PDOException $e) {
        $error_message = "Ошибка при обновлении клиента: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Редактировать клиента</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <h1>Редактировать клиента</h1>
    
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="text" name="first_name" placeholder="Имя" value="<?php echo htmlspecialchars($client['first_name']); ?>" required>
        <input type="text" name="last_name" placeholder="Фамилия" value="<?php echo htmlspecialchars($client['last_name']); ?>" required>
        <input type="text" name="company" placeholder="Компания" value="<?php echo htmlspecialchars($client['company']); ?>">
        <input type="text" name="hosting" placeholder="Хостинг" value="<?php echo htmlspecialchars($client['hosting']); ?>">
        <input type="text" name="company_number" placeholder="Номер компании" value="<?php echo htmlspecialchars($client['company_number']); ?>">
        <input type="text" name="phone" placeholder="Телефон" value="<?php echo htmlspecialchars($client['phone']); ?>">
        <input type="text" name="email" placeholder="Email" value="<?php echo htmlspecialchars($client['email']); ?>">
        <input type="text" name="address" placeholder="Адрес" value="<?php echo htmlspecialchars($client['address']); ?>">
        <button type="submit">Сохранить изменения</button>
    </form>
</body>
</html> 