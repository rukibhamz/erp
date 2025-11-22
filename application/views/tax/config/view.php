<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Tax Type: <?= htmlspecialchars($tax_type['name'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <?php if (has_permission('tax', 'update')): ?>
                <a href="<?= base_url('tax/config/edit/' . $tax_type['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?= base_url('tax/config') ?>" class="btn btn-outline-secondary">
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
                <h5 class="card-title mb-0">Tax Type Information</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Tax Code</dt>
                    <dd><strong><?= htmlspecialchars($tax_type['code'] ?? 'N/A') ?></strong></dd>
                    
                    <dt>Tax Name</dt>
                    <dd><?= htmlspecialchars($tax_type['name'] ?? 'N/A') ?></dd>
                    
                    <dt>Tax Rate</dt>
                    <dd>
                        <?php if (($tax_type['calculation_method'] ?? '') === 'progressive'): ?>
                            <span class="badge bg-info">Progressive</span>
                            <small class="text-muted">(Rate calculated based on brackets)</small>
                        <?php else: ?>
                            <strong><?= number_format($tax_type['rate'] ?? 0, 2) ?>%</strong>
                        <?php endif; ?>
                    </dd>
                    
                    <dt>Calculation Method</dt>
                    <dd><?= ucfirst(str_replace('_', ' ', $tax_type['calculation_method'] ?? 'percentage')) ?></dd>
                    
                    <dt>Tax Authority</dt>
                    <dd><?= htmlspecialchars($tax_type['authority'] ?? 'N/A') ?></dd>
                    
                    <dt>Filing Frequency</dt>
                    <dd><?= ucfirst($tax_type['filing_frequency'] ?? 'N/A') ?></dd>
                    
                    <dt>Tax Inclusive</dt>
                    <dd>
                        <?php if ($tax_type['tax_inclusive'] ?? false): ?>
                            <span class="badge bg-success">Yes</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">No</span>
                        <?php endif; ?>
                    </dd>
                    
                    <dt>Status</dt>
                    <dd>
                        <span class="badge bg-<?= ($tax_type['is_active'] ?? false) ? 'success' : 'secondary' ?>">
                            <?= ($tax_type['is_active'] ?? false) ? 'Active' : 'Inactive' ?>
                        </span>
                    </dd>
                    
                    <?php if (!empty($tax_type['description'])): ?>
                    <dt>Description</dt>
                    <dd><?= htmlspecialchars($tax_type['description']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($tax_type['created_at'])): ?>
                    <dt>Created</dt>
                    <dd><?= format_date($tax_type['created_at']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($tax_type['updated_at'])): ?>
                    <dt>Last Updated</dt>
                    <dd><?= format_date($tax_type['updated_at']) ?></dd>
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
                    <?php if (has_permission('tax', 'update')): ?>
                        <a href="<?= base_url('tax/config/edit/' . $tax_type['id']) ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Tax Type
                        </a>
                    <?php endif; ?>
                    <?php if (has_permission('tax', 'delete')): ?>
                        <a href="<?= base_url('tax/config/delete/' . $tax_type['id']) ?>" class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to deactivate this tax type?')">
                            <i class="bi bi-trash"></i> Deactivate Tax Type
                        </a>
                    <?php endif; ?>
                    <?php if (has_permission('tax', 'update')): ?>
                        <a href="<?= base_url('tax/config') ?>" class="btn btn-outline-primary">
                            <i class="bi bi-list"></i> View All Tax Types
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

