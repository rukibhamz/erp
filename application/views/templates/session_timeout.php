<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!-- Session Timeout Warning Modal -->
<div class="modal fade" id="sessionTimeoutModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-clock"></i> Session Expiring Soon
                </h5>
            </div>
            <div class="modal-body text-center">
                <p class="lead">Your session will expire in:</p>
                <h2 class="text-danger">
                    <span id="timeoutCountdown">5:00</span>
                </h2>
                <p>You will be automatically logged out for security reasons.</p>
                <p class="text-muted">Click "Stay Logged In" to extend your session.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-lg btn-block" onclick="extendSession()">
                    <i class="fas fa-check"></i> Stay Logged In
                </button>
                <button type="button" class="btn btn-secondary btn-sm btn-block" onclick="logoutNow()">
                    <i class="fas fa-sign-out-alt"></i> Logout Now
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Session timeout configuration
const SESSION_TIMEOUT = <?= $this->config->item('sess_expiration') ?? 3600 ?>; // seconds
const WARNING_TIME = 300; // Show warning 5 minutes before timeout
const CHECK_INTERVAL = 60000; // Check every minute

let lastActivity = Date.now();
let warningShown = false;
let countdownInterval = null;

// Track user activity
function resetActivity() {
    lastActivity = Date.now();
    warningShown = false;
    
    // Hide warning if shown
    if ($('#sessionTimeoutModal').hasClass('show')) {
        $('#sessionTimeoutModal').modal('hide');
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
    }
}

// Check session timeout
function checkSessionTimeout() {
    const now = Date.now();
    const elapsed = (now - lastActivity) / 1000; // seconds
    const remaining = SESSION_TIMEOUT - elapsed;
    
    // Show warning if within warning time
    if (remaining <= WARNING_TIME && !warningShown) {
        showTimeoutWarning(remaining);
        warningShown = true;
    }
    
    // Auto logout if expired
    if (remaining <= 0) {
        logoutNow();
    }
}

// Show timeout warning modal
function showTimeoutWarning(remainingSeconds) {
    $('#sessionTimeoutModal').modal('show');
    
    // Start countdown
    countdownInterval = setInterval(function() {
        remainingSeconds--;
        
        if (remainingSeconds <= 0) {
            clearInterval(countdownInterval);
            logoutNow();
            return;
        }
        
        // Update countdown display
        const minutes = Math.floor(remainingSeconds / 60);
        const seconds = remainingSeconds % 60;
        $('#timeoutCountdown').text(
            minutes + ':' + (seconds < 10 ? '0' : '') + seconds
        );
    }, 1000);
}

// Extend session
function extendSession() {
    $.ajax({
        url: '<?= base_url('auth/extend_session') ?>',
        method: 'POST',
        data: {
            csrf_token: $('input[name="csrf_token"]').val()
        },
        success: function(response) {
            resetActivity();
            $('#sessionTimeoutModal').modal('hide');
            
            // Show success message
            toastr.success('Session extended successfully');
        },
        error: function() {
            toastr.error('Failed to extend session');
        }
    });
}

// Logout immediately
function logoutNow() {
    window.location.href = '<?= base_url('auth/logout') ?>';
}

// Initialize session timeout monitoring
$(document).ready(function() {
    // Track user activity
    $(document).on('mousemove keypress click scroll', function() {
        resetActivity();
    });
    
    // Check timeout periodically
    setInterval(checkSessionTimeout, CHECK_INTERVAL);
    
    // Initial check
    checkSessionTimeout();
});
</script>

<style>
#sessionTimeoutModal .modal-header {
    border-bottom: none;
}

#sessionTimeoutModal .modal-footer {
    border-top: none;
    flex-direction: column;
}

#timeoutCountdown {
    font-size: 3rem;
    font-weight: bold;
    font-family: monospace;
}
</style>
