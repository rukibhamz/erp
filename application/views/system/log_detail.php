<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Log Entry #<?= $log['id'] ?></h3>
                    <div class="card-tools">
                        <a href="<?= base_url('system_logs') ?>" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Logs
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Level</th>
                                    <td>
                                        <span class="badge badge-<?= getLevelBadgeClass($log['level']) ?>">
                                            <?= $log['level'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Module</th>
                                    <td><?= $log['module'] ?? '-' ?></td>
                                </tr>
                                <tr>
                                    <th>User</th>
                                    <td>
                                        <?php if ($log['username']): ?>
                                            <?= $log['username'] ?> (<?= $log['email'] ?>)
                                        <?php else: ?>
                                            System
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>IP Address</th>
                                    <td><?= $log['ip_address'] ?></td>
                                </tr>
                                <tr>
                                    <th>User Agent</th>
                                    <td><?= $log['user_agent'] ?></td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Message</h5>
                            <div class="alert alert-<?= getLevelAlertClass($log['level']) ?>">
                                <?= nl2br(htmlspecialchars($log['message'])) ?>
                            </div>
                            
                            <?php if ($log['url']): ?>
                                <h5>URL</h5>
                                <p><code><?= htmlspecialchars($log['url']) ?></code></p>
                            <?php endif; ?>
                            
                            <?php if ($log['context']): ?>
                                <h5>Context</h5>
                                <pre class="bg-light p-3"><?= json_encode(json_decode($log['context']), JSON_PRETTY_PRINT) ?></pre>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function getLevelBadgeClass($level) {
    $classes = [
        'DEBUG' => 'secondary',
        'INFO' => 'info',
        'WARNING' => 'warning',
        'ERROR' => 'danger',
        'CRITICAL' => 'dark'
    ];
    return $classes[$level] ?? 'secondary';
}

function getLevelAlertClass($level) {
    $classes = [
        'DEBUG' => 'secondary',
        'INFO' => 'info',
        'WARNING' => 'warning',
        'ERROR' => 'danger',
        'CRITICAL' => 'danger'
    ];
    return $classes[$level] ?? 'secondary';
}
?>
