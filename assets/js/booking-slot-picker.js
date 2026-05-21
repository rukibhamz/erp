/**
 * Reschedule / edit booking — duration-aware time block picker.
 * Expects get-slots API with exclude_booking_id and required_duration_hours.
 */
(function (global) {
    'use strict';

    function pad2(n) {
        return String(n).padStart(2, '0');
    }

    function normalizeTime(t) {
        const s = String(t || '');
        const m = s.match(/^(\d{1,2}):(\d{2})/);
        return m ? pad2(parseInt(m[1], 10)) + ':' + m[2] : s.substring(0, 5);
    }

    function parseTimeToMinutes(t) {
        const n = normalizeTime(t);
        const [h, m] = n.split(':').map(Number);
        return h * 60 + m;
    }

    function minutesToTime(mins) {
        const h = Math.floor(mins / 60) % 24;
        const m = mins % 60;
        return pad2(h) + ':' + pad2(m);
    }

    function addHoursToTime(time, hours) {
        return minutesToTime(parseTimeToMinutes(time) + Math.round(hours * 60));
    }

    function formatTime12(t) {
        const n = normalizeTime(t);
        const [h, m] = n.split(':').map(Number);
        if (isNaN(h)) return t;
        const ampm = h >= 12 ? 'PM' : 'AM';
        const h12 = h % 12 || 12;
        return h12 + ':' + pad2(m) + ' ' + ampm;
    }

    function rangesOverlap(startA, endA, startB, endB) {
        const a0 = parseTimeToMinutes(startA);
        const a1 = parseTimeToMinutes(endA);
        const b0 = parseTimeToMinutes(startB);
        const b1 = parseTimeToMinutes(endB);
        return a0 < b1 && a1 > b0;
    }

    function canBookRange(start, end, date, availableSlots, occupiedSlots) {
        if (parseTimeToMinutes(end) <= parseTimeToMinutes(start)) {
            return false;
        }
        for (let i = 0; i < occupiedSlots.length; i++) {
            const occ = occupiedSlots[i];
            if ((occ.date || date) !== date) continue;
            if (rangesOverlap(start, end, occ.start, occ.end)) {
                return false;
            }
        }
        let cursor = start;
        while (parseTimeToMinutes(cursor) < parseTimeToMinutes(end)) {
            const next = addHoursToTime(cursor, 1);
            const found = availableSlots.some(function (s) {
                return (s.date || date) === date
                    && normalizeTime(s.start) === normalizeTime(cursor)
                    && normalizeTime(s.end) === normalizeTime(next);
            });
            if (!found) {
                return false;
            }
            cursor = next;
        }
        return true;
    }

    function buildValidStarts(availableSlots, occupiedSlots, date, durationHours) {
        const seen = {};
        const starts = [];
        availableSlots.forEach(function (slot) {
            if ((slot.date || date) !== date) return;
            const start = normalizeTime(slot.start);
            if (seen[start]) return;
            seen[start] = true;
            const end = addHoursToTime(start, durationHours);
            if (canBookRange(start, end, date, availableSlots, occupiedSlots)) {
                starts.push({ start: start, end: end });
            }
        });
        starts.sort(function (a, b) {
            return parseTimeToMinutes(a.start) - parseTimeToMinutes(b.start);
        });
        return starts;
    }

    function renderOccupiedSlots(occupiedSlots, date, formatTime) {
        const fmt = formatTime || formatTime12;
        return occupiedSlots.map(function (slot) {
            const isBuffer = !!slot.is_buffer;
            const btnClass = isBuffer ? 'btn-warning text-dark' : 'btn-danger';
            const label = isBuffer ? 'Buffer' : 'Occupied';
            const textClass = isBuffer ? 'text-dark' : 'text-white';
            return '<div class="col-6 col-md-4 col-lg-3">' +
                '<div class="booking-slot-btn ' + btnClass + ' disabled" aria-disabled="true">' +
                '<span class="booking-slot-time">' + fmt(slot.start) + ' – ' + fmt(slot.end) + '</span>' +
                '<span class="booking-slot-status ' + textClass + '">' + label + '</span>' +
                '</div></div>';
        }).join('');
    }

    /**
     * @param {Object} opts
     * @param {HTMLElement} opts.container
     * @param {number} opts.durationHours
     * @param {string} opts.date
     * @param {Array} opts.availableSlots
     * @param {Array} opts.occupiedSlots
     * @param {string} [opts.savedStart]
     * @param {string} [opts.savedEnd]
     * @param {string} [opts.savedDate]
     * @param {function} opts.onSelect — (start, end) => void
     * @param {function} [opts.formatTime]
     */
    function renderDurationPicker(opts) {
        const container = opts.container;
        const date = opts.date;
        const durationHours = Math.max(1, parseInt(opts.durationHours, 10) || 1);
        const available = opts.availableSlots || [];
        const occupied = opts.occupiedSlots || [];
        const onSelect = opts.onSelect || function () {};
        const fmt = opts.formatTime || formatTime12;

        const validStarts = buildValidStarts(available, occupied, date, durationHours);

        if (validStarts.length === 0 && occupied.length === 0) {
            container.innerHTML = '<div class="col-12"><div class="alert alert-warning mb-0">No time blocks available on this date. Try another date.</div></div>';
            return;
        }

        let html = '<div class="col-12 mb-2"><p class="small text-muted mb-0">' +
            '<i class="bi bi-info-circle"></i> Select a <strong>' + durationHours + '-hour</strong> start time. The full block will be reserved.</p></div>';

        validStarts.forEach(function (block) {
            html += '<div class="col-6 col-md-4 col-lg-3">' +
                '<button type="button" class="booking-slot-btn btn btn-outline-success w-100 available-start-slot" ' +
                'data-start="' + block.start + '" data-end="' + block.end + '">' +
                '<span class="booking-slot-time">' + fmt(block.start) + ' – ' + fmt(block.end) + '</span>' +
                '<span class="booking-slot-status text-success">' + durationHours + ' hr block</span>' +
                '</button></div>';
        });

        html += renderOccupiedSlots(occupied, date, fmt);

        if (validStarts.length === 0) {
            html = '<div class="col-12"><div class="alert alert-warning mb-2">No ' + durationHours + '-hour blocks fit on this date.</div></div>' + html;
        }

        container.innerHTML = '<div class="row g-2 booking-slot-grid">' + html + '</div>';

        container.querySelectorAll('.available-start-slot').forEach(function (btn) {
            btn.addEventListener('click', function () {
                container.querySelectorAll('.available-start-slot').forEach(function (b) {
                    b.classList.remove('btn-success', 'active');
                    b.classList.add('btn-outline-success');
                });
                this.classList.remove('btn-outline-success');
                this.classList.add('btn-success', 'active');
                onSelect(this.dataset.start, this.dataset.end, this);
            });
        });

        const savedStart = normalizeTime(opts.savedStart);
        const savedEnd = normalizeTime(opts.savedEnd);
        const savedDate = opts.savedDate || '';
        if (savedDate === date && savedStart && savedEnd) {
            const match = Array.from(container.querySelectorAll('.available-start-slot'))
                .find(function (b) {
                    return normalizeTime(b.dataset.start) === savedStart
                        && normalizeTime(b.dataset.end) === savedEnd;
                });
            if (match) {
                match.click();
            } else if (canBookRange(savedStart, savedEnd, date, available, occupied)) {
                onSelect(savedStart, savedEnd, null);
            }
        }
    }

    global.BookingSlotPicker = {
        normalizeTime: normalizeTime,
        formatTime12: formatTime12,
        addHoursToTime: addHoursToTime,
        canBookRange: canBookRange,
        buildValidStarts: buildValidStarts,
        renderDurationPicker: renderDurationPicker
    };
})(typeof window !== 'undefined' ? window : this);
