<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Generate Utility Bill</h1>
        <a href="<?= base_url('utilities/bills') ?>" class="btn btn-outline-secondary">
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
        <form action="<?= base_url('utilities/bills/generate' . ($selected_meter_id ? '/' . $selected_meter_id : '')) ?>
            <?php echo csrf_field(); ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="meter_id" class="form-label">Meter <span class="text-danger">*</span></label>
                    <select class="form-select" id="meter_id" name="meter_id" required onchange="loadMeterDetails(this.value)">
                        <option value="">Select Meter</option>
                        <?php foreach ($meters as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= ($selected_meter_id ?? null) == $m['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['meter_number']) ?> - <?= htmlspecialchars($m['utility_type_name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($meter): ?>
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <strong>Meter:</strong> <?= htmlspecialchars($meter['meter_number']) ?><br>
                            <strong>Utility Type:</strong> <?= htmlspecialchars($meter['utility_type_name']) ?> (<?= htmlspecialchars($meter['unit_of_measure']) ?>)<br>
                            <strong>Location:</strong> <?= htmlspecialchars($meter['meter_location'] ?: 'N/A') ?>
                            <?php if ($meter['property_name']): ?>
                                <br><strong>Property:</strong> <?= htmlspecialchars($meter['property_name']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="col-md-6">
                    <label for="billing_period_start" class="form-label">Billing Period Start <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="billing_period_start" name="billing_period_start" value="<?= date('Y-m-01') ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="billing_period_end" class="form-label">Billing Period End <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="billing_period_end" name="billing_period_end" value="<?= date('Y-m-t') ?>" required>
                </div>
                
                <?php if ($last_reading): ?>
                    <div class="col-md-12">
                        <div class="alert alert-success">
                            <strong>Last Reading:</strong> <?= number_format($last_reading['reading_value'], 2) ?> 
                            on <?= date('M d, Y', strtotime($last_reading['reading_date'])) ?>
                        </div>
                    </div>
                <?php elseif ($meter && $meter['last_reading']): ?>
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <strong>Last Recorded Reading:</strong> <?= number_format($meter['last_reading'], 2) ?>
                            <?php if ($meter['last_reading_date']): ?>
                                on <?= date('M d, Y', strtotime($meter['last_reading_date'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="col-md-6">
                    <label for="previous_reading" class="form-label">Previous Reading <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" class="form-control" id="previous_reading" name="previous_reading" 
                           value="<?= $last_reading ? number_format($last_reading['reading_value'], 4) : ($meter['last_reading'] ?? 0) ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="current_reading" class="form-label">Current Reading <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" class="form-control" id="current_reading" name="current_reading" required onchange="calculateConsumption()">
                </div>
                
                <div class="col-md-12">
                    <div class="alert alert-secondary">
                        <strong>Calculated Consumption:</strong> <span id="consumption_display">0.00</span>
                        <?php if ($meter): ?>
                            <small class="text-muted"><?= htmlspecialchars($meter['unit_of_measure']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('utilities/bills') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-receipt"></i> Generate Bill
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function calculateConsumption() {
    const currentReading = parseFloat(document.getElementById('current_reading').value) || 0;
    const previousReading = parseFloat(document.getElementById('previous_reading').value) || 0;
    const consumption = Math.max(0, currentReading - previousReading);
    document.getElementById('consumption_display').textContent = consumption.toFixed(4);
}

<?php if ($selected_meter_id): ?>
    document.addEventListener('DOMContentLoaded', function() {
        loadMeterDetails(<?= $selected_meter_id ?>);
    });
<?php endif; ?>
</script>

