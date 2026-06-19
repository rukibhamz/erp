<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared UI helpers for list bulk-delete (checkboxes + toolbar).
 */
function bulk_delete_colspan(int $baseCols, bool $enabled): int {
    return $enabled ? $baseCols + 1 : $baseCols;
}

function bulk_delete_render_toolbar(bool $enabled, $rows, string $url, string $itemLabel, ?string $confirmMessage = null, string $formId = 'bulk-delete-form'): void {
    if (!$enabled || empty($rows)) {
        return;
    }
    $bulk_delete_url = $url;
    $bulk_item_label = $itemLabel;
    $bulk_form_id = $formId;
    if ($confirmMessage !== null) {
        $bulk_confirm_message = $confirmMessage;
    }
    include BASEPATH . 'views/partials/list_bulk_delete.php';
}

function bulk_delete_render_checkbox_th(bool $enabled, string $formId = 'bulk-delete-form'): void {
    if (!$enabled) {
        return;
    }
    include BASEPATH . 'views/partials/list_bulk_checkbox_header.php';
}

function bulk_delete_render_checkbox_td(bool $enabled, int $id, string $label, string $formId = 'bulk-delete-form'): void {
    if (!$enabled) {
        return;
    }
    $bulk_row_id = $id;
    $bulk_row_label = $label;
    $bulk_form_id = $formId;
    include BASEPATH . 'views/partials/list_bulk_checkbox_cell.php';
}
