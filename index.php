<?php
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/i18n.php';

// Обеспечиваем наличие таблицы платежей для графиков
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS tv_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        year INT NOT NULL,
        paid DECIMAL(10,2) NOT NULL,
        provider_cost DECIMAL(10,2) NOT NULL,
        earned DECIMAL(10,2) NOT NULL,
        created_at DATETIME NOT NULL,
        INDEX(client_id), INDEX(year)
    )");
} catch (PDOException $e) {
}

try {
    $conn->exec("CREATE TABLE IF NOT EXISTS tv_events (id INT AUTO_INCREMENT PRIMARY KEY, type VARCHAR(32) NOT NULL, client_id INT, provider_id INT, amount DECIMAL(10,2), months INT, metadata TEXT, user VARCHAR(64), created_at DATETIME NOT NULL, INDEX(client_id), INDEX(type), INDEX(created_at))");
} catch (PDOException $e) {
}

// Получаем статистику
// Количество клиентов
$stmt = $conn->prepare("SELECT COUNT(*) FROM tv_clients");
$stmt->execute();
$total_clients = $stmt->fetchColumn();

// Количество провайдеров
$stmt = $conn->prepare("SELECT COUNT(*) FROM tv_providers");
$stmt->execute();
$total_providers = $stmt->fetchColumn();

// Заработок за текущий год по платежам
$current_year = date('Y');
$stmt = $conn->prepare("SELECT COALESCE(SUM(earned),0) as total_earned FROM tv_payments WHERE year = ?");
$stmt->execute([$current_year]);
$total_earned = $stmt->fetchColumn() ?: 0;

// Подсчет подписок, которые заканчиваются в ближайший месяц
$stmt = $conn->prepare("SELECT COUNT(*) FROM tv_clients WHERE DATE_ADD(subscription_date, INTERVAL months MONTH) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 MONTH)");
$stmt->execute();
$expiring_soon = $stmt->fetchColumn();

// Клиенты с истекающими подписками (для секции admins)
$stmt = $conn->prepare("
    SELECT c.*, p.operator,
           DATEDIFF(DATE_ADD(c.subscription_date, INTERVAL c.months MONTH), NOW()) as days_left
    FROM tv_clients c
    LEFT JOIN tv_providers p ON c.provider_id = p.id
    WHERE DATE_ADD(c.subscription_date, INTERVAL c.months MONTH) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 MONTH)
    ORDER BY days_left ASC
    LIMIT 6
");
$stmt->execute();
$expiring_clients = $stmt->fetchAll();

