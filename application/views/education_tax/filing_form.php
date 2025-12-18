<div class="container-fluid px-4">
    <div class="mb-4">
        <?= back_button('education_tax') ?>
        <h1 class="h3 mt-3"><?= $page_title ?></h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="alert alert-info border-left-info shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill fs-3 me-3"></i>
                    <div>
                        <strong>Nigerian Education Tax (EDT)</strong> is calculated at 2.5% of the assessable profit of a company. 
                        Assessable profit is typically determined after making all necessary adjustments for capital allowances and non-deductible expenses.
                    </div>
                </div>
            </div>

            <form action="" method="POST" class="mt-4">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tax Year</label>
                        <input type="text" class="form-control bg-light" value="<?= $year ?>" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Assessable Profit (Estimated)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" name="assessable_profit" id="profit" class="form-control" value="<?= $profit ?>" required>
                        </div>
                        <div class="form-text">Estimated based on revenue minus expenses for the year.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tax Rate (Configured)</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-light" value="<?= $config['tax_rate'] ?? '2.50' ?>" readonly id="rate">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>

                <div class="bg-light p-4 rounded mb-4 mt-2">
                    <h5 class="mb-3">Tax Calculation Preview</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Assessable Profit:</span>
                        <span id="display-profit" class="fw-bold text-dark">$<?= number_format($profit, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                        <span>Tax Rate:</span>
                        <span id="display-rate" class="fw-bold text-dark"><?= $config['tax_rate'] ?? '2.50' ?>%</span>
                    </div>
                    <div class="d-flex justify-content-between mt-2 fs-5">
                        <span class="fw-bold">Tax Liability:</span>
                        <span id="display-tax" class="fw-bold text-primary">$<?= number_format($profit * (($config['tax_rate'] ?? 2.5) / 100), 2) ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes / Comments</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Add any notes regarding this filing..."></textarea>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4">Submit Filing</button>
                    <a href="<?= base_url('education_tax') ?>" class="btn btn-link text-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('profit').addEventListener('input', function() {
    const profit = parseFloat(this.value) || 0;
    const rate = parseFloat(document.getElementById('rate').value) || 2.5;
    const tax = profit * (rate / 100);
    
    document.getElementById('display-profit').textContent = '$' + profit.toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('display-tax').textContent = '$' + tax.toLocaleString(undefined, {minimumFractionDigits: 2});
});
</script>
