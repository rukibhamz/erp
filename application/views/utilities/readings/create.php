<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Record Meter Reading</h1>
        <a href="<?= base_url('utilities/readings') ?>" class="btn btn-primary">
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
        <form method="POST" action="<?= base_url('utilities/readings/create') ?>">
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
                    <label for="reading_date" class="form-label">Reading Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="reading_date" name="reading_date" 
                           value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <?php if ($meter): ?>
                <div class="alert alert-info">
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
                    <label for="reading_value" class="form-label">Reading Value <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="reading_value" 
                           name="reading_value" required min="0">
                    <small class="text-muted">Enter the current meter reading</small>
                </div>
                <div class="col-md-6">
                    <label for="reading_type" class="form-label">Reading Type</label>
                    <select class="form-select" id="reading_type" name="reading_type">
                        <option value="actual">Actual Reading</option>
                        <option value="estimated">Estimated</option>
                        <option value="corrected">Corrected</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="reader_name" class="form-label">Reader Name</label>
                    <input type="text" class="form-control" id="reader_name" name="reader_name" 
                           placeholder="Name of person who took the reading">
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="is_verified" name="is_verified">
                        <label class="form-check-label" for="is_verified">
                            Verified Reading
                        </label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3" 
                          placeholder="Additional notes about this reading (optional)"></textarea>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('utilities/readings') ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Record Reading
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const meterSelect = document.getElementById('meter_id');
    const readingValueInput = document.getElementById('reading_value');
    
    // Load meter details when selected
    meterSelect.addEventListener('change', function() {
        const meterId = this.value;
        if (meterId) {
            // Reload page with meter_id to show meter info
            window.location.href = '<?= base_url('utilities/readings/create') ?>?meter_id=' + meterId;
        }
    });
    
    // Auto-fill last reading + 1 if meter is preselected
    <?php if ($meter && $last_reading): ?>
        const lastReading = <?= floatval($last_reading['reading_value'] ?? $meter['last_reading'] ?? 0) ?>;
        if (lastReading > 0 && !readingValueInput.value) {
            readingValueInput.value = lastReading;
        }
    <?php endif; ?>
});
</script>

