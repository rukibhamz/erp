<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title mb-0">Manager Dashboard</h1>
            <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars(($current_user['first_name'] ?? '') . ' ' . ($current_user['last_name'] ?? '') ?: ($current_user['username'] ?? 'User')) ?>!</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('reports') ?>" class="btn btn-primary">
                <i class="bi bi-graph-up"></i> Reports
            </a>
        </div>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon success me-3">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <div class="stat-label">Today's Revenue</div>
                        <div class="stat-number"><?= format_currency($kpis['revenue_today'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon primary me-3">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <div class="stat-label">Pending Bookings</div>
                        <div class="stat-number"><?= format_large_number($kpis['bookings_pending'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon warning me-3">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <div class="stat-label">Occupancy Rate</div>
                        <div class="stat-number"><?= number_format($kpis['occupancy_rate'] ?? 0, 1) ?>%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon danger me-3">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <div class="stat-label">Outstanding Receivables</div>
                        <div class="stat-number"><?= format_currency($kpis['outstanding_receivables'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon info me-3">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <div class="stat-label">Cash Balance</div>
                        <div class="stat-number"><?= format_currency($kpis['cash_balance'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon primary me-3">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <div>
                        <div class="stat-label">Inventory Value</div>
                        <div class="stat-number"><?= format_currency($kpis['inventory_value'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon success me-3">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Invoices</div>
                        <div class="stat-number"><?= format_large_number($kpis['total_invoices'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon warning me-3">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Expenses</div>
                        <div class="stat-number"><?= format_currency($kpis['total_expenses'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Revenue Trend (Last 12 Months)</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-calendar"></i> Booking Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="bookingChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Expense Breakdown</h5>
            </div>
            <div class="card-body">
                <canvas id="expenseChart" height="150"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Module Activity</h5>
            </div>
            <div class="card-body">
                <canvas id="moduleChart" height="150"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats Widgets -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="bi bi-clock-history"></i> Pending Payments</h6>
            </div>
            <div class="card-body">
                <h4 class="mb-1"><?= format_large_number($quick_stats['pending_payments']['count'] ?? 0) ?></h4>
                <p class="text-muted mb-0 small"><?= format_currency($quick_stats['pending_payments']['amount'] ?? 0) ?></p>
                <a href="<?= base_url('receivables/invoices') ?>" class="btn btn-sm btn-primary mt-2">View All</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="bi bi-calendar-x"></i> Expiring Leases (60 days)</h6>
            </div>
            <div class="card-body">
                <h4 class="mb-1"><?= count($quick_stats['expiring_leases'] ?? []) ?></h4>
                <p class="text-muted mb-0 small">Leases expiring soon</p>
                <a href="<?= base_url('leases') ?>" class="btn btn-sm btn-primary mt-2">View All</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Low Stock Items</h6>
            </div>
            <div class="card-body">
                <h4 class="mb-1"><?= count($quick_stats['low_stock_items'] ?? []) ?></h4>
                <p class="text-muted mb-0 small">Items below reorder point</p>
                <a href="<?= base_url('inventory/stock-levels') ?>" class="btn btn-sm btn-primary mt-2">View All</a>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions & Recent Items -->
<div class="row g-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2 d-md-flex">
                    <a href="<?= base_url('receivables/invoices/create') ?>" class="btn btn-dark">
                        <i class="bi bi-file-earmark-plus"></i> Create Invoice
                    </a>
                    <a href="<?= base_url('receivables/payments/create') ?>" class="btn btn-primary">
                        <i class="bi bi-cash-coin"></i> Record Payment
                    </a>
                    <a href="<?= base_url('bookings/create') ?>" class="btn btn-primary">
                        <i class="bi bi-calendar-plus"></i> New Booking
                    </a>
                    <a href="<?= base_url('payables/bills/create') ?>" class="btn btn-primary">
                        <i class="bi bi-receipt-cutoff"></i> Add Expense
                    </a>
                    <a href="<?= base_url('reports') ?>" class="btn btn-primary">
                        <i class="bi bi-graph-up"></i> Generate Report
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Bookings -->
        <div class="card mt-3">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="bi bi-calendar-check"></i> Recent Bookings</h6>
            </div>
            <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                <?php if (empty($recent_bookings)): ?>
                    <p class="text-muted small mb-0">No recent bookings</p>
                <?php else: ?>
                    <?php foreach (array_slice($recent_bookings, 0, 5) as $booking): ?>
                        <div class="mb-2 pb-2 border-bottom">
                            <strong><?= htmlspecialchars($booking['booking_number'] ?? '') ?></strong><br>
                            <small class="text-muted"><?= date('M d, Y', strtotime($booking['booking_date'] ?? '')) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Revenue Trend Chart
const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($revenue_trend ?? [], 'month')) ?>,
            datasets: [{
                label: 'Revenue',
                data: <?= json_encode(array_column($revenue_trend ?? [], 'revenue')) ?>,
                borderColor: '#000',
                backgroundColor: 'rgba(0,0,0,0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₦' + new Intl.NumberFormat().format(value);
                        }
                    }
                }
            }
        }
    });
}

// Booking Trend Chart
const bookingCtx = document.getElementById('bookingChart');
if (bookingCtx) {
    new Chart(bookingCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($booking_trend ?? [], 'month')) ?>,
            datasets: [{
                label: 'Bookings',
                data: <?= json_encode(array_column($booking_trend ?? [], 'count')) ?>,
                backgroundColor: '#000'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

// Expense Breakdown Chart
const expenseCtx = document.getElementById('expenseChart');
if (expenseCtx) {
    const expenseData = <?= json_encode($expense_breakdown ?? []) ?>;
    new Chart(expenseCtx, {
        type: 'pie',
        data: {
            labels: expenseData.map(e => e.account_name || 'Unknown'),
            datasets: [{
                data: expenseData.map(e => parseFloat(e.total || 0)),
                backgroundColor: [
                    '#000', '#333', '#666', '#999', '#ccc',
                    '#4b5563', '#6b7280', '#9ca3af', '#d1d5db', '#e5e7eb'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Module Activity Chart
const moduleCtx = document.getElementById('moduleChart');
if (moduleCtx) {
    const moduleData = <?= json_encode($module_activity ?? []) ?>;
    new Chart(moduleCtx, {
        type: 'bar',
        data: {
            labels: moduleData.map(m => m.module || 'Unknown'),
            datasets: [{
                label: 'Activity',
                data: moduleData.map(m => parseInt(m.count || 0)),
                backgroundColor: '#000'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}
</script>
