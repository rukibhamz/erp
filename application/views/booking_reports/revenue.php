<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Revenue Report</h1>
        <a href="<?= base_url('booking-reports') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= base_url('booking-reports/revenue') ?>" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Facility (Optional)</label>
                    <select name="facility_id" class="form-select">
                        <option value="">All Facilities</option>
                        <?php foreach ($facilities as $facility): ?>
                            <option value="<?= $facility['id'] ?>" <?= $facility_id == $facility['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($facility['facility_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Revenue by Date -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Daily Revenue</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($revenue_by_date)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Bookings</th>
                                <th class="text-end">Total Revenue</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenue_by_date as $data): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($data['date'])) ?></td>
                                    <td class="text-end"><?= $data['total_bookings'] ?></td>
                                    <td class="text-end"><strong><?= format_currency($data['total_revenue']) ?></strong></td>
                                    <td class="text-end text-success"><?= format_currency($data['paid_revenue']) ?></td>
                                    <td class="text-end text-warning"><?= format_currency($data['pending_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total</td>
                                <td class="text-end"><?= array_sum(array_column($revenue_by_date, 'total_bookings')) ?></td>
                                <td class="text-end"><?= format_currency(array_sum(array_column($revenue_by_date, 'total_revenue'))) ?></td>
                                <td class="text-end"><?= format_currency(array_sum(array_column($revenue_by_date, 'paid_revenue'))) ?></td>
                                <td class="text-end"><?= format_currency(array_sum(array_column($revenue_by_date, 'pending_revenue'))) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No revenue data for the selected period</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Revenue by Facility -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Revenue by Facility</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($revenue_by_facility)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Facility</th>
                                <th class="text-end">Bookings</th>
                                <th class="text-end">Total Revenue</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenue_by_facility as $facility): ?>
                                <tr>
                                    <td><?= htmlspecialchars($facility['facility_name']) ?></td>
                                    <td class="text-end"><?= $facility['total_bookings'] ?></td>
                                    <td class="text-end"><strong><?= format_currency($facility['total_revenue']) ?></strong></td>
                                    <td class="text-end text-success"><?= format_currency($facility['paid_revenue']) ?></td>
                                    <td class="text-end text-warning"><?= format_currency($facility['pending_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No revenue data available</p>
            <?php endif; ?>
        </div>
    </div>
</div>
