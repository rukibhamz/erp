<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1><?= htmlspecialchars($page_title) ?></h1>
                <div class="page-actions">
                    <a href="<?= base_url('bookings/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Booking
                    </a>
                </div>
            </div>

            <?php if (!empty($flash)): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                    <?= $flash['message'] ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <div class="calendar-container">
                <!-- Header Controls -->
                <div class="calendar-header">
                    <div class="view-controls">
                        <a href="<?= base_url('bookings/calendar?view=month&date=' . $selected_date . '&facility_id=' . $selected_facility_id) ?>" 
                           class="btn btn-sm <?= $view_type === 'month' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                            <i class="fas fa-calendar-alt"></i> Month
                        </a>
                        <a href="<?= base_url('bookings/calendar?view=day&date=' . $selected_date . '&facility_id=' . $selected_facility_id) ?>" 
                           class="btn btn-sm <?= $view_type === 'day' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                            <i class="fas fa-clock"></i> Day
                        </a>
                    </div>
                    
                    <div class="date-navigation">
                        <button class="btn btn-sm btn-outline-secondary" id="prev-date">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <input type="date" class="form-control form-control-sm" id="selected-date" value="<?= $selected_date ?>">
                        <button class="btn btn-sm btn-outline-secondary" id="next-date">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary" id="today-btn">Today</button>
                    </div>
                    
                    <div class="facility-selector">
                        <select class="form-control form-control-sm" id="facility-select">
                            <option value="">Select Facility</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?= $facility['id'] ?>" 
                                        <?= $facility['id'] == $selected_facility_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($facility['facility_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if (!$selected_facility_id): ?>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> Please select a facility to view time slot availability.
                    </div>
                <?php else: ?>
                    <!-- Time Slot Grid -->
                    <div class="time-grid">
                        <div class="time-labels">
                            <?php foreach ($time_slots as $slot): ?>
                                <div class="time-label">
                                    <?= htmlspecialchars(explode(' - ', $slot['label'])[0]) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="time-slots">
                            <?php foreach ($time_slots as $slot): ?>
                                <?php 
                                    $statusClass = $slot['available'] ? 'available' : 'booked';
                                    if ($slot['booking'] && $slot['booking']['status'] === 'pending') {
                                        $statusClass = 'pending';
                                    }
                                ?>
                                <div class="time-slot <?= $statusClass ?>"
                                     data-start="<?= $slot['start_time'] ?>"
                                     data-end="<?= $slot['end_time'] ?>"
                                     data-date="<?= $selected_date ?>"
                                     data-facility="<?= $selected_facility_id ?>"
                                     <?= $slot['available'] ? 'onclick="openQuickBookingModal(this)"' : '' ?>>
                                    
                                    <?php if (!$slot['available'] && $slot['booking']): ?>
                                        <div class="booking-info">
                                            <strong><?= htmlspecialchars($slot['booking']['customer_name']) ?></strong>
                                            <small class="d-block"><?= htmlspecialchars($slot['booking']['booking_number']) ?></small>
                                            <small class="d-block text-muted">
                                                <?= $slot['booking']['start_time'] ?> - <?= $slot['booking']['end_time'] ?>
                                            </small>
                                            <a href="<?= base_url('bookings/view/' . $slot['booking']['id']) ?>" 
                                               class="btn btn-xs btn-outline-light mt-1"
                                               onclick="event.stopPropagation()">
                                                View Details
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="slot-status">
                                            <i class="fas fa-check-circle"></i> Available
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Legend -->
                    <div class="calendar-legend">
                        <div class="legend-item">
                            <span class="color-box available"></span> Available
                        </div>
                        <div class="legend-item">
                            <span class="color-box booked"></span> Booked
                        </div>
                        <div class="legend-item">
                            <span class="color-box pending"></span> Pending
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Booking Modal -->
<div class="modal fade" id="quick-booking-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Quick Booking</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="<?= base_url('bookings/create') ?>" method="POST" id="quick-booking-form">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <input type="hidden" name="facility_id" id="modal-facility-id">
                    <input type="hidden" name="booking_date" id="modal-date">
                    <input type="hidden" name="start_time" id="modal-start-time">
                    <input type="hidden" name="end_time" id="modal-end-time">
                    <input type="hidden" name="booking_type" value="hourly">
                    <input type="hidden" name="status" value="confirmed">
                    
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Time Slot</label>
                        <input type="text" class="form-control" id="modal-time-display" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Customer Name *</label>
                        <input type="text" class="form-control" name="customer_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Customer Email</label>
                        <input type="email" class="form-control" name="customer_email">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Customer Phone</label>
                        <input type="tel" class="form-control" name="customer_phone">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-users"></i> Number of Guests</label>
                        <input type="number" class="form-control" name="number_of_guests" value="1" min="1">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-sticky-note"></i> Notes</label>
                        <textarea class="form-control" name="booking_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Create Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= base_url('assets/css/calendar-timeslots.css') ?>">
<script src="<?= base_url('assets/js/calendar-timeslots.js') ?>"></script>
