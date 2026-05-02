<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <!-- Progress Steps -->
            <div class="mb-4 mb-md-5">
                <ul class="nav nav-pills nav-wizard justify-content-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <span class="step-num">1</span>
                            <span class="step-text">Location</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#">
                            <span class="step-num">2</span>
                            <span class="step-text">DateTime</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#">
                            <span class="step-num">3</span>
                            <span class="step-text">Extras</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#">
                            <span class="step-num">4</span>
                            <span class="step-text">Info</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#">
                            <span class="step-num">5</span>
                            <span class="step-text">Review</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="text-center mb-4">
                <h1 class="display-6 fw-bold mb-3">Select Location & Space</h1>
                <p class="lead text-muted">Choose a location and then select the space you'd like to book</p>
                <div class="alert alert-warning d-inline-block">
                    <i class="bi bi-info-circle"></i> <strong>Note:</strong> Spaces are bookable between <strong>9:00 AM and 8:00 PM</strong> daily.
                </div>
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
                                    $primaryPhoto = !empty($photos) && !empty($photos[0]['photo_url']) 
                                        ? base_url($photos[0]['photo_url']) 
                                        : 'https://via.placeholder.com/400x200?text=No+Image';
                                    $bookingTypes = $space['booking_types'] ?? ['hourly', 'daily'];
                                    ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card h-100 shadow-sm">
                                            <?php if (!empty($photos)): ?>
                                                <?php if (count($photos) > 1): ?>
                                                    <div id="spaceCarousel<?= $space['id'] ?>" class="carousel slide" data-bs-ride="carousel">
                                                        <div class="carousel-inner">
                                                            <?php foreach ($photos as $index => $photo): ?>
                                                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                                                    <img src="<?= base_url($photo['photo_url']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Space Photo">
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <button class="carousel-control-prev" type="button" data-bs-target="#spaceCarousel<?= $space['id'] ?>" data-bs-slide="prev">
                                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                        </button>
                                                        <button class="carousel-control-next" type="button" data-bs-target="#spaceCarousel<?= $space['id'] ?>" data-bs-slide="next">
                                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <img src="<?= $primaryPhoto ?>" class="card-img-top" alt="<?= htmlspecialchars($space['space_name']) ?>" style="height: 200px; object-fit: cover;">
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <h5 class="card-title d-flex align-items-center gap-2">
                                                    <?= htmlspecialchars($space['space_name']) ?>
                                                    <?php if (!empty($space['is_featured'])): ?>
                                                        <span class="badge bg-warning text-dark">Featured</span>
                                                    <?php endif; ?>
                                                </h5>
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
                                                
                                                <!-- Pricing removed from card as per request -->
                                                
                                                <div class="d-grid gap-2">
                                                    <a href="<?= base_url('booking-wizard/space/details/' . $space['id']) ?>" class="btn btn-outline-primary">
                                                        <i class="bi bi-info-circle"></i> View More
                                                    </a>
                                                    <?php if (!empty($maintenance_mode) && intval($maintenance_mode) === 1 && empty($is_super_admin)): ?>
                                                        <button class="btn btn-secondary" disabled title="Booking is temporarily disabled for maintenance">
                                                            <i class="bi bi-tools"></i> Booking Disabled
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="<?= base_url('booking-wizard/step2/' . $space['id']) ?>" class="btn btn-primary">
                                                            <i class="bi bi-calendar-check"></i> Select & Continue
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
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

<script nonce="<?= csp_nonce() ?>">
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

/* Responsive Wizard Navigation */
.nav-wizard {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 0.5rem;
    -webkit-overflow-scrolling: touch;
    gap: 0.5rem;
}
.nav-wizard::-webkit-scrollbar {
    height: 4px;
}
.nav-wizard::-webkit-scrollbar-thumb {
    background: #dee2e6;
    border-radius: 4px;
}
.nav-wizard .nav-item {
    flex: 0 0 auto;
}
.nav-wizard .nav-link {
    background-color: #f8f9fa;
    color: #4b5563;
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 8px;
    font-size: 0.875rem;
    white-space: nowrap;
}
.nav-wizard .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}
.nav-wizard .step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: rgba(0,0,0,0.1);
    border-radius: 50%;
    font-weight: 700;
    font-size: 0.75rem;
}
.nav-wizard .nav-link.active .step-num {
    background: rgba(255,255,255,0.2);
}
@media (max-width: 576px) {
    .nav-wizard .step-text {
        display: none;
    }
    .nav-wizard .nav-link {
        padding: 0.5rem;
    }
    .display-6 {
        font-size: 1.5rem;
    }
}

.location-section {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 2rem;
}
.location-section:last-child {
    border-bottom: none;
}
</style>
