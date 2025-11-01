<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= $page_title ?? 'Business Management System' ?> - ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/main.css') ?>" rel="stylesheet">
</head>
<body>
    <?php if (isset($current_user)): ?>
    <!-- Sidebar Navigation -->
    <div class="sidebar-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="<?= base_url('dashboard') ?>" class="sidebar-brand">
                    <i class="bi bi-briefcase"></i>
                    <span class="brand-text">Business ERP</span>
                </a>
                <button class="sidebar-toggle d-lg-none" id="sidebarToggle" type="button">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-menu">
                    <?php
                    $current_url = $_GET['url'] ?? '';
                    $current_url = trim($current_url, '/');
                    ?>
                    <li class="nav-item">
                        <a href="<?= base_url('dashboard') ?>" class="nav-link <?= empty($current_url) || $current_url === 'dashboard' ? 'active' : '' ?>">
                            <i class="bi bi-speedometer2"></i>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url('companies') ?>" class="nav-link <?= strpos($current_url, 'companies') === 0 ? 'active' : '' ?>">
                            <i class="bi bi-building"></i>
                            <span class="nav-text">Companies</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url('users') ?>" class="nav-link <?= strpos($current_url, 'users') === 0 ? 'active' : '' ?>">
                            <i class="bi bi-people"></i>
                            <span class="nav-text">Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url('activity') ?>" class="nav-link <?= strpos($current_url, 'activity') === 0 ? 'active' : '' ?>">
                            <i class="bi bi-file-text"></i>
                            <span class="nav-text">Activity Log</span>
                        </a>
                    </li>
                    <li class="nav-divider"></li>
                    <li class="nav-item">
                        <a href="<?= base_url('accounting') ?>" class="nav-link <?= strpos($current_url, 'accounting') === 0 || strpos($current_url, 'accounts') === 0 || strpos($current_url, 'cash') === 0 || strpos($current_url, 'receivables') === 0 || strpos($current_url, 'payables') === 0 || strpos($current_url, 'payroll') === 0 || strpos($current_url, 'ledger') === 0 || strpos($current_url, 'reports') === 0 ? 'active' : '' ?>">
                            <i class="bi bi-calculator"></i>
                            <span class="nav-text">Accounting</span>
                        </a>
                    </li>
                    <li class="nav-divider"></li>
                    <li class="nav-item">
                        <a href="<?= base_url('settings') ?>" class="nav-link <?= strpos($current_url, 'settings') === 0 ? 'active' : '' ?>">
                            <i class="bi bi-gear"></i>
                            <span class="nav-text">Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
        </aside>
        
        <!-- Mobile Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Top Bar (Mobile) -->
        <div class="topbar d-lg-none">
            <button class="topbar-toggle" id="topbarToggle" type="button">
                <i class="bi bi-list"></i>
            </button>
            <a href="<?= base_url('dashboard') ?>" class="topbar-brand">
                Business ERP
            </a>
            <div class="topbar-right">
                <!-- Notifications -->
                <button class="topbar-icon-btn" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-bell"></i>
                    <span class="notification-badge">0</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end notification-dropdown">
                    <li class="dropdown-header">Notifications</li>
                    <li><hr class="dropdown-divider"></li>
                    <li class="dropdown-item-text text-center text-muted py-3">
                        <small>No new notifications</small>
                    </li>
                </ul>
                
                <!-- Profile -->
                <div class="nav-item-dropdown">
                    <button class="topbar-avatar-btn" type="button" data-bs-toggle="dropdown">
                        <?php
                        $avatarPath = isset($current_user['avatar']) && $current_user['avatar'] ? 
                            base_url('uploads/avatars/' . $current_user['avatar']) : 
                            base_url('assets/images/default-avatar.png');
                        ?>
                        <img src="<?= $avatarPath ?>" alt="Avatar" class="topbar-avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($current_user['username'] ?? 'User') ?>&background=000&color=fff&size=128'">
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end profile-dropdown">
                        <li class="dropdown-header">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= $avatarPath ?>" alt="Avatar" class="dropdown-avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($current_user['username'] ?? 'User') ?>&background=000&color=fff&size=128'">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars(trim(($current_user['first_name'] ?? '') . ' ' . ($current_user['last_name'] ?? '')) ?: ($current_user['username'] ?? 'User')) ?></div>
                                    <small class="text-muted"><?= ucfirst(str_replace('_', ' ', $current_user['role'] ?? 'user')) ?></small>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= base_url('profile') ?>"><i class="bi bi-person-circle me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="<?= base_url('settings') ?>"><i class="bi bi-gear me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Navbar (Desktop) -->
    <nav class="top-navbar d-none d-lg-block">
        <div class="navbar-content">
            <div class="navbar-search">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search..." class="search-input">
                </div>
            </div>
            <div class="navbar-right">
                <!-- Notifications -->
                <div class="nav-item-dropdown">
                    <button class="nav-icon-btn" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <span class="notification-badge">0</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown">
                        <li class="dropdown-header">Notifications</li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-item-text text-center text-muted py-3">
                            <small>No new notifications</small>
                        </li>
                    </ul>
                </div>
                
                <!-- Profile Dropdown -->
                <div class="nav-item-dropdown">
                    <button class="nav-profile-btn" type="button" data-bs-toggle="dropdown">
                        <?php
                        $avatarPath = isset($current_user['avatar']) && $current_user['avatar'] ? 
                            base_url('uploads/avatars/' . $current_user['avatar']) : 
                            base_url('assets/images/default-avatar.png');
                        ?>
                        <img src="<?= $avatarPath ?>" alt="Avatar" class="profile-avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($current_user['username'] ?? 'User') ?>&background=000&color=fff&size=128'">
                        <span class="profile-name"><?= htmlspecialchars(trim(($current_user['first_name'] ?? '') . ' ' . ($current_user['last_name'] ?? '')) ?: ($current_user['username'] ?? 'User')) ?></span>
                        <i class="bi bi-chevron-down ms-2"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end profile-dropdown">
                        <li class="dropdown-header">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= $avatarPath ?>" alt="Avatar" class="dropdown-avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($current_user['username'] ?? 'User') ?>&background=000&color=fff&size=128'">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars(trim(($current_user['first_name'] ?? '') . ' ' . ($current_user['last_name'] ?? '')) ?: ($current_user['username'] ?? 'User')) ?></div>
                                    <small class="text-muted"><?= ucfirst(str_replace('_', ' ', $current_user['role'] ?? 'user')) ?></small>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= base_url('profile') ?>"><i class="bi bi-person-circle me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="<?= base_url('settings') ?>"><i class="bi bi-gear me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper">
    <?php endif; ?>
    
    <div class="container-fluid <?= isset($current_user) ? 'with-sidebar' : '' ?>">
        <?php if (isset($flash)): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

