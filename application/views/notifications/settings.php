<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$notificationTypes = [
    'all' => 'All Notifications',
    'system' => 'System Notifications',
    'booking_confirmation' => 'Booking Confirmations',
    'booking_reminder' => 'Booking Reminders',
    'booking_cancelled' => 'Booking Cancellations',
    'booking_modified' => 'Booking Modifications',
    'payment_received' => 'Payment Received',
    'payment_due' => 'Payment Due',
    'task' => 'Task Assignments',
    'approval' => 'Approval Requests',
    'other' => 'Other Notifications'
];
?>

<div class="page-header mb-4">
    <h1 class="page-title mb-0">Notification Preferences</h1>
    <p class="text-muted mb-0">Manage how and when you receive notifications</p>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form id="notificationPreferencesForm">
    <!-- Email Notifications -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-envelope"></i> Email Notifications</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Notification Type</th>
                            <th class="text-center">Enabled</th>
                            <th>Frequency</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notificationTypes as $typeKey => $typeLabel): ?>
                            <?php 
                            $pref = $preferences['email'][$typeKey] ?? null;
                            $enabled = $pref ? (bool)$pref['enabled'] : true;
                            $frequency = $pref['frequency'] ?? 'instant';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($typeLabel) ?></td>
                                <td class="text-center">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" 
                                               name="preferences[email][<?= $typeKey ?>][enabled]"
                                               id="email_<?= $typeKey ?>_enabled"
                                               <?= $enabled ? 'checked' : '' ?>>
                                    </div>
                                </td>
                                <td>
                                    <select name="preferences[email][<?= $typeKey ?>][frequency]" 
                                            class="form-select form-select-sm" 
                                            id="email_<?= $typeKey ?>_frequency">
                                        <option value="instant" <?= $frequency === 'instant' ? 'selected' : '' ?>>Instant</option>
                                        <option value="daily" <?= $frequency === 'daily' ? 'selected' : '' ?>>Daily Digest</option>
                                        <option value="weekly" <?= $frequency === 'weekly' ? 'selected' : '' ?>>Weekly Digest</option>
                                        <option value="never" <?= $frequency === 'never' ? 'selected' : '' ?>>Never</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="row g-3 mt-3">
                <div class="col-md-6">
                    <label class="form-label">Quiet Hours Start</label>
                    <input type="time" name="preferences[email][all][quiet_start]" 
                           class="form-control" 
                           value="<?= htmlspecialchars($preferences['email']['all']['quiet_hours_start'] ?? '22:00') ?>">
                    <small class="text-muted">No email notifications during these hours</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Quiet Hours End</label>
                    <input type="time" name="preferences[email][all][quiet_end]" 
                           class="form-control" 
                           value="<?= htmlspecialchars($preferences['email']['all']['quiet_hours_end'] ?? '08:00') ?>">
                </div>
            </div>
        </div>
    </div>
    
    <!-- SMS Notifications -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-chat-dots"></i> SMS Notifications</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> SMS notifications require a verified phone number. Only urgent notifications are sent via SMS.
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Notification Type</th>
                            <th class="text-center">Enabled</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $smsTypes = [
                            'all' => 'All SMS Notifications',
                            'booking_confirmation' => 'Booking Confirmations',
                            'payment_received' => 'Payment Received',
                            'system' => 'Urgent System Alerts'
                        ];
                        foreach ($smsTypes as $typeKey => $typeLabel): 
                            $pref = $preferences['sms'][$typeKey] ?? null;
                            $enabled = $pref ? (bool)$pref['enabled'] : false;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($typeLabel) ?></td>
                                <td class="text-center">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" 
                                               name="preferences[sms][<?= $typeKey ?>][enabled]"
                                               id="sms_<?= $typeKey ?>_enabled"
                                               <?= $enabled ? 'checked' : '' ?>>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- In-App Notifications -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-bell"></i> In-App Notifications</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Notification Type</th>
                            <th class="text-center">Enabled</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notificationTypes as $typeKey => $typeLabel): ?>
                            <?php 
                            $pref = $preferences['in_app'][$typeKey] ?? null;
                            $enabled = $pref ? (bool)$pref['enabled'] : true;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($typeLabel) ?></td>
                                <td class="text-center">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" 
                                               name="preferences[in_app][<?= $typeKey ?>][enabled]"
                                               id="in_app_<?= $typeKey ?>_enabled"
                                               <?= $enabled ? 'checked' : '' ?>>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-dark">
            <i class="bi bi-save"></i> Save Preferences
        </button>
        <a href="<?= base_url('notifications') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</form>

<script>
document.getElementById('notificationPreferencesForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const preferences = {};
    
    // Convert form data to nested object
    for (let [key, value] of formData.entries()) {
        const parts = key.match(/preferences\[(\w+)\]\[(\w+)\]\[(\w+)\]/);
        if (parts) {
            const [, prefType, notifType, setting] = parts;
            if (!preferences[prefType]) preferences[prefType] = {};
            if (!preferences[prefType][notifType]) preferences[prefType][notifType] = {};
            preferences[prefType][notifType][setting] = value;
        }
    }
    
    // Handle checkboxes
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        const parts = checkbox.name.match(/preferences\[(\w+)\]\[(\w+)\]\[(\w+)\]/);
        if (parts) {
            const [, prefType, notifType, setting] = parts;
            if (!preferences[prefType]) preferences[prefType] = {};
            if (!preferences[prefType][notifType]) preferences[prefType][notifType] = {};
            preferences[prefType][notifType][setting] = checkbox.checked;
        }
    });
    
    try {
        const response = await fetch('<?= base_url("notifications/save-preferences") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'preferences=' + encodeURIComponent(JSON.stringify(preferences))
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Preferences saved successfully!');
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to save preferences'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});
</script>


