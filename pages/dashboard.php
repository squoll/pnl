<?php
session_start();
require_once 'config/db.php';

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Включаем отображение ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Получаем количество клиентов
$clients_count = 0;
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM clients");
    $clients_count = $stmt->fetchColumn();
} catch(PDOException $e) {
    $error_message = "Ошибка при получении количества клиентов: " . $e->getMessage();
}

// Получаем количество счетов
$invoices_count = 0;
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM invoices");
    $invoices_count = $stmt->fetchColumn();
} catch(PDOException $e) {
    $error_message = "Ошибка при получении количества счетов: " . $e->getMessage();
}

// Получаем общий заработок
$total_income = 0;
try {
    $stmt = $conn->query("SELECT SUM(total) as total FROM invoices");
    $total_income = $stmt->fetchColumn();
} catch(PDOException $e) {
    $error_message = "Ошибка при получении общего заработка: " . $e->getMessage();
}

// Получаем общую сумму налогов
$total_taxes = 0;
try {
    $stmt = $conn->query("SELECT SUM(tax) as total FROM invoices");
    $total_taxes = $stmt->fetchColumn();
} catch(PDOException $e) {
    $error_message = "Ошибка при получении общей суммы налогов: " . $e->getMessage();
}

// Функция для получения суммы налогов за определенный период
function getTaxForPeriod($conn, $start_date, $end_date) {
    try {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(tax), 0) as total FROM invoices WHERE date BETWEEN ? AND ?");
        $stmt->execute([$start_date, $end_date]);
        $result = $stmt->fetchColumn();
        return $result ?: 0;
    } catch(PDOException $e) {
        error_log("Error in getTaxForPeriod: " . $e->getMessage());
        return 0;
    }
}

// Получение текущего года
$current_year = date('Y');

// Получение данных для каждого квартала
$q1_tax = getTaxForPeriod($conn, "$current_year-01-01", "$current_year-03-31");
$q2_tax = getTaxForPeriod($conn, "$current_year-04-01", "$current_year-06-30");
$q3_tax = getTaxForPeriod($conn, "$current_year-07-01", "$current_year-09-30");
$q4_tax = getTaxForPeriod($conn, "$current_year-10-01", "$current_year-12-31");

// Получение данных за 4-й квартал предыдущего года
$prev_year_q4_tax = getTaxForPeriod($conn, ($current_year-1).'-10-01', ($current_year-1).'-12-31');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Админ-панель - Dashboard</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .quarters-title {
            text-align: center;
            margin: 2rem 0;
            font-size: 1.5rem;
            color: #333;
        }

        .quarters-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .quarter-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .quarter-card:hover {
            transform: translateY(-5px);
        }

        .quarter-header {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            text-align: center;
        }

        .quarter-amount {
            font-size: 1.5rem;
            color: #2980b9;
            text-align: center;
            margin-bottom: 1rem;
        }

        .days-remaining {
            color: #e74c3c;
            text-align: center;
            margin: 0.5rem 0;
        }

        .paid-status {
            color: #27ae60;
            text-align: center;
            display: none;
        }

        .quarter-checkbox {
            display: block;
            margin: 1rem auto;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .quarter-checkbox:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .quarters-container {
                grid-template-columns: 1fr;
            }
            
            .quarter-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <h1>Добро пожаловать в админ-панель</h1>

    <div class="stats-container">
        <div class="stats-card">
            <h3>Количество клиентов</h3>
            <div class="stats-number"><?php echo $clients_count; ?></div>
        </div>
        <div class="stats-card">
            <h3>Количество счетов</h3>
            <div class="stats-number"><?php echo $invoices_count; ?></div>
        </div>
        <div class="stats-card">
            <h3>Общий заработок</h3>
            <div class="stats-number"><?php echo number_format($total_income, 2, '.', '') . ' €'; ?></div>
        </div>
        <div class="stats-card">
            <h3>Общие налоги</h3>
            <div class="stats-number"><?php echo number_format($total_taxes, 2, '.', '') . ' €'; ?></div>
        </div>
    </div>

    <h2 class="quarters-title">Оплата Налога по кварталам</h2>
    <div class="quarters-container">
        <?php
        // Создаем карточки для каждого квартала
        createQuarterCard(1, $q1_tax, '07', '23', $current_year);
        createQuarterCard(2, $q2_tax, '07', '23', $current_year);
        createQuarterCard(3, $q3_tax, '10', '23', $current_year);
        createQuarterCard(4, $q4_tax, '01', '23', $current_year);

        // Если текущий месяц январь, показываем 4-й квартал предыдущего года
        if (date('n') == 1) {
            createQuarterCard(4, $prev_year_q4_tax, '01', '23', $current_year, true);
        }
        ?>
    </div>

    <script>
        function markAsPaid(cardId) {
            const checkbox = document.getElementById('checkbox_' + cardId);
            const daysElement = document.getElementById('days_' + cardId);
            const paidElement = document.getElementById('paid_' + cardId);
            
            if (checkbox.checked) {
                daysElement.style.display = 'none';
                paidElement.style.display = 'block';
                checkbox.disabled = true;
                
                // Здесь можно добавить AJAX запрос для сохранения статуса в базе данных
                localStorage.setItem('paid_' + cardId, 'true');
            }
        }

        // Восстановление состояния после перезагрузки страницы
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.quarter-checkbox');
            checkboxes.forEach(checkbox => {
                const cardId = checkbox.id.replace('checkbox_', '');
                if (localStorage.getItem('paid_' + cardId) === 'true') {
                    checkbox.checked = true;
                    checkbox.disabled = true;
                    document.getElementById('days_' + cardId).style.display = 'none';
                    document.getElementById('paid_' + cardId).style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>