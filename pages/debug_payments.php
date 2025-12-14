<?php
require_once '../includes/auth.php';
requireAuth();

// Получаем все платежи
$stmt = $conn->query("
    SELECT 
        tp.*,
        c.first_name,
        c.phone
    FROM tv_payments tp
    LEFT JOIN tv_clients c ON c.id = tp.client_id
    ORDER BY tp.created_at DESC
    LIMIT 100
");
$payments = $stmt->fetchAll();

// Итоги по годам
$stmt = $conn->query("
    SELECT 
        year,
        COUNT(*) as records,
        SUM(paid) as total_paid,
        SUM(provider_cost) as total_cost,
        SUM(earned) as total_earned
    FROM tv_payments
    GROUP BY year
    ORDER BY year DESC
");
$year_totals = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="p-4">
    <div class="welcome mb-4">
        <div class="content rounded-3 p-3">
            <h1 class="fs-3"><?= htmlspecialchars(t('debug_payments_title')) ?></h1>
            <p class="mb-0"><?= htmlspecialchars(t('debug_payments_subtitle')) ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><?= htmlspecialchars(t('debug_year_totals')) ?></h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?= htmlspecialchars(ucfirst(t('year'))) ?></th>
                        <th><?= htmlspecialchars(t('records_count')) ?></th>
                        <th><?= htmlspecialchars(ucfirst(t('income'))) ?> (€)</th>
                        <th><?= htmlspecialchars(ucfirst(t('expenses'))) ?> (€)</th>
                        <th><?= htmlspecialchars(t('profit_eur')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($year_totals as $row): ?>
                        <tr>
                            <td><?= $row['year'] ?></td>
                            <td><?= $row['records'] ?></td>
                            <td><?= number_format($row['total_paid'], 2) ?></td>
                            <td><?= number_format($row['total_cost'], 2) ?></td>
                            <td class="<?= $row['total_earned'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($row['total_earned'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><?= htmlspecialchars(t('last_100_payments')) ?></h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?= htmlspecialchars(t('client')) ?></th>
                            <th><?= htmlspecialchars(ucfirst(t('year'))) ?></th>
                            <th><?= htmlspecialchars(ucfirst(t('income'))) ?></th>
                            <th><?= htmlspecialchars(ucfirst(t('expenses'))) ?></th>
                            <th><?= htmlspecialchars(t('profit')) ?></th>
                            <th><?= htmlspecialchars(t('date_created')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= $payment['id'] ?></td>
                                <td>
                                    <?= htmlspecialchars($payment['first_name'] ?? 'N/A') ?>
                                    <small class="text-muted">(ID: <?= $payment['client_id'] ?>)</small>
                                </td>
                                <td><?= $payment['year'] ?></td>
                                <td class="<?= $payment['paid'] < 0 ? 'text-danger' : '' ?>">
                                    <?= number_format($payment['paid'], 2) ?>
                                </td>
                                <td class="<?= $payment['provider_cost'] < 0 ? 'text-danger' : '' ?>">
                                    <?= number_format($payment['provider_cost'], 2) ?>
                                </td>
                                <td class="<?= $payment['earned'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format($payment['earned'], 2) ?>
                                </td>
                                <td><?= $payment['created_at'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="tv_clients.php" class="btn btn-primary"><?= htmlspecialchars(t('back_to_clients')) ?></a>
        <a href="../index.php" class="btn btn-secondary"><?= htmlspecialchars(t('back_to_home')) ?></a>
    </div>
</div>
<style>
    .card {
        background-color: #2a2b3d;
        color: white;
        border: 1px solid #4b5563;
    }
    .card-header {
        background-color: #374151;
        border-color: #4b5563;
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
</style>
<?php include '../includes/footer.php'; ?>
