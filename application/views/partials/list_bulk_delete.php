<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Bulk-delete toolbar for list tables.
 *
 * Required: $bulk_delete_url, $bulk_item_label (e.g. "booking")
 * Optional: $bulk_form_id (default bulk-delete-form), $bulk_confirm_message
 */
$bulk_form_id = $bulk_form_id ?? 'bulk-delete-form';
$bulk_item_label = $bulk_item_label ?? 'record';
$bulk_confirm_message = $bulk_confirm_message ?? (
    'Delete the selected ' . $bulk_item_label . '(s)? Related invoices and transactions will also be removed.'
);
?>
<div class="d-flex align-items-center gap-2 mb-3 list-bulk-actions" id="<?= htmlspecialchars($bulk_form_id) ?>-toolbar">
    <form method="POST" action="<?= htmlspecialchars($bulk_delete_url) ?>" id="<?= htmlspecialchars($bulk_form_id) ?>" class="d-flex align-items-center gap-2 flex-wrap">
        <?php echo csrf_field(); ?>
        <button type="submit" class="btn btn-danger btn-sm" id="<?= htmlspecialchars($bulk_form_id) ?>-btn" disabled>
            <i class="bi bi-trash"></i> Delete selected (<span class="bulk-selected-count">0</span>)
        </button>
        <span class="text-muted small bulk-selection-hint">Select rows using the checkboxes below.</span>
    </form>
</div>
<script nonce="<?= csp_nonce() ?>">
(function() {
    const formId = <?= json_encode($bulk_form_id) ?>;
    const form = document.getElementById(formId);
    const btn = document.getElementById(formId + '-btn');
    const countEl = form ? form.querySelector('.bulk-selected-count') : null;
    const selectAll = document.querySelector('.bulk-select-all[data-form-id="' + formId + '"]');
    const confirmMsg = <?= json_encode($bulk_confirm_message) ?>;

    function rowChecks() {
        return document.querySelectorAll('.bulk-row-check[form="' + formId + '"]');
    }

    function updateBulkState() {
        const checks = rowChecks();
        let n = 0;
        checks.forEach(function(c) { if (c.checked) n++; });
        if (countEl) countEl.textContent = String(n);
        if (btn) btn.disabled = n === 0;
        if (selectAll && checks.length) {
            selectAll.indeterminate = n > 0 && n < checks.length;
            selectAll.checked = n > 0 && n === checks.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            rowChecks().forEach(function(c) { c.checked = selectAll.checked; });
            updateBulkState();
        });
    }

    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('bulk-row-check') && e.target.getAttribute('form') === formId) {
            updateBulkState();
        }
    });

    if (form) {
        form.addEventListener('submit', function(e) {
            const n = parseInt(countEl ? countEl.textContent : '0', 10) || 0;
            if (n === 0) {
                e.preventDefault();
                return;
            }
            if (!confirm(confirmMsg)) {
                e.preventDefault();
            }
        });
    }

    updateBulkState();
})();
</script>
