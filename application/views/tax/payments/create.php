<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Record Tax Payment</h1>
        <a href="<?= base_url('tax/payments') ?>" class="btn btn-primary">
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
        <h5 class="mb-0"><i class="bi bi-credit-card"></i> Payment Information</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('tax/payments/create') ?>" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
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
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select" required>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                        <option value="online">Online Payment</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Reference Number</label>
                    <input type="text" name="reference" class="form-control" placeholder="Payment reference/teller number">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Period Covered</label>
                    <input type="text" name="period_covered" class="form-control" placeholder="e.g., 2024-01 or Q1 2024">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank_name" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account Number</label>
                    <input type="text" name="account_number" class="form-control">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Payment Receipt</label>
                <input type="file" name="receipt" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Upload payment receipt (PDF, JPG, PNG)</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes about this payment"></textarea>
            </div>
            
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?= base_url('tax/payments') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-dark">Record Payment</button>
            </div>
        </form>
    </div>
</div>
