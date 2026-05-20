<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$filters = $filters ?? [];
$mismatched = $mismatched ?? [];
$pagination = $pagination ?? [];
$currentPage = intval($pagination['page'] ?? 1);
$totalPages = intval($pagination['total_pages'] ?? 1);
$perPage = intval($pagination['per_page'] ?? 50);
$perPageOptions = $pagination['per_page_options'] ?? [25, 50, 75, 100, 200];
$fromRow = intval($pagination['from'] ?? 0);
$toRow = intval($pagination['to'] ?? 0);
$totalRows = intval($pagination['total_records'] ?? 0);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Booking financial reconciliation</h1>
            <p class="text-muted mb-0 small">Bookings whose invoice or posted GL revenue does not match the booking total.</p>
        </div>
        <a href="<?= base_url('bookings') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to bookings
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= base_url('bookings/financialReconciliation') ?>" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="1">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active only</option>
                        <option value="all" <?= ($filters['status'] ?? '') === 'all' ? 'selected' : '' ?>>All statuses</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From date</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To date</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Records</label>
                    <select name="per_page" class="form-select">
                        <?php foreach ($perPageOptions as $opt): ?>
                            <option value="<?= intval($opt) ?>" <?= $perPage === intval($opt) ? 'selected' : '' ?>><?= intval($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="full_scan" value="1" id="full_scan" <?= !empty($filters['full_scan']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="full_scan">
                            Full scan (slower, checks every booking in range)
                        </label>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">Scan</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($mismatched)): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> No mismatches found with the current filters.
        </div>
    <?php else: ?>
        <form method="POST" action="<?= base_url('bookings/financialReconciliationBulk') ?>" id="bulkReconcileForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="filter_status" value="<?= htmlspecialchars($filters['status'] ?? '') ?>">
            <input type="hidden" name="filter_date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            <input type="hidden" name="filter_date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
            <input type="hidden" name="filter_per_page" value="<?= intval($perPage) ?>">
            <input type="hidden" name="filter_page" value="<?= intval($currentPage) ?>">
            <?php if (!empty($filters['full_scan'])): ?>
                <input type="hidden" name="filter_full_scan" value="1">
            <?php endif; ?>

            <?php foreach ($mismatched as $row): ?>
                <input type="hidden" name="listed_ids[]" value="<?= intval($row['id']) ?>">
            <?php endforeach; ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><strong><?= count($mismatched) ?></strong> booking(s) need reconciliation</span>
                    <div class="d-flex gap-2">
                        <button type="submit" name="reconcile_all_listed" value="1" class="btn btn-primary btn-sm"
                                onclick="return confirm('Reconcile all <?= count($mismatched) ?> bookings listed? Invoices and GL will be updated.');">
                            <i class="bi bi-arrow-repeat"></i> Reconcile All Listed
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm"
                                onclick="return confirm('Reconcile selected bookings?');">
                            <i class="bi bi-check2-square"></i> Reconcile Selected
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;">
                                        <input type="checkbox" class="form-check-input" id="selectAllRows" checked>
                                    </th>
                                    <th>Booking</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th class="text-end">Booking total</th>
                                    <th class="text-end">Invoice total</th>
                                    <th class="text-end">Posted GL</th>
                                    <th>Issues</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mismatched as $row): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input row-check" name="booking_ids[]"
                                               value="<?= intval($row['id']) ?>" checked>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('bookings/view/' . $row['id']) ?>"><?= htmlspecialchars($row['booking_number']) ?></a>
                                        <span class="badge bg-secondary ms-1"><?= htmlspecialchars($row['status']) ?></span>
                                    </td>
                                    <td><?= !empty($row['booking_date']) ? date('M d, Y', strtotime($row['booking_date'])) : '—' ?></td>
                                    <td><?= htmlspecialchars($row['customer_name'] ?? '') ?></td>
                                    <td class="text-end"><?= format_currency($row['booking_total']) ?></td>
                                    <td class="text-end <?= !empty($row['invoice_mismatch']) ? 'text-warning' : '' ?>">
                                        <?= $row['invoice_total'] !== null ? format_currency($row['invoice_total']) : '—' ?>
                                    </td>
                                    <td class="text-end <?= !empty($row['gl_mismatch']) ? 'text-warning' : '' ?>">
                                        <?= format_currency($row['posted_revenue']) ?>
                                    </td>
                                    <td>
                                        <?php if (in_array('invoice', $row['issues'] ?? [], true)): ?>
                                            <span class="badge bg-warning text-dark">Invoice</span>
                                        <?php endif; ?>
                                        <?php if (in_array('gl', $row['issues'] ?? [], true)): ?>
                                            <span class="badge bg-danger">GL</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= base_url('bookings/view/' . $row['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
                    $buildPageUrl = function ($page) {
                        $params = $_GET;
                        $params['page'] = max(1, intval($page));
                        return '?' . http_build_query($params);
                    };
                ?>
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-muted">
                        Showing <?= $fromRow ?>-<?= $toRow ?> of <?= $totalRows ?> records
                    </div>
                    <nav aria-label="Reconciliation pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $currentPage <= 1 ? '#' : htmlspecialchars($buildPageUrl(1)) ?>">First</a>
                            </li>
                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $currentPage <= 1 ? '#' : htmlspecialchars($buildPageUrl($currentPage - 1)) ?>">Previous</a>
                            </li>
                            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= htmlspecialchars($buildPageUrl($i)) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $currentPage >= $totalPages ? '#' : htmlspecialchars($buildPageUrl($currentPage + 1)) ?>">Next</a>
                            </li>
                            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $currentPage >= $totalPages ? '#' : htmlspecialchars($buildPageUrl($totalPages)) ?>">Last</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </form>

        <script>
        (function () {
            var selectAll = document.getElementById('selectAllRows');
            if (!selectAll) return;
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.row-check').forEach(function (cb) {
                    cb.checked = selectAll.checked;
                });
            });
        })();
        </script>
    <?php endif; ?>
</div>
