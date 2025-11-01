<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Booking Reports</h1>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= base_url('booking-reports') ?>" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Bookings</h6>
                    <h2 class="mb-0"><?= $stats['total_bookings'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Revenue</h6>
                    <h2 class="mb-0 text-success"><?= format_currency($stats['total_revenue']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Paid Revenue</h6>
                    <h2 class="mb-0 text-primary"><?= format_currency($stats['paid_revenue']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Pending Revenue</h6>
                    <h2 class="mb-0 text-warning"><?= format_currency($stats['pending_revenue']) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Links -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <a href="<?= base_url('booking-reports/revenue') ?>?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
               class="card text-decoration-none">
                <div class="card-body text-center">
                    <i class="bi bi-cash-stack" style="font-size: 2rem; color: #0d6efd;"></i>
                    <h6 class="mt-2 mb-0">Revenue Report</h6>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <a href="<?= base_url('booking-reports/utilization') ?>?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
               class="card text-decoration-none">
                <div class="card-body text-center">
                    <i class="bi bi-bar-chart" style="font-size: 2rem; color: #198754;"></i>
                    <h6 class="mt-2 mb-0">Utilization Report</h6>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <a href="<?= base_url('booking-reports/customer-history') ?>?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
               class="card text-decoration-none">
                <div class="card-body text-center">
                    <i class="bi bi-people" style="font-size: 2rem; color: #dc3545;"></i>
                    <h6 class="mt-2 mb-0">Customer History</h6>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <a href="<?= base_url('booking-reports/pending-payments') ?>?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
               class="card text-decoration-none">
                <div class="card-body text-center">
                    <i class="bi bi-clock-history" style="font-size: 2rem; color: #ffc107;"></i>
                    <h6 class="mt-2 mb-0">Pending Payments</h6>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Revenue by Facility -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Revenue by Facility</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($revenue_by_facility)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Facility</th>
                                        <th class="text-end">Bookings</th>
                                        <th class="text-end">Revenue</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Pending</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($revenue_by_facility as $facility): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($facility['facility_name']) ?></td>
                                            <td class="text-end"><?= $facility['total_bookings'] ?></td>
                                            <td class="text-end"><?= format_currency($facility['total_revenue']) ?></td>
                                            <td class="text-end text-success"><?= format_currency($facility['paid_revenue']) ?></td>
                                            <td class="text-end text-warning"><?= format_currency($facility['pending_revenue']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bookings by Status -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Bookings by Status</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($bookings_by_status)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th class="text-end">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings_by_status as $status => $count): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $status === 'confirmed' ? 'success' : 
                                                    ($status === 'pending' ? 'warning' : 
                                                    ($status === 'completed' ? 'info' : 'danger'))
                                                ?>">
                                                    <?= ucfirst($status) ?>
                                                </span>
                                            </td>
                                            <td class="text-end"><?= $count ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
