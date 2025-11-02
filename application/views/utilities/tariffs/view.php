<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Tariff: <?= htmlspecialchars($tariff['tariff_name']) ?></h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('utilities/tariffs/edit/' . $tariff['id']) ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="<?= base_url('utilities/tariffs') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php include(BASEPATH . 'views/utilities/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Tariff Name:</dt>
            <dd class="col-sm-8"><strong><?= htmlspecialchars($tariff['tariff_name']) ?></strong></dd>
            
            <dt class="col-sm-4">Provider:</dt>
            <dd class="col-sm-8"><?= htmlspecialchars($provider['provider_name'] ?? 'N/A') ?></dd>
            
            <dt class="col-sm-4">Effective Date:</dt>
            <dd class="col-sm-8"><?= date('M d, Y', strtotime($tariff['effective_date'])) ?></dd>
            
            <dt class="col-sm-4">Expiry Date:</dt>
            <dd class="col-sm-8"><?= $tariff['expiry_date'] ? date('M d, Y', strtotime($tariff['expiry_date'])) : '-' ?></dd>
            
            <dt class="col-sm-4">Fixed Charge:</dt>
            <dd class="col-sm-8"><?= format_currency($tariff['structure']['fixed_charge'] ?? 0) ?></dd>
            
            <dt class="col-sm-4">Variable Rate:</dt>
            <dd class="col-sm-8"><?= format_currency($tariff['structure']['variable_rate'] ?? 0) ?> per unit</dd>
            
            <?php if (($tariff['structure']['demand_charge'] ?? 0) > 0): ?>
                <dt class="col-sm-4">Demand Charge:</dt>
                <dd class="col-sm-8"><?= format_currency($tariff['structure']['demand_charge']) ?></dd>
            <?php endif; ?>
            
            <dt class="col-sm-4">Tax Rate:</dt>
            <dd class="col-sm-8"><?= number_format($tariff['structure']['tax_rate'] ?? 0, 2) ?>%</dd>
            
            <?php if (!empty($tariff['structure']['tiered_rates'])): ?>
                <dt class="col-sm-4">Tiered Rates:</dt>
                <dd class="col-sm-8">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tariff['structure']['tiered_rates'] as $tier): ?>
                                    <tr>
                                        <td><?= number_format($tier['from'] ?? 0) ?></td>
                                        <td><?= $tier['to'] ? number_format($tier['to']) : 'Above' ?></td>
                                        <td><?= format_currency($tier['rate'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </dd>
            <?php endif; ?>
            
            <dt class="col-sm-4">Status:</dt>
            <dd class="col-sm-8">
                <span class="badge bg-<?= $tariff['is_active'] ? 'success' : 'secondary' ?>">
                    <?= $tariff['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
            </dd>
        </dl>
    </div>
</div>

