<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Journal_cleanup_service {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Delete all journal entries linked to a booking.
     */
    public function deleteBookingJournalEntries($bookingId) {
        $prefix = $this->db->getPrefix();

        $journals = $this->db->fetchAll(
            "SELECT id FROM `{$prefix}journal_entries`
             WHERE reference_id = ?
             AND reference_type IN ('booking_revenue', 'booking_payment', 'booking_cancellation')",
            [$bookingId]
        );

        if (empty($journals)) {
            return;
        }

        foreach ($journals as $journal) {
            $journalId = intval($journal['id'] ?? 0);
            if ($journalId <= 0) {
                continue;
            }

            // Support both legacy and current line FK names.
            try {
                $this->db->query(
                    "DELETE FROM `{$prefix}journal_entry_lines` WHERE journal_entry_id = ?",
                    [$journalId]
                );
            } catch (Exception $e) {
                $this->db->query(
                    "DELETE FROM `{$prefix}journal_entry_lines` WHERE entry_id = ?",
                    [$journalId]
                );
            }

            $this->db->query(
                "DELETE FROM `{$prefix}journal_entries` WHERE id = ?",
                [$journalId]
            );
        }
    }
}
