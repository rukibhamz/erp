<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Currency: <?= htmlspecialchars($currency['currency_name'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <?php if (has_permission('settings', 'update')): ?>
                <a href="<?= base_url('currencies/edit/' . $currency['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?= base_url('currencies') ?>" class="btn btn-outline-secondary">
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
                <h5 class="card-title mb-0">Currency Information</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Currency Code</dt>
                    <dd><strong><?= htmlspecialchars($currency['currency_code'] ?? 'N/A') ?></strong></dd>
                    
                    <dt>Currency Name</dt>
                    <dd><?= htmlspecialchars($currency['currency_name'] ?? 'N/A') ?></dd>
                    
                    <dt>Symbol</dt>
                    <dd><?= htmlspecialchars($currency['symbol'] ?? 'N/A') ?></dd>
                    
                    <dt>Exchange Rate</dt>
                    <dd><?= number_format($currency['exchange_rate'] ?? 1.0, 4) ?></dd>
                    
                    <dt>Position</dt>
                    <dd><?= ucfirst($currency['position'] ?? 'before') ?></dd>
                    
                    <dt>Decimal Precision</dt>
                    <dd><?= $currency['precision'] ?? 2 ?> decimal places</dd>
                    
                    <dt>Base Currency</dt>
                    <dd>
                        <?php if ($currency['is_base'] ?? false): ?>
                            <span class="badge bg-success">Yes</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">No</span>
                        <?php endif; ?>
                    </dd>
                    
                    <dt>Status</dt>
                    <dd>
                        <span class="badge bg-<?= ($currency['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($currency['status'] ?? 'inactive') ?>
                        </span>
                    </dd>
                    
                    <?php if (!empty($currency['created_at'])): ?>
                    <dt>Created</dt>
                    <dd><?= format_date($currency['created_at']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($currency['updated_at'])): ?>
                    <dt>Last Updated</dt>
                    <dd><?= format_date($currency['updated_at']) ?></dd>
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
                    <?php if (has_permission('settings', 'update')): ?>
                        <a href="<?= base_url('currencies/edit/' . $currency['id']) ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Currency
                        </a>
                    <?php endif; ?>
                    <?php if (has_permission('settings', 'read')): ?>
                        <a href="<?= base_url('currencies/rates') ?>" class="btn btn-outline-primary">
                            <i class="bi bi-graph-up"></i> Manage Exchange Rates
                        </a>
                    <?php endif; ?>
                    <?php if (has_permission('settings', 'delete') && !($currency['is_base'] ?? false)): ?>
                        <a href="<?= base_url('currencies/delete/' . $currency['id']) ?>" class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this currency?')">
                            <i class="bi bi-trash"></i> Delete Currency
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

