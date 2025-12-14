<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../includes/auth.php';
require_once '../includes/i18n.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';

try {
    $stmt = $conn->prepare('SELECT id, username, password FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $error = t('account_db_error') . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = trim($_POST['current_password'] ?? '');
    $new = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    
    if (!$user) {
        $error = t('account_user_not_found');
    } elseif ($new === '' || strlen($new) < 6) {
        $error = t('account_password_short');
    } elseif ($new !== $confirm) {
        $error = t('account_passwords_mismatch');
    } else {
        $matches = ($current === $user['password']) || password_verify($current, $user['password']);
        if (!$matches) {
            $error = t('account_current_wrong');
        } else {
            try {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $upd = $conn->prepare('UPDATE users SET password = ?, password_changed_at = NOW() WHERE id = ?');
                $upd->execute([$hash, $user['id']]);
                $success = t('account_success');
            } catch (PDOException $e) {
                $error = t('account_update_error') . $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="p-4">
    <div class="welcome mb-4">
        <div class="content rounded-3 p-3">
            <h1 class="fs-3"><?= htmlspecialchars(t('account_title')) ?></h1>
            <p class="mb-0"><?= htmlspecialchars(t('account_subtitle')) ?> <strong><?= htmlspecialchars($user['username'] ?? '') ?></strong></p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><?= htmlspecialchars(t('account_change_password')) ?></h5>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label"><?= htmlspecialchars(t('account_current_password')) ?></label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label"><?= htmlspecialchars(t('account_new_password')) ?></label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label"><?= htmlspecialchars(t('account_confirm_password')) ?></label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(t('account_update_btn')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

