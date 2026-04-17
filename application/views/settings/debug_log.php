<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Booking Debug Log</h1>
    <div>
        <a href="<?= base_url('settings/system') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Settings
        </a>
        <?php if ($log_exists): ?>
        <form method="POST" action="<?= base_url('settings/debug-log/clear') ?>" style="display:inline"
              onsubmit="return confirm('Clear the debug log?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash"></i> Clear Log
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-file-text"></i> <?= htmlspecialchars($log_file) ?></h6>
        <small class="text-muted">Showing last 200 lines (newest first)</small>
    </div>
    <div class="card-body p-0">
        <?php if (!$log_exists || empty($log_content)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                No log entries yet. Try creating a booking to generate log output.
            </div>
        <?php else: ?>
            <pre class="p-3 mb-0" style="background:#1e1e1e;color:#d4d4d4;font-size:0.78rem;max-height:600px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars($log_content) ?></pre>
        <?php endif; ?>
    </div>
</div>

<div class="mt-3">
    <small class="text-muted">
        <i class="bi bi-info-circle"></i>
        This log is written by <code>Booking_model::create()</code> to help diagnose booking creation failures.
        Remove debug logging once the issue is resolved.
    </small>
</div>
