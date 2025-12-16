/**
 * Calendar Time Slots JavaScript
 * Handles interactivity for the booking calendar time slot view
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
});

function initializeCalendar() {
    // Date navigation
    const prevBtn = document.getElementById('prev-date');
    const nextBtn = document.getElementById('next-date');
    const todayBtn = document.getElementById('today-btn');
    const dateInput = document.getElementById('selected-date');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => navigateDate(-1));
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => navigateDate(1));
    }
    
    if (todayBtn) {
        todayBtn.addEventListener('click', () => {
            dateInput.value = new Date().toISOString().split('T')[0];
            reloadCalendar();
        });
    }
    
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            reloadCalendar();
        });
    }
    
    // Facility selector
    const facilitySelect = document.getElementById('facility-select');
    if (facilitySelect) {
        facilitySelect.addEventListener('click', function() {
            reloadCalendar();
        });
    }
}

/**
 * Open quick booking modal with slot data
 */
function openQuickBookingModal(slotElement) {
    const startTime = slotElement.dataset.start;
    const endTime = slotElement.dataset.end;
    const date = slotElement.dataset.date;
    const facilityId = slotElement.dataset.facility;
    
    // Populate hidden fields
    document.getElementById('modal-facility-id').value = facilityId;
    document.getElementById('modal-date').value = date;
    document.getElementById('modal-start-time').value = startTime;
    document.getElementById('modal-end-time').value = endTime;
    
    // Format and display time slot
    const timeDisplay = formatTime(startTime) + ' - ' + formatTime(endTime) + ' on ' + formatDate(date);
    document.getElementById('modal-time-display').value = timeDisplay;
    
    // Show modal (using Bootstrap)
    $('#quick-booking-modal').modal('show');
}

/**
 * Reload calendar with current selections
 */
function reloadCalendar() {
    const dateInput = document.getElementById('selected-date');
    const facilitySelect = document.getElementById('facility-select');
    
    if (!dateInput || !facilitySelect) return;
    
    const date = dateInput.value;
    const facilityId = facilitySelect.value;
    const view = getActiveView();
    
    // Add loading state
    const timeGrid = document.querySelector('.time-grid');
    if (timeGrid) {
        timeGrid.classList.add('loading');
    }
    
    // Build URL
    const baseUrl = window.location.pathname;
    const params = new URLSearchParams({
        view: view,
        date: date,
        facility_id: facilityId
    });
    
    window.location.href = baseUrl + '?' + params.toString();
}

/**
 * Navigate date by days
 */
function navigateDate(days) {
    const dateInput = document.getElementById('selected-date');
    if (!dateInput) return;
    
    const currentDate = new Date(dateInput.value);
    currentDate.setDate(currentDate.getDate() + days);
    dateInput.value = currentDate.toISOString().split('T')[0];
    reloadCalendar();
}

/**
 * Get currently active view type
 */
function getActiveView() {
    const activeBtn = document.querySelector('.view-controls .btn-primary');
    if (!activeBtn) return 'day';
    
    const href = activeBtn.getAttribute('href');
    if (href && href.includes('view=month')) return 'month';
    return 'day';
}

/**
 * Format time from 24h to 12h format
 */
function formatTime(time) {
    if (!time) return '';
    
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    
    return displayHour + ':' + minutes + ' ' + ampm;
}

/**
 * Format date for display
 */
function formatDate(dateStr) {
    if (!dateStr) return '';
    
    const date = new Date(dateStr + 'T00:00:00'); // Add time to avoid timezone issues
    const options = {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    };
    
    return date.toLocaleDateString('en-US', options);
}

/**
 * Show notification message
 */
function showNotification(message, type) {
    type = type || 'info';
    
    // Create alert element
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '300px';
    alert.innerHTML = message + 
        '<button type="button" class="close" data-dismiss="alert">&times;</button>';
    
    document.body.appendChild(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        alert.classList.remove('show');
        setTimeout(function() {
            alert.remove();
        }, 150);
    }, 5000);
}

/**
 * Refresh availability without full page reload (AJAX)
 */
function refreshAvailability() {
    const facilityId = document.getElementById('facility-select')?.value;
    const date = document.getElementById('selected-date')?.value;
    
    if (!facilityId || !date) return;
    
    const timeGrid = document.querySelector('.time-grid');
    if (timeGrid) {
        timeGrid.classList.add('loading');
    }
    
    fetch('/bookings/getAvailabilityForDate?facility_id=' + facilityId + '&date=' + date)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTimeSlots(data.slots);
            } else {
                showNotification('Error loading availability: ' + (data.error || 'Unknown error'), 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to load availability', 'danger');
        })
        .finally(() => {
            if (timeGrid) {
                timeGrid.classList.remove('loading');
            }
        });
}

/**
 * Update time slots with new availability data
 */
function updateTimeSlots(slots) {
    const timeSlotElements = document.querySelectorAll('.time-slot');
    
    slots.forEach((slot, index) => {
        if (timeSlotElements[index]) {
            const element = timeSlotElements[index];
            
            // Update classes
            element.classList.remove('available', 'booked', 'pending');
            element.classList.add(slot.available ? 'available' : 'booked');
            
            // Update content
            if (slot.available) {
                element.innerHTML = '<span class="slot-status"><i class="fas fa-check-circle"></i> Available</span>';
                element.onclick = function() { openQuickBookingModal(this); };
            } else {
                element.innerHTML = '<div class="booking-info"><strong>Booked</strong></div>';
                element.onclick = null;
            }
        }
    });
}

/**
 * Keyboard shortcuts
 */
document.addEventListener('keydown', function(e) {
    // Left arrow - previous day
    if (e.key === 'ArrowLeft' && !e.target.matches('input, textarea')) {
        navigateDate(-1);
    }
    
    // Right arrow - next day
    if (e.key === 'ArrowRight' && !e.target.matches('input, textarea')) {
        navigateDate(1);
    }
    
    // T - today
    if (e.key === 't' && !e.target.matches('input, textarea')) {
        const todayBtn = document.getElementById('today-btn');
        if (todayBtn) todayBtn.click();
    }
    
    // R - refresh
    if (e.key === 'r' && !e.target.matches('input, textarea')) {
        e.preventDefault();
        refreshAvailability();
    }
});

// Auto-refresh every 2 minutes (optional)
let autoRefreshInterval = null;

function startAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    
    autoRefreshInterval = setInterval(function() {
        const facilityId = document.getElementById('facility-select')?.value;
        if (facilityId) {
            refreshAvailability();
        }
    }, 120000); // 2 minutes
}

// Uncomment to enable auto-refresh
// startAutoRefresh();
