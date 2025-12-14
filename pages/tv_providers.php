<?php
// tv_providers.php - управление провайдерами
include_once '../includes/auth.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$providers = [];
try {
    $stmt = $conn->query("SELECT * FROM tv_providers ORDER BY operator");
    $providers = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = t('error_providers_load') . ": " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_provider'])) {
    $operator = trim($_POST['operator'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $referral_link = trim($_POST['referral_link'] ?? '');
    $balance = floatval($_POST['balance'] ?? 0);
    
    if (empty($operator)) {
        $error = t('error_operator_required');
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO tv_providers (operator, website, login, password, referral_link, balance) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$operator, $website, $login, $password, $referral_link, $balance]);
            $success = t('provider_add_success');
            
            $stmt = $conn->query("SELECT * FROM tv_providers ORDER BY operator");
            $providers = $stmt->fetchAll();
        } catch(PDOException $e) {
            $error = t('error_provider_add') . ": " . $e->getMessage();
        }
    }
}

require_once '../includes/i18n.php';
include '../includes/header.php';
?>

    <div class="p-4">
        <div class="welcome mb-4">
            <div class="content rounded-3 p-3">
                <h1 class="fs-3"><?= htmlspecialchars(t('providers_manage')) ?></h1>
                <p class="mb-0"><?= htmlspecialchars(t('providers_total')) ?>: <strong><?= count($providers) ?></strong></p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Форма добавления провайдера -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><?= htmlspecialchars(t('provider_add')) ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars(t('operator_name')) ?> *</label>
                            <input type="text" name="operator" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars(t('website')) ?></label>
                            <input type="url" name="website" class="form-control" placeholder="https://">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars(t('login_label')) ?></label>
                            <input type="text" name="login" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars(t('password_label')) ?></label>
                            <input type="text" name="password" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label"><?= htmlspecialchars(t('referral_link')) ?></label>
                            <input type="url" name="referral_link" class="form-control" placeholder="https://">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars(t('balance_eur')) ?></label>
                            <input type="number" step="0.01" name="balance" class="form-control" value="0">
                        </div>
                        <div class="col-12">
                            <button type="submit" name="add_provider" class="btn btn-success w-100"><?= htmlspecialchars(t('add_provider_btn')) ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Список провайдеров -->
        <div class="row">
            <?php foreach ($providers as $provider): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><?= htmlspecialchars($provider['operator']) ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if ($provider['website']): ?>
                                <p><strong><?= htmlspecialchars(t('website')) ?>:</strong> 
                                    <a href="<?= htmlspecialchars($provider['website']) ?>" target="_blank" class="text-decoration-none">
                                        <?= htmlspecialchars($provider['website']) ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($provider['login']): ?>
                                <p><strong><?= htmlspecialchars(t('login_label')) ?>:</strong> <?= htmlspecialchars($provider['login']) ?></p>
                            <?php endif; ?>
                            
                            <?php if ($provider['password']): ?>
                                <p><strong><?= htmlspecialchars(t('password_label')) ?>:</strong> <?= htmlspecialchars($provider['password']) ?></p>
                            <?php endif; ?>
                            
                            <p><strong><?= htmlspecialchars(t('balance_eur')) ?>:</strong> €<?= number_format($provider['balance'], 2) ?></p>
                            
                            <?php if ($provider['referral_link']): ?>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($provider['referral_link']) ?>" readonly>
                                    <button class="btn btn-outline-secondary" onclick="copyToClipboard('<?= htmlspecialchars($provider['referral_link']) ?>', this)">
                                        <?= htmlspecialchars(t('copy_btn')) ?>
                                        </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
    </div>
    </div>
<script>
    function copyToClipboard(text, button) {
        navigator.clipboard.writeText(text).then(() => {
            const original = button.innerHTML;
            button.innerHTML = '✓';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.innerHTML = original;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 2000);
        });
    }
    </script>
<?php include '../includes/footer.php'; ?>
