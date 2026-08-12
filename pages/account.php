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
    <div class="card mt-4" id="webauthnRegisterContainer" style="display: none;">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-fingerprint"></i> Face ID / Fingerprint</h5>
        </div>
        <div class="card-body">
            <p>You can use Face ID or Fingerprint to log in without a password.</p>
            <button class="btn btn-success" id="btnRegisterWebAuthn">Register Device</button>
        </div>
    </div>
</div>

<script>
    function base64urlToBuffer(base64url) {
        var padding = '='.repeat((4 - base64url.length % 4) % 4);
        var base64 = (base64url + padding).replace(/\-/g, '+').replace(/_/g, '/');
        var rawData = window.atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray.buffer;
    }
    function bufferToBase64url(buffer) {
        var bytes = new Uint8Array(buffer);
        var str = '';
        for (var i = 0; i < bytes.byteLength; i++) {
            str += String.fromCharCode(bytes[i]);
        }
        return window.btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }

    if (window.PublicKeyCredential) {
        PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then((available) => {
            if (available) {
                document.getElementById('webauthnRegisterContainer').style.display = 'block';
            }
        });
    }

    document.getElementById('btnRegisterWebAuthn').addEventListener('click', async function() {
        try {
            const getArgsResp = await fetch('../api/webauthn_register.php?action=getArgs');
            const getArgs = await getArgsResp.json();
            
            getArgs.publicKey.challenge = base64urlToBuffer(getArgs.publicKey.challenge);
            getArgs.publicKey.user.id = base64urlToBuffer(getArgs.publicKey.user.id);
            if (getArgs.publicKey.excludeCredentials) {
                for (let i = 0; i < getArgs.publicKey.excludeCredentials.length; i++) {
                    getArgs.publicKey.excludeCredentials[i].id = base64urlToBuffer(getArgs.publicKey.excludeCredentials[i].id);
                }
            }
            
            const cred = await navigator.credentials.create(getArgs);
            
            const formData = new FormData();
            formData.append('clientDataJSON', bufferToBase64url(cred.response.clientDataJSON));
            formData.append('attestationObject', bufferToBase64url(cred.response.attestationObject));
            
            const processResp = await fetch('../api/webauthn_register.php?action=process', {
                method: 'POST',
                body: formData
            });
            const processData = await processResp.json();
            
            if (processData.success) {
                localStorage.setItem('faceid_enabled', '1');
                alert('Face ID / Fingerprint registered successfully!');
            } else {
                alert('Registration failed: ' + processData.msg);
            }
        } catch (err) {
            console.error(err);
            if (err.name !== 'NotAllowedError') {
                alert('Face ID / Fingerprint registration failed.');
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>

