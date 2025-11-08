-- ============================================================================
-- UPDATE MODULE ICONS TO BOOTSTRAP ICONS FORMAT
-- ============================================================================
-- This script updates existing icon_class values from old format (icon-*) 
-- to Bootstrap Icons format (bi-*)
-- ============================================================================

UPDATE `erp_module_labels` 
SET `icon_class` = CASE
    WHEN `icon_class` = 'icon-home' THEN 'bi-speedometer2'
    WHEN `icon_class` = 'icon-calculator' THEN 'bi-calculator'
    WHEN `icon_class` = 'icon-calendar' THEN 'bi-calendar'
    WHEN `icon_class` = 'icon-building' THEN 'bi-building'
    WHEN `icon_class` = 'icon-package' THEN 'bi-box-seam'
    WHEN `icon_class` = 'icon-zap' THEN 'bi-lightning'
    WHEN `icon_class` = 'icon-bar-chart' THEN 'bi-bar-chart'
    WHEN `icon_class` = 'icon-settings' THEN 'bi-gear'
    WHEN `icon_class` = 'icon-users' THEN 'bi-people'
    WHEN `icon_class` = 'icon-bell' THEN 'bi-bell'
    WHEN `icon_class` = 'icon-shopping-cart' THEN 'bi-cart'
    WHEN `icon_class` = 'icon-file-text' THEN 'bi-file-text'
    ELSE `icon_class`
END
WHERE `icon_class` LIKE 'icon-%';

-- Also update any that might have 'bi bi-' format to just 'bi-'
UPDATE `erp_module_labels`
SET `icon_class` = REPLACE(`icon_class`, 'bi bi-', 'bi-')
WHERE `icon_class` LIKE 'bi bi-%';

