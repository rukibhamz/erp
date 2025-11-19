<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Generate Utility Bill</h1>
        <a href="<?= base_url('utilities/bills') ?>" class="btn btn-primary">
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
        <form method="POST" action="<?= base_url('utilities/bills/generate') ?>">
            <?php echo csrf_field(); ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="meter_id" class="form-label">Meter <span class="text-danger">*</span></label>
                    <select class="form-select" id="meter_id" name="meter_id" required>
                        <option value="">Select Meter</option>
                        <?php foreach ($meters ?? [] as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= ($selected_meter_id ?? null) == $m['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['meter_number']) ?> - 
                                <?= htmlspecialchars($m['utility_type_name'] ?? 'N/A') ?>
                                <?php if ($m['property_name'] ?? $m['Location_name'] ?? null): ?>
                                    (<?= htmlspecialchars($m['property_name'] ?? $m['Location_name']) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="billing_date" class="form-label">Billing Date</label>
                    <input type="date" class="form-control" id="billing_date" name="billing_date" 
                           value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <?php if ($meter): ?>
                <div class="alert alert-info mb-3">
                    <strong>Meter Information:</strong><br>
                    Meter Number: <?= htmlspecialchars($meter['meter_number']) ?><br>
                    Utility Type: <?= htmlspecialchars($meter['utility_type_name'] ?? 'N/A') ?><br>
                    <?php if ($last_reading): ?>
                        Last Reading: <strong><?= number_format($last_reading['reading_value'] ?? 0, 2) ?></strong> 
                        on <?= date('M d, Y', strtotime($last_reading['reading_date'] ?? 'now')) ?>
                    <?php else: ?>
                        Last Reading: <strong><?= number_format($meter['last_reading'] ?? 0, 2) ?></strong>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="billing_period_start" class="form-label">Billing Period Start <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="billing_period_start" 
                           name="billing_period_start" required>
                </div>
                <div class="col-md-6">
                    <label for="billing_period_end" class="form-label">Billing Period End <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="billing_period_end" 
                           name="billing_period_end" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="previous_reading" class="form-label">Previous Reading <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="previous_reading" 
                           name="previous_reading" required min="0">
                    <small class="text-muted">Reading at start of billing period</small>
                </div>
                <div class="col-md-6">
                    <label for="current_reading" class="form-label">Current Reading <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="current_reading" 
                           name="current_reading" required min="0">
                    <small class="text-muted">Reading at end of billing period</small>
                </div>
            </div>

            <div class="alert alert-warning">
                <i class="bi bi-info-circle"></i> 
                <strong>Note:</strong> Consumption will be calculated automatically (Current Reading - Previous Reading). 
                Bill amount will be calculated based on the tariff associated with the meter's provider.
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('utilities/bills') ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Generate Bill
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const meterSelect = document.getElementById('meter_id');
    const previousReadingInput = document.getElementById('previous_reading');
    const currentReadingInput = document.getElementById('current_reading');
    
    // Load meter details when selected
    meterSelect.addEventListener('change', function() {
        const meterId = this.value;
        if (meterId) {
            window.location.href = '<?= base_url('utilities/bills/generate') ?>?meter_id=' + meterId;
        }
    });
    
    // Auto-fill previous reading from last reading
    <?php if ($meter && $last_reading): ?>
        const lastReading = <?= floatval($last_reading['reading_value'] ?? $meter['last_reading'] ?? 0) ?>;
        if (lastReading > 0 && !previousReadingInput.value) {
            previousReadingInput.value = lastReading;
        }
    <?php endif; ?>
    
    // Set default billing period (last month)
    const today = new Date();
    const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
    const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    
    document.getElementById('billing_period_start').value = lastMonthStart.toISOString().split('T')[0];
    document.getElementById('billing_period_end').value = lastMonthEnd.toISOString().split('T')[0];
});
</script>

