<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Database Migrations</h1>
        <div>
            <a href="<?= base_url('system_migrate/up') ?>" class="btn btn-primary" onclick="return confirm('Ensure you have backed up your database before running migrations. Continue?')">
                <i class="bi bi-arrow-up-circle"></i> Run Pending Migrations
            </a>
        </div>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Migration Status</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Migration File</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($migrations)): ?>
                        <?php foreach ($migrations as $index => $m): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><code><?= htmlspecialchars($m['name']) ?></code></td>
                                <td>
                                    <?php if ($m['status'] == 'executed'): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i> Executed
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-clock"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center py-4">No migrations found in <code>database/migrations/</code></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4">
    <div class="alert alert-info">
        <h5><i class="bi bi-info-circle"></i> About Migrations</h5>
        <p class="mb-0">Migrations are used to update the database schema across different installations. If you see "Pending" migrations, click the <strong>Run Pending Migrations</strong> button to apply updates.</p>
    </div>
</div>
