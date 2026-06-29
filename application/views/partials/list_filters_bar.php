<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Standard list filters card (search + records + apply/clear).
 *
 * Required: $list_filter_action
 * Optional: $search_placeholder, $per_page, $search_col_class, $list_filter_secondary (HTML),
 *           $list_filter_extra_keys (array for active-filter detection, default ['search'])
 */
if (empty($list_filter_action)) {
    return;
}

$perPage = intval($per_page ?? ($pagination['per_page'] ?? 50));
$search_placeholder = $search_placeholder ?? 'Search name, ID, email, phone…';
$search_col_class = $search_col_class ?? 'col-12 col-md';
$list_filter_secondary = $list_filter_secondary ?? '';
$filterKeys = $list_filter_extra_keys ?? ['search'];
$hasFilters = list_has_active_filters($filterKeys);
if (!$hasFilters && list_search_term() !== '') {
    $hasFilters = true;
}
?>
<div class="card shadow-sm mb-4 list-filters-card">
    <div class="card-body">
        <form method="GET" action="<?= htmlspecialchars($list_filter_action) ?>" class="list-filters-form">
            <div class="row g-2 align-items-end list-filters-row">
                <?php include(BASEPATH . 'views/partials/list_search_field.php'); ?>
                <?php render_list_filter_per_page($perPage); ?>
                <?php render_list_filter_submit_buttons($list_filter_action); ?>
            </div>

            <?= $list_filter_secondary ?>

            <?php if ($hasFilters): ?>
            <div class="list-active-filters">
                <span class="small text-muted me-1"><i class="bi bi-funnel"></i> Active:</span>
                <?php if (list_search_term() !== ''): ?>
                    <span class="badge bg-secondary">Search: <?= htmlspecialchars(list_search_term()) ?></span>
                <?php endif; ?>
                <?php if (!empty($list_filter_active_badges)) {
                    echo $list_filter_active_badges;
                } ?>
                <a href="<?= htmlspecialchars($list_filter_action) ?>" class="small ms-1">Clear all</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>
