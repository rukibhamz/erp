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
    <link href="<?= base_url('assets/css/buttons-override.css') ?>" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-light bg-light border-bottom py-2">
        <div class="container flex-column flex-sm-row gap-2">
            <a class="navbar-brand fw-bold m-0" href="<?= base_url() ?>">
                <i class="bi bi-calendar-check text-primary"></i> <span class="d-none d-sm-inline">Booking Portal</span><span class="d-sm-none">Portal</span>
            </a>
            <div class="d-flex align-items-center gap-1 gap-sm-2 flex-wrap justify-content-center">
                <?php
                // Get return link from settings
                $db = Database::getInstance();
                $prefix = $db->getPrefix();
                $returnSetting = $db->fetchOne("SELECT setting_value FROM `{$prefix}settings` WHERE setting_key = 'portal_return_link'");
                $returnLink = !empty($returnSetting['setting_value']) ? $returnSetting['setting_value'] : 'https://acropolispark.com/';
                ?>
                <a href="<?= htmlspecialchars($returnLink) ?>" class="btn btn-outline-primary btn-sm px-2 px-sm-3">
                    <i class="bi bi-arrow-left"></i> <span class="d-none d-md-inline">Return to Website</span><span class="d-md-none">Return</span>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= base_url('dashboard') ?>" class="btn btn-primary btn-sm px-2 px-sm-3">
                        <i class="bi bi-speedometer2"></i> <span class="d-none d-sm-inline">Dashboard</span>
                    </a>
                <?php elseif (isset($_SESSION['customer_user_id'])): ?>
                    <a href="<?= base_url('customer-portal/dashboard') ?>" class="btn btn-primary btn-sm px-2 px-sm-3">
                        <i class="bi bi-speedometer2"></i> <span class="d-none d-sm-inline">Dashboard</span>
                    </a>
                <?php else: ?>
                    <a href="<?= base_url('customer-portal/login') ?>" class="btn btn-outline-dark btn-sm px-2 px-sm-3">
                        <i class="bi bi-person-badge"></i> <span class="d-none d-md-inline">Customer Login</span><span class="d-md-none">Customer</span>
                    </a>
                    <a href="<?= base_url('login') ?>" class="btn btn-primary btn-sm px-2 px-sm-3">
                        <i class="bi bi-box-arrow-in-right"></i> <span class="d-none d-md-inline">Staff Login</span><span class="d-md-none">Staff</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if (isset($flash)): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert" style="margin-bottom: 0;">
            <?= esc($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

