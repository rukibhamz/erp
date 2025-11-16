<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Recurring Transactions</h1>
        <?php if (has_permission('accounting', 'create')): ?>
            <a href="<?= base_url('recurring/process') ?>" class="btn btn-primary" onclick="return confirm('Process all due recurring transactions?')">
                <i class="bi bi-play-circle"></i> Process Due Transactions
            </a>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Transaction Type</th>
                            <th>Description</th>
                            <th>Frequency</th>
                            <th>Start Date</th>
                            <th>Next Occurrence</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recurring_transactions)): ?>
                            <?php foreach ($recurring_transactions as $recurring): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= ucfirst($recurring['transaction_type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($recurring['description'] ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $frequency = $recurring['frequency'] ?? 'monthly';
                                        $interval = $recurring['frequency_interval'] ?? 1;
                                        echo ucfirst($frequency);
                                        if ($interval > 1) {
                                            echo " (Every {$interval})";
                                        }
                                        ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($recurring['start_date'])) ?></td>
                                    <td>
                                        <?php
                                        $nextDate = $recurring['next_run_date'] ?? $recurring['next_occurrence_date'] ?? '-';
                                        if ($nextDate !== '-') {
                                            $nextTimestamp = strtotime($nextDate);
                                            $isDue = $nextTimestamp <= time();
                                            echo '<span class="' . ($isDue ? 'text-danger' : '') . '">' . date('M d, Y', $nextTimestamp) . '</span>';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $recurring['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($recurring['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('recurring/view/' . $recurring['id']) ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No recurring transactions found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


