<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $page_title ?></h1>
        <div>
            <a href="<?= base_url('education_tax/record_payment') ?>" class="btn btn-outline-success">
                <i class="bi bi-cash-stack"></i> Record Payment
            </a>
            <a href="<?= base_url('education_tax/file_return') ?>" class="btn btn-primary ms-2">
                <i class="bi bi-file-earmark-text"></i> File Tax Return
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Dashboard Summary Mini Cards could go here -->
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-light">
            <h6 class="m-0 font-weight-bold text-primary">Annual Summary (Nigeria Education Tax - 2.5%)</h6>
            <a href="<?= base_url('education_tax/config') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-gear"></i> Configure Rates
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tax Year</th>
                            <th>Assessable Profit</th>
                            <th>Tax Due (2.5%)</th>
                            <th>Total Paid</th>
                            <th>Balance Due</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($summary)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No tax history found. Start by filing a return or recording a payment.</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($summary as $row): ?>
                        <tr>
                            <td><strong><?= $row['tax_year'] ?></strong></td>
                            <td>$<?= number_format($row['assessable_profit'], 2) ?></td>
                            <td class="text-primary fw-bold">$<?= number_format($row['tax_due'], 2) ?></td>
                            <td class="text-success">$<?= number_format($row['total_paid'], 2) ?></td>
                            <td>
                                <span class="fw-bold <?= $row['balance_due'] > 0 ? 'text-danger' : 'text-success' ?>">
                                    $<?= number_format($row['balance_due'], 2) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['balance_due'] <= 0 && $row['tax_due'] > 0): ?>
                                    <span class="badge bg-success">Fully Paid</span>
                                <?php elseif ($row['total_paid'] > 0): ?>
                                    <span class="badge bg-warning text-dark">Partially Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Unpaid</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Options
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="<?= base_url('education_tax/returns?year=' . $row['tax_year']) ?>"><i class="bi bi-file-text"></i> View Returns</a></li>
                                        <li><a class="dropdown-item" href="<?= base_url('education_tax/payments?year=' . $row['tax_year']) ?>"><i class="bi bi-cash"></i> View Payments</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
