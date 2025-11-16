<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Provider: <?= htmlspecialchars($provider['provider_name']) ?></h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('utilities/providers/edit/' . $provider['id']) ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="<?= base_url('utilities/providers') ?>" class="btn btn-outline-secondary">
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
            <dt class="col-sm-4">Provider Name:</dt>
            <dd class="col-sm-8"><strong><?= htmlspecialchars($provider['provider_name']) ?></strong></dd>
            
            <dt class="col-sm-4">Utility Type:</dt>
            <dd class="col-sm-8"><?= htmlspecialchars($provider['utility_type_name'] ?? 'N/A') ?></dd>
            
            <dt class="col-sm-4">Account Number:</dt>
            <dd class="col-sm-8"><?= htmlspecialchars($provider['account_number'] ?: '-') ?></dd>
            
            <dt class="col-sm-4">Contact Person:</dt>
            <dd class="col-sm-8"><?= htmlspecialchars($provider['contact_person'] ?: '-') ?></dd>
            
            <dt class="col-sm-4">Email:</dt>
            <dd class="col-sm-8"><?= htmlspecialchars($provider['email'] ?: '-') ?></dd>
            
            <dt class="col-sm-4">Phone:</dt>
            <dd class="col-sm-8"><?= htmlspecialchars($provider['phone'] ?: '-') ?></dd>
            
            <dt class="col-sm-4">Address:</dt>
            <dd class="col-sm-8"><?= nl2br(htmlspecialchars($provider['address'] ?: '-')) ?></dd>
            
            <dt class="col-sm-4">Payment Terms:</dt>
            <dd class="col-sm-8"><?= $provider['payment_terms'] ?> days</dd>
            
            <dt class="col-sm-4">Status:</dt>
            <dd class="col-sm-8">
                <span class="badge bg-<?= $provider['is_active'] ? 'success' : 'secondary' ?>">
                    <?= $provider['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
            </dd>
        </dl>
    </div>
</div>

