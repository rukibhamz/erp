<?php
/**
 * Standardized Page Header Partial
 * Usage: include(BASEPATH . 'views/partials/page_header.php');
 * 
 * Required variables:
 * - $page_title: The page title
 * 
 * Optional variables:
 * - $actions: Array of action buttons [['url' => '', 'label' => '', 'icon' => '', 'class' => 'btn-primary']]
 * - $back_url: URL for back button
 * - $show_back: Boolean to show/hide back button (default: false)
 */
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0"><?= htmlspecialchars($page_title ?? 'Page Title') ?></h1>
        <div class="d-flex gap-2">
            <?php if (!empty($actions)): ?>
                <?php foreach ($actions as $action): ?>
                    <a href="<?= base_url($action['url'] ?? '#') ?>" 
                       class="btn <?= $action['class'] ?? 'btn-primary' ?>"
                       <?= !empty($action['onclick']) ? 'onclick="' . htmlspecialchars($action['onclick']) . '"' : '' ?>>
                        <?php if (!empty($action['icon'])): ?>
                            <i class="bi <?= htmlspecialchars($action['icon']) ?>"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($action['label'] ?? '') ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($show_back) || !empty($back_url)): ?>
                <a href="<?= base_url($back_url ?? 'javascript:history.back()') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

