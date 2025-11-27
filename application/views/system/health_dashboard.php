<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">System Health Dashboard</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-primary" onclick="refreshHealth()">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Overall Status -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-<?= getHealthAlertClass($health['overall']) ?> text-center">
                                <h4>
                                    <i class="fas fa-<?= getHealthIcon($health['overall']) ?>"></i>
                                    System Status: <?= strtoupper($health['overall']) ?>
                                </h4>
                                <p class="mb-0">Last checked: <?= date('Y-m-d H:i:s') ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Health Metrics -->
                    <div class="row">
                        <!-- Database Health -->
                        <div class="col-md-4 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-<?= getHealthBgClass($health['database']['status']) ?>">
                                    <i class="fas fa-database"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Database</span>
                                    <span class="info-box-number"><?= $health['database']['message'] ?></span>
                                    <?php if (isset($health['database']['response_time'])): ?>
                                        <small>Response: <?= $health['database']['response_time'] ?>ms | Size: <?= $health['database']['size_mb'] ?>MB</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Disk Space -->
                        <div class="col-md-4 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-<?= getHealthBgClass($health['disk']['status']) ?>">
                                    <i class="fas fa-hdd"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Disk Space</span>
                                    <span class="info-box-number"><?= $health['disk']['percent_used'] ?>% Used</span>
                                    <small><?= $health['disk']['free_gb'] ?>GB free of <?= $health['disk']['total_gb'] ?>GB</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Error Rate -->
                        <div class="col-md-4 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-<?= getHealthBgClass($health['errors']['status']) ?>">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Errors (Last Hour)</span>
                                    <span class="info-box-number"><?= $health['errors']['errors_last_hour'] ?></span>
                                    <small>Warnings: <?= $health['errors']['warnings_last_hour'] ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Active Sessions -->
                        <div class="col-md-4 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-users"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Active Sessions</span>
                                    <span class="info-box-number"><?= $health['sessions']['active_sessions'] ?></span>
                                    <small>Users currently logged in</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Failed Logins -->
                        <div class="col-md-4 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-<?= getHealthBgClass($health['failed_logins']['status']) ?>">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Locked Accounts</span>
                                    <span class="info-box-number"><?= $health['failed_logins']['locked_accounts'] ?></span>
                                    <small><?= $health['failed_logins']['message'] ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Load -->
                        <?php if ($health['system_load']['status'] !== 'unknown'): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-<?= getHealthBgClass($health['system_load']['status']) ?>">
                                    <i class="fas fa-tachometer-alt"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">System Load</span>
                                    <span class="info-box-number"><?= $health['system_load']['load_1min'] ?></span>
                                    <small>5min: <?= $health['system_load']['load_5min'] ?> | 15min: <?= $health['system_load']['load_15min'] ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Quick Actions</h5>
                            <a href="<?= base_url('system_logs') ?>" class="btn btn-info">
                                <i class="fas fa-list"></i> View System Logs
                            </a>
                            <a href="<?= base_url('system_logs?level=ERROR') ?>" class="btn btn-danger">
                                <i class="fas fa-exclamation-circle"></i> View Errors
                            </a>
                            <a href="<?= base_url('system_health/check') ?>" class="btn btn-secondary" target="_blank">
                                <i class="fas fa-heartbeat"></i> Health Check API
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Health metrics are updated in real-time. Auto-refresh every 30 seconds.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refreshHealth() {
    $.get('<?= base_url('system_health/ajax_get_health') ?>', function(response) {
        if (response.success) {
            location.reload();
        }
    });
}

// Auto-refresh every 30 seconds
setInterval(refreshHealth, 30000);
</script>

<?php
function getHealthAlertClass($status) {
    $classes = [
        'healthy' => 'success',
        'warning' => 'warning',
        'critical' => 'danger',
        'unknown' => 'secondary'
    ];
    return $classes[$status] ?? 'secondary';
}

function getHealthBgClass($status) {
    $classes = [
        'healthy' => 'success',
        'warning' => 'warning',
        'critical' => 'danger',
        'unknown' => 'secondary'
    ];
    return $classes[$status] ?? 'secondary';
}

function getHealthIcon($status) {
    $icons = [
        'healthy' => 'check-circle',
        'warning' => 'exclamation-triangle',
        'critical' => 'times-circle',
        'unknown' => 'question-circle'
    ];
    return $icons[$status] ?? 'question-circle';
}
?>
