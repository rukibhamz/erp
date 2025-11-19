<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Stock Take</h1>
        <a href="<?= base_url('inventory/stock-takes') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/inventory/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_url('inventory/stock-takes/create') ?>">
            <?php echo csrf_field(); ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="location_id" class="form-label">Location <span class="text-danger">*</span></label>
                    <select class="form-select" id="location_id" name="location_id" required>
                        <option value="">Select Location</option>
                        <?php foreach ($locations ?? [] as $location): ?>
                            <option value="<?= $location['id'] ?>">
                                <?= htmlspecialchars($location['Location_name'] ?? $location['property_name'] ?? 'N/A') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Select the location to perform stock take</small>
                </div>
                <div class="col-md-6">
                    <label for="scheduled_date" class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" 
                           value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="type" class="form-label">Stock Take Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="full">Full Stock Take</option>
                        <option value="partial">Partial Stock Take</option>
                        <option value="cycle">Cycle Count</option>
                    </select>
                    <small class="text-muted">
                        <strong>Full:</strong> Count all items<br>
                        <strong>Partial:</strong> Count specific items<br>
                        <strong>Cycle:</strong> Regular cycle count
                    </small>
                </div>
                <div class="col-md-6">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                              placeholder="Additional notes about this stock take (optional)"></textarea>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> 
                <strong>Note:</strong> After creating the stock take, a count sheet will be automatically generated 
                with all items at the selected location. You can then start the stock take and record actual counts.
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('inventory/stock-takes') ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Stock Take
                </button>
            </div>
        </form>
    </div>
</div>

