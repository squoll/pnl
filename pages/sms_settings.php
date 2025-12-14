<?php
require_once '../includes/auth.php';
requireAuth();

// Путь к файлу с шаблоном SMS
$template_file = __DIR__ . '/../config/sms_template.txt';

// Обработка сохранения шаблона
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    $template = $_POST['sms_template'] ?? '';
    file_put_contents($template_file, $template);
    $success = t('sms_saved_success');
}

// Загружаем текущий шаблон
$default_template = t('sms_default_template');
$current_template = file_exists($template_file) ? file_get_contents($template_file) : $default_template;

include '../includes/header.php';
?>

<section class="p-4">
    <div class="welcome mb-4">
        <div class="content rounded-3 p-3">
            <h1 class="fs-3"><?= htmlspecialchars(t('sms_settings_title')) ?></h1>
            <p class="mb-0"><?= htmlspecialchars(t('sms_settings_subtitle')) ?></p>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?= htmlspecialchars(t('sms_template_card')) ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars(t('sms_template_label')) ?></label>
                            <textarea name="sms_template" class="form-control" rows="6" required><?= htmlspecialchars($current_template) ?></textarea>
                            <div class="form-text">
                                <?= htmlspecialchars(t('available_variables')) ?>:
                                <ul class="mt-2">
                                    <li><code>{NAME}</code> - <?= htmlspecialchars(t('var_name')) ?></li>
                                    <li><code>{PHONE}</code> - <?= htmlspecialchars(t('var_phone')) ?></li>
                                    <li><code>{DAYS}</code> - <?= htmlspecialchars(t('var_days')) ?></li>
                                </ul>
                            </div>
                        </div>
                        <button type="submit" name="save_template" class="btn btn-primary"><?= htmlspecialchars(t('save_template')) ?></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?= htmlspecialchars(t('info')) ?></h5>
                </div>
                <div class="card-body">
                    <h6><?= htmlspecialchars(t('sms_how_it_works')) ?></h6>
                    <p class="small">
                        <strong><?= htmlspecialchars(t('sms_android_title')) ?>:</strong> <?= htmlspecialchars(t('sms_android_hint')) ?>
                    </p>
                    <p class="small">
                        <strong><?= htmlspecialchars(t('sms_pc_title')) ?>:</strong> <?= htmlspecialchars(t('sms_pc_hint')) ?>
                    </p>
                    <hr>
                    <h6><?= htmlspecialchars(t('sms_button_visible')) ?></h6>
                    <ul class="small">
                        <li><?= htmlspecialchars(t('sms_rule_1')) ?></li>
                        <li><?= htmlspecialchars(t('sms_rule_2')) ?></li>
                        <li><?= htmlspecialchars(t('sms_rule_3')) ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
