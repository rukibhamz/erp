    </div>
    
    <?php if (isset($current_user)): ?>
    </div> <!-- Close main-content-wrapper -->
    <?php endif; ?>
    
    <footer>
        <div class="container-fluid">
            <div class="text-center text-muted" style="font-size: 0.875rem;">
                &copy; <?= date('Y') ?> Business ERP
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?= csp_nonce() ?>">
        // Set base URL for JavaScript files
        window.BASE_URL = '<?= base_url() ?>';
    </script>
    <script src="<?= base_url('assets/js/main.js') ?>"></script>
    <script src="<?= base_url('assets/js/search.js') ?>"></script>
    <?php if (isset($current_user)): ?>
    <script src="<?= base_url('assets/js/sidebar.js') ?>"></script>
    <?php endif; ?>
    <script nonce="<?= csp_nonce() ?>">
    // Notification functions
    function markNotificationRead(notificationId) {
        fetch('<?= base_url('notifications/mark-read') ?>/' + notificationId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('notificationBadge') || document.getElementById('notificationBadgeDesktop');
                    if (badge) {
                        const currentCount = parseInt(badge.textContent) || 0;
                        if (currentCount > 0) {
                            badge.textContent = currentCount - 1;
                            if (currentCount - 1 === 0) {
                                badge.style.display = 'none';
                            }
                        }
                    }
                    const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (item) {
                        item.classList.remove('unread');
                        const dot = item.querySelector('.badge.bg-primary');
                        if (dot) dot.remove();
                    }
                }
            })
            .catch(error => console.error('Error marking notification as read:', error));
    }
    
    function markAllNotificationsRead(event) {
        event.preventDefault();
        fetch('<?= base_url('notifications/mark-all-read') ?>')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('notificationBadge') || document.getElementById('notificationBadgeDesktop');
                    if (badge) {
                        badge.textContent = '0';
                        badge.style.display = 'none';
                    }
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                        const dot = item.querySelector('.badge.bg-primary');
                        if (dot) dot.remove();
                    });
                }
            })
            .catch(error => console.error('Error marking all notifications as read:', error));
    }
    
    <?php if (isset($current_user)): ?>
    // Auto-refresh notifications every 30 seconds
    setInterval(function() {
        fetch('<?= base_url('notifications/get-notifications') ?>?unread_only=1&limit=10')
            .then(response => {
                // If redirected to login (non-JSON), stop polling silently
                const ct = response.headers.get('content-type') || '';
                if (!ct.includes('application/json')) return null;
                return response.json();
            })
            .then(data => {
                if (!data || !data.success) return;
                const badge = document.getElementById('notificationBadge') || document.getElementById('notificationBadgeDesktop');
                if (badge) {
                    badge.textContent = data.unread_count || 0;
                    badge.style.display = data.unread_count > 0 ? 'block' : 'none';
                }
            })
            .catch(() => {}); // Suppress network errors silently
    }, 30000);
    <?php endif; ?>
    </script>
</body>
</html>

