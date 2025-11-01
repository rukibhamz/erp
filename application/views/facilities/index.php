<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Facilities</h1>
        <?php if (has_permission('bookings', 'create')): ?>
            <a href="<?= base_url('facilities/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Facility
            </a>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if (!empty($facilities)): ?>
            <?php foreach ($facilities as $facility): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?= htmlspecialchars($facility['facility_name']) ?>
                                <span class="badge bg-<?= $facility['status'] === 'active' ? 'success' : 'secondary' ?> float-end">
                                    <?= ucfirst($facility['status']) ?>
                                </span>
                            </h5>
                            <p class="text-muted mb-2"><?= htmlspecialchars($facility['facility_code']) ?></p>
                            <?php if ($facility['description']): ?>
                                <p class="card-text"><?= htmlspecialchars(substr($facility['description'], 0, 100)) ?><?= strlen($facility['description']) > 100 ? '...' : '' ?></p>
                            <?php endif; ?>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-people"></i> Capacity: <?= $facility['capacity'] ?><br>
                                    <i class="bi bi-clock"></i> Min Duration: <?= $facility['minimum_duration'] ?> hour(s)
                                </small>
                            </div>
                            
                            <div class="mb-2">
                                <strong>Rates:</strong><br>
                                <small>Hourly: <?= format_currency($facility['hourly_rate']) ?></small><br>
                                <small>Daily: <?= format_currency($facility['daily_rate']) ?></small>
                                <?php if ($facility['weekend_rate'] > 0): ?>
                                    <br><small>Weekend: <?= format_currency($facility['weekend_rate']) ?></small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex gap-2 mt-3">
                                <a href="<?= base_url('facilities/edit/' . $facility['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="<?= base_url('bookings?facility_id=' . $facility['id']) ?>" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-calendar"></i> View Bookings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    No facilities found. <a href="<?= base_url('facilities/create') ?>">Create your first facility</a>.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

