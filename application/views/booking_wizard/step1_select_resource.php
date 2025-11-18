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
                        <a class="nav-link active" href="#"><strong>Step 1:</strong> Select Location & Space</a>
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
                <h1 class="display-5 fw-bold mb-3">Select Location & Space</h1>
                <p class="lead text-muted">Choose a location and then select the space you'd like to book</p>
            </div>

            <!-- Location Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Select Location</label>
                            <select id="location_filter" class="form-select" onchange="filterByLocation()">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= $location['id'] ?>" <?= ($selected_location_id ?? null) == $location['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($location['Location_name'] ?? $location['property_name'] ?? 'N/A') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Locations and Spaces Grid -->
            <div id="spaces-container">
                <?php if (!empty($locations)): ?>
                    <?php foreach ($locations as $location): ?>
                        <?php 
                        $locationSpaces = $spaces_by_location[$location['id']] ?? [];
                        if (empty($locationSpaces)) continue;
                        ?>
                        <div class="location-section mb-5" data-location-id="<?= $location['id'] ?>">
                            <h3 class="mb-3">
                                <i class="bi bi-geo-alt"></i> 
                                <?= htmlspecialchars($location['Location_name'] ?? $location['property_name'] ?? 'N/A') ?>
                            </h3>
                            <div class="row g-4">
                                <?php foreach ($locationSpaces as $space): ?>
                                    <?php
                                    $photos = $space['photos'] ?? [];
                                    $amenities = json_decode($space['amenities'] ?? '[]', true) ?: [];
                                    $primaryPhoto = !empty($photos) && !empty($photos[0]['photo_path']) 
                                        ? base_url($photos[0]['photo_path']) 
                                        : 'https://via.placeholder.com/400x200?text=No+Image';
                                    $bookingTypes = $space['booking_types'] ?? ['hourly', 'daily'];
                                    ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card h-100 shadow-sm">
                                            <?php if (!empty($primaryPhoto)): ?>
                                                <img src="<?= $primaryPhoto ?>" class="card-img-top" alt="<?= htmlspecialchars($space['space_name']) ?>" style="height: 200px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <h5 class="card-title"><?= htmlspecialchars($space['space_name']) ?></h5>
                                                <?php if (!empty($space['space_number'])): ?>
                                                    <p class="text-muted mb-2">
                                                        <small><i class="bi bi-hash"></i> <?= htmlspecialchars($space['space_number']) ?></small>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if ($space['description']): ?>
                                                    <p class="card-text text-muted small">
                                                        <?= htmlspecialchars(substr($space['description'], 0, 100)) ?>
                                                        <?= strlen($space['description']) > 100 ? '...' : '' ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-people"></i> Capacity: <?= $space['capacity'] ?? 'N/A' ?><br>
                                                        <i class="bi bi-calendar-check"></i> Booking Types: <?= implode(', ', array_map(function($t) { return ucfirst(str_replace('_', ' ', $t)); }, $bookingTypes)) ?>
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
                                                    <span class="h5 text-primary"><?= format_currency($space['hourly_rate'] ?? 0) ?></span>
                                                    <small class="text-muted">/hour</small>
                                                    <?php if (!empty($space['daily_rate']) && $space['daily_rate'] > 0): ?>
                                                        <br><small>or <?= format_currency($space['daily_rate']) ?>/day</small>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <a href="<?= base_url('booking-wizard/step2/' . $space['id']) ?>" class="btn btn-primary w-100">
                                                    <i class="bi bi-calendar-check"></i> Select & Continue
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle"></i> No bookable spaces available at this time.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function filterByLocation() {
    const locationId = document.getElementById('location_filter').value;
    const sections = document.querySelectorAll('.location-section');
    
    sections.forEach(section => {
        if (!locationId || section.dataset.locationId === locationId) {
            section.style.display = 'block';
        } else {
            section.style.display = 'none';
        }
    });
}

// Apply filter on page load if location is selected
document.addEventListener('DOMContentLoaded', function() {
    const selectedLocation = document.getElementById('location_filter').value;
    if (selectedLocation) {
        filterByLocation();
    }
});
</script>

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
.location-section {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 2rem;
}
.location-section:last-child {
    border-bottom: none;
}
</style>
