<div class="container-fluid px-4">
    <div class="mb-4">
        <?= back_button('education_tax') ?>
        <h1 class="h3 mt-3"><?= $page_title ?></h1>
    </div>

    <div class="card shadow mb-4 mx-auto" style="max-width: 600px;">
        <div class="card-body p-4">
            <form action="" method="POST">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Tax Year</label>
                    <select name="tax_year" class="form-select" required>
                        <?php 
                        $currentYear = date('Y');
                        for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                            <option value="<?= $y ?>"><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Amount Paid</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" name="amount_paid" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Payment Date</label>
                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Payment Reference / Receipt No.</label>
                    <input type="text" name="reference" class="form-control" required placeholder="e.g. FIRS-EDT-2025-001">
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-success w-100">Record Payment</button>
                    <a href="<?= base_url('education_tax') ?>" class="btn btn-link w-100 mt-2 text-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
