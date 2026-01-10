<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title"><i class="fas fa-clock me-2"></i>Receivables Aging Report</h1>
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
                    <form action="<?= site_url('receivables/aging') ?>" method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label for="customer_id" class="form-label">Customer</label>
                            <select name="customer_id" id="customer_id" class="form-select">
                                <option value="">All Customers</option>
                                <?php foreach ($customers ?? [] as $customer): ?>
                                    <option value="<?= $customer['id'] ?>" <?= ($filter['customer_id'] ?? '') == $customer['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($customer['name'] ?? '') ?>
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
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Invoice Date</th>
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
                                <?php if (!empty($invoices)): ?>
                                    <?php foreach ($invoices as $invoice): 
                                        $daysOverdue = intval($invoice['days_overdue'] ?? 0);
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
                                        if (($invoice['status'] ?? '') === 'partial') {
                                            $statusClass = 'bg-secondary';
                                            $statusText = 'Partial';
                                        }
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($invoice['invoice_number'] ?? '') ?></td>
                                            <td>
                                                <a href="<?= site_url('customers/view/' . $invoice['customer_id']) ?>">
                                                    <?= htmlspecialchars($invoice['customer_name'] ?? '') ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($invoice['invoice_date'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($invoice['due_date'] ?? '') ?></td>
                                            <td class="text-end">₦<?= number_format(floatval($invoice['total'] ?? 0), 2) ?></td>
                                            <td class="text-end">₦<?= number_format(floatval($invoice['amount_paid'] ?? 0), 2) ?></td>
                                            <td class="text-end fw-bold">₦<?= number_format(floatval($invoice['balance'] ?? 0), 2) ?></td>
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
                                                    <a href="<?= site_url('receivables/view/' . $invoice['id']) ?>" 
                                                       class="btn btn-outline-primary" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= site_url('receivables/record-payment/' . $invoice['id']) ?>" 
                                                       class="btn btn-outline-success" title="Record Payment">
                                                        <i class="fas fa-money-bill"></i>
                                                    </a>
                                                    <a href="<?= site_url('receivables/send-reminder/' . $invoice['id']) ?>" 
                                                       class="btn btn-outline-warning" title="Send Reminder">
                                                        <i class="fas fa-envelope"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="fas fa-check-circle fa-3x mb-3 d-block opacity-50"></i>
                                            No outstanding receivables found
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
    const customerId = document.getElementById('customer_id').value;
    const status = document.getElementById('status').value;
    const asOfDate = document.getElementById('as_of_date').value;
    
    let url = '<?= site_url('receivables/export-aging/') ?>' + format + '?';
    if (customerId) url += 'customer_id=' + customerId + '&';
    if (status) url += 'status=' + status + '&';
    if (asOfDate) url += 'as_of_date=' + asOfDate;
    
    window.location.href = url;
}
</script>
