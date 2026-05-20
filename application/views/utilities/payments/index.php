<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Utility Payments</h1>
</div>

<?php include(BASEPATH . 'views/utilities/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($payments)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-cash" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No payments recorded yet.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
    <div class="card-header d-flex justify-content-end py-2">
        <form method="GET" action="" class="d-flex align-items-center gap-2 mb-0 flex-wrap">
            <input type="search" name="search" class="form-control form-control-sm" style="min-width:200px" value="<?= htmlspecialchars(list_search_term()) ?>" placeholder="Search name, ID, code…">
            <input type="hidden" name="page" value="1">
            <label class="small text-muted mb-0">Records</label>
            <?php render_pagination_per_page_select(intval($pagination['per_page'] ?? 50), 'per_page', 'form-select form-select-sm'); ?>
            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Payment #</th>
                            <th>Bill #</th>
                            <th>Meter</th>
                            <th>Amount</th>
                            <th>Payment Date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($payment['payment_number']) ?></strong></td>
                                <td>
                                    <?php if (!empty($payment['bill_id'])): ?>
                                        <a href="<?= base_url('utilities/bills/view/' . $payment['bill_id']) ?>">
                                            <?= htmlspecialchars($payment['bill_number'] ?? 'Bill #' . $payment['bill_id']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($payment['meter_number'] ?? '-') ?></td>
                                <td><strong><?= format_currency($payment['amount']) ?></strong></td>
                                <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></td>
                                <td><?= htmlspecialchars($payment['reference_number'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($payment['bill_id'])): ?>
                                        <a href="<?= base_url('utilities/bills/view/' . $payment['bill_id']) ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

    <?php render_pagination_controls($pagination ?? null); ?>
        </div>
    </div>
<?php endif; ?>

