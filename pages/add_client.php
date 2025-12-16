<?php
// template.php - шаблон для всех страниц
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../includes/auth.php';
// i18n.php will be handled by header.php or is safe to include multiple times now
require_once '../includes/i18n.php';

// Проверяем авторизацию
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}
?>
        <?php

// Получаем список провайдеров
$query_providers = "SELECT * FROM tv_providers ORDER BY operator";
$providers = $conn->query($query_providers)->fetchAll();

// Обработка формы
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_valid = false;
    if (isset($_POST['csrf_token'])) {
        $sessionToken = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
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
        $first_name = trim(isset($_POST['first_name']) ? $_POST['first_name'] : '');
        $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
        $address = trim(isset($_POST['address']) ? $_POST['address'] : '');
        $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
        $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;
        $provider_id = intval(isset($_POST['provider_id']) ? $_POST['provider_id'] : 0);
        $subscription_date = trim(isset($_POST['subscription_date']) ? $_POST['subscription_date'] : date('Y-m-d'));
        $months = intval(isset($_POST['months']) ? $_POST['months'] : 0);
        $login = trim(isset($_POST['login']) ? $_POST['login'] : '');
        $password = trim(isset($_POST['password']) ? $_POST['password'] : '');
        $device_count = intval(isset($_POST['device_count']) ? $_POST['device_count'] : 0);
        $viewing_program = trim(isset($_POST['viewing_program']) ? $_POST['viewing_program'] : '');
        $paid = floatval(isset($_POST['paid']) ? $_POST['paid'] : 0);
        $provider_cost = floatval(isset($_POST['provider_cost']) ? $_POST['provider_cost'] : 0);
        $earned = floatval(isset($_POST['earned']) ? $_POST['earned'] : 0);
        $notes = trim($_POST['notes'] ?? '');

        // Автоматический расчет заработка, если не указан
        if ($earned == 0 && $paid > 0 && $provider_cost > 0) {
            $earned = $paid - $provider_cost;
        }

        if (empty($first_name) || empty($phone)) {
            $error = t('name_phone_required');
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO tv_clients (first_name, phone, address, latitude, longitude, provider_id, subscription_date, months, login, password, device_count, viewing_program, paid, provider_cost, earned, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$first_name, $phone, $address, $latitude, $longitude, $provider_id, $subscription_date, $months, $login, $password, $device_count, $viewing_program, $paid, $provider_cost, $earned, $notes]);
                $clientId = $conn->lastInsertId();

                try {
                    $conn->exec("CREATE TABLE IF NOT EXISTS tv_payments (id INT AUTO_INCREMENT PRIMARY KEY, client_id INT NOT NULL, year INT NOT NULL, paid DECIMAL(10,2) NOT NULL, provider_cost DECIMAL(10,2) NOT NULL, earned DECIMAL(10,2) NOT NULL, created_at DATETIME NOT NULL, INDEX(client_id), INDEX(year))");
                    if ($paid > 0 || $provider_cost > 0 || $earned > 0) {
                        $stmtPay = $conn->prepare("INSERT INTO tv_payments (client_id, year, paid, provider_cost, earned, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmtPay->execute([$clientId, intval(date('Y', strtotime($subscription_date))), $paid, $provider_cost, $earned, $subscription_date . ' 00:00:00']);
                    }
                } catch(PDOException $ePay) {
                }

                try {
                    $conn->exec("CREATE TABLE IF NOT EXISTS tv_events (id INT AUTO_INCREMENT PRIMARY KEY, type VARCHAR(32) NOT NULL, client_id INT, provider_id INT, amount DECIMAL(10,2), months INT, metadata TEXT, user VARCHAR(64), created_at DATETIME NOT NULL, INDEX(client_id), INDEX(type), INDEX(created_at))");
                    $stmtEv = $conn->prepare("INSERT INTO tv_events (type, client_id, provider_id, amount, months, metadata, user, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtEv->execute(['client_created', $clientId, $provider_id ?: null, null, $months, json_encode(['phone'=>$phone,'address'=>$address]), (isset($_SESSION['username']) ? $_SESSION['username'] : null), date('Y-m-d H:i:s')]);
                    if ($paid > 0 || $provider_cost > 0 || $earned > 0) {
                        $stmtEv->execute(['payment_recorded', $clientId, $provider_id ?: null, $earned, null, json_encode(['paid'=>$paid,'provider_cost'=>$provider_cost]), (isset($_SESSION['username']) ? $_SESSION['username'] : null), date('Y-m-d H:i:s')]);
                    }
                } catch(PDOException $eEv) {
                }

                $success = t('client_added_success');
                
                if (isset($_POST['clear_after_success'])) {
                    $_POST = [];
                }
            } catch(PDOException $e) {
                $error = t('error_add_client') . ': ' . $e->getMessage();
            }
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
    <style>
        .form-container {
            background: var(--dk-dark-bg);
            border: 1px solid var(--dk-gray-700);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            color: var(--dk-gray-300);
        }
        .form-section {
            background: var(--dk-dark-bg);
            border: 1px solid var(--dk-gray-700);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #007bff;
        }
        .form-section h4 {
            color: #007bff;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .required::after {
            content: " *";
            color: #dc3545;
        }
        .auto-calc {
            background-color: #e8f4fd;
            border: 1px solid #b6d4fe;
        }
        .summary-box {
            background: linear-gradient(135deg, #313348 0%, #2a2b3d 100%);
            color: var(--dk-gray-300);
            border: 1px solid var(--dk-gray-700);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .summary-value {
            font-size: 24px;
            font-weight: bold;
        }
        
        .address-suggestions {
            position: absolute;
            background: var(--dk-dark-bg);
            border: 1px solid var(--dk-gray-700);
            border-radius: 0 0 5px 5px;
            width: 95%;
            z-index: 1000;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        
        .address-suggestion-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid var(--dk-gray-700);
        }
        
        .address-suggestion-item:hover {
            background-color: var(--dk-gray-800);
        }
        
        .address-wrapper {
            position: relative;
        }
    </style>
<div class="p-4">
    <div class="welcome mb-4">
        <div class="content rounded-3 p-3">
            <h1 class="fs-3"><?= htmlspecialchars(t('add_new_client_title')) ?></h1>
            <p class="mb-0"><?= htmlspecialchars(t('add_new_client_subtitle')) ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="form-container">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!isset($_SESSION['csrf_token'])) {
                    if (function_exists('random_bytes')) {
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
                    } elseif (function_exists('openssl_random_pseudo_bytes')) {
                        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(16));
                    } else {
                        $_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true));
                    }
                } ?>
                <form method="POST" id="clientForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <!-- Основная информация -->
                    <div class="form-section">
                        <h4><i class="uil uil-user-circle"></i> <?= htmlspecialchars(t('section_main_info')) ?></h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label required"><?= htmlspecialchars(t('client_name')) ?></label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?= htmlspecialchars(isset($_POST['first_name']) ? $_POST['first_name'] : '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label required"><?= htmlspecialchars(t('phone')) ?></label>
                                <input type="text" id="phone" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label"><?= htmlspecialchars(t('address')) ?></label>
                                <div class="address-wrapper">
                                    <input type="text" id="address" name="address" class="form-control" 
                                           value="<?= htmlspecialchars(isset($_POST['address']) ? $_POST['address'] : '') ?>" autocomplete="off" placeholder="Start typing to search address...">
                                    <input type="hidden" id="latitude" name="latitude" value="<?= htmlspecialchars(isset($_POST['latitude']) ? $_POST['latitude'] : '') ?>">
                                    <input type="hidden" id="longitude" name="longitude" value="<?= htmlspecialchars(isset($_POST['longitude']) ? $_POST['longitude'] : '') ?>">
                                    <div id="address-suggestions" class="address-suggestions"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Информация о подписке -->
                    <div class="form-section">
                        <h4><i class="uil uil-calendar-alt"></i> <?= htmlspecialchars(t('section_subscription_info')) ?></h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="provider_id" class="form-label"><?= htmlspecialchars(t('provider')) ?></label>
                                <select id="provider_id" name="provider_id" class="form-select">
                                    <option value=""><?= htmlspecialchars(t('select_provider')) ?></option>
                                    <?php foreach ($providers as $provider): ?>
                                        <option value="<?= $provider['id'] ?>" 
                                            <?= (isset($_POST['provider_id']) ? $_POST['provider_id'] : 0) == $provider['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($provider['operator']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="subscription_date" class="form-label"><?= htmlspecialchars(t('subscription_start_date')) ?></label>
                                <input type="date" id="subscription_date" name="subscription_date" class="form-control" 
                                       value="<?= htmlspecialchars(isset($_POST['subscription_date']) ? $_POST['subscription_date'] : date('Y-m-d')) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="months" class="form-label"><?= htmlspecialchars(t('months_count')) ?></label>
                                <input type="number" id="months" name="months" class="form-control" min="1" 
                                       value="<?= htmlspecialchars(isset($_POST['months']) ? $_POST['months'] : 12) ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Данные для входа -->
                    <div class="form-section">
                        <h4><i class="uil uil-key-skeleton"></i> <?= htmlspecialchars(t('section_login_info')) ?></h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="login" class="form-label"><?= htmlspecialchars(t('login_label')) ?></label>
                                <input type="text" id="login" name="login" class="form-control" 
                                       value="<?= htmlspecialchars(isset($_POST['login']) ? $_POST['login'] : '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label"><?= htmlspecialchars(t('password_label')) ?></label>
                                <input type="text" id="password" name="password" class="form-control" 
                                       value="<?= htmlspecialchars(isset($_POST['password']) ? $_POST['password'] : '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Техническая информация -->
                    <div class="form-section">
                        <h4><i class="uil uil-desktop"></i> <?= htmlspecialchars(t('section_technical_info')) ?></h4>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="device_count" class="form-label"><?= htmlspecialchars(t('device_count')) ?></label>
                                <input type="number" id="device_count" name="device_count" class="form-control" min="1" 
                                       value="<?= htmlspecialchars(isset($_POST['device_count']) ? $_POST['device_count'] : 1) ?>">
                            </div>
                            <div class="col-md-8">
                                <label for="viewing_program" class="form-label"><?= htmlspecialchars(t('viewing_program_label')) ?></label>
                                <input type="text" id="viewing_program" name="viewing_program" class="form-control" 
                                       value="<?= htmlspecialchars(isset($_POST['viewing_program']) ? $_POST['viewing_program'] : '') ?>">
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label"><?= htmlspecialchars(t('notes')) ?></label>
                                <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="<?= htmlspecialchars(t('notes_placeholder')) ?>"><?= htmlspecialchars(isset($_POST['notes']) ? $_POST['notes'] : '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Финансовая информация -->
                    <div class="form-section">
                        <h4><i class="uil uil-euro"></i> <?= htmlspecialchars(t('section_financial_info')) ?></h4>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="paid" class="form-label"><?= htmlspecialchars(t('paid_eur')) ?></label>
                                <input type="number" step="0.01" id="paid" name="paid" class="form-control" min="0" 
                                       value="<?= htmlspecialchars(isset($_POST['paid']) ? $_POST['paid'] : 0) ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="provider_cost" class="form-label"><?= htmlspecialchars(t('provider_cost_eur')) ?></label>
                                <input type="number" step="0.01" id="provider_cost" name="provider_cost" class="form-control" min="0" 
                                       value="<?= htmlspecialchars(isset($_POST['provider_cost']) ? $_POST['provider_cost'] : 0) ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="earned" class="form-label"><?= htmlspecialchars(t('my_earned_eur')) ?></label>
                                <input type="number" step="0.01" id="earned" name="earned" class="form-control auto-calc" min="0" 
                                       value="<?= htmlspecialchars(isset($_POST['earned']) ? $_POST['earned'] : 0) ?>" readonly>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="clear_after_success" id="clear_after_success" checked>
                                <label class="form-check-label" for="clear_after_success">
                                    <?= htmlspecialchars(t('clear_form_after_success')) ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="tv_clients.php" class="btn btn-secondary me-md-2"><?= htmlspecialchars(t('cancel')) ?></a>
                        <button type="submit" class="btn btn-success px-4">
                            <i class="uil uil-user-plus"></i> <?= htmlspecialchars(t('add_client_btn')) ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Краткая сводка -->
            <div class="summary-box">
                <h4 class="mb-3"><i class="uil uil-chart-pie"></i> <?= htmlspecialchars(t('financial_summary')) ?></h4>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><?= htmlspecialchars(t('client_pays')) ?>:</span>
                            <span class="summary-value" id="summary-paid">€0.00</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><?= htmlspecialchars(t('provider_cost_label')) ?>:</span>
                            <span class="summary-value" id="summary-cost">€0.00</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><?= htmlspecialchars(t('your_earned')) ?>:</span>
                            <span class="summary-value" id="summary-earned">€0.00</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <span><?= htmlspecialchars(t('margin')) ?>:</span>
                            <span class="summary-value" id="summary-margin">0%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Быстрые подсказки -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="uil uil-lightbulb"></i> <?= htmlspecialchars(t('hints_title')) ?></h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li><?= htmlspecialchars(t('hint_required_fields')) ?></li>
                        <li><?= htmlspecialchars(t('hint_auto_earnings')) ?></li>
                        <li><?= htmlspecialchars(t('hint_default_date')) ?></li>
                        <li><?= htmlspecialchars(t('hint_default_months')) ?></li>
                    </ul>
                </div>
            </div>

            <!-- Быстрый переход -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="uil uil-fast-mail"></i> <?= htmlspecialchars(t('quick_actions')) ?></h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="tv_clients.php" class="btn btn-outline-primary">
                            <i class="uil uil-users-alt"></i> <?= htmlspecialchars(t('all_clients')) ?>
                        </a>
                        <a href="tv_providers.php" class="btn btn-outline-success">
                            <i class="uil uil-tv-retro"></i> <?= htmlspecialchars(t('providers')) ?>
                        </a>
                        <a href="../index.php" class="btn btn-outline-info">
                            <i class="uil uil-estate"></i> <?= htmlspecialchars(t('go_home')) ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Автоматический расчет заработка
    function calculateEarnings() {
        const paid = parseFloat(document.getElementById('paid').value) || 0;
        const cost = parseFloat(document.getElementById('provider_cost').value) || 0;
        const earned = paid - cost;
        
        document.getElementById('earned').value = earned.toFixed(2);
        
        // Обновление сводки
        document.getElementById('summary-paid').textContent = '€' + paid.toFixed(2);
        document.getElementById('summary-cost').textContent = '€' + cost.toFixed(2);
        document.getElementById('summary-earned').textContent = '€' + earned.toFixed(2);
        
        // Расчет маржинальности
        const margin = paid > 0 ? ((earned / paid) * 100).toFixed(1) : 0;
        document.getElementById('summary-margin').textContent = margin + '%';
        
        // Цвет маржинальности
        const marginElement = document.getElementById('summary-margin');
        marginElement.className = 'summary-value';
        if (margin > 30) {
            marginElement.classList.add('text-success');
        } else if (margin > 10) {
            marginElement.classList.add('text-warning');
        } else if (margin > 0) {
            marginElement.classList.add('text-info');
        } else {
            marginElement.classList.add('text-danger');
        }
    }

    // Обработчики событий
    document.getElementById('paid').addEventListener('input', calculateEarnings);
    document.getElementById('provider_cost').addEventListener('input', calculateEarnings);

    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', calculateEarnings);

    // Генерация пароля
    document.getElementById('generate-password').addEventListener('click', function() {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let password = '';
        for (let i = 0; i < 8; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('password').value = password;
    });

    // Автокомплит адреса
    const addressInput = document.getElementById('address');
    const suggestionsBox = document.getElementById('address-suggestions');
    const latInput = document.getElementById('latitude');
    const lonInput = document.getElementById('longitude');
    let debounceTimer;

    addressInput.addEventListener('input', function() {
        const query = this.value;
        
        // Сбрасываем координаты при изменении
        if (latInput.value) {
            latInput.value = '';
            lonInput.value = '';
        }
        
        clearTimeout(debounceTimer);
        suggestionsBox.style.display = 'none';
        
        if (query.length < 3) return;
        
        debounceTimer = setTimeout(() => {
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ', Latvia')}&limit=5&addressdetails=1&accept-language=ru`, {
                headers: { 'Accept-Language': 'ru' }
            })
            .then(response => response.json())
            .then(data => {
                suggestionsBox.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'address-suggestion-item';
                        div.textContent = item.display_name;
                        div.addEventListener('click', function() {
                            addressInput.value = item.display_name;
                            latInput.value = item.lat;
                            lonInput.value = item.lon;
                            suggestionsBox.style.display = 'none';
                        });
                        suggestionsBox.appendChild(div);
                    });
                    suggestionsBox.style.display = 'block';
                }
            })
            .catch(err => console.error('Geocoding error:', err));
        }, 500);
    });

    // Скрывать подсказки при клике вне
    document.addEventListener('click', function(e) {
        if (e.target !== addressInput && e.target !== suggestionsBox) {
            suggestionsBox.style.display = 'none';
        }
    });
</script>
<?php include '../includes/footer.php'; ?>
