<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Tax Deadline</h1>
        <a href="<?= base_url('tax/compliance') ?>" class="btn btn-outline-dark">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Deadline Information</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('tax/compliance/create-deadline') ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tax Type <span class="text-danger">*</span></label>
                    <select name="tax_type" class="form-select" required>
                        <option value="">Select Tax Type</option>
                        <?php foreach ($tax_types as $type): ?>
                            <option value="<?= htmlspecialchars($type['code']) ?>">
                                <?= htmlspecialchars($type['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Deadline Type <span class="text-danger">*</span></label>
                    <select name="deadline_type" class="form-select" required>
                        <option value="filing">Filing</option>
                        <option value="payment">Payment</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Deadline Date <span class="text-danger">*</span></label>
                    <input type="date" name="deadline_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Period Covered</label>
                    <input type="text" name="period_covered" class="form-control" placeholder="e.g., 2024-01 or Q1 2024">
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?= base_url('tax/compliance') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-dark">Create Deadline</button>
            </div>
        </form>
    </div>
</div>
