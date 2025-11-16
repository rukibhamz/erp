<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Backup & Restore</h1>
        <form method="POST" action="<?= base_url('settings/backup/create') ?><?php echo csrf_field(); ?>" onsubmit="return confirm('Create a new backup? This may take a few moments.')">
            <button type="submit" class="btn btn-dark">
                <i class="bi bi-database"></i> Create Backup Now
            </button>
        </form>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    <strong>Backup Information:</strong>
    <ul class="mb-0 mt-2">
        <li>Backups are stored in the <code>backups/</code> directory</li>
        <li>Only the last 30 backups are kept (oldest are automatically deleted)</li>
        <li>Backups include the complete database</li>
        <li>Download backups regularly and store them securely</li>
        <li>Recommended: Schedule daily automated backups</li>
    </ul>
</div>

<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Available Backups</h5>
    </div>
    <div class="card-body">
        <?php if (empty($backups ?? [])): ?>
            <p class="text-muted text-center py-4">No backups found. Create your first backup above.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Backup File</th>
                            <th>Size</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($backup['filename']) ?></code></td>
                                <td><?= htmlspecialchars($backup['size_formatted']) ?></td>
                                <td><?= date('M d, Y H:i:s', strtotime($backup['created'])) ?></td>
                                <td>
                                    <a href="<?= base_url('settings/backup/download/' . urlencode($backup['filename'])) ?>" 
                                       class="btn btn-sm btn-dark">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-shield-exclamation"></i> Restore from Backup</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Warning:</strong> Restoring a backup will replace all current data with the backup data. This action cannot be undone.
            <br><strong>Always create a new backup before restoring!</strong>
        </div>
        <form method="POST" action="<?= base_url('settings/backup/restore') ?>" 
              enctype="multipart/form-data"
              onsubmit="return confirm('Are you sure you want to restore from backup? ALL current data will be replaced!')">
            <div class="mb-3">
                <label class="form-label">Upload Backup File (.sql)</label>
                <input type="file" name="backup_file" class="form-control" accept=".sql" required>
                <small class="text-muted">Select a backup file to restore</small>
            </div>
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-arrow-counterclockwise"></i> Restore from Backup
            </button>
        </form>
    </div>
</div>



