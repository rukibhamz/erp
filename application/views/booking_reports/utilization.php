<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Utilization Report</h1>
        <a href="<?= base_url('booking-reports') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= base_url('booking-reports/utilization') ?>" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Facility (Optional)</label>
                    <select name="facility_id" class="form-select">
                        <option value="">All Facilities</option>
                        <?php foreach ($facilities as $facility): ?>
                            <option value="<?= $facility['id'] ?>" <?= $facility_id == $facility['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($facility['facility_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Utilization Data -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Resource Utilization</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($utilization_data)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Facility</th>
                                <th class="text-end">Total Bookings</th>
                                <th class="text-end">Booked Hours</th>
                                <th class="text-end">Available Hours</th>
                                <th class="text-end">Utilization Rate</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilization_data as $data): ?>
                                <?php $facility = $data['facility']; ?>
                                <tr>
                                    <td><?= htmlspecialchars($facility['facility_name']) ?></td>
                                    <td class="text-end"><?= $data['total_bookings'] ?></td>
                                    <td class="text-end"><?= number_format($data['total_hours'], 1) ?></td>
                                    <td class="text-end"><?= number_format($data['available_hours'], 1) ?></td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <div class="progress" style="width: 100px; height: 20px; margin-right: 10px;">
                                                <div class="progress-bar bg-<?= $data['utilization_rate'] > 70 ? 'success' : ($data['utilization_rate'] > 40 ? 'warning' : 'danger') ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= min(100, $data['utilization_rate']) ?>%">
                                                </div>
                                            </div>
                                            <span><?= number_format($data['utilization_rate'], 1) ?>%</span>
                                        </div>
                                    </td>
                                    <td class="text-end"><?= format_currency($data['total_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No utilization data for the selected period</p>
            <?php endif; ?>
        </div>
    </div>
</div>
