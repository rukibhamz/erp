<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Backfill_customer_identity {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function up() {
        require_once BASEPATH . 'services/Identity_resolver.php';
        
        try {
            $prefix = $this->db->getPrefix();
            $resolver = new Identity_resolver();
            
            $stats = [
                'tenants_scanned' => 0,
                'tenants_linked' => 0,
                'bookings_scanned' => 0,
                'bookings_linked' => 0,
                'errors' => 0
            ];

            error_log("--- Starting Unified Customer Identity Backfill (Migration 010) ---");

            // 1. Backfill Tenants
            $tenantsToProcess = $this->db->fetchAll(
                "SELECT id, email, tenant_name, phone 
                 FROM `{$prefix}tenants` 
                 WHERE customer_id IS NULL AND email IS NOT NULL AND email != ''"
            );
            
            $stats['tenants_scanned'] = count($tenantsToProcess);
            error_log("Found {$stats['tenants_scanned']} tenants to process.");

            foreach ($tenantsToProcess as $tenant) {
                try {
                    $displayName = $tenant['tenant_name'] ?: $tenant['email'];
                    $customerId = $resolver->resolve($tenant['email'], $displayName, $tenant['phone'] ?? '', 'tenant');

                    if ($customerId) {
                        $this->db->update('tenants', ['customer_id' => $customerId], 'id = ?', [$tenant['id']]);
                        $stats['tenants_linked']++;
                    } else {
                        $stats['errors']++;
                    }
                } catch (Exception $e) {
                    error_log("  ERROR processing tenant ID {$tenant['id']}: " . $e->getMessage());
                    $stats['errors']++;
                }
            }

            // 2. Backfill Bookings
            $bookingsToProcess = $this->db->fetchAll(
                "SELECT id, customer_email as email, customer_name, customer_phone 
                 FROM `{$prefix}bookings` 
                 WHERE customer_id IS NULL AND customer_email IS NOT NULL AND customer_email != ''"
            );

            $stats['bookings_scanned'] = count($bookingsToProcess);
            error_log("Found {$stats['bookings_scanned']} bookings to process.");

            foreach ($bookingsToProcess as $booking) {
                try {
                    $displayName = $booking['customer_name'] ?: $booking['email'];
                    $customerId = $resolver->resolve($booking['email'], $displayName, $booking['customer_phone'] ?? '', 'booking');
                    
                    if ($customerId) {
                        $this->db->update('bookings', ['customer_id' => $customerId], 'id = ?', [$booking['id']]);
                        $stats['bookings_linked']++;
                    } else {
                        $stats['errors']++;
                    }
                } catch (Exception $e) {
                    error_log("  ERROR processing booking ID {$booking['id']}: " . $e->getMessage());
                    $stats['errors']++;
                }
            }

            error_log("--- Backfill Complete ---");
            error_log("Tenants Linked:    {$stats['tenants_linked']}/{$stats['tenants_scanned']}");
            error_log("Bookings Linked:    {$stats['bookings_linked']}/{$stats['bookings_scanned']}");
            error_log("Errors:            {$stats['errors']}");
            
            return true;
        } catch (Exception $e) {
            error_log("Fatal Error in Backfill Migration: " . $e->getMessage());
            return false;
        }
    }

    public function down() {
        // Data cannot easily be down-migrated as customer records shouldn't be deleted 
        // to avoid invalidating other relational integrity. We'll leave it as a no-op 
        // or just log it. MIGRATION 009 handles dropping the columns anyway.
        error_log("Migration 010.down() is a no-op. Migration 009 handles column removal.");
        return true;
    }
}
