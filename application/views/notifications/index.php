<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Notifications</h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('notifications?unread_only=1') ?>" class="btn btn-<?= $unread_only ? 'primary' : 'outline-primary' ?>">
                Unread Only
            </a>
            <a href="<?= base_url('notifications') ?>" class="btn btn-<?= !$unread_only ? 'primary' : 'outline-primary' ?>">
                All
            </a>
            <button class="btn btn-secondary" onclick="markAllRead()">
                <i class="bi bi-check-all"></i> Mark All as Read
            </button>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-bell-slash" style="font-size: 3rem;"></i>
                <p class="mt-3">No notifications found.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $notification): ?>
                    <div class="list-group-item list-group-item-action <?= $notification['is_read'] ? 'bg-light' : 'bg-white border-start border-4 border-primary' ?>" id="notification-<?= $notification['id'] ?> shadow-sm">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($notification['title']) ?></h6>
                                <p class="mb-1 text-mutedSmall"><?= htmlspecialchars($notification['message']) ?></p>
                                <small class="text-secondary"><?= format_datetime($notification['created_at']) ?></small>
                                <?php if (!empty($notification['action_url'])): ?>
                                    <div class="mt-2">
                                        <a href="<?= base_url($notification['action_url']) ?>" class="btn btn-sm btn-outline-primary btn-rounded px-3">
                                            View Details
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <button class="btn btn-link btn-sm text-primary" onclick="markAsRead(<?= $notification['id'] ?>)">
                                    <i class="bi bi-check-circle-fill"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function markAsRead(id) {
    fetch('<?= base_url('notifications/mark-read/') ?>' + id, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': '<?= get_csrf_token() ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.getElementById('notification-' + id);
            item.classList.remove('bg-white', 'border-start', 'border-4', 'border-primary');
            item.classList.add('bg-light');
            const btn = item.querySelector('button');
            if (btn) btn.remove();
        }
    });
}

function markAllRead() {
    if (!confirm('Mark all notifications as read?')) return;
    
    fetch('<?= base_url('notifications/mark-all-read') ?>', {
        method: 'POST',
        headers: {
            'X-CSRF-Token': '<?= get_csrf_token() ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>

<style>
.list-group-item {
    transition: all 0.2s ease;
    border-radius: 0 !important;
}
.list-group-item:hover {
    background-color: #f1f3f5 !important;
}
.text-mutedSmall {
    font-size: 0.9rem;
}
.btn-rounded {
    border-radius: 2rem;
}
</style>
