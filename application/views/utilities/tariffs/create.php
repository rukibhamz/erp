<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Tariff</h1>
        <a href="<?= base_url('utilities/tariffs') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/utilities/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="<?= base_url('utilities/tariffs/create') ?>
            <?php echo csrf_field(); ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="tariff_name" class="form-label">Tariff Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="tariff_name" name="tariff_name" required>
                </div>
                
                <div class="col-md-6">
                    <label for="provider_id" class="form-label">Provider <span class="text-danger">*</span></label>
                    <select class="form-select" id="provider_id" name="provider_id" required>
                        <option value="">Select Provider</option>
                        <?php foreach ($providers as $provider): ?>
                            <option value="<?= $provider['id'] ?>">
                                <?= htmlspecialchars($provider['provider_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="effective_date" class="form-label">Effective Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="effective_date" name="effective_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="expiry_date" class="form-label">Expiry Date</label>
                    <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                </div>
                
                <div class="col-md-12">
                    <h6 class="mb-3">Pricing Structure</h6>
                </div>
                
                <div class="col-md-6">
                    <label for="fixed_charge" class="form-label">Fixed Charge (Monthly)</label>
                    <input type="number" step="0.01" class="form-control" id="fixed_charge" name="fixed_charge" value="0">
                </div>
                
                <div class="col-md-6">
                    <label for="variable_rate" class="form-label">Variable Rate (Per Unit)</label>
                    <input type="number" step="0.01" class="form-control" id="variable_rate" name="variable_rate" value="0">
                </div>
                
                <div class="col-md-6">
                    <label for="demand_charge" class="form-label">Demand Charge</label>
                    <input type="number" step="0.01" class="form-control" id="demand_charge" name="demand_charge" value="0">
                </div>
                
                <div class="col-md-6">
                    <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                    <input type="number" step="0.01" class="form-control" id="tax_rate" name="tax_rate" value="0">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('utilities/tariffs') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Tariff
                </button>
            </div>
        </form>
    </div>
</div>

