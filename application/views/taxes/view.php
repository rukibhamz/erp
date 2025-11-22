<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Tax Rate: <?= htmlspecialchars($tax['tax_name'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <?php if (has_permission('taxes', 'update')): ?>
                <a href="<?= base_url('taxes/edit/' . $tax['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?= base_url('taxes') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

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
                <h5 class="card-title mb-0">Tax Rate Information</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Tax Name</dt>
                    <dd><strong><?= htmlspecialchars($tax['tax_name'] ?? 'N/A') ?></strong></dd>
                    
                    <dt>Tax Code</dt>
                    <dd><?= htmlspecialchars($tax['tax_code'] ?? '-') ?></dd>
                    
                    <dt>Tax Type</dt>
                    <dd><span class="badge bg-info"><?= ucfirst($tax['tax_type'] ?? 'percentage') ?></span></dd>
                    
                    <dt>Rate</dt>
                    <dd class="fs-4 fw-bold">
                        <?php if (($tax['tax_type'] ?? 'percentage') === 'percentage'): ?>
                            <?= number_format($tax['rate'] ?? 0, 2) ?>%
                        <?php else: ?>
                            <?= format_currency($tax['rate'] ?? 0, 'USD') ?>
                        <?php endif; ?>
                    </dd>
                    
                    <dt>Tax Inclusive</dt>
                    <dd>
                        <span class="badge bg-<?= ($tax['tax_inclusive'] ?? false) ? 'success' : 'secondary' ?>">
                            <?= ($tax['tax_inclusive'] ?? false) ? 'Yes' : 'No' ?>
                        </span>
                    </dd>
                    
                    <dt>Status</dt>
                    <dd>
                        <span class="badge bg-<?= ($tax['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($tax['status'] ?? 'inactive') ?>
                        </span>
                    </dd>
                    
                    <?php if (!empty($tax['description'])): ?>
                    <dt>Description</dt>
                    <dd><?= htmlspecialchars($tax['description']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($tax['created_at'])): ?>
                    <dt>Created</dt>
                    <dd><?= format_date($tax['created_at']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($tax['updated_at'])): ?>
                    <dt>Last Updated</dt>
                    <dd><?= format_date($tax['updated_at']) ?></dd>
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
                    <?php if (has_permission('taxes', 'update')): ?>
                        <a href="<?= base_url('taxes/edit/' . $tax['id']) ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Tax Rate
                        </a>
                    <?php endif; ?>
                    <?php if (has_permission('taxes', 'delete')): ?>
                        <a href="<?= base_url('taxes/delete/' . $tax['id']) ?>" class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this tax rate?')">
                            <i class="bi bi-trash"></i> Delete Tax Rate
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

