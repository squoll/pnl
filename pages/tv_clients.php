<?php
// tv_clients.php - управление клиентами
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../includes/auth.php';
// i18n.php included via header.php usually, but for safety:
require_once '../includes/i18n.php';

// Проверяем авторизацию
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Получаем провайдеров для выпадающего списка
$providers = [];
try {
    $stmt = $conn->query("SELECT * FROM tv_providers ORDER BY operator");
    $providers = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = t('error_loading_providers') . ": " . $e->getMessage();
}

// Обработка поиска
$clients = [];
$search = trim($_GET['search'] ?? '');
$sort_by = $_GET['sort'] ?? 'subscription_date';
$sort_order = $_GET['order'] ?? 'DESC';

// Валидация сортировки
$allowed_sort = ['subscription_date', 'months', 'first_name', 'status'];
if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'subscription_date';
}
if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

try {
    $sql = "SELECT c.*, p.operator FROM tv_clients c LEFT JOIN tv_providers p ON c.provider_id = p.id";
    $params = [];
    
    if ($search !== '') {
        $digits = preg_replace('/\D+/', '', $search);
        $sql .= " WHERE (LOWER(c.first_name) LIKE LOWER(?) OR LOWER(c.address) LIKE LOWER(?) OR LOWER(c.login) LIKE LOWER(?)";
        if ($digits !== '') {
            $sql .= " OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(c.phone,' ',''),'-',''),'+',''),'(',')'),')','') LIKE ?";
        }
        $sql .= ")";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        if ($digits !== '') {
            $params[] = "%$digits%";
        }
    }
    
    // Сортировка
    if ($sort_by === 'status') {
        $sql .= " ORDER BY DATE_ADD(c.subscription_date, INTERVAL c.months MONTH) " . $sort_order;
    } else {
        $sql .= " ORDER BY c." . $sort_by . " " . $sort_order;
    }
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $conn->query($sql);
    }
    $clients = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = t('error_loading_clients') . ": " . $e->getMessage();
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="clients.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [t('name'), t('phone'), t('address'), t('provider'), t('subscription_date'), t('months'), t('login_label'), t('password_label'), t('devices'), t('viewing_program_label'), t('paid_eur_short'), t('provider_cost_eur_short'), t('my_earned_eur_short')]);
    foreach ($clients as $c) {
        fputcsv($out, [
            $c['first_name'], $c['phone'], $c['address'], (isset($c['operator']) ? $c['operator'] : ''), $c['subscription_date'], $c['months'],
            $c['login'], $c['password'], $c['device_count'], $c['viewing_program'], $c['paid'], $c['provider_cost'], $c['earned']
        ]);
    }
    fclose($out);
    exit();
}

