<?php
require_once '../includes/auth.php';
require_once '../includes/i18n.php'; // Required for t() function if not already loaded by header later, but good practice to have it available for logic if needed (though here mostly used in UI)
requireAuth();

// Получаем параметры фильтрации
$filter_type = $_GET['type'] ?? 'all';
$filter_severity = $_GET['severity'] ?? 'all';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Построение запроса с фильтрами
$where_conditions = [];
$params = [];

if ($filter_type !== 'all') {
    $where_conditions[] = "event_type = ?";
    $params[] = $filter_type;
}

if ($filter_severity !== 'all') {
    $where_conditions[] = "severity = ?";
    $params[] = $filter_severity;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Получаем общее количество записей
$count_query = "SELECT COUNT(*) FROM security_logs $where_clause";
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = max(1, ceil($total_records / $per_page));

// Получаем логи
$query = "SELECT * FROM security_logs $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$stmt = $conn->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем статистику по типам событий
$stats_query = "SELECT event_type, COUNT(*) as count FROM security_logs WHERE event_type IS NOT NULL AND event_type != '' GROUP BY event_type ORDER BY count DESC LIMIT 10";
$stats = $conn->query($stats_query)->fetchAll(PDO::FETCH_ASSOC);

// Получаем топ заблокированных IP
$blocked_query = "SELECT ip_address, blocked_until, reason, attempts_count FROM blocked_ips WHERE blocked_until > NOW() ORDER BY attempts_count DESC";
$blocked_ips = $conn->query($blocked_query)->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../includes/header.php'; ?>

<style>
    .severity-low { color: #28a745; }
    .severity-medium { color: #ffc107; }
    .severity-high { color: #fd7e14; }
    .severity-critical { color: #dc3545; font-weight: bold; }
    
    .stat-card {
        background: var(--dk-dark-bg);
        border: 1px solid var(--dk-gray-700);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .log-table { font-size: 0.875rem; }
</style>

<div class="p-4">
    <div class="welcome mb-4">
        <div class="content rounded-3 p-3">
            <h1 class="fs-3"><?= htmlspecialchars(t('logs_title')) ?></h1>
            <p class="mb-0"><?= htmlspecialchars(t('logs_subtitle')) ?></p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <h5><?= htmlspecialchars(t('logs_total_events')) ?></h5>
                <h2><?= number_format($total_records) ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <h5><?= htmlspecialchars(t('logs_blocked_ips')) ?></h5>
                <h2><?= count($blocked_ips) ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <h5><?= htmlspecialchars(t('logs_critical_events')) ?></h5>
                <?php
                $critical_count = $conn->query("SELECT COUNT(*) FROM security_logs WHERE severity = 'critical' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
                ?>
                <h2 class="severity-critical"><?= $critical_count ?></h2>
                <small><?= htmlspecialchars(t('logs_last_24h')) ?></small>
            </div>
        </div>
    </div>

    <?php if (!empty($blocked_ips)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><?= htmlspecialchars(t('logs_blocked_ips_title')) ?></h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars(t('logs_ip_address')) ?></th>
                            <th><?= htmlspecialchars(t('logs_reason')) ?></th>
                            <th><?= htmlspecialchars(t('logs_attempts')) ?></th>
                            <th><?= htmlspecialchars(t('logs_blocked_until')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blocked_ips as $blocked): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($blocked['ip_address']) ?></code></td>
                            <td><?= htmlspecialchars($blocked['reason']) ?></td>
                            <td><span class="badge bg-danger"><?= $blocked['attempts_count'] ?></span></td>
                            <td><?= htmlspecialchars($blocked['blocked_until']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= htmlspecialchars(t('logs_filters')) ?></h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><?= htmlspecialchars(t('logs_event_type')) ?></label>
                    <select name="type" class="form-select">
                        <option value="all" <?= $filter_type === 'all' ? 'selected' : '' ?>><?= htmlspecialchars(t('filter_all_types')) ?></option>
                        <option value="login_success" <?= $filter_type === 'login_success' ? 'selected' : '' ?>><?= htmlspecialchars(t('filter_login_success')) ?></option>
                        <option value="login_failed" <?= $filter_type === 'login_failed' ? 'selected' : '' ?>><?= htmlspecialchars(t('filter_login_failed')) ?></option>
                        <option value="ip_blocked" <?= $filter_type === 'ip_blocked' ? 'selected' : '' ?>><?= htmlspecialchars(t('filter_ip_blocked')) ?></option>
                        <option value="csrf_attack" <?= $filter_type === 'csrf_attack' ? 'selected' : '' ?>><?= htmlspecialchars(t('filter_csrf_attack')) ?></option>
                        <option value="bot_detected" <?= $filter_type === 'bot_detected' ? 'selected' : '' ?>><?= htmlspecialchars(t('filter_bot_detected')) ?></option>
                        <option value="suspicious_request" <?= $filter_type === 'suspicious_request' ? 'selected' : '' ?>><?= htmlspecialchars(t('filter_suspicious_request')) ?></option>
                        <option value="session_hijack_attempt" <?= $filter_type === 'session_hijack_attempt' ? 'selected' : '' ?>><?= htmlspecialchars(t('filter_session_hijack')) ?></option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= htmlspecialchars(t('logs_severity')) ?></label>
                    <select name="severity" class="form-select">
                        <option value="all" <?= $filter_severity === 'all' ? 'selected' : '' ?>><?= htmlspecialchars(t('filter_all_levels')) ?></option>
                        <option value="low" <?= $filter_severity === 'low' ? 'selected' : '' ?>><?= htmlspecialchars(t('filter_low')) ?></option>
                        <option value="medium" <?= $filter_severity === 'medium' ? 'selected' : '' ?>><?= htmlspecialchars(t('filter_medium')) ?></option>
                        <option value="high" <?= $filter_severity === 'high' ? 'selected' : '' ?>><?= htmlspecialchars(t('filter_high')) ?></option>
                        <option value="critical" <?= $filter_severity === 'critical' ? 'selected' : '' ?>><?= htmlspecialchars(t('filter_critical')) ?></option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><?= htmlspecialchars(t('logs_apply')) ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><?= sprintf(t('logs_page_title'), $page, $total_pages) ?></h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm log-table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars(t('logs_id')) ?></th>
                            <th><?= htmlspecialchars(t('logs_time')) ?></th>
                            <th><?= htmlspecialchars(t('logs_type')) ?></th>
                            <th><?= htmlspecialchars(t('logs_ip')) ?></th>
                            <th><?= htmlspecialchars(t('logs_user')) ?></th>
                            <th><?= htmlspecialchars(t('logs_description')) ?></th>
                            <th><?= htmlspecialchars(t('logs_level')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= $log['id'] ?></td>
                            <td><?= htmlspecialchars($log['created_at'] ?? '') ?></td>
                            <td><code><?= htmlspecialchars($log['event_type'] ?? 'unknown') ?></code></td>
                            <td><code><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></code></td>
                            <td><?= htmlspecialchars($log['username'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($log['description'] ?? t('logs_no_desc')) ?></td>
                            <td><span class="severity-<?= $log['severity'] ?? 'low' ?>"><?= strtoupper($log['severity'] ?? 'LOW') ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&type=<?= urlencode($filter_type) ?>&severity=<?= urlencode($filter_severity) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

