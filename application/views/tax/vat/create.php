<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create VAT Return</h1>
        <a href="<?= base_url('tax/vat') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/tax/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_url('tax/vat/create') ?>">
            <?php echo csrf_field(); ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="period_start" class="form-label">Period Start <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="period_start" name="period_start" required>
                    <small class="text-muted">Start date of the VAT period</small>
                </div>
                <div class="col-md-6">
                    <label for="period_end" class="form-label">Period End <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="period_end" name="period_end" required>
                    <small class="text-muted">End date of the VAT period</small>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> 
                <strong>Note:</strong> The VAT return will be calculated automatically based on invoices and bills 
                within the selected period. Output VAT (from sales) and Input VAT (from purchases) will be calculated, 
                and the net VAT payable will be determined.
            </div>

            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> 
                <strong>Important:</strong> Ensure all invoices and bills for this period are properly recorded 
                before creating the VAT return.
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('tax/vat') ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create VAT Return
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default period to last month
    const today = new Date();
    const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
    const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    
    document.getElementById('period_start').value = lastMonthStart.toISOString().split('T')[0];
    document.getElementById('period_end').value = lastMonthEnd.toISOString().split('T')[0];
});
</script>

