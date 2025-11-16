<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Budget</h1>
        <a href="<?= base_url('budgets') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Financial Year *</label>
                            <select name="financial_year_id" class="form-select" required>
                                <option value="">Select Financial Year</option>
                                <?php foreach ($financial_years as $year): ?>
                                    <option value="<?= $year['id'] ?>">
                                        <?= htmlspecialchars($year['year_name'] ?? date('Y', strtotime($year['start_date']))) ?>
                                        (<?= date('M d, Y', strtotime($year['start_date'])) ?> - <?= date('M d, Y', strtotime($year['end_date'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Account *</label>
                            <select name="account_id" class="form-select" required>
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?= $account['id'] ?>">
                                        <?= htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Budget Name</label>
                    <input type="text" name="budget_name" class="form-control" placeholder="e.g., 2024 Operating Budget">
                </div>

                <h5 class="mb-3">Monthly Budget Allocation</h5>
                <div class="row">
                    <?php
                    $months = [
                        1 => ['name' => 'January', 'field' => 'january'],
                        2 => ['name' => 'February', 'field' => 'february'],
                        3 => ['name' => 'March', 'field' => 'march'],
                        4 => ['name' => 'April', 'field' => 'april'],
                        5 => ['name' => 'May', 'field' => 'may'],
                        6 => ['name' => 'June', 'field' => 'june'],
                        7 => ['name' => 'July', 'field' => 'july'],
                        8 => ['name' => 'August', 'field' => 'august'],
                        9 => ['name' => 'September', 'field' => 'september'],
                        10 => ['name' => 'October', 'field' => 'october'],
                        11 => ['name' => 'November', 'field' => 'november'],
                        12 => ['name' => 'December', 'field' => 'december']
                    ];
                    foreach ($months as $num => $month):
                    ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><?= $month['name'] ?></label>
                            <input type="number" name="<?= $month['field'] ?>" class="form-control" step="0.01" value="0" onchange="calculateTotal()">
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card bg-light mt-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Total Annual Budget:</strong>
                            </div>
                            <div class="col-md-6 text-end">
                                <h5 id="totalBudget">0.00</h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3 mt-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('budgets') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Budget</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calculateTotal() {
    let total = 0;
    const months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
    months.forEach(month => {
        const value = parseFloat(document.querySelector(`input[name="${month}"]`).value) || 0;
        total += value;
    });
    document.getElementById('totalBudget').textContent = total.toFixed(2);
}
</script>


