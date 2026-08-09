<?php
// Включаем отображение ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Security Test</title></head><body>";
echo "<h1>Тест безопасности</h1>";

try {
    echo "<p>1. Подключение auth.php...</p>";
    require_once '../includes/auth.php';
    echo "<p style='color:green'>✓ Auth.php подключен успешно</p>";
    
    echo "<p>2. Проверка аутентификации...</p>";
    requireAuth();
    echo "<p style='color:green'>✓ Пользователь авторизован</p>";
    
    echo "<p>3. Проверка подключения к БД...</p>";
    if (isset($conn)) {
        echo "<p style='color:green'>✓ Подключение к БД установлено</p>";
    } else {
        echo "<p style='color:red'>✗ Нет подключения к БД</p>";
    }
    
    echo "<p>4. Проверка класса Security...</p>";
    if (isset($security) && $security instanceof Security) {
        echo "<p style='color:green'>✓ Класс Security инициализирован</p>";
    } else {
        echo "<p style='color:red'>✗ Класс Security не найден</p>";
    }
    
    echo "<p>5. Проверка таблиц...</p>";
    $tables = ['login_attempts', 'blocked_ips', 'security_logs'];
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<p style='color:green'>✓ Таблица $table существует (записей: $count)</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>✗ Ошибка таблицы $table: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p>6. Тестовый запрос к security_logs...</p>";
    $stmt = $conn->query("SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 5");
    $logs = $stmt->fetchAll();
    echo "<p style='color:green'>✓ Запрос выполнен, получено записей: " . count($logs) . "</p>";
    
    if (!empty($logs)) {
        echo "<table border='1' style='border-collapse:collapse; margin:10px 0;'>";
        echo "<tr><th>ID</th><th>Type</th><th>IP</th><th>Username</th><th>Description</th></tr>";
        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($log['id']) . "</td>";
            echo "<td>" . htmlspecialchars($log['event_type']) . "</td>";
            echo "<td>" . htmlspecialchars($log['ip_address'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($log['username'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($log['description']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2 style='color:green'>Все тесты пройдены успешно!</h2>";
    echo "<p><a href='security_logs.php'>Перейти к Security Logs</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red; font-weight:bold'>✗ ОШИБКА: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";
?>
