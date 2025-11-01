<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Customer History Report</h1>
        <a href="<?= base_url('booking-reports') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= base_url('booking-reports/customer-history') ?>" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Customer Email (Optional)</label>
                    <input type="email" name="customer_email" class="form-control" 
                           value="<?= htmlspecialchars($customer_email ?? '') ?>" 
                           placeholder="Filter by customer email">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($customer_email && !empty($bookings)): ?>
        <!-- Customer Detail View -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Customer: <?= htmlspecialchars($customer_email) ?></h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Total Bookings</h6>
                                <h3><?= $customer_stats['total_bookings'] ?? 0 ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Total Spent</h6>
                                <h3 class="text-success"><?= format_currency($customer_stats['total_spent'] ?? 0) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Outstanding</h6>
                                <h3 class="text-danger"><?= format_currency($customer_stats['outstanding_balance'] ?? 0) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Avg Booking Value</h6>
                                <h3><?= format_currency($customer_stats['avg_booking_value'] ?? 0) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <h6>Booking History</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Booking #</th>
                                <th>Facility</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['booking_number']) ?></td>
                                    <td><?= htmlspecialchars($booking['facility_name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($booking['booking_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $booking['status'] === 'confirmed' ? 'success' : 
                                            ($booking['status'] === 'pending' ? 'warning' : 'secondary')
                                        ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end"><?= format_currency($booking['total_amount']) ?></td>
                                    <td class="text-end"><?= format_currency($booking['balance_amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif (!empty($customer_stats)): ?>
        <!-- All Customers Summary -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Customer Summary</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th class="text-end">Total Bookings</th>
                                <th class="text-end">Total Spent</th>
                                <th class="text-end">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customer_stats as $customer): ?>
                                <tr>
                                    <td><?= htmlspecialchars($customer['name']) ?></td>
                                    <td>
                                        <a href="<?= base_url('booking-reports/customer-history?customer_email=' . urlencode($customer['email'])) ?>">
                                            <?= htmlspecialchars($customer['email']) ?>
                                        </a>
                                    </td>
                                    <td class="text-end"><?= $customer['total_bookings'] ?></td>
                                    <td class="text-end text-success"><?= format_currency($customer['total_spent']) ?></td>
                                    <td class="text-end <?= $customer['outstanding_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= format_currency($customer['outstanding_balance']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No customer data available. Use filters to search for specific customers.
        </div>
    <?php endif; ?>
</div>
