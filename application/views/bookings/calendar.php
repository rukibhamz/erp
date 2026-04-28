<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Booking Calendar</h1>
        <div>
            <a href="<?= base_url('bookings') ?>" class="btn btn-primary">
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
                    <div class="input-group">
                        <input type="hidden" name="month" id="month-value" value="<?= htmlspecialchars($selected_month) ?>">
                        <input type="text" class="form-control" id="month-display" value="<?= date('F Y', strtotime($selected_month . '-01')) ?>" readonly style="cursor: pointer;">
                        <button class="btn btn-outline-secondary" type="button" id="open-month-picker" aria-label="Select month and year">
                            <i class="bi bi-calendar3"></i>
                        </button>
                    </div>
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

<!-- Month-Year Picker Modal -->
<div class="modal fade" id="monthPickerModal" tabindex="-1" aria-labelledby="monthPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="monthPickerModalLabel">Select Month</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="picker-month" class="form-label">Month</label>
                    <select id="picker-month" class="form-select">
                        <option value="01">January</option>
                        <option value="02">February</option>
                        <option value="03">March</option>
                        <option value="04">April</option>
                        <option value="05">May</option>
                        <option value="06">June</option>
                        <option value="07">July</option>
                        <option value="08">August</option>
                        <option value="09">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label for="picker-year" class="form-label">Year</label>
                    <select id="picker-year" class="form-select"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="apply-month-picker">Apply</button>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(() => {
    const form = document.querySelector('.card .card-body form[method="GET"]');
    const monthValue = document.getElementById('month-value');
    const monthDisplay = document.getElementById('month-display');
    const openMonthPicker = document.getElementById('open-month-picker');
    const applyMonthPicker = document.getElementById('apply-month-picker');
    const pickerMonth = document.getElementById('picker-month');
    const pickerYear = document.getElementById('picker-year');
    const modalEl = document.getElementById('monthPickerModal');
    const pickerModal = new bootstrap.Modal(modalEl);

    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    function ensureYearOptions(selectedYear) {
        const currentYear = new Date().getFullYear();
        const startYear = currentYear - 10;
        const endYear = currentYear + 10;
        pickerYear.innerHTML = '';

        for (let y = startYear; y <= endYear; y++) {
            const option = document.createElement('option');
            option.value = String(y);
            option.textContent = String(y);
            if (String(y) === String(selectedYear)) {
                option.selected = true;
            }
            pickerYear.appendChild(option);
        }
    }

    function openPicker() {
        const raw = monthValue.value || '';
        const [year, month] = raw.split('-');
        const now = new Date();
        const activeYear = year || String(now.getFullYear());
        const activeMonth = month || String(now.getMonth() + 1).padStart(2, '0');

        ensureYearOptions(activeYear);
        pickerMonth.value = activeMonth;
        pickerModal.show();
    }

    function formatDisplay(year, month) {
        const monthIndex = Number(month) - 1;
        const monthName = monthNames[monthIndex] || 'January';
        return `${monthName} ${year}`;
    }

    monthDisplay.addEventListener('click', openPicker);
    openMonthPicker.addEventListener('click', openPicker);

    applyMonthPicker.addEventListener('click', () => {
        const selectedYear = pickerYear.value;
        const selectedMonth = pickerMonth.value;
        const finalValue = `${selectedYear}-${selectedMonth}`;
        monthValue.value = finalValue;
        monthDisplay.value = formatDisplay(selectedYear, selectedMonth);
        pickerModal.hide();
        form.submit();
    });
})();
</script>


