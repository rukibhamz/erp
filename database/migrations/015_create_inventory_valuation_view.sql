-- ============================================================================
-- MIGRATION: 015_create_inventory_valuation_view
-- ============================================================================
-- Purpose: Create database view for inventory valuation reports
-- Used by: Inventory_reports::valuation()
-- IDEMPOTENT - Safe to run multiple times
-- ============================================================================

-- Drop existing view if exists to ensure clean creation
DROP VIEW IF EXISTS `erp_vw_inventory_valuation`;

-- Create the inventory valuation view
-- Provides: Item details, total quantity across locations, total inventory value
CREATE OR REPLACE VIEW `erp_vw_inventory_valuation` AS
SELECT 
    i.id AS item_id,
    i.sku,
    i.item_name,
    i.item_type,
    i.category,
    i.unit_of_measure,
    i.cost_price,
    i.selling_price,
    i.costing_method,
    COALESCE(SUM(sl.quantity), 0) AS total_quantity,
    COALESCE(SUM(sl.quantity * COALESCE(sl.unit_cost, i.cost_price, 0)), 0) AS total_value,
    i.item_status,
    i.created_at,
    i.updated_at
FROM 
    `erp_items` i
LEFT JOIN 
    `erp_stock_levels` sl ON i.id = sl.item_id
WHERE 
    i.item_status = 'active' OR i.item_status IS NULL
GROUP BY 
    i.id, i.sku, i.item_name, i.item_type, i.category, 
    i.unit_of_measure, i.cost_price, i.selling_price, i.costing_method,
    i.item_status, i.created_at, i.updated_at;

-- ============================================================================
-- VERIFICATION
-- ============================================================================
-- Check view was created
SELECT 'Inventory Valuation View' as migration, 
       IF(COUNT(*) > 0, 'SUCCESS', 'FAILED') as status
FROM information_schema.views 
WHERE table_schema = DATABASE() 
AND table_name = 'erp_vw_inventory_valuation';
