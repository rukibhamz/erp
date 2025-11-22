<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Facility</h1>
        <a href="<?= base_url('facilities') ?>" class="btn btn-outline-secondary">
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
            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="facility_code" class="form-label">Facility Code</label>
                        <input type="text" id="facility_code" name="facility_code" class="form-control" placeholder="Leave empty to auto-generate">
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="facility_name" class="form-label">
                            Facility Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="facility_name" name="facility_name" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" name="capacity" class="form-control" min="0" value="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Minimum Duration (Hours)</label>
                            <input type="number" name="minimum_duration" class="form-control" min="1" value="1">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Security Deposit</label>
                            <input type="number" name="security_deposit" class="form-control" step="0.01" value="0">
                        </div>
                    </div>
                </div>

                <h5 class="mb-3">Pricing</h5>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Hourly Rate</label>
                            <input type="number" name="hourly_rate" class="form-control" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Daily Rate</label>
                            <input type="number" name="daily_rate" class="form-control" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Weekend Rate</label>
                            <input type="number" name="weekend_rate" class="form-control" step="0.01" value="0">
                            <small class="text-muted">Leave 0 to use regular rate</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Peak Rate</label>
                            <input type="number" name="peak_rate" class="form-control" step="0.01" value="0">
                            <small class="text-muted">Leave 0 to use regular rate</small>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Peak Hours Start</label>
                            <input type="time" name="peak_start" class="form-control" value="17:00">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Peak Hours End</label>
                            <input type="time" name="peak_end" class="form-control" value="22:00">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Setup Time (Minutes)</label>
                            <input type="number" name="setup_time" class="form-control" min="0" value="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Cleanup Time (Minutes)</label>
                            <input type="number" name="cleanup_time" class="form-control" min="0" value="0">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('facilities') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Facility</button>
                </div>
            </form>
        </div>
    </div>
</div>

