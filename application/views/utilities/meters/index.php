<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Meters</h1>
        <a href="<?= base_url('utilities/meters/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Meter
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
            <div class="col-md-4">
                <label class="form-label">Filter by Property</label>
                <select name="property_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Properties</option>
                    <?php foreach ($properties as $property): ?>
                        <option value="<?= $property['id'] ?>" <?= ($selected_property_id ?? null) == $property['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($property['property_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <a href="<?= base_url('utilities/meters') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($meters)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-speedometer2" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No meters found.</p>
            <a href="<?= base_url('utilities/meters/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add First Meter
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
                            <th>Meter Number</th>
                            <th>Utility Type</th>
                            <th>Location</th>
                            <th>Property/Space</th>
                            <th>Last Reading</th>
                            <th>Last Reading Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meters as $meter): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($meter['meter_number']) ?></strong></td>
                                <td>
                                    <i class="bi <?= $meter['utility_type_icon'] ?? 'bi-lightning' ?>"></i>
                                    <?= htmlspecialchars($meter['utility_type_name'] ?? 'N/A') ?>
                                </td>
                                <td><?= htmlspecialchars($meter['meter_location'] ?: 'N/A') ?></td>
                                <td>
                                    <?php if ($meter['space_name']): ?>
                                        <?= htmlspecialchars($meter['space_name']) ?>
                                        <?php if ($meter['property_name']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($meter['property_name']) ?></small>
                                        <?php endif; ?>
                                    <?php elseif ($meter['property_name']): ?>
                                        <?= htmlspecialchars($meter['property_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($meter['last_reading']): ?>
                                        <?= number_format($meter['last_reading'], 2) ?>
                                        <small class="text-muted"><?= htmlspecialchars($meter['unit_of_measure'] ?? '') ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($meter['last_reading_date']): ?>
                                        <?= date('M d, Y', strtotime($meter['last_reading_date'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $meter['status'] === 'active' ? 'success' : ($meter['status'] === 'faulty' ? 'danger' : 'secondary') ?>">
                                        <?= ucfirst($meter['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('utilities/meters/view/' . $meter['id']) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= base_url('utilities/meters/edit/' . $meter['id']) ?>" class="btn btn-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= base_url('utilities/readings/create?meter_id=' . $meter['id']) ?>" class="btn btn-outline-success" title="Record Reading">
                                            <i class="bi bi-clipboard-data"></i>
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

