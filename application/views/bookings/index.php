<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$sort = $sort ?? 'booking_number';
$sort_dir = $sort_dir ?? 'asc';
$pagination = $pagination ?? [];
$perPage = intval($pagination['per_page'] ?? 50);

$buildSortUrl = function ($column) use ($sort, $sort_dir) {
    $params = $_GET;
    $params['sort'] = $column;
    $params['dir'] = ($sort === $column && $sort_dir === 'asc') ? 'desc' : 'asc';
    return '?' . http_build_query($params);
};

$sortIcon = function ($column) use ($sort, $sort_dir) {
    if ($sort !== $column) {
        return '<i class="bi bi-arrow-down-up text-muted ms-1" style="font-size:0.75rem;"></i>';
    }
    return $sort_dir === 'asc'
        ? '<i class="bi bi-sort-alpha-down ms-1"></i>'
        : '<i class="bi bi-sort-alpha-up ms-1"></i>';
};

$sortableTh = function ($column, $label) use ($buildSortUrl, $sortIcon) {
  return '<th class="sortable-th"><a href="' . htmlspecialchars($buildSortUrl($column)) . '" class="text-decoration-none text-reset">' . htmlspecialchars($label) . $sortIcon($column) . '</a></th>';
};
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Bookings</h1>
        <div>
            <a href="<?= base_url('booking-reports') ?>" class="btn btn-primary">
                <i class="bi bi-graph-up"></i> Reports
            </a>
            <a href="<?= base_url('bookings/calendar') ?>" class="btn btn-primary">
                <i class="bi bi-calendar-month"></i> Calendar View
            </a>
            <?php if (has_permission('bookings', 'update')): ?>
                <a href="<?= base_url('bookings/financialReconciliation') ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-repeat"></i> Reconcile Finances
                </a>
                <a href="<?= base_url('resource-management/addons') ?>" class="btn btn-primary">
                    <i class="bi bi-puzzle"></i> Manage Add-ons
                </a>
            <?php endif; ?>
            <?php if (has_permission('bookings', 'create')): ?>
                <a href="<?= base_url('bookings/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> New Booking
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php
    $bookingHasFilters = list_has_active_filters(['status', 'date', 'search'])
        || ($selected_status ?? 'all') !== 'all';
    ?>
    <!-- Search & filters -->
    <div class="card shadow-sm mb-4 list-filters-card">
        <div class="card-body">
            <form method="GET" action="<?= base_url('bookings') ?>" class="list-filters-form">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <input type="hidden" name="dir" value="<?= htmlspecialchars($sort_dir) ?>">
                <div class="row g-2 align-items-end">
                    <?php
                    $search_col_class = 'col-md-4 col-sm-12';
                    $search_placeholder = 'Booking #, customer, email, facility…';
                    include(BASEPATH . 'views/partials/list_search_field.php');
                    ?>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label" for="filter-status">Status</label>
                        <select name="status" id="filter-status" class="form-select form-select-sm">
                            <option value="all" <?= $selected_status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="pending" <?= $selected_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $selected_status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="completed" <?= $selected_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $selected_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="in_progress" <?= $selected_status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label" for="filter-date">Date</label>
                        <input type="date" name="date" id="filter-date" class="form-control form-control-sm" value="<?= htmlspecialchars($selected_date) ?>">
                    </div>
                    <?php render_list_filter_per_page($perPage); ?>
                    <?php render_list_filter_submit_buttons(base_url('bookings')); ?>
                </div>

                <?php if ($bookingHasFilters): ?>
                <div class="list-active-filters">
                    <span class="small text-muted me-1"><i class="bi bi-funnel"></i> Active:</span>
                    <?php if (list_search_term() !== ''): ?>
                        <span class="badge bg-secondary">Search: <?= htmlspecialchars(list_search_term()) ?></span>
                    <?php endif; ?>
                    <?php if ($selected_status !== 'all'): ?>
                        <span class="badge bg-primary">Status: <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $selected_status))) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($selected_date)): ?>
                        <span class="badge bg-info text-dark"><?= date('M Y', strtotime($selected_date)) ?></span>
                    <?php endif; ?>
                    <a href="<?= base_url('bookings') ?>" class="small ms-1">Clear all</a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>


    <!-- Bookings Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <?= $sortableTh('booking_number', 'Booking #') ?>
                            <?= $sortableTh('facility', 'Facility') ?>
                            <?= $sortableTh('customer', 'Customer') ?>
                            <?= $sortableTh('date_time', 'Date & Time') ?>
                            <?= $sortableTh('duration', 'Duration') ?>
                            <?= $sortableTh('total_amount', 'Total Amount') ?>
                            <?= $sortableTh('payment_status', 'Payment Status') ?>
                            <?= $sortableTh('status', 'Status') ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bookings)): ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($booking['booking_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($booking['facility_name']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($booking['customer_name']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($booking['customer_phone'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <?= date('M d, Y', strtotime($booking['booking_date'])) ?><br>
                                        <small class="text-muted">
                                            <?= date('h:i A', strtotime($booking['start_time'])) ?> - 
                                            <?= date('h:i A', strtotime($booking['end_time'])) ?>
                                        </small>
                                    </td>
                                    <td><?= number_format($booking['duration_hours'], 1) ?> hrs</td>
                                    <td><?= format_currency($booking['total_amount']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $booking['payment_status'] === 'paid' ? 'success' : ($booking['payment_status'] === 'partial' ? 'warning' : 'danger') ?>">
                                            <?= ucfirst($booking['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $booking['status'] === 'confirmed' ? 'success' : 
                                            ($booking['status'] === 'pending' ? 'warning' : 
                                            ($booking['status'] === 'completed' ? 'info' : 'secondary')) 
                                        ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-sm btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (isSuperAdmin()): ?>
                                            <form method="POST" action="<?= base_url('bookings/delete/' . intval($booking['id'])) ?>" 
                                                  style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this booking? This will also delete associated invoices and transactions!');">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <?php if ($bookingHasFilters): ?>
                                        No bookings match your filters.
                                        <a href="<?= base_url('bookings') ?>" class="d-block mt-2">Clear filters</a>
                                    <?php else: ?>
                                        No bookings found.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php render_pagination_controls($pagination ?? null, $_GET, 'Bookings pagination'); ?>
        </div>
    </div>
</div>

<style>
.sortable-th a { display: inline-flex; align-items: center; white-space: nowrap; cursor: pointer; }
.sortable-th a:hover { color: var(--bs-primary) !important; }
</style>
