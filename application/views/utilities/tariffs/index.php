<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Tariffs</h1>
        <a href="<?= base_url('utilities/tariffs/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Tariff
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/utilities/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filter by Provider</label>
                <select name="provider_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Providers</option>
                    <?php foreach ($providers as $provider): ?>
                        <option value="<?= $provider['id'] ?>" <?= ($selected_provider_id ?? null) == $provider['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($provider['provider_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8">
                <a href="<?= base_url('utilities/tariffs') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($tariffs)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-currency-exchange" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No tariffs found.</p>
            <a href="<?= base_url('utilities/tariffs/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create First Tariff
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tariff Name</th>
                            <th>Provider</th>
                            <th>Effective Date</th>
                            <th>Expiry Date</th>
                            <th>Fixed Charge</th>
                            <th>Variable Rate</th>
                            <th>Tax Rate</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tariffs as $tariff): ?>
                            <?php
                            $structure = json_decode($tariff['structure_json'] ?? '{}', true);
                            $provider = null;
                            foreach ($providers as $p) {
                                if ($p['id'] == $tariff['provider_id']) {
                                    $provider = $p;
                                    break;
                                }
                            }
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($tariff['tariff_name']) ?></strong></td>
                                <td><?= htmlspecialchars($provider['provider_name'] ?? 'N/A') ?></td>
                                <td><?= date('M d, Y', strtotime($tariff['effective_date'])) ?></td>
                                <td><?= $tariff['expiry_date'] ? date('M d, Y', strtotime($tariff['expiry_date'])) : '-' ?></td>
                                <td><?= format_currency($structure['fixed_charge'] ?? 0) ?></td>
                                <td><?= format_currency($structure['variable_rate'] ?? 0) ?>/unit</td>
                                <td><?= number_format($structure['tax_rate'] ?? 0, 2) ?>%</td>
                                <td>
                                    <span class="badge bg-<?= $tariff['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $tariff['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('utilities/tariffs/view/' . $tariff['id']) ?>" class="btn btn-outline-dark" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= base_url('utilities/tariffs/edit/' . $tariff['id']) ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

