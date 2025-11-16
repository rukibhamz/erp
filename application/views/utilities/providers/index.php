<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Utility Providers</h1>
        <a href="<?= base_url('utilities/providers/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Provider
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
                <label class="form-label">Filter by Utility Type</label>
                <select name="utility_type_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <?php foreach ($utility_types as $type): ?>
                        <option value="<?= $type['id'] ?>" <?= ($selected_utility_type_id ?? null) == $type['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8">
                <a href="<?= base_url('utilities/providers') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($providers)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-building" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No providers found.</p>
            <a href="<?= base_url('utilities/providers/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add First Provider
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
                            <th>Provider Name</th>
                            <th>Utility Type</th>
                            <th>Account Number</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Payment Terms</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($providers as $provider): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($provider['provider_name']) ?></strong></td>
                                <td><?= htmlspecialchars($provider['utility_type_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($provider['account_number'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($provider['contact_person'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($provider['email'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($provider['phone'] ?: '-') ?></td>
                                <td><?= $provider['payment_terms'] ?> days</td>
                                <td>
                                    <span class="badge bg-<?= $provider['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $provider['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('utilities/providers/view/' . $provider['id']) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= base_url('utilities/providers/edit/' . $provider['id']) ?>" class="btn btn-primary" title="Edit">
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

