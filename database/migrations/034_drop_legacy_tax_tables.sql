-- Clean up legacy/conflicting tax tables to avoid parallel configuration bugs
-- These tables were superseded by erp_tax_types

DROP TABLE IF EXISTS `erp_tax_group_items`;
DROP TABLE IF EXISTS `erp_tax_groups`;
DROP TABLE IF EXISTS `erp_taxes`;
