<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $_SESSION['role'] ?? '';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Vendor: <?= htmlspecialchars($vendor['company_name'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <?php if (hasPermission('payables', 'update')): ?>
                <a href="<?= base_url('payables/vendors/edit/' . $vendor['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?= base_url('payables/bills/create?vendor_id=' . $vendor['id']) ?>" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Create Bill
            </a>
            <?php if (in_array($role, ['super_admin', 'admin'])): ?>
                <form method="POST" action="<?= base_url('payables/deleteVendor/' . intval($vendor['id'])) ?>" style="display:inline" onsubmit="return confirm('Permanently delete this vendor and all associated records? This cannot be undone.')">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </form>
            <?php endif; ?>
            <a href="<?= base_url('payables/vendors') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="card-title mb-0">Vendor Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Vendor Code:</strong><br>
                        <?= htmlspecialchars($vendor['vendor_code'] ?? 'N/A') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Company Name:</strong><br>
                        <?= htmlspecialchars($vendor['company_name'] ?? 'N/A') ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Contact Name:</strong><br>
                        <?= htmlspecialchars($vendor['contact_name'] ?? '-') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Email:</strong><br>
                        <?php if (!empty($vendor['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($vendor['email']) ?>"><?= htmlspecialchars($vendor['email']) ?></a>
                        <?php else: ?>-<?php endif; ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Phone:</strong><br>
                        <?= htmlspecialchars($vendor['phone'] ?? '-') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Tax ID:</strong><br>
                        <?= htmlspecialchars($vendor['tax_id'] ?? '-') ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge bg-<?= ($vendor['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($vendor['status'] ?? 'inactive') ?>
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>Outstanding Balance:</strong><br>
                        <span class="fs-5 <?= ($outstanding ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= format_currency($outstanding ?? 0) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($bills)): ?>
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="card-title mb-0">Bills</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Bill #</th>
                                <th>Date</th>
                                <th>Due Date</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bills as $bill): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($bill['bill_number'] ?? 'N/A') ?></strong></td>
                                    <td><?= format_date($bill['bill_date'] ?? '') ?></td>
                                    <td><?= format_date($bill['due_date'] ?? '') ?></td>
                                    <td class="text-end"><?= format_currency($bill['total_amount'] ?? 0) ?></td>
                                    <td class="text-end"><?= format_currency($bill['paid_amount'] ?? 0) ?></td>
                                    <td class="text-end">
                                        <strong class="<?= ($bill['balance_amount'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                                            <?= format_currency($bill['balance_amount'] ?? 0) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php $colors = ['draft'=>'secondary','sent'=>'info','paid'=>'success','partially_paid'=>'warning','overdue'=>'danger']; ?>
                                        <span class="badge bg-<?= $colors[$bill['status'] ?? 'draft'] ?? 'secondary' ?>">
                                            <?= ucfirst(str_replace('_', ' ', $bill['status'] ?? 'draft')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('payables/bills/view/' . intval($bill['id'])) ?>" class="btn btn-sm btn-primary">
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
    </div>
</div>
<?php endif; ?>
