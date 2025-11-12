<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Book a Facility' ?> - ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/main.css') ?>" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= base_url() ?>">
                <i class="bi bi-calendar-check text-primary"></i> Booking Portal
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="<?= base_url('login') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-box-arrow-in-right"></i> Admin Login
                </a>
            </div>
        </div>
    </nav>

    <?php if (isset($flash)): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert" style="margin-bottom: 0;">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

