<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Currencies</h1>
        <div>
            <?php if (has_permission('settings', 'create')): ?>
                <a href="<?= base_url('currencies/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Currency
                </a>
            <?php endif; ?>
            <?php if (has_permission('settings', 'read')): ?>
                <a href="<?= base_url('currencies/rates') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-graph-up"></i> Exchange Rates
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Symbol</th>
                            <th>Exchange Rate</th>
                            <th>Position</th>
                            <th>Base Currency</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($currencies)): ?>
                            <?php foreach ($currencies as $currency): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($currency['currency_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($currency['currency_name']) ?></td>
                                    <td><?= htmlspecialchars($currency['symbol']) ?></td>
                                    <td><?= number_format($currency['exchange_rate'], 4) ?></td>
                                    <td><?= ucfirst($currency['position'] ?? 'before') ?></td>
                                    <td>
                                        <?php if ($currency['is_base']): ?>
                                            <span class="badge bg-success">Base</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $currency['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($currency['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (has_permission('settings', 'update')): ?>
                                            <a href="<?= base_url('currencies/edit/' . $currency['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No currencies found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('layouts/footer'); ?>

