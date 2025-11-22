<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Supplier: <?= htmlspecialchars($supplier['supplier_name'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <?php if (has_permission('inventory', 'update')): ?>
                <a href="<?= base_url('inventory/suppliers/edit/' . $supplier['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?= base_url('inventory/suppliers') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php include(BASEPATH . 'views/inventory/_nav.php'); ?>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Supplier Information</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Supplier Code</dt>
                    <dd><strong><?= htmlspecialchars($supplier['supplier_code'] ?? 'N/A') ?></strong></dd>
                    
                    <dt>Supplier Name</dt>
                    <dd><?= htmlspecialchars($supplier['supplier_name'] ?? 'N/A') ?></dd>
                    
                    <dt>Contact Person</dt>
                    <dd><?= htmlspecialchars($supplier['contact_person'] ?: 'N/A') ?></dd>
                    
                    <dt>Email</dt>
                    <dd>
                        <?php if (!empty($supplier['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($supplier['email']) ?>">
                                <?= htmlspecialchars($supplier['email']) ?>
                            </a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </dd>
                    
                    <dt>Phone</dt>
                    <dd>
                        <?php if (!empty($supplier['phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($supplier['phone']) ?>">
                                <?= htmlspecialchars($supplier['phone']) ?>
                            </a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </dd>
                    
                    <dt>Address</dt>
                    <dd><?= htmlspecialchars($supplier['address'] ?: 'N/A') ?></dd>
                    
                    <dt>Payment Terms</dt>
                    <dd><?= $supplier['payment_terms'] ?? 30 ?> days</dd>
                    
                    <dt>Lead Time</dt>
                    <dd><?= $supplier['lead_time_days'] ?? 0 ?> days</dd>
                    
                    <dt>Status</dt>
                    <dd>
                        <span class="badge bg-<?= ($supplier['is_active'] ?? false) ? 'success' : 'secondary' ?>">
                            <?= ($supplier['is_active'] ?? false) ? 'Active' : 'Inactive' ?>
                        </span>
                    </dd>
                    
                    <?php if (!empty($supplier['created_at'])): ?>
                    <dt>Created</dt>
                    <dd><?= format_date($supplier['created_at']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($supplier['updated_at'])): ?>
                    <dt>Last Updated</dt>
                    <dd><?= format_date($supplier['updated_at']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if (has_permission('inventory', 'update')): ?>
                        <a href="<?= base_url('inventory/suppliers/edit/' . $supplier['id']) ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Supplier
                        </a>
                    <?php endif; ?>
                    <?php if (has_permission('inventory', 'delete')): ?>
                        <a href="<?= base_url('inventory/suppliers/delete/' . $supplier['id']) ?>" class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this supplier?')">
                            <i class="bi bi-trash"></i> Delete Supplier
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