// Обработка добавления клиента
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_valid = false;
    if (isset($_POST['csrf_token'])) {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $postedToken = $_POST['csrf_token'];
        if (function_exists('hash_equals')) {
            $csrf_valid = hash_equals($sessionToken, $postedToken);
        } else {
            $csrf_valid = ($sessionToken === $postedToken);
        }
    }
    if (!$csrf_valid) {
        $error = t('invalid_csrf_token');
    } else {
    if (isset($_POST['add_client'])) {
        $first_name = trim(isset($_POST['first_name']) ? $_POST['first_name'] : '');
        $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
        $address = trim(isset($_POST['address']) ? $_POST['address'] : '');
        $provider_id = intval(isset($_POST['provider_id']) ? $_POST['provider_id'] : 0);
        $subscription_date = isset($_POST['subscription_date']) ? $_POST['subscription_date'] : date('Y-m-d');
        $months = intval(isset($_POST['months']) ? $_POST['months'] : 12);
        $login = trim(isset($_POST['login']) ? $_POST['login'] : '');
        $password = trim(isset($_POST['password']) ? $_POST['password'] : '');
        $device_count = intval(isset($_POST['device_count']) ? $_POST['device_count'] : 1);
        $viewing_program = trim(isset($_POST['viewing_program']) ? $_POST['viewing_program'] : '');
        $paid = floatval(isset($_POST['paid']) ? $_POST['paid'] : 0);
        $provider_cost = floatval(isset($_POST['provider_cost']) ? $_POST['provider_cost'] : 0);
        $earned = $paid - $provider_cost;
        
        if (empty($first_name) || empty($phone)) {
            $error = t('name_phone_required');
        } else {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO tv_clients 
                    (first_name, phone, address, provider_id, subscription_date, months, login, password, device_count, viewing_program, paid, provider_cost, earned) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $first_name, $phone, $address, $provider_id, $subscription_date, $months, 
                    $login, $password, $device_count, $viewing_program, $paid, $provider_cost, $earned
                ]);
                $clientId = $conn->lastInsertId();
                
                // Записываем платеж в tv_payments для графиков
                try {
                    $conn->exec("CREATE TABLE IF NOT EXISTS tv_payments (id INT AUTO_INCREMENT PRIMARY KEY, client_id INT NOT NULL, year INT NOT NULL, paid DECIMAL(10,2) NOT NULL, provider_cost DECIMAL(10,2) NOT NULL, earned DECIMAL(10,2) NOT NULL, created_at DATETIME NOT NULL, INDEX(client_id), INDEX(year))");
                    if ($paid > 0 || $provider_cost > 0 || $earned > 0) {
                        $stmtPay = $conn->prepare("INSERT INTO tv_payments (client_id, year, paid, provider_cost, earned, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmtPay->execute([$clientId, intval(date('Y', strtotime($subscription_date))), $paid, $provider_cost, $earned, $subscription_date . ' 00:00:00']);
                    }
                } catch(PDOException $ePay) {
                    // Игнорируем ошибки создания таблицы
                }
                
                // Записываем события
                try {
                    $conn->exec("CREATE TABLE IF NOT EXISTS tv_events (id INT AUTO_INCREMENT PRIMARY KEY, type VARCHAR(32) NOT NULL, client_id INT, provider_id INT, amount DECIMAL(10,2), months INT, metadata TEXT, user VARCHAR(64), created_at DATETIME NOT NULL, INDEX(client_id), INDEX(type), INDEX(created_at))");
                    $stmtEv = $conn->prepare("INSERT INTO tv_events (type, client_id, provider_id, amount, months, metadata, user, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtEv->execute(['client_created', $clientId, $provider_id ?: null, null, $months, json_encode(['phone'=>$phone,'address'=>$address]), (isset($_SESSION['username']) ? $_SESSION['username'] : null), date('Y-m-d H:i:s')]);
                    if ($paid > 0 || $provider_cost > 0 || $earned > 0) {
                        $stmtEv->execute(['payment_recorded', $clientId, $provider_id ?: null, $earned, null, json_encode(['paid'=>$paid,'provider_cost'=>$provider_cost]), (isset($_SESSION['username']) ? $_SESSION['username'] : null), date('Y-m-d H:i:s')]);
                    }
                } catch(PDOException $eEv) {
                    // Игнорируем ошибки событий
                }
                
                $success = t('client_added_success');
                
                // Обновляем список клиентов
                $stmt = $conn->query(
                    "
                    SELECT c.*, p.operator 
                    FROM tv_clients c 
                    LEFT JOIN tv_providers p ON c.provider_id = p.id 
                    ORDER BY c.id DESC
                "
                );
                $clients = $stmt->fetchAll();
            } catch(PDOException $e) {
                $error = t('error_add_client') . ": " . $e->getMessage();
            }
        }
    }
    
    // Обработка обновления клиента
    if (isset($_POST['update_client'])) {
        $client_id = intval(isset($_POST['client_id']) ? $_POST['client_id'] : 0);
        $first_name = trim(isset($_POST['first_name']) ? $_POST['first_name'] : '');
        $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
        $address = trim(isset($_POST['address']) ? $_POST['address'] : '');
        $provider_id = intval(isset($_POST['provider_id']) ? $_POST['provider_id'] : 0);
        $subscription_date = isset($_POST['subscription_date']) ? $_POST['subscription_date'] : date('Y-m-d');
        $months = intval(isset($_POST['months']) ? $_POST['months'] : 12);
        $login = trim(isset($_POST['login']) ? $_POST['login'] : '');
        $password = trim(isset($_POST['password']) ? $_POST['password'] : '');
        $device_count = intval(isset($_POST['device_count']) ? $_POST['device_count'] : 1);
        $viewing_program = trim(isset($_POST['viewing_program']) ? $_POST['viewing_program'] : '');
        $paid = floatval(isset($_POST['paid']) ? $_POST['paid'] : 0);
        $provider_cost = floatval(isset($_POST['provider_cost']) ? $_POST['provider_cost'] : 0);
        $earned = $paid - $provider_cost;
        
        try {
            // Получаем старые значения ДО обновления для определения разницы
            $stmt = $conn->prepare("SELECT paid, provider_cost, earned FROM tv_clients WHERE id = ?");
            $stmt->execute([$client_id]);
            $old_client = $stmt->fetch();
            
            $stmt = $conn->prepare("
                UPDATE tv_clients SET 
                first_name = ?, phone = ?, address = ?, provider_id = ?, subscription_date = ?, months = ?, 
                login = ?, password = ?, device_count = ?, viewing_program = ?, paid = ?, provider_cost = ?, earned = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $first_name, $phone, $address, $provider_id, $subscription_date, $months, 
                $login, $password, $device_count, $viewing_program, $paid, $provider_cost, $earned, $client_id
            ]);
            
            // Если финансовые данные изменились, записываем разницу в tv_payments
            if ($old_client && ($old_client['paid'] != $paid || $old_client['provider_cost'] != $provider_cost)) {
                $payment_diff_paid = $paid - $old_client['paid'];
                $payment_diff_cost = $provider_cost - $old_client['provider_cost'];
                $payment_diff_earned = $earned - $old_client['earned'];
                
                // Записываем изменение в tv_payments только если есть разница
                if ($payment_diff_paid != 0 || $payment_diff_cost != 0) {
                    try {
                        $conn->exec("CREATE TABLE IF NOT EXISTS tv_payments (id INT AUTO_INCREMENT PRIMARY KEY, client_id INT NOT NULL, year INT NOT NULL, paid DECIMAL(10,2) NOT NULL, provider_cost DECIMAL(10,2) NOT NULL, earned DECIMAL(10,2) NOT NULL, created_at DATETIME NOT NULL, INDEX(client_id), INDEX(year))");
                        $stmtPay = $conn->prepare("INSERT INTO tv_payments (client_id, year, paid, provider_cost, earned, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmtPay->execute([$client_id, intval(date('Y')), $payment_diff_paid, $payment_diff_cost, $payment_diff_earned, date('Y-m-d H:i:s')]);
                    } catch(PDOException $ePay) {
                        // Игнорируем ошибки создания таблицы
                    }
                    
                    try {
                        $conn->exec("CREATE TABLE IF NOT EXISTS tv_events (id INT AUTO_INCREMENT PRIMARY KEY, type VARCHAR(32) NOT NULL, client_id INT, provider_id INT, amount DECIMAL(10,2), months INT, metadata TEXT, user VARCHAR(64), created_at DATETIME NOT NULL, INDEX(client_id), INDEX(type), INDEX(created_at))");
                        $stmtEv = $conn->prepare("INSERT INTO tv_events (type, client_id, provider_id, amount, months, metadata, user, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmtEv->execute(['payment_updated', $client_id, $provider_id ?: null, $payment_diff_earned, null, json_encode(['paid'=>$payment_diff_paid,'provider_cost'=>$payment_diff_cost,'old_paid'=>$old_client['paid'],'new_paid'=>$paid]), (isset($_SESSION['username']) ? $_SESSION['username'] : null), date('Y-m-d H:i:s')]);
                    } catch(PDOException $eEv) {
                        // Игнорируем ошибки событий
                    }
                }
            }
            
            $success = t('client_updated_success');
            
            // Обновляем список клиентов
            $stmt = $conn->query(
                "
                SELECT c.*, p.operator 
                FROM tv_clients c 
                LEFT JOIN tv_providers p ON c.provider_id = p.id 
                ORDER BY c.subscription_date DESC
            "
            );
            $clients = $stmt->fetchAll();
        } catch(PDOException $e) {
            $error = t('error_update_client') . ": " . $e->getMessage();
        }
    }
    
    // Обработка продления подписки
    if (isset($_POST['extend_subscription'])) {
        $client_id = intval(isset($_POST['client_id']) ? $_POST['client_id'] : 0);
        $months_to_add = intval(isset($_POST['months_to_add']) ? $_POST['months_to_add'] : 1);
        $renewal_paid = floatval(isset($_POST['renewal_paid']) ? $_POST['renewal_paid'] : 0);
        $renewal_provider_cost = floatval(isset($_POST['renewal_provider_cost']) ? $_POST['renewal_provider_cost'] : 0);
        $renewal_earned = $renewal_paid - $renewal_provider_cost;
        
        try {
            $stmt = $conn->prepare("SELECT months, paid, provider_cost, earned FROM tv_clients WHERE id = ?");
            $stmt->execute([$client_id]);
            $client = $stmt->fetch();
            if ($client) {
                $new_months = $client['months'] + $months_to_add;
                $new_paid = $client['paid'] + $renewal_paid;
                $new_cost = $client['provider_cost'] + $renewal_provider_cost;
                $new_earned = $client['earned'] + $renewal_earned;
                
                $stmt = $conn->prepare("UPDATE tv_clients SET months = ?, paid = ?, provider_cost = ?, earned = ? WHERE id = ?");
                $stmt->execute([$new_months, $new_paid, $new_cost, $new_earned, $client_id]);

                try {
                    $conn->exec("CREATE TABLE IF NOT EXISTS tv_payments (id INT AUTO_INCREMENT PRIMARY KEY, client_id INT NOT NULL, year INT NOT NULL, paid DECIMAL(10,2) NOT NULL, provider_cost DECIMAL(10,2) NOT NULL, earned DECIMAL(10,2) NOT NULL, created_at DATETIME NOT NULL, INDEX(client_id), INDEX(year))");
                    $stmtPay = $conn->prepare("INSERT INTO tv_payments (client_id, year, paid, provider_cost, earned, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmtPay->execute([$client_id, intval(date('Y')), $renewal_paid, $renewal_provider_cost, $renewal_earned, date('Y-m-d H:i:s')]);
                } catch(PDOException $ePay) {
                }

                try {
                    $conn->exec("CREATE TABLE IF NOT EXISTS tv_events (id INT AUTO_INCREMENT PRIMARY KEY, type VARCHAR(32) NOT NULL, client_id INT, provider_id INT, amount DECIMAL(10,2), months INT, metadata TEXT, user VARCHAR(64), created_at DATETIME NOT NULL, INDEX(client_id), INDEX(type), INDEX(created_at))");
                    $stmtEv = $conn->prepare("INSERT INTO tv_events (type, client_id, provider_id, amount, months, metadata, user, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtEv->execute(['subscription_extended', $client_id, null, null, $months_to_add, json_encode(['paid'=>$renewal_paid,'provider_cost'=>$renewal_provider_cost]), (isset($_SESSION['username']) ? $_SESSION['username'] : null), date('Y-m-d H:i:s')]);
                    if ($renewal_paid > 0 || $renewal_provider_cost > 0) {
                        $stmtEv->execute(['payment_recorded', $client_id, null, $renewal_earned, null, json_encode(['paid'=>$renewal_paid,'provider_cost'=>$renewal_provider_cost]), (isset($_SESSION['username']) ? $_SESSION['username'] : null), date('Y-m-d H:i:s')]);
                    }
                } catch(PDOException $eEv) {
                }

                $success = t('subscription_extended_success');
                
                $stmt = $conn->query(
                    "SELECT c.*, p.operator FROM tv_clients c LEFT JOIN tv_providers p ON c.provider_id = p.id ORDER BY c.subscription_date DESC"
                );
                $clients = $stmt->fetchAll();
            }
        } catch(PDOException $e) {
            $error = t('error_extend_subscription') . ': ' . $e->getMessage();
        }
    }
    }
}

// Получаем данные клиента для редактирования
$edit_client = null;
if (isset($_GET['edit'])) {
    $client_id = intval($_GET['edit']);
    try {
        $stmt = $conn->prepare("SELECT * FROM tv_clients WHERE id = ?");
        $stmt->execute([$client_id]);
        $edit_client = $stmt->fetch();
    } catch(PDOException $e) {
        $error = t('error_client_load') . ": " . $e->getMessage();
    }
}
?>

<?php include '../includes/header.php'; ?>
<style>
        .accordiontv {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            padding: 12px;
            border: none;
            text-align: left;
            width: 100%;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .panel {
            padding: 20px;
            display: none;
            background-color: #2a2b3d;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .table {
            color: #ffffff !important;
        }
        .table th {
            background-color: #374151;
            color: white;
            border-color: #4b5563;
        }
        .table td {
            border-color: #4b5563;
            color: #ffffff !important;
        }
        .badge-expiring {
            background-color: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .modal-content {
            background-color: #2a2b3d;
            color: white;
        }
        .form-control, .form-select {
            background-color: #374151;
            border-color: #4b5563;
            color: white;
        }
        .form-control:focus, .form-select:focus {
            background-color: #374151;
            border-color: #007bff;
            color: white;
        }
        label {
            color: #d1d5db;
        }
        .card {
            background-color: #2a2b3d;
            color: white;
        }
        .card-header {
            background-color: #374151;
            border-color: #4b5563;
            color: white;
        }
        .alert {
            color: white;
        }
        .alert-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .alert-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }
    </style>
    <div class="p-4">
        <?php if (!isset($_SESSION['csrf_token'])) {
            if (function_exists('random_bytes')) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
            } elseif (function_exists('openssl_random_pseudo_bytes')) {
                $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(16));
            } else {
                $_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true));
            }
        } ?>
        <div class="welcome mb-4">
            <div class="content rounded-3 p-3">
                <h1 class="fs-3"><?= htmlspecialchars(t('clients_manage')) ?></h1>
                <p class="mb-0"><?= htmlspecialchars(t('clients_total')) ?>: <strong><?= count($clients) ?></strong></p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= htmlspecialchars(t('search_clients_placeholder')) ?></h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="<?= htmlspecialchars(t('quick_search_placeholder')) ?>" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><?= htmlspecialchars(t('apply_filters')) ?></button>
                    </div>
                    <?php if ($search !== ''): ?>
                        <div class="col-12">
                            <a href="tv_clients.php" class="btn btn-secondary btn-sm"><?= htmlspecialchars(t('reset_filters')) ?></a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Добавление клиента -->
        <button class="accordiontv">➕ <?= htmlspecialchars(t('add_new_client')) ?></button>
        <div class="panel">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><?= htmlspecialchars(t('name_required')) ?></label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= htmlspecialchars(t('phone_required')) ?></label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?= htmlspecialchars(t('address')) ?></label>
                        <input type="text" name="address" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= htmlspecialchars(t('provider')) ?></label>
                        <select name="provider_id" class="form-select">
                            <option value=""><?= htmlspecialchars(t('select_provider')) ?></option>
                            <?php foreach ($providers as $provider): ?>
                                <option value="<?= $provider['id'] ?>"><?= htmlspecialchars($provider['operator']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><?= htmlspecialchars(t('subscription_date')) ?></label>
                        <input type="date" name="subscription_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><?= htmlspecialchars(t('months')) ?></label>
                        <input type="number" name="months" class="form-control" value="12" min="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= htmlspecialchars(t('login_label')) ?></label>
                        <input type="text" name="login" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= htmlspecialchars(t('password_label')) ?></label>
                        <input type="text" name="password" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?= htmlspecialchars(t('devices')) ?></label>
                        <input type="number" name="device_count" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label"><?= htmlspecialchars(t('viewing_program_label')) ?></label>
                        <input type="text" name="viewing_program" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?= htmlspecialchars(t('paid_eur')) ?></label>
                        <input type="number" step="0.01" name="paid" class="form-control" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?= htmlspecialchars(t('provider_cost_eur')) ?></label>
                        <input type="number" step="0.01" name="provider_cost" class="form-control" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?= htmlspecialchars(t('my_earned_eur')) ?></label>
                        <input type="number" step="0.01" name="earned" class="form-control" value="0" readonly>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="add_client" class="btn btn-success w-100"><?= htmlspecialchars(t('add_client_btn')) ?></button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Список клиентов -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?= htmlspecialchars(t('clients_list')) ?></h5>
            </div>
            <div class="card-body">
                <?php if (empty($clients)): ?>
                    <p class="text-center"><?= htmlspecialchars(t('clients_not_found')) ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>
                                        <a href="?sort=first_name&order=<?= ($sort_by === 'first_name' && $sort_order === 'ASC') ? 'DESC' : 'ASC' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="text-decoration-none text-white">
                                            <?= htmlspecialchars(t('name')) ?> <?= $sort_by === 'first_name' ? ($sort_order === 'ASC' ? '▲' : '▼') : '' ?>
                                        </a>
                                    </th>
                                    <th><?= htmlspecialchars(t('phone')) ?></th>
                                    <th><?= htmlspecialchars(t('address')) ?></th>
                                    <th><?= htmlspecialchars(t('provider')) ?></th>
                                    <th>
                                        <a href="?sort=subscription_date&order=<?= ($sort_by === 'subscription_date' && $sort_order === 'ASC') ? 'DESC' : 'ASC' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="text-decoration-none text-white">
                                            <?= htmlspecialchars(t('subscription_date')) ?> <?= $sort_by === 'subscription_date' ? ($sort_order === 'ASC' ? '▲' : '▼') : '' ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="?sort=months&order=<?= ($sort_by === 'months' && $sort_order === 'ASC') ? 'DESC' : 'ASC' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="text-decoration-none text-white">
                                            <?= htmlspecialchars(t('months')) ?> <?= $sort_by === 'months' ? ($sort_order === 'ASC' ? '▲' : '▼') : '' ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="?sort=status&order=<?= ($sort_by === 'status' && $sort_order === 'ASC') ? 'DESC' : 'ASC' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="text-decoration-none text-white">
                                            <?= htmlspecialchars(t('status')) ?> <?= $sort_by === 'status' ? ($sort_order === 'ASC' ? '▲' : '▼') : '' ?>
                                        </a>
                                    </th>
                                    <th><?= htmlspecialchars(t('actions')) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                    <?php
                                    $end_date = new DateTime($client['subscription_date']);
                                    $end_date->modify("+{$client['months']} months");
                                    $now = new DateTime();
                                    $days_left = $now->diff($end_date)->days;
                                    $is_past = $now > $end_date;
                                    
                                    // Рассчитываем количество месяцев просрочки
                                    if ($is_past) {
                                        $interval = $end_date->diff($now);
                                        $overdue_months = $interval->y * 12 + $interval->m;
                                        $overdue_days = $interval->d;
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($client['first_name']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($client['phone']) ?></td>
                                        <td><?= htmlspecialchars($client['address']) ?></td>
                                        <td><?= htmlspecialchars(isset($client['operator']) ? $client['operator'] : t('none')) ?></td>
                                        <td><?= htmlspecialchars($client['subscription_date']) ?></td>
                                        <td><?= $client['months'] ?></td>
                                        <td>
                                            <?php if ($is_past): ?>
                                                <span class="badge bg-danger">
                                                    <?= htmlspecialchars(t('expired')) ?> 
                                                    <?php if ($overdue_months > 0): ?>
                                                        -<?= $overdue_months ?> <?= t('months_suffix') ?> <?= $overdue_days ?> <?= t('days') ?>
                                                    <?php else: ?>
                                                        -<?= $overdue_days ?> <?= t('days') ?>
                                                    <?php endif; ?>
                                                </span>
                                            <?php elseif ($days_left <= 30): ?>
                                                <span class="badge bg-warning text-dark"><?= $days_left ?> <?= htmlspecialchars(t('days')) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?= htmlspecialchars(t('active')) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-warning" onclick="editClient(<?= $client['id'] ?>)"><?= htmlspecialchars(t('edit')) ?></button>
                                                <button class="btn btn-sm btn-info" onclick="extendSubscription(<?= $client['id'] ?>)"><?= htmlspecialchars(t('extend')) ?></button>
                                                <button class="btn btn-sm btn-primary" onclick="viewClient(<?= $client['id'] ?>)"><?= htmlspecialchars(t('view')) ?></button>
                                                <?php if (!empty($client['login'])): ?>
                                                    <button class="btn btn-sm btn-outline-secondary copy-link" data-link="<?= htmlspecialchars($client['login']) ?>" title="<?= htmlspecialchars(t('copy_login')) ?>"><?= htmlspecialchars(t('login_short')) ?></button>
                                                <?php endif; ?>
                                                <?php if (!empty($client['password'])): ?>
                                                    <button class="btn btn-sm btn-outline-secondary copy-link" data-link="<?= htmlspecialchars($client['password']) ?>" title="<?= htmlspecialchars(t('copy_password')) ?>"><?= htmlspecialchars(t('password_short')) ?></button>
                                                <?php endif; ?>
                                                <?php if (!empty($client['address'])): ?>
                                                    <button class="btn btn-sm btn-outline-secondary copy-link" data-link="<?= htmlspecialchars($client['address']) ?>" title="<?= htmlspecialchars(t('copy_address')) ?>"><?= htmlspecialchars(t('address_short')) ?></button>
                                                <?php endif; ?>
                                                <?php if (!empty($client['address'])): ?>
                                                    <a class="btn btn-sm btn-outline-info" href="map.php?client_id=<?= $client['id'] ?>" title="<?= htmlspecialchars(t('open_map')) ?>"><?= htmlspecialchars(t('map')) ?></a>
                                                <?php endif; ?>
                                                <?php if (($is_past || $days_left <= 30) && !empty($client['phone'])): ?>
                                                    <button class="btn btn-sm btn-outline-success" onclick="sendRenewalSMS('<?= htmlspecialchars($client['phone']) ?>', '<?= htmlspecialchars($client['first_name']) ?>')" title="<?= htmlspecialchars(t('send_sms')) ?>">
                                                        <i class="uil-envelope"></i> SMS
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Модальное окно для редактирования -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars(t('edit_client')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="client_id" id="edit_client_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars(t('name_required')) ?></label>
                            <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars(t('phone_required')) ?></label>
                            <input type="text" name="phone" id="edit_phone" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?= htmlspecialchars(t('address')) ?></label>
                            <input type="text" name="address" id="edit_address" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars(t('provider')) ?></label>
                            <select name="provider_id" id="edit_provider_id" class="form-select">
                                <option value=""><?= htmlspecialchars(t('select_provider')) ?></option>
                                <?php foreach ($providers as $provider): ?>
                                    <option value="<?= $provider['id'] ?>"><?= htmlspecialchars($provider['operator']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?= htmlspecialchars(t('subscription_date')) ?></label>
                            <input type="date" name="subscription_date" id="edit_subscription_date" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?= htmlspecialchars(t('months')) ?></label>
                            <input type="number" name="months" id="edit_months" class="form-control" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars(t('login_label')) ?></label>
                            <input type="text" name="login" id="edit_login" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars(t('password_label')) ?></label>
                            <input type="text" name="password" id="edit_password" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= htmlspecialchars(t('devices')) ?></label>
                            <input type="number" name="device_count" id="edit_device_count" class="form-control" min="1">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label"><?= htmlspecialchars(t('viewing_program_label')) ?></label>
                            <input type="text" name="viewing_program" id="edit_viewing_program" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= htmlspecialchars(t('paid_eur')) ?></label>
                            <input type="number" step="0.01" name="paid" id="edit_paid" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= htmlspecialchars(t('provider_cost_eur')) ?></label>
                            <input type="number" step="0.01" name="provider_cost" id="edit_provider_cost" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= htmlspecialchars(t('my_earned_eur')) ?></label>
                            <input type="number" step="0.01" name="earned" id="edit_earned" class="form-control" readonly>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(t('cancel')) ?></button>
                <button type="submit" form="editForm" name="update_client" class="btn btn-success"><?= htmlspecialchars(t('save')) ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для продления -->
<div class="modal fade" id="extendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars(t('extend_subscription_title')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="extendForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="client_id" id="extend_client_id">
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(t('months_to_extend')) ?></label>
                        <input type="number" name="months_to_add" class="form-control" value="1" min="1" max="36">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><?= htmlspecialchars(t('paid_eur')) ?></label>
                            <input type="number" step="0.01" name="renewal_paid" id="extend_paid" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= htmlspecialchars(t('provider_cost_eur')) ?></label>
                            <input type="number" step="0.01" name="renewal_provider_cost" id="extend_provider_cost" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= htmlspecialchars(t('my_earned_eur')) ?></label>
                            <input type="number" step="0.01" id="extend_earned" class="form-control" readonly>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(t('cancel')) ?></button>
                <button type="submit" form="extendForm" name="extend_subscription" class="btn btn-primary"><?= htmlspecialchars(t('extend_btn')) ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для просмотра -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars(t('client_info')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="clientInfo">
                <!-- Данные будут загружены через AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(t('close')) ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    // Аккордеон
    function toggleAccordion(button) {
        const panel = button.nextElementSibling;
        if (panel.style.display === "block") {
            panel.style.display = "none";
        } else {
            panel.style.display = "block";
        }
    }

    // Расчет заработка
    document.addEventListener('DOMContentLoaded', function() {
        // Для формы добавления
        const paidInput = document.querySelector('input[name="paid"]');
        const costInput = document.querySelector('input[name="provider_cost"]');
        const earnedInput = document.querySelector('input[name="earned"]');
        
        function calculateEarned() {
            const paid = parseFloat(paidInput.value) || 0;
            const cost = parseFloat(costInput.value) || 0;
            earnedInput.value = (paid - cost).toFixed(2);
        }
        
        if (paidInput && costInput && earnedInput) {
            paidInput.addEventListener('input', calculateEarned);
            costInput.addEventListener('input', calculateEarned);
        }
        
        // Для формы редактирования
        const editPaidInput = document.getElementById('edit_paid');
        const editCostInput = document.getElementById('edit_provider_cost');
        const editEarnedInput = document.getElementById('edit_earned');
        
        if (editPaidInput && editCostInput && editEarnedInput) {
            editPaidInput.addEventListener('input', function() {
                const paid = parseFloat(this.value) || 0;
                const cost = parseFloat(editCostInput.value) || 0;
                editEarnedInput.value = (paid - cost).toFixed(2);
            });
            
            editCostInput.addEventListener('input', function() {
                const paid = parseFloat(editPaidInput.value) || 0;
                const cost = parseFloat(this.value) || 0;
                editEarnedInput.value = (paid - cost).toFixed(2);
            });
        }
        const extendPaid = document.getElementById('extend_paid');
        const extendCost = document.getElementById('extend_provider_cost');
        const extendEarned = document.getElementById('extend_earned');
        function calcExtendEarned() {
            const paid = parseFloat(extendPaid?.value) || 0;
            const cost = parseFloat(extendCost?.value) || 0;
            if (extendEarned) extendEarned.value = (paid - cost).toFixed(2);
        }
        if (extendPaid && extendCost && extendEarned) {
            extendPaid.addEventListener('input', calcExtendEarned);
            extendCost.addEventListener('input', calcExtendEarned);
        }
        
        // Поиск по Enter
        const searchInput = document.getElementById('global-search');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    window.location.href = 'tv_clients.php?search=' + encodeURIComponent(this.value);
                }
            });
        }
        
        // Открыть модальное окно редактирования если есть GET параметр edit
        <?php if (isset($_GET['edit'])): ?>
            editClient(<?= intval($_GET['edit']) ?>);
        <?php endif; ?>
    });
    
    // Редактирование клиента
    function editClient(clientId) {
        fetch('get_client.php?id=' + clientId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const client = data.client;
                    document.getElementById('edit_client_id').value = client.id;
                    document.getElementById('edit_first_name').value = client.first_name;
                    document.getElementById('edit_phone').value = client.phone;
                    document.getElementById('edit_address').value = client.address || '';
                    document.getElementById('edit_provider_id').value = client.provider_id || '';
                    document.getElementById('edit_subscription_date').value = client.subscription_date;
                    document.getElementById('edit_months').value = client.months;
                    document.getElementById('edit_login').value = client.login || '';
                    document.getElementById('edit_password').value = client.password || '';
                    document.getElementById('edit_device_count').value = client.device_count;
                    document.getElementById('edit_viewing_program').value = client.viewing_program || '';
                    document.getElementById('edit_paid').value = client.paid;
                    document.getElementById('edit_provider_cost').value = client.provider_cost;
                    document.getElementById('edit_earned').value = (client.paid - client.provider_cost).toFixed(2);
                    
                    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                    editModal.show();
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Продление подписки
    function extendSubscription(clientId) {
        document.getElementById('extend_client_id').value = clientId;
        const extendModal = new bootstrap.Modal(document.getElementById('extendModal'));
        extendModal.show();
    }
    
    // Просмотр клиента
    function viewClient(clientId) {
        fetch('get_client.php?id=' + clientId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const client = data.client;
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><?= t('name') ?>:</strong> ${client.first_name}</p>
                                <p><strong><?= t('phone') ?>:</strong> ${client.phone}</p>
                                <p><strong><?= t('address') ?>:</strong> ${client.address || '<?= t('none') ?>'}</p>
                                <p><strong><?= t('provider') ?>:</strong> ${client.operator || '<?= t('none') ?>'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><?= t('subscription_date') ?>:</strong> ${client.subscription_date}</p>
                                <p><strong><?= t('subscription_term') ?>:</strong> ${client.months} <?= t('months_suffix') ?></p>
                                <p><strong><?= t('login_label') ?>:</strong> ${client.login || '<?= t('none') ?>'}</p>
                                <p><strong><?= t('password_label') ?>:</strong> ${client.password || '<?= t('none') ?>'}</p>
                            </div>
                            <div class="col-12">
                                <hr>
                                <p><strong><?= t('device_count') ?>:</strong> ${client.device_count}</p>
                                <p><strong><?= t('viewing_program_label') ?>:</strong> ${client.viewing_program || '<?= t('none') ?>'}</p>
                                <p><strong><?= t('paid_eur') ?>:</strong> €${parseFloat(client.paid).toFixed(2)}</p>
                                <p><strong><?= t('provider_cost_eur') ?>:</strong> €${parseFloat(client.provider_cost).toFixed(2)}</p>
                                <p><strong><?= t('my_earned_eur') ?>:</strong> €${parseFloat(client.earned).toFixed(2)}</p>
                            </div>
                        </div>
                    `;
                    document.getElementById('clientInfo').innerHTML = html;
                    const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
                    viewModal.show();
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Отправка SMS напоминания о продлении
    function sendRenewalSMS(phone, clientName) {
        // Удаляем все нецифровые символы из номера телефона
        const cleanPhone = phone.replace(/\D/g, '');
        
        // Загружаем шаблон SMS с сервера
        fetch(`get_sms_template.php?name=${encodeURIComponent(clientName)}&phone=${encodeURIComponent(phone)}`)
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
                        if (confirm(`<?= t('confirm_sms') ?> ${phone}?\n\n<?= t('text') ?>:\n${message}`)) {
                            navigator.clipboard.writeText(message).then(() => {
                                alert(`<?= t('sms_copied') ?>\n<?= t('number') ?>: ${phone}`);
                            }).catch(() => {
                                alert(`<?= t('number') ?>: ${phone}\n${message}`);
                            });
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('<?= t('error_loading_sms') ?>');
            });
    }
</script>
<?php include '../includes/footer.php'; ?>