// Статистика для графиков
// Новые клиенты по месяцам текущего года на основе первого платежа
$stmt = $conn->prepare("
    SELECT MONTH(tp.created_at) as month, COUNT(*) as count
    FROM tv_payments tp
    INNER JOIN tv_clients c ON c.id = tp.client_id
    WHERE YEAR(tp.created_at) = ?
      AND YEAR(c.subscription_date) = YEAR(tp.created_at)
      AND MONTH(c.subscription_date) = MONTH(tp.created_at)
    GROUP BY MONTH(tp.created_at)
    ORDER BY month
");
$stmt->execute([$current_year]);
$new_clients_by_month = $stmt->fetchAll();

// Продления по месяцам текущего года (все платежи не совпадающие с месяцем старта подписки)
$stmt = $conn->prepare("
    SELECT MONTH(tp.created_at) as month, COUNT(*) as count
    FROM tv_payments tp
    INNER JOIN tv_clients c ON c.id = tp.client_id
    WHERE YEAR(tp.created_at) = ?
      AND NOT (YEAR(c.subscription_date) = YEAR(tp.created_at) AND MONTH(c.subscription_date) = MONTH(tp.created_at))
    GROUP BY MONTH(tp.created_at)
    ORDER BY month
");
$stmt->execute([$current_year]);
$renewals_by_month = $stmt->fetchAll();

// Заработок по месяцам текущего года по платежам
$stmt = $conn->prepare("
    SELECT MONTH(created_at) as month, SUM(earned) as total
    FROM tv_payments 
    WHERE YEAR(created_at) = ?
    GROUP BY MONTH(created_at)
    ORDER BY month
");
$stmt->execute([$current_year]);
$earnings_by_month = $stmt->fetchAll();

// Фолбэки, если в платежах пока нет данных
if (empty($new_clients_by_month)) {
    $stmt = $conn->prepare("
        SELECT MONTH(subscription_date) as month, COUNT(*) as count
        FROM tv_clients 
        WHERE YEAR(subscription_date) = ?
        GROUP BY MONTH(subscription_date)
        ORDER BY month
    ");
    $stmt->execute([$current_year]);
    $new_clients_by_month = $stmt->fetchAll();
}

if (empty($earnings_by_month)) {
    $stmt = $conn->prepare("
        SELECT MONTH(subscription_date) as month, SUM(earned) as total
        FROM tv_clients 
        WHERE YEAR(subscription_date) = ? AND earned > 0
        GROUP BY MONTH(subscription_date)
        ORDER BY month
    ");
    $stmt->execute([$current_year]);
    $earnings_by_month = $stmt->fetchAll();
}

// Подготовка данных для JavaScript
$new_clients_data = array_fill(0, 12, 0);
$renewals_data = array_fill(0, 12, 0);
$earnings_data = array_fill(0, 12, 0);
$paid_data = array_fill(0, 12, 0);
$cost_data = array_fill(0, 12, 0);

foreach ($new_clients_by_month as $row) {
    $new_clients_data[$row['month'] - 1] = $row['count'];
}

foreach ($renewals_by_month as $row) {
    $renewals_data[$row['month'] - 1] = $row['count'];
}

foreach ($earnings_by_month as $row) {
    $earnings_data[$row['month'] - 1] = floatval($row['total']);
}

// Дополнительные агрегаты
$stmt = $conn->prepare("SELECT MONTH(created_at) as month, SUM(paid) as total FROM tv_payments WHERE YEAR(created_at) = ? GROUP BY MONTH(created_at) ORDER BY month");
$stmt->execute([$current_year]);
$paid_by_month = $stmt->fetchAll();
foreach ($paid_by_month as $row) { $paid_data[$row['month'] - 1] = floatval($row['total']); }

$stmt = $conn->prepare("SELECT MONTH(created_at) as month, SUM(provider_cost) as total FROM tv_payments WHERE YEAR(created_at) = ? GROUP BY MONTH(created_at) ORDER BY month");
$stmt->execute([$current_year]);
$cost_by_month = $stmt->fetchAll();
foreach ($cost_by_month as $row) { $cost_data[$row['month'] - 1] = floatval($row['total']); }

// KPI
$stmt = $conn->prepare("SELECT COALESCE(SUM(paid),0) FROM tv_payments WHERE year = ?");
$stmt->execute([$current_year]);
$total_paid = $stmt->fetchColumn() ?: 0;

$stmt = $conn->prepare("SELECT COALESCE(SUM(provider_cost),0) FROM tv_payments WHERE year = ?");
$stmt->execute([$current_year]);
$total_cost = $stmt->fetchColumn() ?: 0;

// Events Pagination Logic
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Count total events
$stmt = $conn->prepare("SELECT COUNT(*) FROM tv_events");
$stmt->execute();
$total_events = $stmt->fetchColumn();
$total_pages = ceil($total_events / $limit);

// Fetch events with limit
$stmt = $conn->prepare("SELECT e.*, c.first_name, c.phone, p.operator 
                        FROM tv_events e 
                        LEFT JOIN tv_clients c ON c.id = e.client_id 
                        LEFT JOIN tv_providers p ON p.id = e.provider_id 
                        ORDER BY e.created_at DESC 
                        LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$events = $stmt->fetchAll();

// Month labels for JS
$months_labels = [
    t('month_jan'), t('month_feb'), t('month_mar'), t('month_apr'),
    t('month_may'), t('month_jun'), t('month_jul'), t('month_aug'),
    t('month_sep'), t('month_oct'), t('month_nov'), t('month_dec')
];
?>
<?php include 'includes/header.php'; ?>
    <div class="p-4">
        <div class="welcome">
            <div class="content rounded-3 p-3">
                <h1 class="fs-3"><?= htmlspecialchars(t('welcome_title')) ?></h1>
                <p class="mb-0"><?= htmlspecialchars(t('hello_user')) ?> <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>.</p>
            </div>
        </div>

        <section class="statistics mt-4">
            <div class="row">
                <div class="col-lg-4">
                    <div class="box d-flex rounded-2 align-items-center mb-4 mb-lg-0 p-3">
                        <i class="uil-users-alt fs-2 text-center bg-primary rounded-circle"></i>
                        <div class="ms-3">
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0"><?= $total_clients ?></h3>
                                <span class="d-block ms-2"><?= htmlspecialchars(t('clients')) ?></span>
                            </div>
                            <p class="fs-normal mb-0"><?= htmlspecialchars(t('clients_stat_desc')) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="box d-flex rounded-2 align-items-center mb-4 mb-lg-0 p-3">
                        <i class="uil-user-exclamation fs-2 text-center bg-danger rounded-circle"></i>
                        <div class="ms-3">
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0"><?= $expiring_soon ?></h3>
                                <span class="d-block ms-2"><?= htmlspecialchars(t('subscriptions')) ?></span>
                            </div>
                            <p class="fs-normal mb-0"><?= htmlspecialchars(t('expiring_soon_desc')) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="box d-flex rounded-2 align-items-center p-3">
                        <i class="uil-euro fs-2 text-center bg-success rounded-circle"></i>
                        <div class="ms-3">
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0"><?= number_format($total_earned, 2) ?></h3>
                                <span class="d-block ms-2">€</span>
                            </div>
                            <p class="fs-normal mb-0"><?= htmlspecialchars(t('earned_in')) ?> <?= $current_year ?> <?= htmlspecialchars(t('year')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-lg-6">
                    <div class="box d-flex rounded-2 align-items-center p-3">
                        <i class="uil-chart-line fs-2 text-center bg-info rounded-circle"></i>
                        <div class="ms-3">
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0"><?= number_format($total_paid, 2) ?></h3>
                                <span class="d-block ms-2"><?= htmlspecialchars(t('income')) ?></span>
                            </div>
                            <p class="fs-normal mb-0"><?= htmlspecialchars(t('for_year')) ?> <?= $current_year ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="box d-flex rounded-2 align-items-center p-3">
                        <i class="uil-bill fs-2 text-center bg-warning rounded-circle"></i>
                        <div class="ms-3">
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0"><?= number_format($total_cost, 2) ?></h3>
                                <span class="d-block ms-2"><?= htmlspecialchars(t('expenses')) ?></span>
                            </div>
                            <p class="fs-normal mb-0"><?= htmlspecialchars(t('for_year')) ?> <?= $current_year ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="charts mt-4">
            <div class="row">
                <div class="col-lg-6">
                    <div class="chart-container rounded-2 p-3">
                        <h3 class="fs-6 mb-3"><?= htmlspecialchars(t('subscriptions_and_renewals')) ?></h3>
                        <canvas id="myChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="chart-container rounded-2 p-3">
                        <h3 class="fs-6 mb-3"><?= htmlspecialchars(t('earnings_monthly')) ?> (€)</h3>
                        <canvas id="myChart2"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <section class="charts mt-4">
            <div class="row">
                <div class="col-12">
                    <div class="chart-container rounded-2 p-3">
                        <h3 class="fs-6 mb-3"><?= htmlspecialchars(t('finance_chart_title')) ?></h3>
                        <canvas id="chartFinance"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <section class="admins mt-4">
            <div class="row">
                <div class="col-md-12">
                    <div class="box">
                        <h4><?= htmlspecialchars(t('subscriptions_expiring_header')) ?></h4>
                        <?php if (empty($expiring_clients)): ?>
                            <p class="text-muted"><?= htmlspecialchars(t('no_expiring_subscriptions')) ?></p>
                        <?php else: ?>
                            <?php foreach ($expiring_clients as $client): ?>
                                <div class="admin d-flex align-items-center rounded-2 p-3 mb-3" style="cursor: pointer;" onclick="window.location.href='pages/tv_clients.php#client-<?= $client['id'] ?>';">
                                    <div class="img">
                                        <div class="rounded-circle bg-<?= $client['days_left'] <= 7 ? 'danger' : ($client['days_left'] <= 14 ? 'warning' : 'info') ?> d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <span class="text-white fw-bold"><?= $client['days_left'] ?><?= htmlspecialchars(t('days_short')) ?></span>
                                        </div>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <h3 class="fs-5 mb-1"><?= htmlspecialchars($client['first_name']) ?></h3>
                                        <p class="mb-1"><?= htmlspecialchars(t('phone')) ?>: <?= htmlspecialchars($client['phone']) ?></p>
                                        <p class="mb-0"><?= htmlspecialchars(t('address')) ?>: <?= htmlspecialchars($client['address']) ?> | <?= htmlspecialchars(t('provider')) ?>: <?= htmlspecialchars($client['operator']) ?></p>
                                    </div>
                                    <?php if (!empty($client['phone'])): ?>
                                        <div class="ms-2">
                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="event.stopPropagation(); sendRenewalSMS('<?= htmlspecialchars($client['phone']) ?>', '<?= htmlspecialchars($client['first_name']) ?>'); return false;" title="<?= htmlspecialchars(t('send_sms')) ?>">
                                                <i class="uil-envelope"></i> SMS
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-4">
            <div class="row">
                <div class="col-md-12">
                    <div class="box">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0"><?= htmlspecialchars(t('event_feed')) ?></h4>
                            <span class="text-muted fs-6"><?= $total_events ?> <?= htmlspecialchars(t('events_total') ?? 'events') ?></span>
                        </div>
                        
                        <?php if (empty($events)): ?>
                            <p class="text-muted"><?= htmlspecialchars(t('no_events')) ?></p>
                        <?php else: ?>
                            <div class="list-group">
                            <?php foreach ($events as $ev): ?>
                                <div class="admin d-flex align-items-center rounded-2 p-2 mb-2 border-bottom">
                                    <div class="img">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"
                                             class="<?= $ev['type']==='payment_recorded' ? 'bg-success' : ($ev['type']==='client_created' ? 'bg-primary' : 'bg-warning') ?>">
                                            <span class="text-white fw-bold fs-6">
                                                <?= $ev['type']==='payment_recorded' ? '€' : ($ev['type']==='client_created' ? '+' : '+M') ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h3 class="fs-6 mb-0 fw-bold">
                                                <?= htmlspecialchars($ev['type']==='payment_recorded' ? t('payment') : ($ev['type']==='client_created' ? t('new_client') : t('renewal'))) ?>
                                            </h3>
                                            <small class="text-muted"><?= htmlspecialchars($ev['created_at']) ?></small>
                                        </div>
                                        <p class="mb-0 small">
                                            <?= htmlspecialchars(t('client')) ?>: <strong><?= htmlspecialchars($ev['first_name'] ?? '') ?></strong>
                                            <?php if (!empty($ev['operator'])): ?> | <?= htmlspecialchars($ev['operator']) ?><?php endif; ?>
                                            <?php if ($ev['type']==='payment_recorded'): ?>
                                                | <span class="text-success fw-bold">€<?= number_format($ev['amount'] ?? 0, 2) ?></span>
                                            <?php elseif ($ev['type']==='subscription_extended'): ?>
                                                | <?= intval($ev['months'] ?? 0) ?> <?= htmlspecialchars(t('mon') ?? 'mon') ?>.
                                            <?php endif; ?>
                                            <?php if (!empty($ev['user'])): ?> | <i class="uil-user"></i> <?= htmlspecialchars($ev['user']) ?><?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Event feed pagination" class="mt-3">
                                <ul class="pagination pagination-sm justify-content-center" style="margin-bottom: 0;">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <?php
                                    $range = 2;
                                    $start_page = max(1, $page - $range);
                                    $end_page = min($total_pages, $page + $range);
                                    
                                    if ($start_page > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                        if ($start_page > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }

                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                                    }

                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                                    }
                                    ?>

                                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        <section class="statis mt-4 text-center">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="box bg-primary p-4">
                        <i class="uil-users-alt fs-1"></i>
                        <h3 class="mt-2"><?= $total_clients ?></h3>
                        <p class="lead"><?= htmlspecialchars(t('clients')) ?></p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="box bg-danger p-4">
                        <i class="uil-tv-retro fs-1"></i>
                        <h3 class="mt-2"><?= $total_providers ?></h3>
                        <p class="lead"><?= htmlspecialchars(t('providers')) ?></p>
                    </div>
                </div>
            </div>
        </section>

        <section class="charts mt-4">
            <div class="chart-container p-3">
                <h3 class="fs-6 mb-3"><?= htmlspecialchars(t('statistics_year')) ?></h3>
                <div style="height: 300px">
                    <canvas id="chart3" width="100%"></canvas>
                </div>
            </div>
        </section>
    </div>
</section>

<script>
    const newClientsData = <?= json_encode($new_clients_data) ?>;
    const renewalsData = <?= json_encode($renewals_data) ?>;
    const earningsData = <?= json_encode($earnings_data) ?>;
    const labels = {
        months: <?= json_encode($months_labels) ?>,
        new_subs: <?= json_encode(t('chart_new_subs')) ?>,
        renewals: <?= json_encode(t('chart_renewals')) ?>,
        earnings: <?= json_encode(t('chart_earnings')) ?>,
        new_clients: <?= json_encode(t('chart_new_clients')) ?>,
        earnings_hundreds: <?= json_encode(t('chart_earnings_hundreds')) ?>,
        income: <?= json_encode(t('income')) ?> + ' (€)',
        expenses: <?= json_encode(t('expenses')) ?> + ' (€)',
        profit: <?= json_encode(t('profit')) ?> + ' (€)',
        confirm_sms: <?= json_encode(t('confirm_sms')) ?>,
        sms_copied: <?= json_encode(t('sms_copied')) ?>,
        error_loading_sms: <?= json_encode(t('error_loading_sms')) ?>,
        number: <?= json_encode(t('number')) ?>,
        text: <?= json_encode(t('text')) ?>,
    };

    // Use labels.months if we have dynamic months, but for now we kept them hardcoded in array or need proper i18n for months.
    // For simplicity, let's just use numeric or English months if we wanted, but the detailed translation is better.
    // For this pass, I will leave the array months hardcoded or switch to numeric/English if easy. 
    // Actually, let's try to inject them if possible, but the original code had them hardcoded in JS.
    const months = labels.months; 

    // График 1: Подписки и продления
    window.addEventListener('load', function() {
    var c1 = document.getElementById('myChart');
    if (c1 && typeof Chart !== 'undefined') new Chart(c1, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: labels.new_subs,
                data: newClientsData,
                backgroundColor: "#0d6efd",
                borderColor: 'transparent',
                borderWidth: 2.5,
                barPercentage: 0.4,
            }, {
                label: labels.renewals,
                data: renewalsData,
                backgroundColor: "#dc3545",
                borderColor: 'transparent',
                borderWidth: 2.5,
                barPercentage: 0.4,
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    gridLines: {},
                    ticks: {
                        beginAtZero: true,
                        stepSize: 1
                    },
                }],
                xAxes: [{
                    gridLines: {
                        display: false,
                    }
                }]
            }
        }
    });

    // График 2: Заработок
    var c2 = document.getElementById('myChart2');
    if (c2 && typeof Chart !== 'undefined') new Chart(c2, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: labels.earnings,
                data: earningsData,
                backgroundColor: 'transparent',
                borderColor: '#28a745',
                lineTension: .4,
                borderWidth: 2,
                pointBackgroundColor: '#28a745',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    gridLines: {
                        drawBorder: false
                    },
                    ticks: {
                        beginAtZero: true,
                        callback: function(value) {
                            return '€' + value;
                        }
                    }
                }],
                xAxes: [{
                    gridLines: {
                        display: false,
                    },
                }]
            }
        }
    });

    // График 3: Комбинированная статистика
    var c3 = document.getElementById('chart3');
    if (c3 && typeof Chart !== 'undefined') new Chart(c3, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: labels.new_clients,
                lineTension: 0.2,
                borderColor: '#0d6efd',
                borderWidth: 2,
                showLine: true,
                data: newClientsData,
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true
            }, {
                label: labels.earnings_hundreds,
                lineTension: 0.2,
                borderColor: '#28a745',
                borderWidth: 2,
                data: earningsData.map(v => v / 100),
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                fill: true
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    gridLines: {
                        drawBorder: false
                    },
                    ticks: {
                        beginAtZero: true
                    }
                }],
                xAxes: [{
                    gridLines: {
                        display: false,
                    },
                }],
            }
        }
    });

    var cf = document.getElementById('chartFinance');
    if (cf && typeof Chart !== 'undefined') new Chart(cf, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: labels.income,
                data: <?= json_encode($paid_data) ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,.1)',
                borderWidth: 2,
                fill: true
            }, {
                label: labels.expenses,
                data: <?= json_encode($cost_data) ?>,
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255,193,7,.1)',
                borderWidth: 2,
                fill: true
            }, {
                label: labels.profit,
                data: <?= json_encode($earnings_data) ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40,167,69,.1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: { beginAtZero: true },
                    gridLines: { drawBorder: false }
                }],
                xAxes: [{ gridLines: { display: false } }]
            }
        }
    });

    // Глобальный поиск
    document.getElementById('global-search').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const query = this.value.trim();
            if (query) {
                window.location.href = 'pages/tv_clients.php?search=' + encodeURIComponent(query);
            }
        }
    });
    });
    
    // Функция отправки SMS (скопирована из tv_clients.php)
    function sendRenewalSMS(phone, clientName) {
        // Удаляем все нецифровые символы из номера телефона
        const cleanPhone = phone.replace(/\D/g, '');
        
        // Загружаем шаблон SMS с сервера
        fetch(`pages/get_sms_template.php?name=${encodeURIComponent(clientName)}&phone=${encodeURIComponent(phone)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const message = data.template;
                    
                    // Проверяем, является ли устройство Android или iOS
                    const isAndroid = /Android/i.test(navigator.userAgent);
                    const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
                    
                    if (isAndroid || isIOS) {
                        // Для мобильных устройств: открываем SMS приложение
                        const smsUri = `sms:${cleanPhone}${isIOS ? '&' : '?'}body=${encodeURIComponent(message)}`;
                        window.location.href = smsUri;
                    } else {
                        // Для десктопа: копируем в буфер обмена
                        if (confirm(`${labels.confirm_sms} ${phone}?\n\n${labels.text}:\n${message}`)) {
                            navigator.clipboard.writeText(message).then(() => {
                                alert(`${labels.sms_copied}\n${labels.number}: ${phone}`);
                            }).catch(() => {
                                alert(`${labels.number}: ${phone}\n${message}`);
                            });
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(labels.error_loading_sms);
            });
    }
</script>
<?php include 'includes/footer.php'; ?>
