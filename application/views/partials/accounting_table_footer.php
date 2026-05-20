<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Pagination footer for accounting module list tables (single footer below table).
 * Expects: $pagination (optional), $queryParams (optional), $ariaLabel (optional)
 */
if (empty($pagination) || intval($pagination['total_records'] ?? 0) <= 0) {
    return;
}
$queryParams = $queryParams ?? $_GET;
$ariaLabel = $ariaLabel ?? 'List pagination';
?>
<div class="card-footer bg-white border-top">
    <?php render_pagination_controls($pagination, $queryParams, $ariaLabel); ?>
</div>
