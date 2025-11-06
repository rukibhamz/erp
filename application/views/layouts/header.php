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
    <link href="<?= base_url('assets/css/search.css') ?>" rel="stylesheet">
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
                    <?php
                    // Get active modules for navigation
                    $activeModules = get_active_modules();
                    $moduleRoutes = [
                        'accounting' => ['accounting', 'accounts', 'cash', 'receivables', 'payables', 'payroll', 'ledger', 'reports'],
                        'bookings' => ['bookings', 'facilities'],
                        'properties' => ['properties', 'spaces', 'leases', 'rent-invoices'],
                        'utilities' => ['utilities'],
                        'inventory' => ['inventory'],
                        'tax' => ['tax'],
                        'pos' => ['pos']
                    ];
                    
                    foreach ($activeModules as $module):
                        $moduleKey = $module['module_key'];
                        $moduleName = $module['display_name'];
                        $moduleIcon = $module['icon'] ?? 'bi-puzzle';
                        
                        // Get routes for this module
                        $routes = $moduleRoutes[$moduleKey] ?? [$moduleKey];
                        $isActive = false;
                        foreach ($routes as $route) {
                            if (strpos($current_url, $route) === 0) {
                                $isActive = true;
                                break;
                            }
                        }
                    ?>
                    <li class="nav-item">
                        <a href="<?= base_url($moduleKey) ?>" class="nav-link <?= $isActive ? 'active' : '' ?>">
                            <i class="bi <?= htmlspecialchars($moduleIcon) ?>"></i>
                            <span class="nav-text"><?= htmlspecialchars($moduleName) ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <li class="nav-divider"></li>
                    <?php if (isset($current_user) && $current_user['role'] === 'super_admin'): ?>
                    <li class="nav-item">
                        <a href="<?= base_url('modules') ?>" class="nav-link <?= strpos($current_url, 'modules') === 0 ? 'active' : '' ?>">
                            <i class="bi bi-puzzle"></i>
                            <span class="nav-text">Modules</span>
                        </a>
                    </li>
                    <?php endif; ?>
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
                <div class="nav-item-dropdown">
                    <button class="topbar-icon-btn" type="button" data-bs-toggle="dropdown" id="notificationsDropdown">
                        <i class="bi bi-bell"></i>
                        <span class="notification-badge" id="notificationBadge"><?= $unread_notification_count ?? 0 ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" id="notificationsMenu">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Notifications</span>
                            <?php if (($unread_notification_count ?? 0) > 0): ?>
                                <a href="<?= base_url('notifications/mark-all-read') ?>" class="btn btn-sm btn-link text-decoration-none" onclick="markAllNotificationsRead(event)">
                                    Mark all read
                                </a>
                            <?php endif; ?>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <div id="notificationsList">
                            <?php if (!empty($notifications)): ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <li>
                                        <a class="dropdown-item notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>" 
                                           href="<?= $notification['related_module'] && $notification['related_id'] ? base_url($notification['related_module'] . '/view/' . $notification['related_id']) : '#' ?>"
                                           data-notification-id="<?= $notification['id'] ?>"
                                           onclick="markNotificationRead(<?= $notification['id'] ?>)">
                                            <div class="d-flex w-100 justify-content-between">
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($notification['title']) ?></h6>
                                                    <p class="mb-0 small text-muted"><?= htmlspecialchars(substr($notification['message'], 0, 60)) ?></p>
                                                    <small class="text-muted"><?= timeAgo($notification['created_at']) ?></small>
                                                </div>
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="badge bg-primary rounded-circle" style="width: 8px; height: 8px;"></span>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="<?= base_url('notifications') ?>">View all notifications</a></li>
                            <?php else: ?>
                                <li class="dropdown-item-text text-center text-muted py-3">
                                    <small>No new notifications</small>
                                </li>
                            <?php endif; ?>
                        </div>
                    </ul>
                </div>
                
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
                <form action="<?= base_url('search') ?>" method="GET" class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" name="q" placeholder="Search across all modules..." 
                           class="search-input" id="globalSearchInput" autocomplete="off">
                    <input type="hidden" name="module" value="all">
                </form>
                <div id="searchResults" class="search-results-dropdown" style="display: none;"></div>
            </div>
            <div class="navbar-right">
                <!-- Notifications -->
                <div class="nav-item-dropdown">
                    <button class="nav-icon-btn" type="button" data-bs-toggle="dropdown" id="notificationsDropdownDesktop">
                        <i class="bi bi-bell"></i>
                        <span class="notification-badge" id="notificationBadgeDesktop"><?= $unread_notification_count ?? 0 ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" id="notificationsMenuDesktop">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Notifications</span>
                            <?php if (($unread_notification_count ?? 0) > 0): ?>
                                <a href="<?= base_url('notifications/mark-all-read') ?>" class="btn btn-sm btn-link text-decoration-none" onclick="markAllNotificationsRead(event)">
                                    Mark all read
                                </a>
                            <?php endif; ?>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <div id="notificationsListDesktop">
                            <?php if (!empty($notifications)): ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <li>
                                        <a class="dropdown-item notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>" 
                                           href="<?= $notification['related_module'] && $notification['related_id'] ? base_url($notification['related_module'] . '/view/' . $notification['related_id']) : '#' ?>"
                                           data-notification-id="<?= $notification['id'] ?>"
                                           onclick="markNotificationRead(<?= $notification['id'] ?>)">
                                            <div class="d-flex w-100 justify-content-between">
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($notification['title']) ?></h6>
                                                    <p class="mb-0 small text-muted"><?= htmlspecialchars(substr($notification['message'], 0, 60)) ?></p>
                                                    <small class="text-muted"><?= timeAgo($notification['created_at']) ?></small>
                                                </div>
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="badge bg-primary rounded-circle" style="width: 8px; height: 8px;"></span>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="<?= base_url('notifications') ?>">View all notifications</a></li>
                            <?php else: ?>
                                <li class="dropdown-item-text text-center text-muted py-3">
                                    <small>No new notifications</small>
                                </li>
                            <?php endif; ?>
                        </div>
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

