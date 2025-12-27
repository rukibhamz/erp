-- Inventory Valuation View
-- Creates a view for the inventory valuation report
-- Run this SQL to create the view needed by Inventory_reports::valuation()

-- Drop existing view if exists
DROP VIEW IF EXISTS `erp_vw_inventory_valuation`;

-- Create the inventory valuation view
CREATE VIEW `erp_vw_inventory_valuation` AS
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

-- Add comment
-- This view provides:
-- - Item details (SKU, name, category, etc.)
-- - Total quantity across all locations
-- - Total inventory value (quantity * unit cost)
-- - Used by: Inventory_reports::valuation()
