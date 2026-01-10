<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>404 - Page Not Found | Business ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/design-system.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/ui-consistency.css') ?>" rel="stylesheet">
    <style>
        :root {
            --primary-black: #000000;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            max-width: 500px;
            width: 100%;
            text-align: center;
            padding: 3rem 2rem;
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .error-number {
            font-size: 8rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #000 0%, #333 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 1.5rem;
        }
        .error-desc {
            color: #6c757d;
            margin-bottom: 2.5rem;
            font-size: 1rem;
        }
        .btn-home {
            background-color: var(--primary-black);
            color: #fff;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 0.75rem;
            font-weight: 500;
            transition: transform 0.2s, background-color 0.2s;
        }
        .btn-home:hover {
            background-color: #333;
            color: #fff;
            transform: translateY(-2px);
        }
        .illustration {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container p-4">
        <div class="error-card mx-auto">
            <div class="illustration">
                <i class="bi bi-search"></i>
            </div>
            <div class="error-number">404</div>
            <h1 class="error-title">Oops! Page Not Found</h1>
            <p class="error-desc">
                The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
            </p>
            
            <?php
            $homeUrl = isset($dashboard_url) ? $dashboard_url : base_url();
            $buttonText = isset($is_logged_in) && $is_logged_in ? 'Back to Dashboard' : 'Return Home';
            ?>
            
            <div class="d-grid gap-2">
                <a href="<?= $homeUrl ?>" class="btn btn-home">
                    <i class="bi bi-arrow-left me-2"></i> <?= $buttonText ?>
                </a>
                <a href="javascript:history.back()" class="btn btn-link text-decoration-none text-muted">
                    Go Back
                </a>
            </div>
        </div>
    </div>
</body>
</html>

