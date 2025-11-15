<?php
$page_title = $page_title ?? 'My Profile';
?>

<div class="page-header">
    <h1 class="page-title mb-0">My Profile</h1>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <?php 
                $avatarPath = $user['avatar'] ? base_url('uploads/avatars/' . $user['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['username'] ?? 'User') . '&background=0066cc&color=fff&size=128';
                ?>
                <img src="<?= $avatarPath ?>" alt="Avatar" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/150'">
                
                <h4><?= htmlspecialchars(($user['first_name'] . ' ' . $user['last_name']) ?: $user['username']) ?></h4>
                <p class="text-muted mb-2"><?= htmlspecialchars($user['email']) ?></p>
                <span class="badge bg-<?= getRoleBadgeClass($user['role']) ?>"><?= ucfirst(str_replace('_', ' ', $user['role'])) ?></span>
                
                <form method="POST" enctype="multipart/form-data" class="mt-3">
                    <input type="hidden" name="action" value="upload_avatar">
                    <div class="mb-3">
                        <input type="file" class="form-control form-control-sm" name="avatar" id="avatar" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-upload"></i> Upload Avatar
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <!-- Profile Information -->
        <div class="card mb-4">
            <div class="card-header">
                Profile Information
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                        <small class="text-muted">Username cannot be changed</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="card mb-4">
            <div class="card-header">
                Change Password
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                        <small class="text-muted">Must be at least 8 characters with uppercase, lowercase, number, and special character</small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-shield-lock"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Active Sessions -->
        <?php if (!empty($sessions)): ?>
        <div class="card">
            <div class="card-header">
                Active Sessions
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th>IP Address</th>
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): ?>
                                <tr <?= $session['id'] === session_id() ? 'class="table-primary"' : '' ?>>
                                    <td><?= htmlspecialchars($session['user_agent'] ?? 'Unknown') ?></td>
                                    <td><?= htmlspecialchars($session['ip_address'] ?? 'N/A') ?></td>
                                    <td><?= date('M d, Y H:i', $session['last_activity']) ?></td>
                                    <td>
                                        <?php if ($session['id'] !== session_id()): ?>
                                            <a href="<?= base_url('profile/terminate-session/' . $session['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to terminate this session?')">
                                                <i class="bi bi-x-circle"></i> Terminate
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Current Session</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

