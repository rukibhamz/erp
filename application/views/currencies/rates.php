<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Exchange Rates</h1>
        <a href="<?= base_url('currencies') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($base_currency): ?>
        <div class="alert alert-info">
            <strong>Base Currency:</strong> <?= htmlspecialchars($base_currency['currency_code']) ?> - <?= htmlspecialchars($base_currency['currency_name']) ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Update Exchange Rate</h5>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From Currency</label>
                    <select name="from_currency" class="form-select" required>
                        <option value="">Select Currency</option>
                        <?php foreach ($currencies as $currency): ?>
                            <option value="<?= $currency['currency_code'] ?>"><?= htmlspecialchars($currency['currency_code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Currency</label>
                    <select name="to_currency" class="form-select" required>
                        <option value="">Select Currency</option>
                        <?php foreach ($currencies as $currency): ?>
                            <option value="<?= $currency['currency_code'] ?>"><?= htmlspecialchars($currency['currency_code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Rate</label>
                    <input type="number" name="rate" class="form-control" step="0.0001" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" name="rate_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Update Rate</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Current Exchange Rates</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Currency</th>
                            <th>Rate (Base Currency)</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($currencies)): ?>
                            <?php foreach ($currencies as $currency): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($currency['currency_code']) ?></strong> - 
                                        <?= htmlspecialchars($currency['currency_name']) ?>
                                        <?php if ($currency['is_base']): ?>
                                            <span class="badge bg-success">Base</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($currency['exchange_rate'], 4) ?></td>
                                    <td><?= $currency['updated_at'] ? date('M d, Y H:i', strtotime($currency['updated_at'])) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">No currencies found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('layouts/footer'); ?>

