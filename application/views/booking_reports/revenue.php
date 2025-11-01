<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Revenue by Facility</h1>
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

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Revenue</h6>
                    <h4 class="text-success mb-0"><?= format_currency($total_revenue) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Paid Revenue</h6>
                    <h4 class="text-primary mb-0"><?= format_currency($total_paid) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Pending Revenue</h6>
                    <h4 class="text-warning mb-0"><?= format_currency($total_pending) ?></h4>
                </div>
            </div>
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
                            <th class="text-end">Total Revenue</th>
                            <th class="text-end">Paid Revenue</th>
                            <th class="text-end">Pending Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($revenue_by_facility)): ?>
                            <?php foreach ($revenue_by_facility as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['facility_name']) ?></td>
                                    <td><?= $item['total_bookings'] ?></td>
                                    <td class="text-end"><?= format_currency($item['total_revenue']) ?></td>
                                    <td class="text-end text-success"><?= format_currency($item['paid_revenue']) ?></td>
                                    <td class="text-end text-warning"><?= format_currency($item['pending_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No data found for the selected period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-primary">
                        <tr>
                            <th>Total</th>
                            <th></th>
                            <th class="text-end"><?= format_currency($total_revenue) ?></th>
                            <th class="text-end"><?= format_currency($total_paid) ?></th>
                            <th class="text-end"><?= format_currency($total_pending) ?></th>
                        </tr>
                    </tfoot>
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

