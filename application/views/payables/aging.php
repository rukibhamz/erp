<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="fas fa-clock me-2"></i>Payables Aging Report</h1>
    </div>
    <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-primary" onclick="exportReport('excel')">
            <i class="fas fa-file-excel me-1"></i> <span class="hide-mobile">Export </span>Excel
        </button>
        <button type="button" class="btn btn-danger" onclick="exportReport('pdf')">
            <i class="fas fa-file-pdf me-1"></i> <span class="hide-mobile">Export </span>PDF
        </button>
    </div>
</div>
            <div class="card shadow-sm">
                <!-- card-header removed -->
                <div class="card-body">
                    <?php if (isset($flash) && $flash): ?>
                        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
                            <?= htmlspecialchars($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Total Outstanding</h6>
                                    <h4 class="mb-0">₦<?= number_format(floatval($summary['total_outstanding'] ?? 0), 2) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Current (0-30 days)</h6>
                                    <h4 class="mb-0">₦<?= number_format(floatval($summary['current'] ?? 0), 2) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body text-center">
                                    <h6 class="card-title">31-60 Days</h6>
                                    <h4 class="mb-0">₦<?= number_format(floatval($summary['days_31_60'] ?? 0), 2) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Over 60 Days</h6>
                                    <h4 class="mb-0">₦<?= number_format(floatval($summary['days_over_60'] ?? 0), 2) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Form -->
                    <form action="<?= site_url('payables/aging') ?>" method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label for="vendor_id" class="form-label">Vendor</label>
                            <select name="vendor_id" id="vendor_id" class="form-select">
                                <option value="">All Vendors</option>
                                <?php foreach ($vendors ?? [] as $vendor): ?>
                                    <option value="<?= $vendor['id'] ?>" <?= ($filter['vendor_id'] ?? '') == $vendor['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($vendor['name'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= ($filter['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="overdue" <?= ($filter['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                                <option value="partial" <?= ($filter['status'] ?? '') === 'partial' ? 'selected' : '' ?>>Partially Paid</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="as_of_date" class="form-label">As of Date</label>
                            <input type="date" name="as_of_date" id="as_of_date" class="form-control" 
                                   value="<?= htmlspecialchars($filter['as_of_date'] ?? date('Y-m-d')) ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Apply Filters
                            </button>
                        </div>
                    </form>

                    <!-- Aging Table -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="aging-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Vendor</th>
                                    <th>Bill #</th>
                                    <th>Bill Date</th>
                                    <th>Due Date</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-center">Days Overdue</th>
                                    <th class="text-center">Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($bills)): ?>
                                    <?php foreach ($bills as $bill): 
                                        $daysOverdue = intval($bill['days_overdue'] ?? 0);
                                        $statusClass = 'bg-success';
                                        $statusText = 'Current';
                                        if ($daysOverdue > 60) {
                                            $statusClass = 'bg-danger';
                                            $statusText = 'Over 60 days';
                                        } elseif ($daysOverdue > 30) {
                                            $statusClass = 'bg-warning text-dark';
                                            $statusText = '31-60 days';
                                        } elseif ($daysOverdue > 0) {
                                            $statusClass = 'bg-info';
                                            $statusText = '1-30 days';
                                        }
                                        if (($bill['status'] ?? '') === 'partial') {
                                            $statusClass = 'bg-secondary';
                                            $statusText = 'Partial';
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <a href="<?= site_url('vendors/view/' . $bill['vendor_id']) ?>">
                                                    <?= htmlspecialchars($bill['vendor_name'] ?? '') ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($bill['bill_number'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($bill['bill_date'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($bill['due_date'] ?? '') ?></td>
                                            <td class="text-end">₦<?= number_format(floatval($bill['total'] ?? 0), 2) ?></td>
                                            <td class="text-end">₦<?= number_format(floatval($bill['amount_paid'] ?? 0), 2) ?></td>
                                            <td class="text-end fw-bold">₦<?= number_format(floatval($bill['balance'] ?? 0), 2) ?></td>
                                            <td class="text-center">
                                                <?php if ($daysOverdue > 0): ?>
                                                    <span class="badge bg-danger"><?= $daysOverdue ?> days</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= site_url('payables/view/' . $bill['id']) ?>" 
                                                       class="btn btn-outline-primary" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= site_url('payables/record-payment/' . $bill['id']) ?>" 
                                                       class="btn btn-outline-success" title="Record Payment">
                                                        <i class="fas fa-money-bill"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="fas fa-check-circle fa-3x mb-3 d-block opacity-50"></i>
                                            No outstanding payables found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Totals:</strong></td>
                                    <td class="text-end"><strong>₦<?= number_format(floatval($summary['total_outstanding'] ?? 0), 2) ?></strong></td>
                                    <td class="text-end"><strong>₦<?= number_format(floatval($summary['total_paid'] ?? 0), 2) ?></strong></td>
                                    <td class="text-end"><strong>₦<?= number_format(floatval($summary['total_balance'] ?? 0), 2) ?></strong></td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportReport(format) {
    const vendorId = document.getElementById('vendor_id').value;
    const status = document.getElementById('status').value;
    const asOfDate = document.getElementById('as_of_date').value;
    
    let url = '<?= site_url('payables/export-aging/') ?>' + format + '?';
    if (vendorId) url += 'vendor_id=' + vendorId + '&';
    if (status) url += 'status=' + status + '&';
    if (asOfDate) url += 'as_of_date=' + asOfDate;
    
    window.location.href = url;
}
</script>
