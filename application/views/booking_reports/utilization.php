<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Facility Utilization Report</h1>
        <div>
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="<?= base_url('booking-reports') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Facility</th>
                            <th>Total Bookings</th>
                            <th class="text-end">Booked Hours</th>
                            <th class="text-end">Available Hours</th>
                            <th class="text-end">Utilization %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($utilization)): ?>
                            <?php foreach ($utilization as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['facility']['facility_name']) ?></td>
                                    <td><?= $item['total_bookings'] ?></td>
                                    <td class="text-end"><?= number_format($item['booked_hours'], 1) ?></td>
                                    <td class="text-end"><?= number_format($item['available_hours'], 1) ?></td>
                                    <td class="text-end">
                                        <?php
                                        $percent = $item['utilization_percent'];
                                        $class = $percent >= 80 ? 'text-danger' : ($percent >= 50 ? 'text-warning' : 'text-success');
                                        ?>
                                        <span class="<?= $class ?>">
                                            <?= number_format($percent, 1) ?>%
                                        </span>
                                        <div class="progress mt-1" style="height: 5px; width: 100px; margin-left: auto;">
                                            <div class="progress-bar <?= $percent >= 80 ? 'bg-danger' : ($percent >= 50 ? 'bg-warning' : 'bg-success') ?>" 
                                                 style="width: <?= min(100, $percent) ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No data found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .card.mb-4 { display: none; }
    .card { border: none; box-shadow: none; }
}
</style>

