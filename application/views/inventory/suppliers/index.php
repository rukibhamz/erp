<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Suppliers</h1>
        <a href="<?= base_url('inventory/suppliers/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Supplier
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

<?php if (empty($suppliers)): ?>
    <?php
$list_filter_action = base_url('inventory/suppliers');
$search_placeholder = 'Supplier name, code, email…';
include(BASEPATH . 'views/partials/list_filters_bar.php');
?>

<div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-truck" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No suppliers found.</p>
            <a href="<?= base_url('inventory/suppliers/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add First Supplier
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">

    <div class="card-body">
        <?php
        $bulk_delete_enabled = has_permission('inventory', 'delete');
        bulk_delete_render_toolbar($bulk_delete_enabled, $suppliers, base_url('inventory/suppliers/bulk-delete'), 'supplier', 'Are you sure you want to delete the selected suppliers?');
        ?>
        <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <?php bulk_delete_render_checkbox_th($bulk_delete_enabled); ?>
                            <th>Code</th>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Payment Terms</th>
                            <th>Lead Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <?php bulk_delete_render_checkbox_td($bulk_delete_enabled, (int)$supplier['id'], 'supplier ' . $supplier['supplier_name']); ?>
                                <td><strong><?= htmlspecialchars($supplier['supplier_code']) ?></strong></td>
                                <td><?= htmlspecialchars($supplier['supplier_name']) ?></td>
                                <td><?= htmlspecialchars($supplier['contact_person'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($supplier['email'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($supplier['phone'] ?: '-') ?></td>
                                <td><?= $supplier['payment_terms'] ?> days</td>
                                <td><?= $supplier['lead_time_days'] ?> days</td>
                                <td>
                                    <span class="badge bg-<?= $supplier['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $supplier['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('inventory/suppliers/view/' . $supplier['id']) ?>" 
                                           class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (has_permission('inventory', 'update')): ?>
                                        <a href="<?= base_url('inventory/suppliers/edit/' . $supplier['id']) ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (has_permission('inventory', 'delete')): ?>
                                        <form method="post" action="<?= base_url('inventory/suppliers/delete/' . $supplier['id']) ?>" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this supplier?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>

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

