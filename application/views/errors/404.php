<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        body {
            background: #f9fafb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="text-center">
        <h1 class="display-1 fw-bold" style="color: #000000; font-weight: 700;">404</h1>
        <h2 class="mb-4" style="color: #374151;">Page Not Found</h2>
        <p class="lead mb-4" style="color: #6b7280;">The page you're looking for doesn't exist.</p>
        <?php
        // Use dashboard URL if logged in, otherwise home
        $homeUrl = isset($dashboard_url) ? $dashboard_url : base_url();
        $buttonText = isset($is_logged_in) && $is_logged_in ? 'Go to Dashboard' : 'Go Home';
        ?>
        <a href="<?= $homeUrl ?>" class="btn btn-dark btn-lg" style="background: #000000; border: none; font-weight: 500;">
            <i class="bi bi-house-door"></i> <?= $buttonText ?>
        </a>
    </div>
</body>
</html>

