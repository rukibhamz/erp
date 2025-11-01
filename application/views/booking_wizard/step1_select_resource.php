<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <!-- Progress Steps -->
            <div class="mb-5">
                <ul class="nav nav-pills nav-justified">
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><strong>Step 1:</strong> Select Resource</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#"><strong>Step 2:</strong> Date & Time</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#"><strong>Step 3:</strong> Add Extras</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#"><strong>Step 4:</strong> Your Info</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#"><strong>Step 5:</strong> Review & Pay</a>
                    </li>
                </ul>
            </div>

            <div class="text-center mb-4">
                <h1 class="display-5 fw-bold mb-3">Select a Resource</h1>
                <p class="lead text-muted">Choose the facility or equipment you'd like to book</p>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="<?= base_url('booking-wizard') ?>" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Resource Type</label>
                            <select name="type" class="form-select">
                                <option value="all" <?= $selected_type === 'all' ? 'selected' : '' ?>>All Types</option>
                                <option value="hall" <?= $selected_type === 'hall' ? 'selected' : '' ?>>Halls</option>
                                <option value="meeting_room" <?= $selected_type === 'meeting_room' ? 'selected' : '' ?>>Meeting Rooms</option>
                                <option value="equipment" <?= $selected_type === 'equipment' ? 'selected' : '' ?>>Equipment</option>
                                <option value="vehicle" <?= $selected_type === 'vehicle' ? 'selected' : '' ?>>Vehicles</option>
                                <option value="staff" <?= $selected_type === 'staff' ? 'selected' : '' ?>>Staff</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="all" <?= $selected_category === 'all' ? 'selected' : '' ?>>All Categories</option>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>" <?= $selected_category === $cat ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resources Grid -->
            <div class="row g-4">
                <?php if (!empty($resources)): ?>
                    <?php foreach ($resources as $resource): ?>
                        <?php
                        // Photos are loaded from controller
                        $photos = $resource['photos'] ?? [];
                        $amenities = json_decode($resource['amenities'] ?? '[]', true) ?: [];
                        $primaryPhoto = !empty($photos) && !empty($photos[0]['photo_path']) 
                            ? base_url($photos[0]['photo_path']) 
                            : 'https://via.placeholder.com/400x200?text=No+Image';
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm">
                                <?php if (!empty($primaryPhoto)): ?>
                                    <img src="<?= base_url($primaryPhoto) ?>" class="card-img-top" alt="<?= htmlspecialchars($resource['facility_name']) ?>" style="height: 200px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($resource['facility_name']) ?></h5>
                                    <p class="text-muted mb-2">
                                        <small>
                                            <i class="bi bi-tag"></i> <?= ucfirst(str_replace('_', ' ', $resource['resource_type'] ?? 'hall')) ?>
                                            <?php if (!empty($resource['category'])): ?>
                                                | <?= htmlspecialchars($resource['category']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </p>
                                    
                                    <?php if ($resource['description']): ?>
                                        <p class="card-text text-muted small">
                                            <?= htmlspecialchars(substr($resource['description'], 0, 100)) ?>
                                            <?= strlen($resource['description']) > 100 ? '...' : '' ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="bi bi-people"></i> Capacity: <?= $resource['capacity'] ?? 'N/A' ?><br>
                                            <i class="bi bi-clock"></i> Min Duration: <?= $resource['minimum_duration'] ?? 1 ?> hour(s)
                                        </small>
                                    </div>
                                    
                                    <?php if (!empty($amenities)): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <strong>Amenities:</strong><br>
                                                <?php foreach (array_slice($amenities, 0, 3) as $amenity): ?>
                                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($amenity) ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($amenities) > 3): ?>
                                                    <span class="badge bg-light text-dark">+<?= count($amenities) - 3 ?> more</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <strong>Starting from:</strong><br>
                                        <span class="h5 text-primary"><?= format_currency($resource['hourly_rate'] ?? 0) ?></span>
                                        <small class="text-muted">/hour</small>
                                        <?php if (!empty($resource['daily_rate']) && $resource['daily_rate'] > 0): ?>
                                            <br><small>or <?= format_currency($resource['daily_rate']) ?>/day</small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <a href="<?= base_url('booking-wizard/step2/' . $resource['id']) ?>" class="btn btn-primary w-100">
                                        <i class="bi bi-calendar-check"></i> Select & Continue
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle"></i> No resources available matching your criteria.
                            <a href="<?= base_url('booking-wizard') ?>" class="alert-link">View all resources</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
.nav-pills .nav-link {
    background-color: #f8f9fa;
    color: #000;
    border: 1px solid #dee2e6;
}
.nav-pills .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}
</style>

