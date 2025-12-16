<?php
require_once '../includes/auth.php';
requireAuth();
require_once '../includes/i18n.php';

// Функция для форматирования байт
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
}

// Обработка запроса на скачивание бэкапа
if (isset($_GET['action']) && $_GET['action'] === 'backup') {
    $filename = 'iptv_backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo "-- IPTV Dashboard Backup\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Получаем список таблиц
    $tables = [];
    $stmt = $conn->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    foreach ($tables as $table) {
        // Структура
        echo "-- Table structure for table `$table`\n";
        echo "DROP TABLE IF EXISTS `$table`;\n";
        $row = $conn->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        echo $row[1] . ";\n\n";
        
        // Данные
        echo "-- Dumping data for table `$table`\n";
        $rows = $conn->query("SELECT * FROM `$table`");
        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            $keys = array_keys($row);
            $values = array_map(function($value) use ($conn) {
                if ($value === null) return "NULL";
                return $conn->quote($value);
            }, array_values($row));
            
            echo "INSERT INTO `$table` (`" . implode('`, `', $keys) . "`) VALUES (" . implode(', ', $values) . ");\n";
        }
        echo "\n";
    }
    
    exit;
}

// Статистика базы данных
$dbStats = [];
$totalSize = 0;
try {
    $stmt = $conn->query("SHOW TABLE STATUS");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $size = $row['Data_length'] + $row['Index_length'];
        $totalSize += $size;
        $dbStats[] = [
            'name' => $row['Name'],
            'rows' => $row['Rows'],
            'size' => $size,
            'updated' => $row['Update_time']
        ];
    }
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

// Аналитика
$analytics = [];

// 1. ARPU (Средний доход на пользователя)
// Берем доход за последние 30 дней и делим на активных пользователей
$stmt = $conn->query("SELECT COUNT(*) FROM tv_clients WHERE DATE_ADD(subscription_date, INTERVAL months MONTH) > NOW()");
$activeClients = $stmt->fetchColumn();

// Доход за последние 30 дней
$stmt = $conn->query("SELECT SUM(earned) FROM tv_payments WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
$revenue30d = $stmt->fetchColumn() ?: 0;

$analytics['arpu'] = $activeClients > 0 ? round($revenue30d / $activeClients, 2) : 0;

// 2. Churn Rate (Отток клиентов)
// Кол-во клиентов, у которых подписка истекла за последние 30 дней и не была продлена
$stmt = $conn->query("
    SELECT COUNT(*) 
    FROM tv_clients 
    WHERE DATE_ADD(subscription_date, INTERVAL months MONTH) BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW()
");
$expired30d = $stmt->fetchColumn();
$totalClients = $total_clients ?? 0; // Определено в index.php, но тут надо заново или передавать. Считаем заново
if (!isset($total_clients)) {
    $totalClients = $conn->query("SELECT COUNT(*) FROM tv_clients")->fetchColumn();
}

$analytics['churn'] = $totalClients > 0 ? round(($expired30d / $totalClients) * 100, 1) : 0;
$analytics['active_clients'] = $activeClients;
$analytics['total_clients'] = $totalClients;
$analytics['expired_30d'] = $expired30d;

?>
<?php include '../includes/header.php'; ?>

<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fs-4"><?= htmlspecialchars(t('system_health_title') ?? 'System Health & Analytics') ?></h2>
        <a href="?action=backup" class="btn btn-primary">
            <i class="uil-download-alt"></i> <?= htmlspecialchars(t('download_backup') ?? 'Download Backup') ?>
        </a>
    </div>

    <!-- Analytics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="box bg-dark p-4 rounded-3 text-center border border-secondary">
                <h3 class="fs-5 text-muted"><?= htmlspecialchars(t('arpu_30d') ?? 'ARPU (30 Days)') ?></h3>
                <div class="display-4 fw-bold text-success">€<?= $analytics['arpu'] ?></div>
                <p class="mb-0 text-muted small"><?= htmlspecialchars(t('avg_revenue_user') ?? 'Average Revenue Per User') ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box bg-dark p-4 rounded-3 text-center border border-secondary">
                <h3 class="fs-5 text-muted"><?= htmlspecialchars(t('churn_rate') ?? 'Churn Rate') ?></h3>
                <div class="display-4 fw-bold text-danger"><?= $analytics['churn'] ?>%</div>
                <p class="mb-0 text-muted small"><?= $expired30d ?> <?= htmlspecialchars(t('clients_expired_30d') ?? 'clients expired in last 30 days') ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box bg-dark p-4 rounded-3 text-center border border-secondary">
                <h3 class="fs-5 text-muted"><?= htmlspecialchars(t('db_size') ?? 'Database Size') ?></h3>
                <div class="display-4 fw-bold text-info"><?= formatBytes($totalSize) ?></div>
                <p class="mb-0 text-muted small"><?= count($dbStats) ?> <?= htmlspecialchars(t('tables') ?? 'tables') ?></p>
            </div>
        </div>
    </div>

    <!-- Database Stats Table -->
    <div class="card bg-dark border-secondary">
        <div class="card-header border-secondary bg-transparent">
            <h5 class="mb-0"><?= htmlspecialchars(t('db_stats') ?? 'Database Statistics') ?></h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4"><?= htmlspecialchars(t('table_name') ?? 'Table Name') ?></th>
                            <th><?= htmlspecialchars(t('rows') ?? 'Rows') ?></th>
                            <th><?= htmlspecialchars(t('size') ?? 'Size') ?></th>
                            <th><?= htmlspecialchars(t('last_updated') ?? 'Last Updated') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dbStats as $stat): ?>
                        <tr>
                            <td class="ps-4 font-monospace"><?= htmlspecialchars($stat['name']) ?></td>
                            <td><?= number_format($stat['rows']) ?></td>
                            <td><?= formatBytes($stat['size']) ?></td>
                            <td><?= htmlspecialchars($stat['updated'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
