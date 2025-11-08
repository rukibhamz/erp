<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Booking Calendar</h1>
        <div>
            <a href="<?= base_url('bookings') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-list"></i> List View
            </a>
            <?php if (has_permission('bookings', 'create')): ?>
                <a href="<?= base_url('bookings/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> New Booking
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Facility</label>
                    <select name="facility_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Facilities</option>
                        <?php foreach ($facilities as $facility): ?>
                            <option value="<?= $facility['id'] ?>" <?= ($selected_facility_id ?? '') == $facility['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($facility['facility_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Month</label>
                    <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($selected_month) ?>" onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

    <!-- Calendar -->
    <div class="card">
        <div class="card-body">
            <div id="calendar" class="calendar-container">
                <?php
                $daysInMonth = date('t', strtotime($selected_month . '-01'));
                $firstDay = date('w', strtotime($selected_month . '-01'));
                
                // Group bookings by date
                $bookingsByDate = [];
                foreach ($bookings as $booking) {
                    $date = $booking['booking_date'];
                    if (!isset($bookingsByDate[$date])) {
                        $bookingsByDate[$date] = [];
                    }
                    $bookingsByDate[$date][] = $booking;
                }
                
                // Status colors
                $statusColors = [
                    'pending' => 'warning',
                    'confirmed' => 'success',
                    'completed' => 'info',
                    'cancelled' => 'secondary'
                ];
                ?>
                
                <table class="table table-bordered calendar-table">
                    <thead>
                        <tr>
                            <th>Sun</th>
                            <th>Mon</th>
                            <th>Tue</th>
                            <th>Wed</th>
                            <th>Thu</th>
                            <th>Fri</th>
                            <th>Sat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $day = 1;
                        $currentWeek = 0;
                        
                        // First week
                        echo '<tr>';
                        for ($i = 0; $i < 7; $i++) {
                            if ($i < $firstDay) {
                                echo '<td class="calendar-day empty"></td>';
                            } else {
                                $dateStr = $selected_month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                                $hasBookings = isset($bookingsByDate[$dateStr]);
                                
                                echo '<td class="calendar-day' . ($hasBookings ? ' has-bookings' : '') . '">';
                                echo '<div class="day-number">' . $day . '</div>';
                                
                                if ($hasBookings) {
                                    foreach ($bookingsByDate[$dateStr] as $booking) {
                                        $color = $statusColors[$booking['status']] ?? 'secondary';
                                        echo '<div class="booking-item bg-' . $color . '" title="' . htmlspecialchars($booking['customer_name'] . ' - ' . $booking['facility_name']) . '">';
                                        echo htmlspecialchars($booking['facility_name']);
                                        echo '</div>';
                                    }
                                }
                                
                                echo '</td>';
                                $day++;
                            }
                        }
                        echo '</tr>';
                        
                        // Remaining weeks
                        while ($day <= $daysInMonth) {
                            echo '<tr>';
                            for ($i = 0; $i < 7 && $day <= $daysInMonth; $i++) {
                                $dateStr = $selected_month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                                $hasBookings = isset($bookingsByDate[$dateStr]);
                                
                                echo '<td class="calendar-day' . ($hasBookings ? ' has-bookings' : '') . '">';
                                echo '<div class="day-number">' . $day . '</div>';
                                
                                if ($hasBookings) {
                                    foreach ($bookingsByDate[$dateStr] as $booking) {
                                        $color = $statusColors[$booking['status']] ?? 'secondary';
                                        echo '<div class="booking-item bg-' . $color . '" title="' . htmlspecialchars($booking['customer_name'] . ' - ' . $booking['facility_name']) . '">';
                                        echo htmlspecialchars($booking['facility_name']);
                                        echo '</div>';
                                    }
                                }
                                
                                echo '</td>';
                                $day++;
                            }
                            
                            // Fill remaining cells in last week
                            while ($i < 7) {
                                echo '<td class="calendar-day empty"></td>';
                                $i++;
                            }
                            
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


