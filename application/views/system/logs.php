<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">System Logs</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-primary" onclick="refreshLogs()">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                        <a href="<?= base_url('system_logs/export?' . http_build_query($filters)) ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-download"></i> Export CSV
                        </a>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?= $stats['DEBUG'] ?? 0 ?></h3>
                                    <p>Debug</p>
                                </div>
                                <div class="icon"><i class="fas fa-bug"></i></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?= $stats['INFO'] ?? 0 ?></h3>
                                    <p>Info</p>
                                </div>
                                <div class="icon"><i class="fas fa-info-circle"></i></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?= $stats['WARNING'] ?? 0 ?></h3>
                                    <p>Warnings</p>
                                </div>
                                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3><?= $stats['ERROR'] ?? 0 ?></h3>
                                    <p>Errors</p>
                                </div>
                                <div class="icon"><i class="fas fa-times-circle"></i></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="small-box bg-dark">
                                <div class="inner">
                                    <h3><?= $stats['CRITICAL'] ?? 0 ?></h3>
                                    <p>Critical</p>
                                </div>
                                <div class="icon"><i class="fas fa-skull-crossbones"></i></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="small-box bg-secondary">
                                <div class="inner">
                                    <h3><?= number_format($total) ?></h3>
                                    <p>Total Logs</p>
                                </div>
                                <div class="icon"><i class="fas fa-list"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <form method="get" action="<?= base_url('system_logs') ?>" class="mb-3">
                        <div class="row">
                            <div class="col-md-2">
                                <select name="level" class="form-control form-control-sm">
                                    <option value="">All Levels</option>
                                    <option value="DEBUG" <?= $filters['level'] == 'DEBUG' ? 'selected' : '' ?>>Debug</option>
                                    <option value="INFO" <?= $filters['level'] == 'INFO' ? 'selected' : '' ?>>Info</option>
                                    <option value="WARNING" <?= $filters['level'] == 'WARNING' ? 'selected' : '' ?>>Warning</option>
                                    <option value="ERROR" <?= $filters['level'] == 'ERROR' ? 'selected' : '' ?>>Error</option>
                                    <option value="CRITICAL" <?= $filters['level'] == 'CRITICAL' ? 'selected' : '' ?>>Critical</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="module" class="form-control form-control-sm">
                                    <option value="">All Modules</option>
                                    <?php foreach ($modules as $mod): ?>
                                        <option value="<?= $mod ?>" <?= $filters['module'] == $mod ? 'selected' : '' ?>><?= ucfirst($mod) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $filters['date_from'] ?>" placeholder="From Date">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $filters['date_to'] ?>" placeholder="To Date">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="search" class="form-control form-control-sm" value="<?= $filters['search'] ?>" placeholder="Search message or URL...">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-sm btn-primary btn-block">Filter</button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Logs Table -->
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="10%">Level</th>
                                    <th width="35%">Message</th>
                                    <th width="10%">Module</th>
                                    <th width="10%">User</th>
                                    <th width="10%">IP</th>
                                    <th width="15%">Time</th>
                                    <th width="5%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No logs found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr class="log-level-<?= strtolower($log['level']) ?>">
                                            <td><?= $log['id'] ?></td>
                                            <td>
                                                <span class="badge badge-<?= getLevelBadgeClass($log['level']) ?>">
                                                    <?= $log['level'] ?>
                                                </span>
                                            </td>
                                            <td><?= character_limiter($log['message'], 100) ?></td>
                                            <td><?= $log['module'] ?? '-' ?></td>
                                            <td><?= $log['username'] ?? 'System' ?></td>
                                            <td><?= $log['ip_address'] ?></td>
                                            <td><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                                            <td>
                                                <a href="<?= base_url('system_logs/view/' . $log['id']) ?>" class="btn btn-xs btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($pages > 1): ?>
                        <nav>
                            <ul class="pagination pagination-sm">
                                <?php for ($i = 1; $i <= $pages; $i++): ?>
                                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= base_url('system_logs?page=' . $i . '&' . http_build_query($filters)) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
                
                <div class="card-footer">
                    <button type="button" class="btn btn-sm btn-danger" onclick="cleanOldLogs()">
                        <i class="fas fa-trash"></i> Clean Old Logs
                    </button>
                    <small class="text-muted ml-3">Showing logs from last 24 hours in statistics</small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.log-level-debug { background-color: #f8f9fa; }
.log-level-info { background-color: #d1ecf1; }
.log-level-warning { background-color: #fff3cd; }
.log-level-error { background-color: #f8d7da; }
.log-level-critical { background-color: #f5c6cb; font-weight: bold; }
</style>

<script>
function refreshLogs() {
    location.reload();
}

function cleanOldLogs() {
    if (confirm('Are you sure you want to delete logs older than 90 days?')) {
        $.post('<?= base_url('system_logs/clean') ?>', {days: 90}, function() {
            location.reload();
        });
    }
}

// Auto-refresh every 30 seconds
setInterval(refreshLogs, 30000);
</script>

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
?>
