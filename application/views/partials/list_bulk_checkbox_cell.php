<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<td>
    <input type="checkbox" class="form-check-input bulk-row-check" name="ids[]" value="<?= (int) ($bulk_row_id ?? 0) ?>" form="<?= htmlspecialchars($bulk_form_id ?? 'bulk-delete-form') ?>" aria-label="Select <?= htmlspecialchars($bulk_row_label ?? 'row') ?>">
</td>
