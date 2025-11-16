<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Purchase Orders</h1>
        <a href="<?= base_url('inventory/purchase-orders/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create PO
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/inventory/_nav.php'); ?>

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
                    <option value="partial" <?= $selected_status === 'partial' ? 'selected' : '' ?>>Partial</option>
                    <option value="received" <?= $selected_status === 'received' ? 'selected' : '' ?>>Received</option>
                    <option value="closed" <?= $selected_status === 'closed' ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>
            <div class="col-md-8">
                <a href="<?= base_url('inventory/purchase-orders') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($purchase_orders)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-cart-plus" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No purchase orders found.</p>
            <a href="<?= base_url('inventory/purchase-orders/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create First PO
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>Order Date</th>
                            <th>Expected Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchase_orders as $po): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($po['po_number']) ?></strong></td>
                                <td><?= htmlspecialchars($po['supplier_name'] ?? 'N/A') ?></td>
                                <td><?= date('M d, Y', strtotime($po['order_date'])) ?></td>
                                <td><?= $po['expected_date'] ? date('M d, Y', strtotime($po['expected_date'])) : '-' ?></td>
                                <td><?= format_currency($po['total_amount']) ?></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $po['status'] === 'closed' ? 'success' : 
                                        ($po['status'] === 'received' ? 'info' : 
                                        ($po['status'] === 'partial' ? 'warning' : 
                                        ($po['status'] === 'sent' ? 'primary' : 'secondary'))) ?>">
                                        <?= ucfirst($po['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= base_url('inventory/purchase-orders/view/' . $po['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

