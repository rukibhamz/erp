<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Rent Invoices</h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('rent-invoices/auto-generate') ?>" class="btn btn-success" onclick="return confirm('Generate invoices for all active leases this month?')">
                <i class="bi bi-lightning"></i> Auto-Generate
            </a>
        </div>
    </div>
</div>

<!-- Location Management Navigation -->
<div class="Location-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('locations') ?>">
            <i class="bi bi-building"></i> Locations
        </a>
        <a class="nav-link" href="<?= base_url('spaces') ?>">
            <i class="bi bi-door-open"></i> Spaces
        </a>
        <a class="nav-link" href="<?= base_url('leases') ?>">
            <i class="bi bi-file-earmark-text"></i> Leases
        </a>
        <a class="nav-link" href="<?= base_url('tenants') ?>">
            <i class="bi bi-people"></i> Tenants
        </a>
        <a class="nav-link active" href="<?= base_url('rent-invoices') ?>">
            <i class="bi bi-receipt"></i> Rent Invoices
        </a>
    </nav>
</div>

<style>
.Location-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.Location-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.Location-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.Location-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.Location-nav .nav-link i {
    margin-right: 0.5rem;
}
</style>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filter by Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $selected_status === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="draft" <?= $selected_status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="sent" <?= $selected_status === 'sent' ? 'selected' : '' ?>>Sent</option>
                    <option value="paid" <?= $selected_status === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="partial" <?= $selected_status === 'partial' ? 'selected' : '' ?>>Partial</option>
                    <option value="overdue" <?= $selected_status === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if (empty($invoices)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-receipt" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No rent invoices found.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Period</th>
                            <th>Tenant</th>
                            <th>Space</th>
                            <th>Total Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong></td>
                                <td>
                                    <?= date('M d', strtotime($invoice['period_start'])) ?> - 
                                    <?= date('M d, Y', strtotime($invoice['period_end'])) ?>
                                </td>
                                <td><?= htmlspecialchars($invoice['business_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($invoice['space_name'] ?? 'N/A') ?></td>
                                <td><?= format_currency($invoice['total_amount']) ?></td>
                                <td><?= format_currency($invoice['paid_amount']) ?></td>
                                <td>
                                    <strong class="<?= floatval($invoice['balance_amount']) > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= format_currency($invoice['balance_amount']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <?= date('M d, Y', strtotime($invoice['due_date'])) ?>
                                    <?php if ($invoice['status'] === 'overdue' || ($invoice['due_date'] < date('Y-m-d') && $invoice['balance_amount'] > 0)): ?>
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $invoice['status'] === 'paid' ? 'success' : 
                                        ($invoice['status'] === 'overdue' ? 'danger' : 
                                        ($invoice['status'] === 'partial' ? 'warning' : 'secondary')) 
                                    ?>">
                                        <?= ucfirst($invoice['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('rent-invoices/view/' . $invoice['id']) ?>" class="btn btn-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

