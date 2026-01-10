<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Resource Availability</h5>
                    <a href="<?= site_url('facilities') ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Facilities
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($flash) && $flash): ?>
                        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
                            <?= htmlspecialchars($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($facility) && $facility): ?>
                        <div class="alert alert-info">
                            <strong>Facility:</strong> <?= htmlspecialchars($facility['facility_name'] ?? '') ?>
                        </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Operating Hours</h6>
                            <form method="POST" action="">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update_hours">
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Opening Time</label>
                                        <input type="time" class="form-control" name="opening_time" 
                                               value="<?= htmlspecialchars($availability['opening_time'] ?? '08:00') ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Closing Time</label>
                                        <input type="time" class="form-control" name="closing_time" 
                                               value="<?= htmlspecialchars($availability['closing_time'] ?? '22:00') ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Available Days</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php 
                                        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                        $availableDays = $availability['available_days'] ?? [1,2,3,4,5,6];
                                        foreach ($days as $index => $day): 
                                        ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="available_days[]" 
                                                       value="<?= $index ?>" id="day_<?= $index ?>"
                                                       <?= in_array($index, $availableDays) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="day_<?= $index ?>">
                                                    <?= substr($day, 0, 3) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Hours
                                </button>
                            </form>
                        </div>

                        <div class="col-md-6">
                            <h6>Booking Settings</h6>
                            <form method="POST" action="">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update_settings">
                                
                                <div class="mb-3">
                                    <label class="form-label">Minimum Booking Duration (hours)</label>
                                    <input type="number" class="form-control" name="min_duration" 
                                           value="<?= intval($availability['min_duration'] ?? 1) ?>" min="1">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Maximum Booking Duration (hours)</label>
                                    <input type="number" class="form-control" name="max_duration" 
                                           value="<?= intval($availability['max_duration'] ?? 24) ?>" min="1">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Buffer Time Between Bookings (minutes)</label>
                                    <input type="number" class="form-control" name="buffer_time" 
                                           value="<?= intval($availability['buffer_time'] ?? 60) ?>" min="0" step="15">
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Settings
                                </button>
                            </form>
                        </div>
                    </div>

                    <hr>

                    <h6>Availability Calendar</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <?php foreach ($days as $day): ?>
                                        <th class="text-center"><?= substr($day, 0, 3) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($hour = 8; $hour <= 21; $hour++): ?>
                                    <tr>
                                        <td><?= sprintf('%02d:00', $hour) ?></td>
                                        <?php foreach ($days as $index => $day): ?>
                                            <td class="text-center <?= in_array($index, $availableDays ?? []) ? 'bg-success-subtle' : 'bg-secondary-subtle' ?>">
                                                <?= in_array($index, $availableDays ?? []) ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>' ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
