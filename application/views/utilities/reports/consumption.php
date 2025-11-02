<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Consumption Report</h1>
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
            <div class="col-md-3">
                <label class="form-label">Meter</label>
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
            <div class="col-md-3">
                <a href="<?= base_url('utilities/reports/consumption') ?>" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<?php if ($meter): ?>
    <div class="card mb-4">
        <div class="card-body">
            <h6>Meter Information</h6>
            <dl class="row mb-0">
                <dt class="col-sm-3">Meter Number:</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($meter['meter_number']) ?></dd>
                <dt class="col-sm-3">Utility Type:</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($meter['utility_type_name'] ?? 'N/A') ?></dd>
                <dt class="col-sm-3">Total Consumption:</dt>
                <dd class="col-sm-9"><strong><?= number_format($total_consumption, 2) ?> <?= htmlspecialchars($meter['unit_of_measure'] ?? 'units') ?></strong></dd>
            </dl>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($readings)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-clipboard-data" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No consumption data found for the selected period.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reading</th>
                            <th>Previous</th>
                            <th>Consumption</th>
                            <th>Type</th>
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
                                        <small class="text-muted"><?= htmlspecialchars($meter['unit_of_measure']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $reading['reading_type'] === 'actual' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($reading['reading_type']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3">Total Consumption:</th>
                            <th><?= number_format($total_consumption, 2) ?> <?= $meter ? htmlspecialchars($meter['unit_of_measure']) : 'units' ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

