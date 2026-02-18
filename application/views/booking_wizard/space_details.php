<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('booking-wizard/step1') ?>">Select Space</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($space['space_name']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Gallery and Video Section -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                <!-- Main Image/Gallery -->
                <?php if (!empty($space['photos'])): ?>
                    <div id="spaceDetailCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($space['photos'] as $index => $photo): ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                    <img src="<?= base_url($photo['photo_url']) ?>" class="d-block w-100" style="height: 500px; object-fit: cover;" alt="<?= htmlspecialchars($space['space_name']) ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($space['photos']) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#spaceDetailCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#spaceDetailCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <img src="https://via.placeholder.com/800x500?text=No+Image+Available" class="img-fluid w-100" style="height: 500px; object-fit: cover;">
                <?php endif; ?>
            </div>

            <!-- Video Section -->
            <?php if (!empty($space['video_url'])): ?>
                <div class="card shadow-sm border-0 mb-4 p-3">
                    <h4 class="mb-3"><i class="bi bi-play-btn"></i> Video Tour</h4>
                    <?php 
                        $videoUrl = $space['video_url'];
                        $embedUrl = '';
                        
                        // Simple YouTube/Vimeo embed logic
                        if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false) {
                            preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $videoUrl, $matches);
                            if (isset($matches[1])) $embedUrl = 'https://www.youtube.com/embed/' . $matches[1];
                        } elseif (strpos($videoUrl, 'vimeo.com') !== false) {
                            preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)/', $videoUrl, $matches);
                            if (isset($matches[1])) $embedUrl = 'https://player.vimeo.com/video/' . $matches[1];
                        }
                    ?>
                    <?php if ($embedUrl): ?>
                        <div class="ratio ratio-16x9 shadow-sm rounded overflow-hidden">
                            <iframe src="<?= $embedUrl ?>" title="Space Video" allowfullscreen></iframe>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light">
                            <i class="bi bi-link-45deg"></i> <a href="<?= htmlspecialchars($videoUrl) ?>" target="_blank">Watch video on external site</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Description -->
            <div class="card shadow-sm border-0 mb-4 p-4">
                <h4 class="mb-3">About this Space</h4>
                <div class="description-content lead fs-6 text-muted">
                    <?= !empty($space['detailed_description']) ? nl2br(htmlspecialchars($space['detailed_description'])) : nl2br(htmlspecialchars($space['description'] ?? '')) ?>
                </div>
            </div>
        </div>

        <!-- Sidebar Section -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 p-4 sticky-top" style="top: 20px;">
                <h2 class="h3 fw-bold mb-3"><?= htmlspecialchars($space['space_name']) ?></h2>
                <p class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($space['property_name'] ?? 'N/A') ?></p>
                
                <hr>

                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Hourly Rate:</span>
                        <span class="fw-bold text-primary fs-4"><?= format_currency($space['hourly_rate'] ?? 0) ?></span>
                    </div>
                    <?php if (!empty($space['daily_rate']) && $space['daily_rate'] > 0): ?>
                        <div class="d-flex justify-content-between">
                            <span>Daily Rate:</span>
                            <span class="fw-bold"><?= format_currency($space['daily_rate']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <h5 class="h6 fw-bold mb-3">Specifications</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bi bi-people me-2"></i> Capacity: <?= $space['capacity'] ?? 'N/A' ?></li>
                        <?php if (!empty($space['space_number'])): ?>
                            <li class="mb-2"><i class="bi bi-hash me-2"></i> Space #: <?= htmlspecialchars($space['space_number']) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($space['floor'])): ?>
                            <li class="mb-2"><i class="bi bi-layers me-2"></i> Floor: <?= htmlspecialchars($space['floor']) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($space['area'])): ?>
                            <li class="mb-2"><i class="bi bi-aspect-ratio me-2"></i> Area: <?= number_format($space['area'], 2) ?> sqm</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <?php if (!empty($space['amenities'])): ?>
                    <div class="mb-4">
                        <h5 class="h6 fw-bold mb-3">Amenities</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($space['amenities'] as $amenity): ?>
                                <span class="badge bg-light text-dark border p-2"><?= htmlspecialchars($amenity) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="d-grid gap-2">
                    <a href="<?= base_url('booking-wizard/step2/' . $space['id']) ?>" class="btn btn-primary btn-lg py-3">
                        <i class="bi bi-calendar-check-fill me-2"></i> Book This Space
                    </a>
                    <a href="<?= base_url('booking-wizard/step1') ?>" class="btn btn-outline-secondary" style="color: #6c757d; border-color: #6c757d; background: transparent;">
                        <i class="bi bi-arrow-left me-1"></i> Back to Selection
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.breadcrumb-item + .breadcrumb-item::before {
    content: "/";
}
.card {
    border-radius: 1rem;
}
.badge {
    font-weight: 500;
}
.description-content {
    line-height: 1.8;
}
.carousel-item img {
    border-radius: 1rem 1rem 0 0;
}
</style>
