<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Record Meter Reading</h1>
        <a href="<?= base_url('utilities/readings' . ($selected_meter_id ? '?meter_id=' . $selected_meter_id : '')) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="<?= base_url('utilities/readings/create') ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="meter_id" class="form-label">Meter <span class="text-danger">*</span></label>
                    <select class="form-select" id="meter_id" name="meter_id" required onchange="loadLastReading(this.value)">
                        <option value="">Select Meter</option>
                        <?php foreach ($meters as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= ($selected_meter_id ?? null) == $m['id'] ? 'selected' : '' ?> data-last-reading="<?= $m['last_reading'] ?? 0 ?>">
                                <?= htmlspecialchars($m['meter_number']) ?> - <?= htmlspecialchars($m['utility_type_name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="reading_date" class="form-label">Reading Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="reading_date" name="reading_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <?php if ($meter): ?>
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <strong>Meter:</strong> <?= htmlspecialchars($meter['meter_number']) ?><br>
                            <strong>Utility Type:</strong> <?= htmlspecialchars($meter['utility_type_name']) ?> (<?= htmlspecialchars($meter['unit_of_measure']) ?>)<br>
                            <?php if ($last_reading): ?>
                                <strong>Last Reading:</strong> <?= number_format($last_reading['reading_value'], 2) ?> on <?= date('M d, Y', strtotime($last_reading['reading_date'])) ?>
                            <?php elseif ($meter['last_reading']): ?>
                                <strong>Last Reading:</strong> <?= number_format($meter['last_reading'], 2) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="col-md-6">
                    <label for="reading_value" class="form-label">Current Reading <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" class="form-control" id="reading_value" name="reading_value" required onchange="calculateConsumption()">
                    <?php if ($meter): ?>
                        <small class="text-muted">Unit: <?= htmlspecialchars($meter['unit_of_measure']) ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Previous Reading</label>
                    <input type="number" step="0.0001" class="form-control" id="previous_reading_display" value="<?= $last_reading ? number_format($last_reading['reading_value'], 4) : ($meter['last_reading'] ?? 0) ?>" readonly>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Calculated Consumption</label>
                    <input type="text" class="form-control" id="consumption_display" readonly>
                </div>
                
                <div class="col-md-6">
                    <label for="reading_type" class="form-label">Reading Type</label>
                    <select class="form-select" id="reading_type" name="reading_type">
                        <option value="actual">Actual</option>
                        <option value="estimated">Estimated</option>
                        <option value="corrected">Corrected</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="reader_name" class="form-label">Reader Name</label>
                    <input type="text" class="form-control" id="reader_name" name="reader_name" value="<?= htmlspecialchars($this->session['username'] ?? '') ?>">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Verify Reading</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" id="is_verified" name="is_verified" value="1">
                        <label class="form-check-label" for="is_verified">
                            Mark as verified
                        </label>
                    </div>
                </div>
                
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any additional notes about this reading"></textarea>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('utilities/readings') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Record Reading
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function loadLastReading(meterId) {
    const selectedOption = document.querySelector('#meter_id option[value="' + meterId + '"]');
    if (selectedOption) {
        const lastReading = selectedOption.getAttribute('data-last-reading') || 0;
        document.getElementById('previous_reading_display').value = parseFloat(lastReading).toFixed(4);
        calculateConsumption();
    }
}

function calculateConsumption() {
    const currentReading = parseFloat(document.getElementById('reading_value').value) || 0;
    const previousReading = parseFloat(document.getElementById('previous_reading_display').value) || 0;
    const consumption = Math.max(0, currentReading - previousReading);
    document.getElementById('consumption_display').value = consumption.toFixed(4);
}

// Calculate on page load if meter is pre-selected
<?php if ($selected_meter_id): ?>
    document.addEventListener('DOMContentLoaded', function() {
        loadLastReading(<?= $selected_meter_id ?>);
    });
<?php endif; ?>
</script>

