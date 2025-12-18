<div class="container-fluid px-4">
    <div class="mb-4">
        <?= back_button('education_tax') ?>
        <h1 class="h3 mt-3"><?= $page_title ?></h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-light">
            <h6 class="m-0 font-weight-bold text-primary">Payment History</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Payment Date</th>
                            <th>Tax Year</th>
                            <th>Amount Paid</th>
                            <th>Reference</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No payments recorded yet.</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($pay['payment_date'])) ?></td>
                            <td><strong><?= $pay['tax_year'] ?></strong></td>
                            <td class="text-success fw-bold">$<?= number_format($pay['amount_paid'], 2) ?></td>
                            <td><code><?= esc($pay['payment_reference']) ?></code></td>
                            <td><span class="badge bg-<?= $pay['status'] === 'completed' ? 'success' : 'secondary' ?>"><?= ucfirst($pay['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
