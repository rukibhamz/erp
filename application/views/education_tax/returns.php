<div class="container-fluid px-4">
    <div class="mb-4">
        <?= back_button('education_tax') ?>
        <h1 class="h3 mt-3"><?= $page_title ?></h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-light">
            <h6 class="m-0 font-weight-bold text-primary">Filing History</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Filing Date</th>
                            <th>Tax Year</th>
                            <th>Assessable Profit</th>
                            <th>Tax Due</th>
                            <th>Receipt / Ref</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($returns)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No tax returns filed yet.</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($returns as $ret): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($ret['filing_date'])) ?></td>
                            <td><strong><?= $ret['tax_year'] ?></strong></td>
                            <td>$<?= number_format($ret['assessable_profit'], 2) ?></td>
                            <td class="text-primary fw-bold">$<?= number_format($ret['tax_due'], 2) ?></td>
                            <td><code><?= esc($ret['submission_receipt'] ?? 'N/A') ?></code></td>
                            <td><span class="badge bg-success"><?= ucfirst($ret['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
