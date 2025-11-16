<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Meter Readings</h1>
        <a href="<?= base_url('utilities/readings/create' . ($selected_meter_id ? '?meter_id=' . $selected_meter_id : '')) ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Record Reading
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

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filter by Meter</label>
                <select name="meter_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Meters</option>
                    <?php foreach ($meters as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($selected_meter_id ?? null) == $m['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['meter_number']) ?> - <?= htmlspecialchars($m['utility_type_name'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-2">
                <a href="<?= base_url('utilities/readings') ?>" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($readings)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-clipboard-data" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No readings found.</p>
            <a href="<?= base_url('utilities/readings/create' . ($selected_meter_id ? '?meter_id=' . $selected_meter_id : '')) ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Record First Reading
            </a>
        </div>
    </div>
<?php else: ?>
    <?php if ($meter): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h6>Meter Information</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-3">Meter Number:</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($meter['meter_number']) ?></dd>
                    <dt class="col-sm-3">Utility Type:</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($meter['utility_type_name'] ?? 'N/A') ?></dd>
                    <dt class="col-sm-3">Location:</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($meter['meter_location'] ?: 'N/A') ?></dd>
                </dl>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reading Value</th>
                            <th>Previous Reading</th>
                            <th>Consumption</th>
                            <th>Type</th>
                            <th>Reader</th>
                            <th>Verified</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($readings as $reading): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($reading['reading_date'])) ?></td>
                                <td><strong><?= number_format($reading['reading_value'], 2) ?></strong></td>
                                <td><?= number_format($reading['previous_reading'] ?? 0, 2) ?></td>
                                <td>
                                    <strong class="text-success"><?= number_format($reading['consumption'] ?? 0, 2) ?></strong>
                                    <?php if ($meter): ?>
                                        <small class="text-muted"><?= htmlspecialchars($meter['unit_of_measure'] ?? '') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $reading['reading_type'] === 'actual' ? 'success' : ($reading['reading_type'] === 'estimated' ? 'warning' : 'info') ?>">
                                        <?= ucfirst($reading['reading_type']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($reading['reader_name'] ?: 'N/A') ?></td>
                                <td>
                                    <?php if ($reading['is_verified']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Verified</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($reading['photo_url']): ?>
                                        <a href="<?= base_url($reading['photo_url']) ?>" target="_blank" class="btn btn-sm btn-primary" title="View Photo">
                                            <i class="bi bi-image"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

