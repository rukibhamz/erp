<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Resources & Facilities</h1>
        <?php if (has_permission('bookings', 'create')): ?>
            <a href="<?= base_url('facilities/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Facility
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
            <?php
            $bulk_delete_enabled = has_permission('bookings', 'delete');
            bulk_delete_render_toolbar($bulk_delete_enabled, $facilities, base_url('facilities/bulk-delete'), 'facility');
            ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <?php bulk_delete_render_checkbox_th($bulk_delete_enabled); ?>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Capacity</th>
                            <th>Hourly Rate</th>
                            <th>Daily Rate</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($facilities)): ?>
                            <?php foreach ($facilities as $facility): ?>
                                <tr>
                                    <?php bulk_delete_render_checkbox_td($bulk_delete_enabled, (int)$facility['id'], 'facility ' . ($facility['facility_name'])); ?>
                                    <td><?= htmlspecialchars($facility['facility_code']) ?></td>
                                    <td><strong><?= htmlspecialchars($facility['facility_name']) ?></strong></td>
                                    <td><?= (int) $facility['capacity'] ?></td>
                                    <td><?= format_currency($facility['hourly_rate']) ?></td>
                                    <td><?= format_currency($facility['daily_rate']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $facility['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($facility['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if (has_permission('bookings', 'read')): ?>
                                                <a href="<?= base_url('facilities/view/' . $facility['id']) ?>" class="btn btn-primary" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (has_permission('bookings', 'update')): ?>
                                                <a href="<?= base_url('facilities/edit/' . $facility['id']) ?>" class="btn btn-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (has_permission('bookings', 'delete')): ?>
                                                <a href="<?= base_url('facilities/delete/' . $facility['id']) ?>" class="btn btn-danger"
                                                   title="Delete" onclick="return confirm('Are you sure you want to delete this facility?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= base_url('bookings?facility_id=' . $facility['id']) ?>" class="btn btn-outline-info" title="View Bookings">
                                                <i class="bi bi-calendar"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= bulk_delete_colspan(7, $bulk_delete_enabled) ?>" class="text-center text-muted">
                                    No facilities found. <a href="<?= base_url('facilities/create') ?>">Create your first facility</a>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

