<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (empty($pagination)) {
    return;
}

$currentPage = intval($pagination['page'] ?? 1);
$totalPages = intval($pagination['total_pages'] ?? 1);
$fromRow = intval($pagination['from'] ?? 0);
$toRow = intval($pagination['to'] ?? 0);
$totalRows = intval($pagination['total_records'] ?? 0);
$queryParams = $queryParams ?? $_GET;
$ariaLabel = $ariaLabel ?? 'Pagination';

$buildPageUrl = function ($page) use ($queryParams) {
    return pagination_page_url($page, $queryParams);
};
?>
<div class="accounting-pagination-bar d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
    <div class="small text-muted">
        Showing <?= $fromRow ?>-<?= $toRow ?> of <?= $totalRows ?> records
    </div>
    <?php if ($totalPages > 1): ?>
    <nav aria-label="<?= htmlspecialchars($ariaLabel) ?>">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $currentPage <= 1 ? '#' : htmlspecialchars($buildPageUrl(1)) ?>">First</a>
            </li>
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $currentPage <= 1 ? '#' : htmlspecialchars($buildPageUrl($currentPage - 1)) ?>">Previous</a>
            </li>
            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($buildPageUrl($i)) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $currentPage >= $totalPages ? '#' : htmlspecialchars($buildPageUrl($currentPage + 1)) ?>">Next</a>
            </li>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $currentPage >= $totalPages ? '#' : htmlspecialchars($buildPageUrl($totalPages)) ?>">Last</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>
